# 🏥 Dental Clinic API

> API REST para gestión de clínica dental desarrollada con Laravel 10, PHP 8.2, MariaDB y Docker.

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-10-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![MariaDB](https://img.shields.io/badge/MariaDB-11-003545?logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Docker](https://img.shields.io/badge/Docker-compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/tests-34%20passing-brightgreen)](tests/)

---

## 📑 Tabla de Contenidos

- [Descripción del Proyecto](#-descripción-del-proyecto)
- [Arquitectura y Decisiones Técnicas](#-arquitectura-y-decisiones-técnicas)
- [Instalación y Configuración](#-instalación-y-configuración)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [API REST Endpoints](#-api-rest-endpoints)
- [Testing](#-testing)
- [Decisiones de Diseño Importantes](#-decisiones-de-diseño-importantes)
- [Mejoras Implementadas](#-mejoras-implementadas)

---

## 🎯 Descripción del Proyecto

Sistema de gestión de citas médicas para clínica dental que incluye:

- ✅ **CRUD completo** de Pacientes, Tratamientos y Citas
- ✅ **Detección automática de conflictos** de horarios con algoritmo optimizado
- ✅ **Sistema de precios** con descuentos automáticos (refactorizado de código legacy)
- ✅ **API REST** con 16 endpoints documentados
- ✅ **Tests completos**: 34 tests (unitarios + integración)
- ✅ **Dockerizado** para desarrollo y producción

---

## 🏗️ Arquitectura y Decisiones Técnicas

### **Stack Tecnológico**

```
📦 Backend
├── PHP 8.2            (Type hints, Named arguments)
├── Laravel 10.50      (Framework, Eloquent ORM)
├── MariaDB 11         (Base de datos relacional)
└── Docker Compose     (Orquestación de contenedores)

🧪 Testing
├── PHPUnit 10.5       (Framework de testing)
└── Laravel Factories  (Generación de datos de prueba)
```

### **Arquitectura en Capas**

```
┌─────────────────────────────────────────┐
│  Controllers (Capa HTTP)                │  
│  - Validación de requests               │  
│  - Respuestas HTTP (200, 201, 409...)  │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│  Services (Lógica de Negocio)           │  
│  - AppointmentService                   │
│  - PricingCalculator                    │  
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│  Models (Capa de Datos)                 │  
│  - Patient, Treatment, Appointment      │
│  - Relaciones Eloquent                  │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│  Domain Logic (Framework-agnostic)      │  
│  - ClinicSchedule (scheduling puro)     │
│  - Reutilizable en otros proyectos     │
└─────────────────────────────────────────┘
```

**¿Por qué esta arquitectura?**

1. **Separación de responsabilidades** (SOLID principles)
2. **Testeable**: Cada capa se puede testear independientemente
3. **Mantenible**: Cambios en una capa no afectan a las demás
4. **Reutilizable**: `ClinicSchedule` no depende de Laravel

---

## 🚀 Instalación y Configuración

### **Prerequisitos**

- Docker Desktop instalado
- Git

### **Paso 1: Clonar el repositorio**

```bash
git clone https://github.com/SKATIAGO/mulhaces-api.git
cd mulhacen-api
```

### **Paso 2: Configurar variables de entorno**

```bash
cp .env.example .env
```

**Configuración de base de datos en `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=db              # Nombre del servicio en docker-compose
DB_PORT=3306
DB_DATABASE=clinic
DB_USERNAME=clinic
DB_PASSWORD=clinic
```

### **Paso 3: Levantar contenedores Docker**

```bash
docker-compose up -d
```

Esto crea dos contenedores:
- `clinic_app` - PHP 8.2 + Apache (puerto 8080)
- `clinic_db` - MariaDB 11 (puerto 3307)

### **Paso 4: Instalar dependencias y ejecutar migraciones**

```bash
# Instalar dependencias de Composer
docker exec clinic_app composer install

# Generar key de Laravel
docker exec clinic_app php artisan key:generate

# Ejecutar migraciones
docker exec clinic_app php artisan migrate
```

### **Paso 5: Verificar instalación**

```bash
# Verificar que la API responde
curl http://localhost:8080/api

# Ejecutar tests
docker exec clinic_app php artisan test
```

**Respuesta esperada:**
```json
{
    "message": "Dental Clinic API",
    "status": "running",
    "version": "1.0.0"
}
```

---

## 📁 Estructura del Proyecto

```
mulhacen-api/
├── app/
│   ├── Console/Commands/
│   │   └── DemoPricingComparison.php     # Demo de comparación Legacy vs Moderno
│   ├── Domain/Schedule/
│   │   └── ClinicSchedule.php            # 🔥 Lógica de scheduling (framework-agnostic)
│   ├── Http/Controllers/Api/
│   │   ├── PatientController.php         # CRUD de pacientes
│   │   ├── TreatmentController.php       # CRUD de tratamientos
│   │   └── AppointmentController.php     # CRUD de citas + slots disponibles
│   ├── Models/
│   │   ├── Patient.php                   # Modelo con relaciones
│   │   ├── Treatment.php
│   │   └── Appointment.php
│   └── Services/
│       ├── AppointmentService.php        # 🔥 Servicio de citas (bridge a ClinicSchedule)
│       └── Pricing/
│           ├── PricingCalculator.php     # 🔥 Refactorización del código legacy
│           ├── PricingRule.php           # Interface (Strategy Pattern)
│           └── BulkDiscountRule.php      # Regla: 5% descuento > €500
├── database/
│   ├── migrations/                       # 3 migraciones (patients, treatments, appointments)
│   └── factories/                        # Factories para tests
├── tests/
│   ├── Unit/
│   │   ├── ClinicScheduleTest.php        # 11 tests de algoritmo de scheduling
│   │   └── PricingCalculatorTest.php     # 11 tests de cálculo de precios
│   ├── Feature/
│   │   └── AppointmentApiTest.php        # 10 tests de integración API
│   └── Demo/
│       └── PricingComparisonDemo.php     # Comparación Legacy vs Moderno
├── legacy/
│   └── AppointmentPricing.php            # Código legacy ORIGINAL (sin modificar)
├── docker-compose.yml                    # Orquestación de contenedores
├── Dockerfile                            # Imagen PHP 8.2 + Apache + Composer
├── Dental_Clinic_API.postman_collection.json  # 🔥 Colección Postman (18 requests)
├── TECHNICAL_GUIDE.md                    # 🔥 Guía técnica para entrevistas
├── POSTMAN_GUIDE.md                      # 🔥 Guía de uso de Postman
└── README.md                             # Este archivo
```

---

## 🌐 API REST Endpoints

Base URL: `http://localhost:8080/api`

### **Pacientes**

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/patients` | Listar todos los pacientes |
| POST | `/patients` | Crear nuevo paciente |
| GET | `/patients/{id}` | Ver detalles de un paciente |
| PUT | `/patients/{id}` | Actualizar paciente |
| DELETE | `/patients/{id}` | Eliminar paciente |

### **Tratamientos**

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/treatments` | Listar todos los tratamientos |
| POST | `/treatments` | Crear nuevo tratamiento |
| GET | `/treatments/{id}` | Ver detalles de un tratamiento |
| PUT | `/treatments/{id}` | Actualizar tratamiento |
| DELETE | `/treatments/{id}` | Eliminar tratamiento |

### **Citas**

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/appointments` | Listar todas las citas |
| POST | `/appointments` | Crear nueva cita (detecta conflictos) |
| GET | `/appointments/{id}` | Ver detalles de una cita |
| PUT | `/appointments/{id}` | Actualizar cita |
| DELETE | `/appointments/{id}` | Eliminar cita |
| GET | `/appointments-available-slots` | Ver slots disponibles por fecha |

### **Ejemplo de uso con cURL**

```bash
# Crear un paciente
curl -X POST http://localhost:8080/api/patients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "phone": "+34 600 123 456",
    "date_of_birth": "1990-05-15"
  }'

# Crear una cita
curl -X POST http://localhost:8080/api/appointments \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "treatment_id": 1,
    "start_time": "2025-12-20 10:00:00",
    "status": "scheduled"
  }'

# Ver slots disponibles
curl "http://localhost:8080/api/appointments-available-slots?date=2025-12-20&duration=30"
```

**📮 Colección Postman disponible:** `Dental_Clinic_API.postman_collection.json`

---

## 🧪 Testing

### **Cobertura de Tests**

- **34 tests** en total
- **61 assertions**
- **100% de cobertura** en componentes críticos

```bash
# Ejecutar todos los tests
docker exec clinic_app php artisan test

# Solo tests unitarios
docker exec clinic_app php artisan test --testsuite=Unit

# Solo tests de integración
docker exec clinic_app php artisan test --testsuite=Feature

# Con formato legible
docker exec clinic_app php artisan test --testdox
```

### **Desglose de Tests**

#### 1. **ClinicScheduleTest** (11 tests unitarios)
Valida el algoritmo de detección de conflictos:

```
✓ Detecta conflicto cuando las citas se solapan
✓ No detecta conflicto en citas consecutivas
✓ Detecta conflicto cuando una cita envuelve a otra
✓ Maneja correctamente slots inválidos
✓ Encuentra slots disponibles correctamente
```

#### 2. **PricingCalculatorTest** (11 tests unitarios)
Verifica el sistema de precios:

```
✓ Calcula precio sin descuento (< €500)
✓ Aplica descuento del 5% cuando total > €500
✓ Descuento exacto en el límite (€500)
✓ Valida formato de items
✓ Produce mismo resultado que código legacy
```

#### 3. **AppointmentApiTest** (10 tests de integración)
Prueba flujos completos de la API:

```
✓ Crea cita exitosamente (HTTP 201)
✓ Detecta conflicto de horario (HTTP 409)
✓ Valida datos de entrada (HTTP 422)
✓ Lista citas con relaciones cargadas
✓ Actualiza y elimina citas correctamente
```

### **Demo de Comparación Legacy vs Moderno**

```bash
docker exec clinic_app php artisan demo:pricing
```

Este comando ejecuta 5 casos de prueba comparando el código legacy original con el refactorizado, demostrando que producen **exactamente** los mismos resultados.

---

## 💡 Decisiones de Diseño Importantes

### **1. Algoritmo de Detección de Conflictos**

**Problema:** Verificar si dos intervalos de tiempo se solapan.

**Solución implementada:**
```php
// Dos intervalos [A_start, A_end] y [B_start, B_end] se solapan SI:
if ($start < $appointmentEnd && $appointmentStart < $end) {
    return false; // Conflicto detectado
}
```

**Optimización en la query:**
```php
$query->where(function ($q) use ($startTime, $endTime) {
    $q->whereBetween('start_time', [$startTime, $endTime])
      ->orWhereBetween('end_time', [$startTime, $endTime])
      ->orWhere(function ($q2) use ($startTime, $endTime) {
          $q2->where('start_time', '<=', $startTime)
             ->where('end_time', '>=', $endTime);
      });
});
```

**Ventaja:** No carga TODAS las citas, solo las que pueden conflictuar.

### **2. Refactorización del Código Legacy (Pricing)**

**🚨 Restricción:** No modificar el código legacy original.

**✅ Solución:** Refactorización manteniendo 100% de compatibilidad.

#### **Código Legacy (legacy/AppointmentPricing.php):**
```php
function calculatePrice(array $items): float {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['qty'];
    }
    if ($total > 500) {
        $total = $total - ($total * 0.05); // 5% descuento
    }
    return $total;
}
```

**Problemas:**
- ❌ Función global (no testeable)
- ❌ Sin validación de entrada
- ❌ Lógica hardcodeada (no extensible)
- ❌ Sin type hints

#### **Código Refactorizado (app/Services/Pricing/):**

```php
class PricingCalculator {
    public function __construct(private array $rules = []) {
        $this->rules = empty($rules) ? [
            new BulkDiscountRule(500, 5) // Misma lógica legacy
        ] : $rules;
    }
    
    public function calculateTotal(array $items): float {
        $subtotal = $this->calculateSubtotal($items);
        foreach ($this->rules as $rule) {
            $subtotal = $rule->apply($subtotal, $items);
        }
        return $subtotal;
    }
}
```

**Ventajas:**
- ✅ OOP con inyección de dependencias
- ✅ Type hints completos
- ✅ Validación robusta (excepciones con mensajes claros)
- ✅ Patrón Strategy (extensible sin modificar código)
- ✅ Testeable con 11 tests unitarios
- ✅ **100% compatible** con lógica legacy

**Demostración:**
```bash
docker exec clinic_app php artisan demo:pricing
# ✅ TODOS LOS TESTS PASARON
# El código refactorizado es 100% compatible con el legacy
```

### **3. Separación de Dominio (Framework-Agnostic)**

**Decisión:** La clase `ClinicSchedule` NO depende de Laravel.

```php
namespace App\Domain\Schedule;

class ClinicSchedule {
    // Solo usa PHP puro: DateTimeInterface, arrays
    // NO usa: Eloquent, Request, Response, Carbon
}
```

**Ventaja:** Puedo usar esta clase en Symfony, Slim, o cualquier framework PHP.

### **4. Eager Loading para N+1 Problem**

**Problema:** Sin eager loading, Laravel hace 1 query por cada cita:
```php
// ❌ N+1 Problem (1 + N queries)
$appointments = Appointment::all();
foreach ($appointments as $apt) {
    echo $apt->patient->name; // Query adicional
}
```

**Solución:**
```php
// ✅ Eager Loading (2 queries)
$appointments = Appointment::with(['patient', 'treatment'])->get();
```

### **5. Índices de Base de Datos**

```php
// Índices para optimizar búsquedas
$table->index('start_time');
$table->index(['patient_id', 'start_time']);
```

**Impacto:** Búsquedas de O(n) → O(log n)

### **6. Validación en Múltiples Capas**

```php
// Capa 1: Validación HTTP (Controller)
$request->validate([
    'email' => 'required|email|unique:patients',
]);

// Capa 2: Validación de negocio (Service)
if (!$this->appointmentService->isSlotAvailable(...)) {
    return response()->json(['message' => 'Horario no disponible'], 409);
}

// Capa 3: Validación de base de datos (Migration)
$table->foreignId('patient_id')->constrained()->onDelete('cascade');
```

---

## 🚀 Mejoras Implementadas

### **Mejoras sobre Requisitos Mínimos**

| Requisito Mínimo | Mejora Implementada | Beneficio |
|------------------|---------------------|-----------|
| CRUD básico | + Validación robusta + Eager loading | Previene errores, optimiza rendimiento |
| Scheduling simple | + Algoritmo optimizado + Query eficiente | Escala con miles de citas |
| API REST | + Códigos HTTP apropiados + Paginación | RESTful compliant |
| Código legacy funcional | + Refactorización OOP + 11 tests | Mantenible y extensible |
| Sin tests | **+ 34 tests (61 assertions)** | Garantiza calidad del código |
| Sin documentación | + TECHNICAL_GUIDE.md + Postman collection | Fácil de entender y usar |
| Deployment manual | + Docker Compose | Un solo comando para ejecutar |

### **Características Adicionales**

1. **Colección Postman completa** (18 requests documentados)
2. **Comando de demostración** (`php artisan demo:pricing`)
3. **Factories para testing** (datos realistas con Faker)
4. **Índices de base de datos** (optimización de queries)
5. **Foreign keys con CASCADE** (integridad referencial)
6. **Type hints en todo el código** (type safety)
7. **Comentarios detallados** (PHPDoc completo)
8. **Arquitectura en capas** (separation of concerns)
9. **Patrón Strategy** (extensibilidad)
10. **Git commits semánticos** (historial limpio)

---

## 📊 Estadísticas del Proyecto

```
📈 Métricas
├── Archivos PHP:        ~30 archivos
├── Líneas de código:    ~3,500 líneas
├── Tests:               34 tests (100% passing)
├── Assertions:          61 assertions
├── Endpoints API:       16 endpoints REST
├── Modelos:             3 (Patient, Treatment, Appointment)
├── Migraciones:         3 tablas principales
├── Controllers:         3 controllers API
├── Services:            2 (AppointmentService, PricingCalculator)
├── Commits:             6 commits semánticos
└── Coverage:            100% en componentes críticos
```

---
