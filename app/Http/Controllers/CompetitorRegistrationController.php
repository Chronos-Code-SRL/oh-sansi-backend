<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Contestant;
use App\Models\Registration;
use App\Models\Group;
use App\Models\Area;
use App\Models\OlympiadArea;
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
        $totalErrors = 0;
        $errorFiles = [];

        DB::beginTransaction();

        try {
            foreach ($files as $file) {
                $result = $this->processCsvFile($file, $olympiadId);
                $results[] = $result;
                $totalSuccessful += $result['successful'];
                $totalErrors += $result['errors'];
                
                if ($result['errors'] > 0) {
                    $errorFiles[] = $result['error_file'];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CSV files processed successfully',
                'data' => [
                    'total_successful' => $totalSuccessful,
                    'total_errors' => $totalErrors,
                    'files_processed' => count($files),
                    'error_files' => $errorFiles,
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
        
        // Remove 'Errores' column if it exists (from error CSV files)
        $errorColumnIndex = array_search('Errores', $header);
        if ($errorColumnIndex !== false) {
            unset($header[$errorColumnIndex]);
            $header = array_values($header); // Re-index array
        }
        
        $data = [];
        $errors = [];
        $successful = 0;

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
                continue;
            }
            
            $rowData = array_combine($header, $row);
            $rowData['row_number'] = $i + 1;
            
            $validation = $this->validateContestantData($rowData, $olympiadId);
            
            if ($validation['valid']) {
                $this->createContestantAndRegistration($rowData, $olympiadId);
                $successful++;
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
            'errors' => count($errors),
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
            'N.' => 'N.',
            'ci' => 'CI Document',
            'NOMBRE' => 'First Name',
            'apellido' => 'Last Name',
            'Genero' => 'Gender',
            'Departamento' => 'Department',
            'COLEGIO' => 'School',
            'AREA' => 'Area',
            'Grado' => 'Grade',
            'Numero tutor' => 'Tutor Number',
            'Nombre Tutor' => 'Tutor Name'
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
        if (!empty($data['apellido'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $data['apellido'])) {
                $errors[] = 'Last Name must be 2-50 characters and contain only letters';
            }
        }

        // CI Document validation (8-13 characters, unique)
        if (!empty($data['ci'])) {
            if (!preg_match('/^[0-9]{8,13}$/', $data['ci'])) {
                $errors[] = 'CI Document must be 8-13 digits';
            } else {
                if (Contestant::where('ci_document', $data['ci'])->exists()) {
                    $errors[] = 'CI Document already exists';
                }
            }
        }

        // Gender validation (F or M)
        if (!empty($data['Genero'])) {
            if (!in_array(strtoupper($data['Genero']), ['F', 'M'])) {
                $errors[] = 'Gender must be F or M';
            }
        }

        // Department validation (2-50 characters, only letters)
        if (!empty($data['Departamento'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $data['Departamento'])) {
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

        // Area validation (must exist and max 3 areas)
        if (!empty($data['AREA'])) {
            // Only support semicolon separator for multiple areas
            $areas = array_map('trim', explode(';', $data['AREA']));
            if (count($areas) > 3) {
                $errors[] = 'Maximum 3 areas allowed';
            }
            
            $validAreas = Area::pluck('name')->toArray();
            foreach ($areas as $area) {
                if (!in_array($area, $validAreas)) {
                    $errors[] = "Area '$area' does not exist";
                }
            }
        }

        // Grade validation (only letters)
        if (!empty($data['Grado'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $data['Grado'])) {
                $errors[] = 'Grade must contain only letters';
            }
        }

        // Tutor Name validation (2-50 characters, only letters)
        if (!empty($data['Nombre Tutor'])) {
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $data['Nombre Tutor'])) {
                $errors[] = 'Tutor Name must be 2-50 characters and contain only letters';
            }
        }

        // Tutor Number validation (8 digits)
        if (!empty($data['Numero tutor'])) {
            if (!preg_match('/^[0-9]{8}$/', $data['Numero tutor'])) {
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
        // Create contestant
        $contestant = Contestant::create([
            'first_name' => $data['NOMBRE'],
            'last_name' => $data['apellido'],
            'ci_document' => $data['ci'],
            'gender' => strtoupper($data['Genero']),
            'school_name' => $data['COLEGIO'],
            'department' => $data['Departamento'],
            'phone_number' => $data['CELULAR'] ?? null,
            'email' => $data['E-MAIL'] ?? null,
            'tutor_name' => $data['Nombre Tutor'],
            'tutor_number' => $data['Numero tutor'],
            'grade' => $data['Grado']
        ]);

        // Get areas and create registrations
        $areas = array_map('trim', explode(';', $data['AREA']));
        foreach ($areas as $areaName) {
            $area = Area::where('name', $areaName)->first();
            if ($area) {
                $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
                    ->where('area_id', $area->id)
                    ->first();
                
                if ($olympiadArea) {
                    $registration = Registration::create([
                        'is_group' => !empty($data['Grupo']),
                        'contestant_id' => $contestant->id,
                        'olympiad_area_id' => $olympiadArea->id
                    ]);

                    // Create group if specified
                    if (!empty($data['Grupo'])) {
                        Group::create([
                            'group_name' => $data['Grupo'],
                            'contestant_id' => $contestant->id,
                            'registration_id' => $registration->id
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Generate error CSV file
     */
    private function generateErrorCsv(string $originalFilename, array $header, array $errors): string
    {
        $errorFilename = pathinfo($originalFilename, PATHINFO_FILENAME) . '-errores.csv';
        $errorPath = 'error-csvs/' . $errorFilename;
        
        // Add error column to header
        $errorHeader = array_merge($header, ['Errores']);
        
        $csvContent = implode(',', array_map(function($field) {
            return '"' . str_replace('"', '""', $field) . '"';
        }, $errorHeader)) . "\n";
        
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
        
        Storage::disk('public')->put($errorPath, $csvContent);
        
        return $errorPath;
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
