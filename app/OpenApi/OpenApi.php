<?php

namespace App\OpenApi;

/**
 * @OA\OpenApi(
 *     openapi="3.0.0"
 * )
 *
 * @OA\Info(
 *   title="OH Sansi API",
 *   version="1.0.0",
 *   description="Documentación de la API para OH Sansi."
 * )
 *
 * @OA\Server(
 *   url="{APP_URL}",
 *   description="Servidor local",
 *   @OA\ServerVariable(
 *     serverVariable="APP_URL",
 *     default="http://localhost:8000",
 *     description="URL base de la aplicación"
 *   )
 * )
 *
 * @OA\Tag(
 *   name="Competitors",
 *   description="Operaciones relacionadas con competidores"
 * )
 *
 * @OA\Schema(
 *   schema="CompetitorRowError",
 *   type="object",
 *   required={"message"},
 *   @OA\Property(property="row", type="integer", nullable=true, example=5, description="Número de fila en el CSV (1-index)."),
 *   @OA\Property(property="message", type="string", example="Faltan campos obligatorios en la fila.")
 * )
 *
 * @OA\Schema(
 *   schema="CompetitorStats",
 *   type="object",
 *   required={"processed","created_contestants","updated_contestants","created_inscriptions","skipped_duplicate_area","errors"},
 *   @OA\Property(property="processed", type="integer", example=12),
 *   @OA\Property(property="created_contestants", type="integer", example=8),
 *   @OA\Property(property="updated_contestants", type="integer", example=4),
 *   @OA\Property(property="created_inscriptions", type="integer", example=10),
 *   @OA\Property(property="skipped_duplicate_area", type="integer", example=2),
 *   @OA\Property(property="errors", type="array", @OA\Items(ref="#/components/schemas/CompetitorRowError"))
 * )
 *
 * @OA\Schema(
 *   schema="CompetitorFileResult",
 *   type="object",
 *   required={"mensaje"},
 *   @OA\Property(property="archivo", type="string", nullable=true, example="competitors.csv"),
 *   @OA\Property(property="mensaje", type="string", example="Archivo procesado correctamente."),
 *   @OA\Property(property="estadisticas", ref="#/components/schemas/CompetitorStats", nullable=true),
 *   @OA\Property(
 *     property="errores",
 *     type="array",
 *     nullable=true,
 *     @OA\Items(type="object",
 *       @OA\Property(property="message", type="string", example="Faltan columnas obligatorias."),
 *       @OA\Property(property="missing_headers", type="array", @OA\Items(type="string"), nullable=true)
 *     )
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="CompetitorUploadResponse",
 *   type="object",
 *   required={"message","resultados"},
 *   @OA\Property(property="message", type="string", example="Procesamiento de archivos CSV finalizado."),
 *   @OA\Property(property="resultados", type="array", @OA\Items(ref="#/components/schemas/CompetitorFileResult"))
 * )
 *
 * @OA\Schema(
 *   schema="MissingFileError",
 *   type="object",
 *   required={"message"},
 *   @OA\Property(property="message", type="string", example="No se recibió ningún archivo. Envíe uno o más archivos CSV.")
 * )
 *
 * @OA\Schema(
 *   schema="ValidationError",
 *   type="object",
 *   required={"message","errors"},
 *   @OA\Property(property="message", type="string", example="Archivo(s) inválido(s). Solo se permiten archivos .csv."),
 *   @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *   schema="CompetitorUploadForm",
 *   type="object",
 *   description="Formulario para subir uno o varios archivos CSV.",
 *   @OA\Property(property="file", type="string", format="binary", description="Archivo CSV (alternativa a 'files')."),
 *   @OA\Property(property="files", type="array", description="Varios archivos CSV.", @OA\Items(type="string", format="binary"))
 * )
 */
class OpenApi
{
    // Archivo sólo para anotaciones globales de OpenAPI.
}
