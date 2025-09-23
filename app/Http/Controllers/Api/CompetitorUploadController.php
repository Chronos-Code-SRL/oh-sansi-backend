<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Contestant;
use App\Models\Inscription;
use App\Models\Level;
use App\Models\School;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompetitorUploadController extends Controller
{
    /**
     * Handle CSV upload, validate headers and rows, upsert contestants and inscriptions.
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid file. Only .csv is allowed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');

        // Canonical short headers expected in CSV (aligned to DB fields)
        $canonicalHeaders = [
            'name',                // Nombre
            'last_name',           // Apellido(s)
            'document',            // Documento de identidad (CI)
            'guardian_contact',    // Contacto tutor legal
            'school_name',         // Unidad educativa
            'department',          // Departamento de procedencia
            'education_level',     // Grado de escolaridad
            'areas',               // Áreas en las que compite (separadas por , ; |)
            'level',               // Nivel de competencia
            'academic_tutor'       // Tutor académico (opcional)
        ];

        // Aliases to support legacy/long headers. key = canonical, value = array of accepted aliases found in CSV
        $headerAliases = [
            'name' => ['name', 'Nombre', 'nombre', 'first_name', 'primer_nombre'],
            'last_name' => ['last_name', 'lastname', 'apellidos', 'apellido'],
            'document' => ['Documento de identidad', 'ci', 'documento', 'document', 'ci_document'],
            'guardian_contact' => ['Contacto de su tutor legal', 'tutor_legal', 'contacto_tutor', 'guardian_contact'],
            'school_name' => ['Datos de la unidad educativa', 'unidad_educativa', 'school', 'school_name'],
            'department' => ['Departamento de procedencia', 'departamento', 'city', 'department'],
            'education_level' => ['Grado de escolaridad', 'grado', 'education_level', 'education_level_name'],
            'areas' => ['Área en la que compite', 'areas', 'area', 'areas_compite'],
            'level' => ['Nivel de competencia', 'nivel', 'level'],
            'academic_tutor' => ['Datos del tutor académico (opcional)', 'tutor_academico', 'academic_tutor']
        ];

        // Legacy single-column full name support
        $legacyFullNameAliases = ['Nombre completo', 'nombre_completo', 'full_name'];

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json(['message' => 'Unable to read uploaded file.'], 400);
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return response()->json(['message' => 'The file is empty.'], 422);
        }

        // Normalize headers by trimming BOM and spaces
        $header = array_map(function ($h) {
            $h = preg_replace('/\x{FEFF}/u', '', $h ?? '');
            return trim($h);
        }, $header);

        // Build a map of canonical header => index by matching any alias present in the CSV header line
        $idx = [];
        foreach ($headerAliases as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (($pos = array_search($alias, $header, true)) !== false) {
                    $idx[$canonical] = $pos;
                    break;
                }
            }
        }

        // Validate header set contains all required canonical headers
        $missing = array_values(array_diff($canonicalHeaders, array_keys($idx)));

        // If name/last_name are missing but a legacy full_name exists, accept and split later
        $fullNameIndex = null;
        if (in_array('name', $missing, true) || in_array('last_name', $missing, true)) {
            foreach ($legacyFullNameAliases as $alias) {
                if (($pos = array_search($alias, $header, true)) !== false) {
                    $fullNameIndex = $pos;
                    $missing = array_values(array_diff($missing, ['name', 'last_name']));
                    break;
                }
            }
        }

        if (count($missing) > 0) {
            fclose($handle);
            return response()->json([
                'message' => 'El archivo no tiene el formato requerido. Verifique las columnas obligatorias',
                'missing_headers' => $missing,
            ], 422);
        }


        $stats = [
            'processed' => 0,
            'created_contestants' => 0,
            'updated_contestants' => 0,
            'created_inscriptions' => 0,
            'skipped_duplicate_area' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $stats['processed']++;

                // Extract CSV values
                // Name and last name extraction (prefer separate columns; fallback to legacy full_name)
                $name = isset($idx['name']) ? trim($row[$idx['name']] ?? '') : '';
                $lastname = isset($idx['last_name']) ? trim($row[$idx['last_name']] ?? '') : '';
                $fullName = '';
                if ($name !== '' || $lastname !== '') {
                    $fullName = trim($name . ' ' . $lastname);
                } elseif ($fullNameIndex !== null) {
                    $fullName = trim($row[$fullNameIndex] ?? '');
                }
                $document = trim($row[$idx['document']] ?? '');
                $legalTutorContact = trim($row[$idx['guardian_contact']] ?? '');
                $schoolName = trim($row[$idx['school_name']] ?? '');
                $department = trim($row[$idx['department']] ?? '');
                $educationLevel = trim($row[$idx['education_level']] ?? '');
                $areas = trim($row[$idx['areas']] ?? '');
                $levelName = trim($row[$idx['level']] ?? '');
                $academicTutor = trim($row[$idx['academic_tutor']] ?? '');

                if ($fullName === '' || $document === '' || $areas === '' || $levelName === '') {
                    $stats['errors'][] = [
                        'row' => $stats['processed'],
                        'message' => 'Missing required fields in row.',
                    ];
                    continue;
                }

                // If name/last_name not provided separately, split from full name best-effort
                if ($name === '' && $lastname === '') {
                    $nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
                    $lastname = array_pop($nameParts);
                    $name = trim(implode(' ', $nameParts));
                    if ($name === '') { $name = $lastname; $lastname = ''; }
                }

                // Optional: store legal tutor contact as Tutor first_name
                $tutor = null;
                if ($legalTutorContact !== '') {
                    $tutor = Tutor::firstOrCreate([
                        'first_name' => $legalTutorContact,
                        'last_name' => null,
                    ]);
                }

                // School
                $school = null;
                if ($schoolName !== '') {
                    $school = School::firstOrCreate(['name' => $schoolName]);
                }

                // Education level catalog (Level) and competition level are the same entity in this simplified scope
                $level = Level::firstOrCreate(['name' => $levelName]);
                $educationLevelModel = null;
                if ($educationLevel !== '') {
                    $educationLevelModel = Level::firstOrCreate(['name' => $educationLevel]);
                }

                // Upsert contestant by CI document
                $contestant = Contestant::updateOrCreate(
                    ['ci_document' => $document],
                    [
                        'name' => $name,
                        'lastname' => $lastname,
                        'tutor_id' => $tutor?->id,
                        'school_id' => $school?->id,
                        'city' => $department !== '' ? $department : null,
                        'education_level_id' => $educationLevelModel?->id,
                    ]
                );

                if ($contestant->wasRecentlyCreated) {
                    $stats['created_contestants']++;
                } else {
                    $stats['updated_contestants']++;
                }

                // Areas may come comma-separated
                $areaNames = array_values(array_filter(array_map(function ($a) {
                    return trim($a);
                }, preg_split('/[,;|]/', $areas))));

                // Track duplicates within the same CSV row
                $seenAreaIds = [];
                foreach ($areaNames as $areaName) {
                    if ($areaName === '') { continue; }
                    $area = Area::firstOrCreate(['name' => $areaName]);

                    if (in_array($area->id, $seenAreaIds, true)) {
                        $stats['skipped_duplicate_area']++;
                        continue; // duplicate area in same row
                    }
                    $seenAreaIds[] = $area->id;

                    // Ensure unique inscription per contestant-area-level
                    $inscription = Inscription::firstOrCreate([
                        'contestant_id' => $contestant->id,
                        'area_id' => $area->id,
                        'level_id' => $level->id,
                    ], [
                        'is_group' => false,
                    ]);

                    if ($inscription->wasRecentlyCreated) {
                        $stats['created_inscriptions']++;
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return response()->json([
                'message' => 'Failed to process CSV.',
                'error' => $e->getMessage(),
            ], 500);
        }

        fclose($handle);

        return response()->json([
            'message' => 'CSV processed successfully.',
            'stats' => $stats,
        ]);
    }
}


