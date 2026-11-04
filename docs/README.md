# Índice de Documentación del Sistema

Bienvenido a la documentación oficial del **Sistema Web de Gestión, Generación, Diseño, Emisión y Verificación de Certificados Académicos con Código QR** (Versión 2.5.0 — Arquitectura Multirrol y Multitenancy Facultativo) desarrollado para la **Facultad de Tecnología — Universidad Mayor de San Andrés (UMSA)**.

---

## 📚 Documentos Disponibles en la Carpeta `docs/`

### 1. 👥 [Manual de Usuario y Guía Operativa](MANUAL_USUARIO.md)
Documento dirigido a **Autoridades Facultativas, Jefes de Carrera, Secretarías Académicas y Personal Administrativo de Cómputo**:
- **Matriz de Roles y Permisos Institucionales**:
  - 👑 **Super Administrador (Cómputo y Sistemas)**: Control absoluto global, gestión de cuentas y administración de Carreras y Departamentos.
  - 🎓 **Jefe de Carrera / Departamento**: Dashboard, Actividades, Certificados, Beneficiarios, Diseñador de Plantillas y **Módulo de Reportes Estadísticos** (todo aislado estrictamente a su propia unidad).
  - 💼 **Secretaria de Carrera**: Dashboard, Actividades, Certificados, Beneficiarios, Diseñador de Plantillas y emisión masiva (aislado a su carrera; con restricción formal de acceso a Reportes).
  - ⚙️ **Operador**: Emisión y validación operativa de documentos.
- **Gestión Dinámica de Carreras y Departamentos (`/carreras-departamentos`)**: Alta, edición y suspensión/reactivación de unidades garantizando que los certificados históricos sigan siendo 100% verificables y válidos.
- **Cumplimiento Reglamentario por Resolución Facultativa**:
  - Configuración de eventos por alcance: *Propia Carrera*, *Toda la Facultad* o *Carreras Específicas Autorizadas por Resolución*.
  - Detección de estudiantes no autorizados (`observados`) y omisión automática de emisión.
- **Regla Ética de Aprobación Obligatoria (>= 51 pts)**:
  - Exclusión automática de estudiantes reprobados (< 51 pts) o sin nota en certificados con evaluación.
- **Padrón Único y Portafolio Académico 360° del Estudiante**:
  - Historial consolidado por C.I. que reúne todos los certificados obtenidos por el alumno en la Facultad con insignias de carrera emisora.
- **Gestor y Diseñador Visual de Plantillas (WYSIWYG)**:
  - Personalización de márgenes, colores, tipografías, co-firmantes con cargos oficiales y variables dinámicas (`{{beneficiario.nombre}}`, `{{certificado.puntaje}}`, etc.).
- **Portal Público de Verificación**:
  - Escaneo de código QR o consulta por código correlativo y token criptográfico SHA-256.

---

### 2. 🏛️ [Documentación Técnica, Arquitectura y Guía de Soporte](ARQUITECTURA_Y_SISTEMA.md)
Documento dirigido a **Arquitectos de Software, Desarrolladores, Administradores de TI y Soporte Técnico**:
- **Arquitectura de Software**: Desacoplamiento en capas (MVC + Capa de Servicios + Multitenancy de Unidades Organizacionales).
- **Stack Tecnológico**: PHP 8.3+ / 8.5 en **Laragon** sobre Windows, Laravel 13, Tailwind CSS v4, Alpine.js, DomPDF y MySQL 8.x.
- **Aislamiento Multitenant (Data Scoping)**:
  - Modelo `OrganizationalUnit` y método `User::isUnitScoped()`.
  - Filtrado automático en consultas de actividades, certificados, plantillas, reportes y métricas según la carrera del usuario autenticado.
- **Motor de Resoluciones y Reglas de Audiencia**:
  - Lógica `Activity::isCareerAllowed()` y sincronización de carreras permitidas en `activity_allowed_units`.
- **Diagrama Entidad-Relación Completo (Mermaid)**:
  - Modelado de las 14 tablas del sistema, llaves foráneas, índices de unicidad y cascadas seguras.
- **Inmutabilidad y Preservación Documental (Regla 40)**:
  - Congelación de snapshots JSON (`snapshot_data`) y sellado criptográfico mediante hashes SHA-256.
- **Suite de Pruebas Automatizadas (PHPUnit)**:
  - Pruebas de integración multirrol, multitenancy, resoluciones facultativas y certificados con puntaje (100% aprobadas).

---

### 3. 🗄️ [Documentación y Diccionario de Base de Datos](BASE_DE_DATOS.md)
Documento técnico de referencia exhaustivo para administradores de bases de datos (DBA) y analistas:
- Diagrama Entidad-Relación (ERD) en formato Mermaid con todas las tablas y cardinalidades.
- Diccionario de datos de cada tabla con tipos de columna, valores nulos, claves primarias/foráneas y restricciones.
- Estructura de esquemas JSON internos (`design_schema`, `snapshot_data`, metadatos de recursos).
- Estrategia de respaldo (dump SQL) e integridad referencial ante desactivación de carreras.

---

### 4. 📈 [Informe de Avances, Correcciones y Evolución](avances.md)
Bitácora histórica del desarrollo del sistema:
- Problemas diagnosticados y soluciones aplicadas durante las distintas fases.
- Evolución desde el motor básico inicial hasta la arquitectura multirrol y multitenancy facultativa actual.
- Registro de mejoras en el Diseñador Visual, importador Excel/CSV y validaciones de aprobación.

---

## 🚀 Inicio Rápido para Pruebas Locales (Laragon)

### Opción A: Lanzador Automático de 1 Clic (Recomendado)
Haz doble clic en el archivo `iniciar_proyecto.bat` ubicado en la raíz del proyecto. Podrás elegir entre:
- **Modo Autónomo:** Limpia temporales y levanta `php artisan serve` con estilos compilados fijos.
- **Modo Desarrollo:** Levanta `php artisan serve` y `npm run dev` en paralelo para recarga en vivo.

---

### Opción B: Ejecución Manual en Terminal de Laragon

```powershell
# 1. Asegurar que MySQL esté iniciado en el panel de Laragon (Puerto 3306)

# 2. Compilar estilos y scripts con Vite (con npm.cmd en Windows PowerShell):
npm.cmd run build

# 3. Iniciar el servidor local de Laravel:
php artisan serve --port=8000

# 4. URLs de Acceso:
# Panel de Control Administrativo: http://localhost:8000/login
# Verificador Público QR:          http://localhost:8000/verificar
# Carreras y Departamentos:       http://localhost:8000/carreras-departamentos
```

---

### Credenciales de Acceso Sembradas (Seeder Oficial)

| Rol Institucional | Correo Electrónico | Contraseña | Alcance / Permisos |
| :--- | :--- | :--- | :--- |
| **Super Administrador (Cómputo)** | `admin@universidad.edu` | `Admin*2026` | Acceso Global a todas las carreras, gestión de cuentas y unidades organizacionales. |
| **Jefe de Carrera (Ej. Química)** | `jefe.quimica@umsa.bo` | `password` | Gestión completa de su carrera (incluye Reportes Estadísticos y Diseñador). |
| **Secretaria (Ej. Química)** | `secretaria.quimica@umsa.bo` | `password` | Gestión operativa de su carrera (excluida del módulo de Reportes). |
| **Operador General** | `operador@universidad.edu` | `Admin*2026` | Operaciones centrales del Vicedecanato. |
