<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Contestant;
use App\Models\Registration;
use App\Models\Area;
use App\Models\OlympiadArea;
use App\Models\Grade;
use App\Models\Level;
use Carbon\Carbon;

class CompetitorRegistrationController extends Controller
{
    /**
     * Upload and process CSV files for competitor registration
     */
    public function uploadCsv(Request $request): JsonResponse
    {
        // Debug: Log what we're receiving
        \Log::info('Upload CSV Request', [
            'has_files' => $request->hasFile('files'),
            'files_count' => $request->file('files') ? count($request->file('files')) : 0,
            'all_files' => $request->allFiles(),
            'olympiad_id' => $request->olympiad_id
        ]);
        
        // Validate that files are present and are CSV
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'olympiad_id' => 'required|exists:olympiads,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $olympiadId = $request->olympiad_id;
        $files = $request->file('files');
        
        // Handle single file upload
        if (!is_array($files)) {
            $files = [$files];
        }
        
        // Filter out null values
        $files = array_filter($files, function($file) {
            return $file !== null;
        });
        
        if (empty($files)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid files provided'
            ], 422);
        }
        $results = [];
        $totalSuccessful = 0;
        $totalCompetitorErrors = 0;
        $totalHeaderErrors = 0;
        $totalRecords = 0;
        $errorFiles = [];
        $filesWithErrorsCount = 0;
        $filesWithHeaderErrorsCount = 0;
        $filesWithCompetitorErrorsCount = 0;
        $processingStartTime = microtime(true);

        DB::beginTransaction();

        try {
            foreach ($files as $file) {
                $result = $this->processCsvFile($file, $olympiadId);
                $results[] = $result;
                $totalSuccessful += $result['successful'];
                $totalCompetitorErrors += $result['competitor_errors'];
                $totalHeaderErrors += $result['header_errors'];
                $totalRecords += $result['total_records'];

                // Count files with different types of errors
                if ($result['header_errors'] > 0) {
                    $filesWithHeaderErrorsCount++;
                }
                if ($result['competitor_errors'] > 0) {
                    $filesWithCompetitorErrorsCount++;
                }
                if ($result['header_errors'] > 0 || $result['competitor_errors'] > 0) {
                    $filesWithErrorsCount++;
                    if ($result['error_file']) {
                        $errorFiles[] = $result['error_file'];
                    }
                }
            }

            DB::commit();

            $processingTime = microtime(true) - $processingStartTime;

            return response()->json([
                'success' => true,
                'message' => 'CSV files processed successfully',
                'data' => [
                    'total_files_processed' => count($files),
                    'total_records_processed' => $totalRecords,
                    'total_successful' => $totalSuccessful,
                    'total_competitor_errors' => $totalCompetitorErrors,
                    'total_header_errors' => $totalHeaderErrors,
                    'total_errors' => $totalCompetitorErrors + $totalHeaderErrors,
                    'success_rate' => $totalRecords > 0 ? round(($totalSuccessful / $totalRecords) * 100, 2) : 0,
                    'competitor_error_rate' => $totalRecords > 0 ? round(($totalCompetitorErrors / $totalRecords) * 100, 2) : 0,
                    'header_error_rate' => $totalRecords > 0 ? round(($totalHeaderErrors / $totalRecords) * 100, 2) : 0,
                    'files_with_errors' => $filesWithErrorsCount,
                    'files_with_header_errors' => $filesWithHeaderErrorsCount,
                    'files_with_competitor_errors' => $filesWithCompetitorErrorsCount,
                    'error_files' => $errorFiles,
                    'processing_time_seconds' => round($processingTime, 2),
                    'records_per_second' => $processingTime > 0 ? round($totalRecords / $processingTime, 2) : 0,
                    'olympiad_id' => $olympiadId,
                    'summary' => [
                        'total_competitors_registered' => $totalSuccessful,
                        'total_competitors_with_errors' => $totalCompetitorErrors,
                        'total_header_errors' => $totalHeaderErrors,
                        'total_files_processed' => count($files),
                        'total_files_with_errors' => $filesWithErrorsCount,
                        'total_files_with_header_errors' => $filesWithHeaderErrorsCount,
                        'total_files_with_competitor_errors' => $filesWithCompetitorErrorsCount,
                        'processing_time_seconds' => round($processingTime, 2)
                    ],
                    'details' => $results
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error processing CSV files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a single CSV file
     */
    private function processCsvFile($file, $olympiadId): array
    {
        $filename = $file->getClientOriginalName();
        $content = file_get_contents($file->getPathname());
        $lines = str_getcsv($content, "\n");

        // Remove BOM if present
        if (substr($lines[0], 0, 3) === "\xEF\xBB\xBF") {
            $lines[0] = substr($lines[0], 3);
        }

        $header = str_getcsv($lines[0]);

        // Expected headers for valid CSV format
        $expectedHeaders = [
            'N.', 'CI', 'NOMBRE', 'APELLIDO', 'GENERO', 'DEPARTAMENTO',
            'COLEGIO', 'CELULAR', 'E-MAIL', 'AREA', 'GRADO', 'NIVEL',
            'NUMERO TUTOR', 'NOMBRE TUTOR'
        ];

        // Check if this is an error CSV file (contains 'Errores' column)
        $errorColumnIndex = array_search('Errores', $header);

        // For validation, work with headers without the 'Errores' column if present
        $headersToValidate = $header;
        if ($errorColumnIndex !== false) {
            unset($headersToValidate[$errorColumnIndex]);
            $headersToValidate = array_values($headersToValidate); // Re-index array
        }

        // Validate that headers match expected format (case-sensitive and order-sensitive)
        if ($headersToValidate !== $expectedHeaders) {
            $errors[] = [
                'row_number' => 1,
                'errors' => "Las cabeceras del CSV no coinciden con el formato requerido. Revise la primera fila de datos para ver las cabeceras correctas."
            ];

            // Generate error CSV with the validation error AND all original data
            $errorFile = $this->generateErrorCsv($filename, $header, $errors, $lines);

            return [
                'filename' => $filename,
                'successful' => 0,
                'competitor_errors' => 0,
                'header_errors' => 1,
                'total_records' => count($lines) - 1, // Excluding header row
                'error_file' => $errorFile
            ];
        }

        // Remove 'Errores' column if it exists (from error CSV files)
        if ($errorColumnIndex !== false) {
            unset($header[$errorColumnIndex]);
            $header = array_values($header); // Re-index array
        }
        
        $data = [];
        $errors = [];
        $successful = 0;
        $seenCis = [];

        // Process each row
        for ($i = 1; $i < count($lines); $i++) {
            if (empty(trim($lines[$i]))) continue;
            
            $row = str_getcsv($lines[$i]);
            
            // Remove 'Errores' column data if it exists (from error CSV files)
            if ($errorColumnIndex !== false && isset($row[$errorColumnIndex])) {
                unset($row[$errorColumnIndex]);
                $row = array_values($row); // Re-index array
            }
            
            // Check if row has same number of columns as header
            if (count($row) !== count($header)) {
                $errors[] = [
                    'row_number' => $i + 1,
                    'errors' => "Row has " . count($row) . " columns but header has " . count($header) . " columns. Please check for missing commas or extra commas in the data."
                ];
            }
            
            $rowData = array_combine($header, $row);
            // Normalize keys by trimming spaces (handles ' NIVEL' vs 'NIVEL')
            $normalized = [];
            foreach ($rowData as $k => $v) {
                $normalized[is_string($k) ? trim($k) : $k] = $v;
            }
            $rowData = $normalized;
            $rowData['row_number'] = $i + 1;
            
            // Normalize header keys that might include spaces or dots
            if (isset($rowData['N.'])) { unset($rowData['N.']); }

            // Duplicate CI within this CSV file
            if (!empty($rowData['CI'])) {
                $ci = $rowData['CI'];
                if (isset($seenCis[$ci])) {
                    $rowData['errors'] = 'CI Document duplicated within the same file';
                    $errors[] = $rowData;
                    continue;
                }
            }

            $validation = $this->validateContestantData($rowData, $olympiadId);

            if ($validation['valid']) {
                $this->createContestantAndRegistration($rowData, $olympiadId);
                $successful++;
                if (!empty($rowData['CI'])) { $seenCis[$rowData['CI']] = true; }
            } else {
                $rowData['errors'] = implode('; ', $validation['errors']);
                $errors[] = $rowData;
            }
        }

        // Generate error CSV if there are errors
        $errorFile = null;
        if (count($errors) > 0) {
            $errorFile = $this->generateErrorCsv($filename, $header, $errors);
        }

        return [
            'filename' => $filename,
            'successful' => $successful,
            'competitor_errors' => count($errors),
            'header_errors' => 0,
            'total_records' => count($lines) - 1, // Excluding header row
            'error_file' => $errorFile
        ];
    }

    /**
     * Validate contestant data according to requirements
     */
    private function validateContestantData(array $data, $olympiadId): array
    {
        $errors = [];

        // Required fields validation
        $requiredFields = [
            'CI' => 'CI Document',
            'NOMBRE' => 'First Name',
            'APELLIDO' => 'Last Name',
            'GENERO' => 'Gender',
            'DEPARTAMENTO' => 'Department',
            'COLEGIO' => 'School',
            'AREA' => 'Area',
            'GRADO' => 'Grade',
            'NUMERO TUTOR' => 'Tutor Number',
            'NOMBRE TUTOR' => 'Tutor Name'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = "$label is required";
            }
        }

        // First Name validation (2-50 characters, only letters)
        if (!empty($data['NOMBRE'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $data['NOMBRE'])) {
                $errors[] = 'First Name must be 2-50 characters and contain only letters';
            }
        }

        // Last Name validation (2-50 characters, only letters)
        if (!empty($data['APELLIDO'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $data['APELLIDO'])) {
                $errors[] = 'Last Name must be 2-50 characters and contain only letters';
            }
        }

        // CI Document validation (8-13 characters, unique)
        if (!empty($data['CI'])) {
            if (!preg_match('/^[0-9]{8,13}$/', $data['CI'])) {
                $errors[] = 'CI Document must be 8-13 digits';
            } else {
                if (Contestant::where('ci_document', $data['CI'])->exists()) {
                    $errors[] = 'CI Document already exists';
                }
            }
        }

        // Gender validation (F or M)
        if (!empty($data['GENERO'])) {
            if (!in_array(strtoupper($data['GENERO']), ['F', 'M'])) {
                $errors[] = 'Gender must be F or M';
            }
        }

        // Department validation (2-50 characters, only letters)
        if (!empty($data['DEPARTAMENTO'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $data['DEPARTAMENTO'])) {
                $errors[] = 'Department must be 2-50 characters and contain only letters';
            }
        }

        // School validation (2-100 characters, alphanumeric)
        if (!empty($data['COLEGIO'])) {
            if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{2,100}$/', $data['COLEGIO'])) {
                $errors[] = 'School must be 2-100 characters and contain only alphanumeric characters';
            }
        }

        // Phone validation (8 digits, optional)
        if (!empty($data['CELULAR'])) {
            if (!preg_match('/^[0-9]{8}$/', $data['CELULAR'])) {
                $errors[] = 'Phone must be exactly 8 digits';
            }
        }

        // Email validation (optional)
        if (!empty($data['E-MAIL'])) {
            if (!filter_var($data['E-MAIL'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email format is invalid';
            } else {
                if (Contestant::where('email', $data['E-MAIL'])->exists()) {
                    $errors[] = 'Email already exists';
                }
            }
        }

        // Area validation (must exist under olympiad and max 3 areas)
        if (!empty($data['AREA'])) {
            // Support comma or semicolon separators for multiple areas
            $areas = array_map('trim', preg_split('/[,;]+/', $data['AREA']));
            if (count($areas) > 3) {
                $errors[] = 'Maximum 3 areas allowed';
            }
            
            foreach ($areas as $areaName) {
                $area = Area::where('name', $areaName)->first();
                if (!$area) {
                    $errors[] = "Area '$areaName' does not exist";
                    continue;
                }
                $existsInOlympiad = OlympiadArea::where('olympiad_id', $olympiadId)
                    ->where('area_id', $area->id)
                    ->exists();
                if (!$existsInOlympiad) {
                    $errors[] = "Area '$areaName' is not configured for the selected Olympiad";
                }
            }
        }

        // Grade validation (only letters)
        if (!empty($data['GRADO'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $data['GRADO'])) {
                $errors[] = 'Grade must contain only letters';
            }
        }

        // Level validation (required, only letters). Accept key with or without leading space
        $levelValue = $data['NIVEL'] ?? ($data[' NIVEL'] ?? null);
        if (!empty($levelValue)) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $levelValue)) {
                $errors[] = 'Level must be 2-50 characters and contain only letters';
            }
        }
        if (empty($levelValue)) {
            $errors[] = 'Level is required';
        }

        // Tutor Name validation (2-50 characters, only letters)
        if (!empty($data['NOMBRE TUTOR'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $data['NOMBRE TUTOR'])) {
                $errors[] = 'Tutor Name must be 2-50 characters and contain only letters';
            }
        }

        // Tutor Number validation (8 digits)
        if (!empty($data['NUMERO TUTOR'])) {
            if (!preg_match('/^[0-9]{8}$/', $data['NUMERO TUTOR'])) {
                $errors[] = 'Tutor Number must be exactly 8 digits';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create contestant and registration records
     */
    private function createContestantAndRegistration(array $data, $olympiadId): void
    {
        // Create contestant (no grade in table per new schema)
        $contestant = Contestant::create([
            'first_name' => $data['NOMBRE'],
            'last_name' => $data['APELLIDO'],
            'ci_document' => $data['CI'],
            'gender' => strtoupper($data['GENERO']),
            'school_name' => $data['COLEGIO'],
            'department' => $data['DEPARTAMENTO'],
            'phone_number' => $data['CELULAR'] ?? null,
            'email' => $data['E-MAIL'] ?? null,
            'tutor_name' => $data['NOMBRE TUTOR'],
            'tutor_number' => $data['NUMERO TUTOR']
        ]);

        // Resolve grade and level for registration
        $grade = null;
        if (!empty($data['GRADO'])) {
            $grade = Grade::firstOrCreate(['name' => trim($data['GRADO'])]);
        }
        $levelName = $data['NIVEL'] ?? ($data[' NIVEL'] ?? null);
        $level = $levelName ? Level::firstOrCreate(['name' => trim($levelName)]) : null;

        // Get areas and create registrations (support comma or semicolon separators)
        $areas = array_map('trim', preg_split('/[,;]+/', $data['AREA']));
        foreach ($areas as $areaName) {
            $area = Area::where('name', $areaName)->first();
            if ($area) {
                $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
                    ->where('area_id', $area->id)
                    ->first();
                
                if ($olympiadArea) {
                    $registration = Registration::create([
                        'contestant_id' => $contestant->id,
                        'olympiad_area_id' => $olympiadArea->id,
                        'grade_id' => $grade?->id,
                        'level_id' => $level?->id
                    ]);
                }
            }
        }
    }

    /**
     * Generate error CSV file
     */
    private function generateErrorCsv(string $originalFilename, array $header, array $errors, array $lines = null): string
    {
        $baseFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
        $errorFilename = $baseFilename . '-errores.csv';
        $errorPath = 'error-csvs/' . $errorFilename;

        // Add error column to header
        $errorHeader = array_merge($header, ['Errores']);

        $csvContent = implode(',', array_map(function($field) {
            return '"' . str_replace('"', '""', $field) . '"';
        }, $errorHeader)) . "\n";

        // If we have original lines (for header validation errors), include all data
        if ($lines !== null && !empty($lines)) {
            // Skip the header row (index 0) and process data rows
            for ($i = 1; $i < count($lines); $i++) {
                if (empty(trim($lines[$i]))) continue;

                $row = str_getcsv($lines[$i]);

                // Remove 'Errores' column data if it exists (from error CSV files)
                $errorColumnIndex = array_search('Errores', $header);
                if ($errorColumnIndex !== false && isset($row[$errorColumnIndex])) {
                    unset($row[$errorColumnIndex]);
                    $row = array_values($row); // Re-index array
                }

                // For header validation errors, provide better guidance
                $errorMessage = '';
                if (!empty($errors)) {
                    // Check if this is a header validation error
                    $headerError = null;
                    foreach ($errors as $error) {
                        if (isset($error['row_number']) && $error['row_number'] === 1) {
                            $headerError = $error;
                            break;
                        }
                    }

                    if ($headerError) {
                        if ($i === 1) { // First data row - add detailed error info
                            $errorMessage = "ERROR EN CABECERAS: " . $headerError['errors'] .
                                          " | CORRECCIÓN: Cambie las cabeceras a: " . implode(', ', [
                                              'N.', 'CI', 'NOMBRE', 'APELLIDO', 'GENERO', 'DEPARTAMENTO',
                                              'COLEGIO', 'CELULAR', 'E-MAIL', 'AREA', 'GRADO', 'NIVEL',
                                              'NUMERO TUTOR', 'NOMBRE TUTOR'
                                          ]);
                        } else {
                            $errorMessage = "Ver fila anterior para detalles del error de cabeceras";
                        }
                    }
                }

                $row[] = $errorMessage;

                $csvContent .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
        } else {
            // Original logic for row validation errors
            foreach ($errors as $error) {
                $row = [];
                foreach ($header as $field) {
                    $row[] = $error[$field] ?? '';
                }
                $row[] = $error['errors'] ?? '';

                $csvContent .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
        }

        Storage::disk('public')->put($errorPath, $csvContent);

        // Return only the filename, not the storage path
        return $errorFilename;
    }

    /**
     * Test endpoint to debug file upload
     */
    public function testUpload(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'has_files' => $request->hasFile('files'),
                'files_count' => $request->file('files') ? count($request->file('files')) : 0,
                'all_files' => array_keys($request->allFiles()),
                'olympiad_id' => $request->olympiad_id,
                'all_input' => $request->all()
            ]
        ]);
    }

    /**
     * Download error CSV file
     */
    public function downloadErrorCsv(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filePath = 'error-csvs/' . $filename;
        
        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Error file not found');
        }
        
        return Storage::disk('public')->download($filePath);
    }
}
