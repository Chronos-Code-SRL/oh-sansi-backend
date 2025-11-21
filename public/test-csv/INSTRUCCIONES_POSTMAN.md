# Instrucciones para Probar el Endpoint con Postman

## Endpoint
```
POST /api/competitor-registration/upload-csv
```

## Configuración en Postman

### 1. Método y URL
- **Método**: POST
- **URL**: `http://localhost:8000/api/competitor-registration/upload-csv`

### 2. Headers
```
Content-Type: multipart/form-data
Accept: application/json
```

### 3. Body (form-data)
- **Key**: `files` (tipo: File)
- **Value**: Seleccionar uno o más archivos CSV
- **Key**: `olympiad_id` (tipo: Text)
- **Value**: `1` (ID de la olimpiada)

## Archivos CSV de Prueba Disponibles

### 1. `competidores_correctos.csv`
- **Descripción**: Datos completamente válidos
- **Resultado esperado**: Todos los competidores se registran exitosamente
- **Errores**: Ninguno

### 2. `competidores_con_errores.csv`
- **Descripción**: Datos con varios tipos de errores
- **Errores incluidos**:
  - CI muy corto (123)
  - Nombre con números (Ana123)
  - Género inválido (X)
  - Teléfono muy corto (123456)
  - Email inválido (invalid-email)
  - Más de 3 áreas
- **Resultado esperado**: Se genera archivo de errores

### 3. `competidores_mixtos.csv`
- **Descripción**: Mezcla de datos válidos e inválidos
- **Resultado esperado**: Algunos se registran, otros generan errores

## Ejemplos de Respuesta

### Respuesta Exitosa
```json
{
  "success": true,
  "message": "CSV files processed successfully",
  "data": {
    "total_successful": 5,
    "total_errors": 0,
    "files_processed": 1,
    "error_files": [],
    "summary": {
      "total_competitors_registered": 5,
      "total_competitors_with_errors": 0,
      "files_with_errors": 0
    },
    "details": [...]
  }
}
```

### Respuesta con Errores
```json
{
  "success": true,
  "message": "CSV files processed successfully",
  "data": {
    "total_successful": 2,
    "total_errors": 3,
    "files_processed": 1,
    "error_files": ["competidores_con_errores-errores.csv"],
    "summary": {
      "total_competitors_registered": 2,
      "total_competitors_with_errors": 3,
      "files_with_errors": 1
    },
    "details": [...]
  }
}
```

## Validaciones Implementadas

### Campos Obligatorios
- CI (8-13 dígitos, único)
- NOMBRE (2-50 caracteres, solo letras)
- APELLIDO (2-50 caracteres, solo letras)
- GENERO (F o M)
- DEPARTAMENTO (2-50 caracteres, solo letras)
- COLEGIO (2-100 caracteres alfanuméricos)
- AREA (máximo 3, separadas por coma)
- GRADO (solo letras)
- NUMERO TUTOR (8 dígitos)
- NOMBRE TUTOR (2-50 caracteres, solo letras)

### Campos Opcionales
- CELULAR (8 dígitos)
- E-MAIL (formato válido)

### Áreas Válidas
- Astronomía
- Biología
- Física
- Informática
- Matemática
- Química
- Robótica
- Astrofísica

## Notas Importantes
1. Los archivos CSV deben tener la cabecera exacta especificada
2. Los errores se generan en archivos separados con sufijo "-errores.csv"
3. Se pueden subir múltiples archivos CSV en una sola petición
4. El sistema valida unicidad de CI y email
5. Las áreas se separan por coma (,) no por punto y coma (;)
