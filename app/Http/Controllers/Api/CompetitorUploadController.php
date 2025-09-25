<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Contestant;
use App\Models\Inscription;
use App\Models\Level;
use App\Models\School;
use App\Models\Tutor;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompetitorUploadController extends Controller
{
    /**
     * Handle CSV upload, validate headers and rows, upsert contestants and inscriptions.
     */
    /**
     * @OA\Post(
     *   path="/api/competitors/upload-csv",
     *   summary="Subir CSV de competidores",
     *   description="Sube uno o varios archivos CSV y procesa participantes, tutores, colegios y sus inscripciones. Cabeceras esperadas: N. (opcional), DOC., NOMBRE, GEN, DEP., COLEGIO, CELULAR, E-MAIL, AREA, NIVEL, GRADO. AREA admite múltiples valores separados por coma, punto y coma o barra vertical. Si se envían file y files a la vez, se prioriza files.",
     *   tags={"Competitors"},
     *   operationId="uploadCompetitorsCsv",
     *   requestBody=@OA\RequestBody(
     *     request="uploadCsv",
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(ref="#/components/schemas/CompetitorUploadForm")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Procesamiento de archivos CSV finalizado",
     *     @OA\JsonContent(ref="#/components/schemas/CompetitorUploadResponse")
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Error de validación o archivo faltante",
     *     @OA\JsonContent(oneOf={
     *       @OA\Schema(ref="#/components/schemas/MissingFileError"),
     *       @OA\Schema(ref="#/components/schemas/ValidationError")
     *     })
     *   )
     * )
     */
    public function upload(Request $request)
    {
        // Normalize input: allow 'file' (single) or 'files' (multiple)
        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'files.*' => 'file|mimes:csv,txt',
            ]);
        } elseif ($request->hasFile('file')) {
            $files = [$request->file('file')];
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:csv,txt',
            ]);
        } else {
            return response()->json([
                'message' => 'No se recibió ningún archivo. Envíe uno o más archivos CSV.',
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Archivo(s) inválido(s). Solo se permiten archivos .csv.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $globalStats = [];
        foreach ($files as $file) {
            $globalStats[] = $this->processCsvFile($file);
        }
        return response()->json([
            'message' => 'Procesamiento de archivos CSV finalizado.',
            'resultados' => $globalStats,
        ]);
    }

    /**
     * Process a single CSV file and return statistics and errors.
     */
    private function processCsvFile($file)
    {
        // Fixed headers without aliases (case-insensitive)
        // Expected exactly: N., DOC., NOMBRE, GEN, DEP., COLEGIO, CELULAR, E-MAIL, AREA, NIVEL, GRADO
        $expectedHeaders = [
            'n.', 'doc.', 'nombre', 'gen', 'dep.', 'colegio', 'celular', 'e-mail', 'area', 'nivel', 'grado'
        ];

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [
                'archivo' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
                'mensaje' => 'No se pudo leer el archivo subido.',
                'estadisticas' => null,
                'errores' => [
                    ['message' => 'Error de lectura del archivo.']
                ],
            ];
        }
        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [
                'archivo' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
                'mensaje' => 'El archivo está vacío.',
                'estadisticas' => null,
                'errores' => [
                    ['message' => 'El archivo no contiene cabeceras ni datos.']
                ],
            ];
        }

        // Normalize headers by trimming BOM and spaces and lowercasing
        $normalizedHeader = array_map(function ($h) {
            $h = preg_replace('/\x{FEFF}/u', '', $h ?? '');
            return mb_strtolower(trim($h));
        }, $header);

        // Build map expected header => index (exact, case-insensitive)
        $idx = [];
        foreach ($expectedHeaders as $h) {
            $pos = array_search($h, $normalizedHeader, true);
            if ($pos !== false) { $idx[$h] = $pos; }
        }

        // Validate required headers are present (ignoring 'N.' which is optional)
        $required = ['doc.', 'nombre', 'dep.', 'colegio', 'celular', 'area', 'nivel', 'grado'];
        $missing = array_diff($required, array_keys($idx));
        
        if (count($missing) > 0) {
            fclose($handle);
            return [
                'archivo' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
                'mensaje' => 'El archivo no tiene el formato requerido. Verifique las columnas obligatorias.',
                'estadisticas' => null,
                'errores' => [
                    ['message' => 'Faltan columnas obligatorias.', 'missing_headers' => $missing]
                ],
            ];
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

                // Extract CSV values from fixed headers
                $fullName = trim($row[$idx['nombre']] ?? '');
                $document = trim($row[$idx['doc.']] ?? '');
                $gender = array_key_exists('gen', $idx) ? trim($row[$idx['gen']] ?? '') : '';
                $department = trim($row[$idx['dep.']] ?? '');
                $schoolName = trim($row[$idx['colegio']] ?? '');
                $tutorPhone = trim($row[$idx['celular']] ?? '');
                $tutorEmail = array_key_exists('e-mail', $idx) ? trim($row[$idx['e-mail']] ?? '') : '';
                $areas = trim($row[$idx['area']] ?? '');
                $levelName = trim($row[$idx['nivel']] ?? '');
                $gradeName = trim($row[$idx['grado']] ?? '');

                if ($fullName === '' || $document === '' || $areas === '' || $levelName === '') {
                    $stats['errors'][] = [
                        'row' => $stats['processed'],
                        'message' => 'Faltan campos obligatorios en la fila.',
                    ];
                    continue;
                }

                // Split full name best-effort into name/lastname
                $name = '';
                $lastname = '';
                $nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
                if (count($nameParts) >= 2) {
                    $lastname = array_pop($nameParts);
                    $name = trim(implode(' ', $nameParts));
                } else {
                    $name = $fullName;
                }

                // Tutor: identify by phone/email; name not provided in fixed header
                $tutor = null;
                if ($tutorPhone !== '' || $tutorEmail !== '') {
                    $tutor = Tutor::firstOrCreate(
                        [ 'phone' => $tutorPhone ?: null, 'email' => $tutorEmail ?: null ],
                        [ 'first_name' => null, 'last_name' => null ]
                    );
                }

                // School
                $school = null;
                if ($schoolName !== '') {
                    $school = School::firstOrCreate(['name' => $schoolName]);
                }

                // Catalogs: competition Level and school Grade
                $level = Level::firstOrCreate(['name' => $levelName]);
                $gradeModel = null;
                if ($gradeName !== '') {
                    $gradeModel = Grade::firstOrCreate(['name' => $gradeName]);
                }

                // Upsert contestant by CI document
                $contestant = Contestant::updateOrCreate(
                    ['ci_document' => $document],
                    [
                        'name' => $name,
                        'lastname' => $lastname,
                        'gender' => $gender !== '' ? $gender : null,
                        'tutor_id' => $tutor?->id,
                        'school_id' => $school?->id,
                        'department' => $department !== '' ? $department : null,
                        'grade_id' => $gradeModel?->id,
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
            return [
                'archivo' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
                'mensaje' => 'Error al procesar el CSV.',
                'estadisticas' => null,
                'errores' => [
                    ['message' => $e->getMessage()]
                ],
            ];
        }

        fclose($handle);

        return [
            'archivo' => $file->getClientOriginalName(),
            'mensaje' => 'Archivo procesado correctamente.',
            'estadisticas' => $stats,
        ];
    }
}


