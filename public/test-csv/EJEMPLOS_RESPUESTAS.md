# Ejemplos de Respuestas del Endpoint

## 1. Respuesta Exitosa (Sin Errores)

### Request
```
POST /api/competitors/upload-csv
Content-Type: multipart/form-data

files: competidores_correctos.csv
olympiad_id: 1
```

### Response (200 OK)
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
    "details": [
      {
        "filename": "competidores_correctos.csv",
        "successful": 5,
        "errors": 0,
        "error_file": null
      }
    ]
  }
}
```

## 2. Respuesta con Errores

### Request
```
POST /api/competitors/upload-csv
Content-Type: multipart/form-data

files: competidores_con_errores.csv
olympiad_id: 1
```

### Response (200 OK)
```json
{
  "success": true,
  "message": "CSV files processed successfully",
  "data": {
    "total_successful": 0,
    "total_errors": 5,
    "files_processed": 1,
    "error_files": ["competidores_con_errores-errores.csv"],
    "summary": {
      "total_competitors_registered": 0,
      "total_competitors_with_errors": 5,
      "files_with_errors": 1
    },
    "details": [
      {
        "filename": "competidores_con_errores.csv",
        "successful": 0,
        "errors": 5,
        "error_file": "competidores_con_errores-errores.csv"
      }
    ]
  }
}
```

## 3. Respuesta Mixta (Algunos Exitosos, Algunos con Errores)

### Request
```
POST /api/competitors/upload-csv
Content-Type: multipart/form-data

files: competidores_mixtos.csv
olympiad_id: 1
```

### Response (200 OK)
```json
{
  "success": true,
  "message": "CSV files processed successfully",
  "data": {
    "total_successful": 3,
    "total_errors": 2,
    "files_processed": 1,
    "error_files": ["competidores_mixtos-errores.csv"],
    "summary": {
      "total_competitors_registered": 3,
      "total_competitors_with_errors": 2,
      "files_with_errors": 1
    },
    "details": [
      {
        "filename": "competidores_mixtos.csv",
        "successful": 3,
        "errors": 2,
        "error_file": "competidores_mixtos-errores.csv"
      }
    ]
  }
}
```

## 4. Respuesta de Error de Validación

### Request
```
POST /api/competitors/upload-csv
Content-Type: multipart/form-data

olympiad_id: 1
// Sin archivos
```

### Response (422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "files": [
      "The files field is required."
    ]
  }
}
```

## 5. Respuesta de Error del Servidor

### Request
```
POST /api/competitors/upload-csv
Content-Type: multipart/form-data

files: archivo_corrupto.csv
olympiad_id: 1
```

### Response (500 Internal Server Error)
```json
{
  "success": false,
  "message": "Error processing CSV files: [mensaje de error específico]"
}
```

## 6. Múltiples Archivos

### Request
```
POST /api/competitors/upload-csv
Content-Type: multipart/form-data

files: [competidores_correctos.csv, competidores_con_errores.csv, competidores_mixtos.csv]
olympiad_id: 1
```

### Response (200 OK)
```json
{
  "success": true,
  "message": "CSV files processed successfully",
  "data": {
    "total_successful": 8,
    "total_errors": 7,
    "files_processed": 3,
    "error_files": ["competidores_con_errores-errores.csv", "competidores_mixtos-errores.csv"],
    "summary": {
      "total_competitors_registered": 8,
      "total_competitors_with_errors": 7,
      "files_with_errors": 2
    },
    "details": [
      {
        "filename": "competidores_correctos.csv",
        "successful": 5,
        "errors": 0,
        "error_file": null
      },
      {
        "filename": "competidores_con_errores.csv",
        "successful": 0,
        "errors": 5,
        "error_file": "competidores_con_errores-errores.csv"
      },
      {
        "filename": "competidores_mixtos.csv",
        "successful": 3,
        "errors": 2,
        "error_file": "competidores_mixtos-errores.csv"
      }
    ]
  }
}
```

## Tipos de Errores que se Pueden Generar

### Errores de Validación de Campos
- "CI Document must be 8-13 digits"
- "CI Document already exists"
- "First Name must be 2-50 characters and contain only letters"
- "Last Name must be 2-50 characters and contain only letters"
- "Gender must be F or M"
- "Department must be 2-50 characters and contain only letters"
- "School must be 2-100 characters and contain only alphanumeric characters"
- "Phone must be exactly 8 digits"
- "Email format is invalid"
- "Email already exists"
- "Maximum 3 areas allowed"
- "Area 'X' does not exist"
- "Grade must contain only letters"
- "Tutor Name must be 2-50 characters and contain only letters"
- "Tutor Number must be exactly 8 digits"

### Errores de Campos Obligatorios
- "CI Document is required"
- "First Name is required"
- "Last Name is required"
- "Gender is required"
- "Department is required"
- "School is required"
- "Area is required"
- "Grade is required"
- "Tutor Number is required"
- "Tutor Name is required"
