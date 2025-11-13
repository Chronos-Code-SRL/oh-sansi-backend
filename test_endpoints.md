# Test Endpoints - Sistema de Clasificación

## Nuevos Endpoints Implementados

### 1. Obtener Competidores por Fase
```
GET /api/olympiads/{olympiadId}/areas/{areaId}/phases/{phaseId}/competitors
```

**Descripción**: Obtiene todos los competidores elegibles para una fase específica.
- Para la primera fase: Todos los inscritos
- Para fases posteriores: Solo clasificados de la fase anterior

**Ejemplo de Respuesta**:
```json
{
  "competitors": [
    {
      "evaluation_id": 1,
      "registration_id": 1,
      "contestant_id": 1,
      "first_name": "Juan",
      "last_name": "Pérez",
      "ci_document": "12345678",
      "score": 85,
      "description": "Buen desempeño",
      "status": true,
      "classification_status": "clasificado",
      "classification_place": "Oro"
    }
  ],
  "olympiad_id": "1",
  "area_id": "1", 
  "phase_id": "1",
  "status": 200
}
```

### 2. Actualizar Estado de Clasificación
```
PATCH /api/evaluations/{id}/classification
```

**Descripción**: Permite actualizar manualmente el estado de clasificación de un competidor.

**Body de Ejemplo**:
```json
{
  "classification_status": "descalificado",
  "description": "Comportamiento inadecuado durante la evaluación"
}
```

## Funcionalidad Automática

### Clasificación Automática
Cuando se marca una fase como "Terminada", el sistema automáticamente:

1. **Calcula el classification_status** basado en score_cuts:
   - `clasificado`: score >= score_cut
   - `desclasificado`: score < score_cut
   - `descalificado`: manual (por motivos disciplinarios)

2. **Asigna medallas** (solo en fase final):
   - `Oro`: Mayor puntuación
   - `Plata`: Segunda mayor puntuación  
   - `Bronce`: Tercera mayor puntuación
   - `Mención honorífica`: Resto de clasificados

### Campos Agregados a la Tabla `evaluations`

- `classification_status`: ENUM('clasificado', 'desclasificado', 'descalificado')
- `classification_place`: ENUM('Oro', 'Plata', 'Bronce', 'Mención honorífica')

## Estados de Clasificación

1. **clasificado**: El competidor cumplió con el score_cut y puede pasar a la siguiente fase
2. **desclasificado**: El competidor no cumplió con el score_cut 
3. **descalificado**: El competidor fue descalificado por motivos disciplinarios o conducta

## Flujo de Fases

- **Fase 1**: Todos los inscritos pueden participar
- **Fase 2+**: Solo los clasificados de la fase anterior
- **Fase Final**: Clasificados + asignación automática de medallas

## Casos de Uso

1. **Evaluador consulta competidores**: 
   - `GET /olympiads/1/areas/2/phases/1/competitors`

2. **Se termina una fase**:
   - `PUT /olympiads/1/areas/2/phase-status` con `status: "Terminada"`
   - Sistema automáticamente clasifica y asigna medallas si es fase final

3. **Descalificar un competidor manualmente**:
   - `PATCH /evaluations/123/classification` con `classification_status: "descalificado"`

4. **Consultar competidores para siguiente fase**:
   - `GET /olympiads/1/areas/2/phases/2/competitors` 
   - Solo retorna clasificados de fase anterior