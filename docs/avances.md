# Informe Completo de Avances, Correcciones y Evolución del Sistema

**Sistema de Gestión, Generación, Emisión y Verificación de Certificados Institucionales**  
**Vicedecanato — Facultad de Ciencias Puras y Naturales (FCPN)**  
**Universidad Mayor de San Andrés (UMSA)**  
*Fecha de Registro: Octubre de 2026 — Versión 2.0.0*  
*Documento de Referencia para Continuación Operativa y de Desarrollo*  

---

## 📌 Índice de Contenidos

1. [Resumen Ejecutivo de la Jornada de Trabajo](#1-resumen-ejecutivo-de-la-jornada-de-trabajo)
2. [Matriz de Problemas Diagnosticados y Soluciones Aplicadas](#2-matriz-de-problemas-diagnosticados-y-soluciones-aplicadas)
3. [Módulos Implementados, Refactorizados y Mejorados](#3-módulos-implementados-refactorizados-y-mejorados)
   - [3.1 Diseñador Visual de Certificados (WYSIWYG A4 Studio)](#31-diseñador-visual-de-certificados-wysiwyg-a4-studio)
   - [3.2 Asistente de Creación de Plantillas en 3 Modos](#32-asistente-de-creación-de-plantillas-en-3-modos)
   - [3.3 Motor de Normalización de Esquemas (TemplateEngineService)](#33-motor-de-normalización-de-esquemas-templateengineservice)
   - [3.4 Motor de Renderizado y Previsualización PDF (DomPDF + Base64)](#34-motor-de-renderizado-y-previsualización-pdf-dompdf--base64)
   - [3.5 Soporte Universal Multiformato de Imágenes (.png, .jpg, .webp, .svg, etc.)](#35-soporte-universal-multiformato-de-imágenes-png-jpg-webp-svg-etc)
   - [3.6 Formulario de Emisión de Certificados y Responsive Layout](#36-formulario-de-emisión-de-certificados-y-responsive-layout)
4. [Documentación y Exportación de la Base de Datos](#4-documentación-y-exportación-de-la-base-de-datos)
5. [Validación y Suite de Pruebas Automatizadas (PHPUnit)](#5-validación-y-suite-de-pruebas-automatizadas-phpunit)
6. [Credenciales y Cuentas de Acceso para Pruebas](#6-credenciales-y-cuentas-de-acceso-para-pruebas)
7. [Mapeo de Archivos Modificados, Nuevos y Recursos](#7-mapeo-de-archivos-modificados-nuevos-y-recursos)
8. [Guía Rápida para Continuar Mañana](#8-guía-rápida-para-continuar-mañana)
9. [Tareas y Mejoras Recomendadas para las Siguientes Fases](#9-tareas-y-mejoras-recomendadas-para-las-siguientes-fases)

---

## 1. Resumen Ejecutivo de la Jornada de Trabajo

Durante la jornada se completó la estabilización técnica, la corrección de errores de base de datos y vistas, la ampliación del soporte multiformato de imágenes institucionales y el rediseño integral del **Diseñador Visual de Certificados**.

El desarrollo se realizó manteniendo intactos los cuatro pilares fundamentales del sistema:
1. **Evolución continua sin reconstruir desde cero**: Se respetó al 100% el stack tecnológico predefinido (Laravel 13, Blade, Tailwind CSS v4, Alpine.js, DomPDF y MySQL en Laragon).
2. **Inmutabilidad Documental (Regla 40)**: Todos los certificados emitidos conservan su snapshot documental congelado en base de datos (`certificates.snapshot_data`), blindándolos ante cambios futuros en plantillas o autoridades.
3. **Fidelidad Visual Absoluta (WYSIWYG ≈ PDF)**: Lo configurado en el lienzo interactivo del navegador coincide con exactitud milimétrica en el documento PDF descargable o impreso.
4. **Soporte Institucional Amplio**: Subida y gestión de logotipos, marcas de agua, sellos secos y firmas digitalizadas en formatos `.png` (con transparencia alfa), `.jpg`, `.jpeg`, `.webp`, `.svg`, `.gif` y `.bmp`.

---

## 2. Matriz de Problemas Diagnosticados y Soluciones Aplicadas

A continuación se resume cada incidencia encontrada durante las pruebas de usuario y la solución técnica implementada en el código fuente:

| # | Problema Reportado | Causa Raíz Identificada | Solución Técnica Aplicada | Archivos Afectados |
|---|---|---|---|---|
| **1** | Error `SQLSTATE[HY000] [2002] Connection refused` | El servicio MySQL en Laragon no estaba iniciado en el puerto estándar `3306`. | Se verificó el servicio MySQL local, se configuró la variable de entorno en `.env` y se ejecutó la base de datos `sistema_certificados`. | `.env`, Laragon MySQL |
| **2** | Error `Unknown column 'is_default'` en catálogo de plantillas | La migración `2026_09_04_000001_create_institutional_designer_tables.php` no había corrido en su totalidad en la base de datos local. | Se ejecutaron las migraciones pendientes y se crearon las tablas `institutional_assets`, `certificate_template_versions` y los campos de `is_default`. | `database/migrations/` |
| **3** | Error `Table 'institutional_assets' doesn't exist` | Falta de la tabla de catálogo de recursos institucionales. | Se creó la tabla con campos de metadatos JSON, tipos de recurso, banderas de obligatoriedad y orden de visualización. | `InstitutionalAsset.php`, migración 000001 |
| **4** | **Logos rotos** mostrando texto alternativo en el navegador | En `InstitutionalAsset::getUrlAttribute()`, la condición `str_starts_with($path, 'assets/')` invocaba `asset()`, pero las imágenes subidas residían en el disco público `storage/app/public/assets/...`, provocando error 404 HTTP. | Se ajustó el accesor para comprobar primero si el archivo existe físicamente en `public/` y, de lo contrario, resolver a través de `Storage::disk('public')->url($path)`. | `app/Models/InstitutionalAsset.php` |
| **5** | Restricción en formatos de imagen (.jpg, .png) | La validación del controlador solo admitía SVG y PNG pequeños, sin calcular dimensiones ni aspect ratio. | Se amplió la validación a `svg,png,jpg,jpeg,webp,gif,bmp` (hasta 5MB) y se implementó en `AssetManagerService` la extracción de resolución (`getimagesize`) y generación de Data URIs Base64. | `InstitutionalAssetController.php`, `AssetManagerService.php` |
| **6** | Error `BadMethodCallException: Call to undefined method App\Models\CertificateTemplate::active()` | Al crear una plantilla o cargar el formulario de emisión, se invocaba `CertificateTemplate::active()` sin que dicho scope estuviera definido en el modelo. | Se implementaron los métodos `scopeActive($query)` y `scopeDefault($query)` en el modelo Eloquent. | `app/Models/CertificateTemplate.php` |
| **7** | Error `Undefined array key "description"` al guardar plantilla | En el método `store()` de `CertificateTemplateController`, al no enviarse el campo opcional `description`, se producía una excepción PHP por acceso a índice indefinido. | Se aplicó el operador de coalescencia nula: `$validated['description'] ?? null`. | `CertificateTemplateController.php` |
| **8** | **Lienzo vacío y bloqueo total de Alpine.js** en el Diseñador | En `designer.blade.php`, la directiva `x-data="certificateStudio({ initialSchema: {!! $json !!} })"` inyectaba JSON con comillas dobles directamente dentro de las comillas dobles del atributo HTML. Esto rompía el árbol sintáctico del navegador, que truncaba el atributo e impedía que Alpine.js se inicializara. | Se desacopló la configuración hacia un tag estructurado `<script>window.__CERTIFICATE_STUDIO_CONFIG = ...</script>`, inicializando Alpine de forma limpia con `x-data="certificateStudio(window.__CERTIFICATE_STUDIO_CONFIG)"`. | `resources/views/templates/designer.blade.php` |
| **9** | **Propiedades y elementos no editables** en el Diseñador | El normalizador generaba tipos canónicos (`text`, `qr`, `signature`, `divider`), mientras que la vista esperaba nombres alternativos (`qr_code`, `signature_block`). Además, los inputs del inspector no tenían enlace bidireccional reactivo con el elemento seleccionado. | Se homologaron los identificadores de tipo (`isTextType`, `isQrType`, `isSignatureType`, `isLineType`, `isImageType`), se sincronizaron las propiedades `styles` con el lienzo y se habilitó la actualización inmediata al modificar coordenadas o fuentes. | `designer.blade.php`, `TemplateEngineService.php` |
| **10** | Error `Route [templates.preview] not defined` | El botón de previsualización en el diseñador llamaba a una ruta que no estaba registrada en `routes/web.php`. | Se registró la ruta GET `/plantillas/{template}/previsualizar-pdf` y se implementó el método `previewPdf()` que genera y transmite el PDF de prueba en tiempo real. | `routes/web.php`, `CertificateTemplateController.php` |
| **11** | **Colapso visual de columnas** en Diseñador y Emisión | Las vistas dependían del breakpoint `xl:` (1280px) de Tailwind, causando que en pantallas medianas o portátiles las columnas se encimaran o cayeran una debajo de otra. Además, se añadieron clases de utilidad nuevas que no estaban en el archivo CSS compilado. | Se adaptó la distribución de cuadrículas a `lg:grid-cols-12` (7 columnas de formulario + 5 columnas de resumen lateral) y se ejecutó `cmd /c "npm run build"` con Tailwind CSS v4. | `certificates/create.blade.php`, `designer.blade.php`, `public/build/` |

---

## 3. Módulos Implementados, Refactorizados y Mejorados

### 3.1 Diseñador Visual de Certificados (WYSIWYG A4 Studio)
**Ubicación:** [`resources/views/templates/designer.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/templates/designer.blade.php)

Se implementó un estudio de diseño interactivo de 3 columnas de estándar profesional:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ BARRA SUPERIOR: [← Volver] [Nombre Plantilla] [v1] [A4 Apaisado] | [Deshacer] [Rehacer] [Zoom] [PDF] | [Guardar] │
├────────────────────────────────┬───────────────────────────────────────┬───────────────────────────────┤
│ PANEL IZQUIERDO (3 cols)       │ LIENZO WYSIWYG A4 INTERACTIVO (6 cols) │ PANEL DERECHO (3 cols)        │
│                                │                                       │                               │
│ 🛠️ HERRAMIENTAS Y ELEMENTOS    │ [ Lienzo Proporcional A4 1:1.414 ]    │ 🎛️ INSPECTOR DE PROPIEDADES   │
│ - Textos institucionales       │ - Doble marco ornamental facultativo  │ - Modo contextual:            │
│ - Variables dinámicas          │ - Guías magnéticas de centrado        │   * Si hay elemento activo:   │
│   {{beneficiario.nombre}}      │ - Arrastre fluido de elementos (Drag) │     Coordenadas X, Y, W, H    │
│   {{actividad.nombre}}, etc.   │ - Tirador de redimensionamiento (SE)  │     Tipografía, tamaño px,    │
│ - Bloques de Firmas Oficiales  │ - Micro-movimiento con flechas teclado│     Color, alineación, z-index│
│ - Código QR de validación      │ - Interpolación con datos en vivo     │   * Si no hay elemento activo:│
│ - Líneas y filetes dorados     │ - Selección con borde azul punteado   │     Configuración de página,  │
│                                │                                       │     Marco, color de fondo,    │
│ 🖼️ BIBLIOTECA INSTITUCIONAL     │                                       │     Marca de agua institucional│
│ - Logos UMSA, FCPN (PNG, JPG)  │                                       │                               │
│ - Firmas y sellos secos        │                                       │                               │
│ - Inserción con un solo clic   │                                       │                               │
│                                │                                       │                               │
│ 📚 GESTOR DE CAPAS (LAYERS)    │                                       │                               │
│ - 👁️ Ocultar / 🔒 Bloquear      │                                       │                               │
└────────────────────────────────┴───────────────────────────────────────┴───────────────────────────────┘
```

#### Características Clave del Diseñador:
1. **Manipulación Fluida en Pantalla**:
   - Arrastre con el mouse mediante coordenadas porcentuales relativas al tamaño del lienzo.
   - Tirador de redimensionamiento en la esquina inferior derecha del elemento seleccionado.
   - Ajuste fino con el teclado: presionar `ArrowUp`, `ArrowDown`, `ArrowLeft` o `ArrowRight` mueve el elemento 0.5% (o 2.0% manteniendo presionado `Shift`).
   - Tecla `Supr` o `Backspace` elimina el elemento seleccionado.
2. **Historial Completo de Deshacer / Rehacer**:
   - Pila de estados con soporte para los atajos universales `Ctrl+Z` y `Ctrl+Y`.
3. **Inspector de Propiedades Bidireccional**:
   - Al seleccionar cualquier elemento, el panel derecho muestra al instante sus controles: contenido de texto, selector de color nativo, tamaño de fuente, alineación (izquierda, centro, derecha), posición $X/Y$, ancho, alto y nivel de capa ($z$-index).
   - Cualquier cambio en el inspector se refleja de inmediato en el lienzo sin necesidad de guardar o recargar.
4. **Previsualización Real en DomPDF**:
   - Botón directo que abre una nueva pestaña con el PDF compilado por el servidor con los mismos datos y posiciones.

---

### 3.2 Asistente de Creación de Plantillas en 3 Modos
**Ubicación:** [`resources/views/templates/create.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/templates/create.blade.php)

El asistente ofrece 3 caminos según las necesidades del usuario:
1. **Plantilla Base Institucional**: Preconfigura los escudos oficiales de la UMSA y FCPN, doble marco perimetral azul y oro, jerarquía de títulos y firmas oficiales de autoridades.
2. **Lienzo en Blanco**: Crea una plantilla vacía para que el usuario construya su composición personalizada desde cero sin elementos predefinidos.
3. **Duplicar Plantilla Existente**: Permite seleccionar una plantilla previa para clonar su diseño íntegro e incrementar su versión de trabajo.

---

### 3.3 Motor de Normalización de Esquemas (TemplateEngineService)
**Ubicación:** [`app/Services/TemplateEngineService.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Services/TemplateEngineService.php)

- **Estructura Jerárquica de Elementos**: Convierte cualquier esquema plano previo en una colección de objetos elementales bien estructurados:
  - `id`: Identificador único (ej. `el_title`, `el_qr`, `el_logo_1`).
  - `type`: `text`, `logo`, `qr`, `signature`, `divider`, `stamp`, `watermark`.
  - `content`: Texto estático o token dinámico (`{{beneficiario.nombre}}`, etc.).
  - `x`, `y`, `width`, `height`: Porcentajes respecto al lienzo A4.
  - `z_index`, `opacity`, `is_visible`, `is_locked`.
  - `styles`: Objeto con `font_size`, `font_weight`, `text_color`, `text_align`, `font_family`.
- **Compatibilidad Retrospectiva**: Si se carga una plantilla creada con versiones antiguas del software, el motor la transforma automáticamente en memoria sin provocar errores en la base de datos.
- **Lienzo Limpio**: El método `createBlankSchema()` garantiza una lista vacía de elementos `elements: []`.

---

### 3.4 Motor de Renderizado y Previsualización PDF (DomPDF + Base64)
**Ubicaciones:**
- [`app/Services/CertificatePdfService.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Services/CertificatePdfService.php)
- [`resources/views/certificates/templates/engine.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/certificates/templates/engine.blade.php)

- **Conversión de Recursos a Base64**: Para garantizar que DomPDF no falle por problemas de rutas locales, políticas de red o directivas CORS, todos los recursos (marcas de agua, logos, firmas y sellos) son convertidos automáticamente a cadenas Data URI en Base64 (`data:image/png;base64,...`).
- **Posicionamiento Absoluto Milimétrico**: La plantilla Blade genera contenedores CSS con posición absoluta utilizando porcentajes y milímetros calculados con precisión para hoja A4 (297×210 mm en horizontal o 210×297 mm en vertical).
- **Interpolación Dinámica de Datos**: Sustituye las variables en tiempo de ejecución:
  - `{{beneficiario.nombre}}`: Nombre completo del participante.
  - `{{beneficiario.documento}}`: Tipo y número de documento (CI/Pasaporte).
  - `{{actividad.nombre}}`: Título de la actividad académica.
  - `{{actividad.horas}}`: Carga horaria certificada.
  - `{{actividad.fechas}}`: Rango de fechas de realización.
  - `{{certificado.tipo}}`: Modalidad de certificación (Aprobación, Disertante, etc.).
  - `{{certificado.codigo}}`: Código oficial de registro (`CERT-2026-XXXXXX`).

---

### 3.5 Soporte Universal Multiformato de Imágenes (.png, .jpg, .webp, .svg, etc.)
**Ubicaciones:**
- [`app/Http/Controllers/InstitutionalAssetController.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Http/Controllers/InstitutionalAssetController.php)
- [`app/Services/AssetManagerService.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Services/AssetManagerService.php)

- **Formatos Permitidos**: Se ampliaron las reglas de validación en el backend para admitir:
  - `.png` (con soporte total de transparencia alfa para sellos y firmas).
  - `.jpg` / `.jpeg` (para fotografías y escudos con fondos planos).
  - `.webp` (formato moderno de alta compresión).
  - `.svg` (gráficos vectoriales de resolución infinita).
  - `.gif` y `.bmp` (para recursos gráficos históricos de la universidad).
- **Extracción Automática de Dimensiones**: Al subir una imagen, `AssetManagerService` detecta mediante `getimagesize()` el ancho, alto y relación de aspecto, almacenándolos en la columna `metadata` (JSON) para que el diseñador preserve automáticamente las proporciones originales.

---

### 3.6 Formulario de Emisión de Certificados y Responsive Layout
**Ubicación:** [`resources/views/certificates/create.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/certificates/create.blade.php)

- **Distribución de Columnas**: Se ajustó la cuadrícula a `lg:grid-cols-12` (7 columnas para los selectores de beneficiario, evento y plantilla, y 5 columnas para la tarjeta de previsualización ejecutiva lateral).
- **Resumen en Tiempo Real**: Al seleccionar participantes o actividades, la tarjeta lateral actualiza al instante la vigencia (+3 años reglamentarios) y los datos que conformarán el documento oficial.

---

## 4. Documentación y Exportación de la Base de Datos

Se generaron los siguientes documentos y archivos de respaldo técnico de la base de datos:

1. **Documento Técnico en Línea con Diagrama ERD:**
   - Archivo: [`docs/BASE_DE_DATOS.md`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/docs/BASE_DE_DATOS.md)
   - Contenido:
     - Diagrama Entidad-Relación interactivo en formato **Mermaid**.
     - Diccionario de datos exhaustivo para las 15 tablas del sistema (`roles`, `users`, `password_reset_tokens`, `sessions`, `beneficiaries`, `activities`, `activity_certificate_configs`, `certificate_types`, `certificate_templates`, `certificate_template_versions`, `institutional_assets`, `certificates`, `certificate_verifications`, `audit_logs`, `settings`).
     - Matriz de claves foráneas con reglas de borrado (`CASCADE`, `RESTRICT`, `SET NULL`).
     - Especificación de los esquemas JSON (`design_schema`, `snapshot_data`, `metadata`).
2. **Exportación Física DDL (Script SQL):**
   - Archivo: [`database/schema_dump.sql`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/database/schema_dump.sql)
   - Contenido: Estructura DDL completa exportada desde MySQL 8.4 con todas las tablas, índices, claves foráneas y codificación UTF-8 MB4.

---

## 5. Validación y Suite de Pruebas Automatizadas (PHPUnit)

Se ejecutó la totalidad de las pruebas unitarias y de integración del proyecto mediante el ejecutable PHP de Laragon:

```
Runtime:       PHP 8.3.28-Win32-vs16-x64
Configuration: phpunit.xml

Tests ejecutados:
- Tests\Feature\AuthenticationTest                     [PASSED]
- Tests\Feature\CertificateGenerationTest              [PASSED]
- Tests\Feature\CertificateTemplateDesignerTest        [PASSED]
  ├── test_admin_can_view_templates_catalog            [PASSED]
  ├── test_admin_can_create_new_template_with_initial  [PASSED]
  ├── test_admin_can_create_blank_canvas_template      [PASSED]
  ├── test_admin_can_create_template_by_duplicating    [PASSED]
  ├── test_admin_can_update_designer_and_increment     [PASSED]
  ├── test_admin_can_save_designer_using_schema_json   [PASSED]
  ├── test_admin_can_duplicate_template                [PASSED]
  ├── test_cannot_delete_template_with_issued_cert     [PASSED]
  ├── test_activity_certificate_config_can_be_saved    [PASSED]
  ├── test_draft_preview_renders_pdf_stream            [PASSED]
  ├── test_draft_preview_with_custom_logos_watermark   [PASSED]
  ├── test_designer_loads_with_element_studio_assets   [PASSED]
  ├── test_institutional_assets_catalog_can_be_viewed  [PASSED]
  └── test_asset_manager_resolves_base64_data_uris     [PASSED]
- Tests\Feature\PublicVerificationTest                 [PASSED]
- Tests\Feature\ExampleTest                            [PASSED]

============================================================
RESULTADO GLOBAL:
Total Tests:       30 pasados (100%)
Total Assertions:  107 comprobaciones
Errores:           0
Fallos:            0
============================================================
```

---

## 6. Credenciales y Cuentas de Acceso para Pruebas

Para acceder al sistema en el entorno local (`http://127.0.0.1:8000`):

| Rol | Correo Electrónico | Contraseña | Capacidades en el Sistema |
|---|---|---|---|
| **Administrador** | `admin@universidad.edu` | `Admin*2026` | Acceso completo: Diseñador visual de plantillas, gestión de recursos institucionales, configuración global, revocación y auditoría. |
| **Operador** | `operador@universidad.edu` | `Operador*2026` | Emisión individual y masiva de certificados, registro de beneficiarios y consulta de actividades académicas. |

---

## 7. Mapeo de Archivos Modificados, Nuevos y Recursos

### Backend (Modelos, Servicios y Controladores)
- [`app/Models/InstitutionalAsset.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Models/InstitutionalAsset.php): Corrección del accesor `getUrlAttribute()` para resolver rutas de almacenamiento público sin error 404.
- [`app/Models/CertificateTemplate.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Models/CertificateTemplate.php): Implementación de scopes `scopeActive()` y `scopeDefault()`.
- [`app/Services/TemplateEngineService.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Services/TemplateEngineService.php): Normalizador de esquemas basados en elementos, preservación de lienzos en blanco y retrocompatibilidad.
- [`app/Services/CertificatePdfService.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Services/CertificatePdfService.php): Renderizado DomPDF con resolución Base64 de marcas de agua, sellos y logos.
- [`app/Services/AssetManagerService.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Services/AssetManagerService.php): Detección de dimensiones y conversión de tipos MIME a Base64.
- [`app/Http/Controllers/CertificateTemplateController.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Http/Controllers/CertificateTemplateController.php): Manejo de campos nulos, soporte de lienzo en blanco y nuevo método `previewPdf()`.
- [`app/Http/Controllers/InstitutionalAssetController.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Http/Controllers/InstitutionalAssetController.php): Validación ampliada para JPG, PNG, WEBP, SVG, GIF, BMP hasta 5MB.
- [`routes/web.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/routes/web.php): Registro de la ruta oficial `templates.preview`.

### Frontend (Vistas Blade y Compilación Vite)
- [`resources/views/templates/designer.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/templates/designer.blade.php): Diseñador interactivo A4 de 3 columnas con Alpine.js aislado y controles bidireccionales.
- [`resources/views/templates/create.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/templates/create.blade.php): Asistente de creación de plantillas con 3 alternativas.
- [`resources/views/certificates/create.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/certificates/create.blade.php): Formulario de emisión adaptado a pantalla responsive (`lg:grid-cols-12`).
- [`resources/views/certificates/templates/engine.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/certificates/templates/engine.blade.php): Motor HTML/CSS con posicionamiento absoluto por elemento para DomPDF.
- `public/build/assets/app-DmWcBAGR.css`: Bundle compilado de Tailwind CSS v4 para producción.

### Documentación y Base de Datos
- [`docs/avances.md`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/docs/avances.md): Este informe integral de progreso y estado del proyecto.
- [`docs/BASE_DE_DATOS.md`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/docs/BASE_DE_DATOS.md): Documento técnico en línea con diagrama Mermaid y diccionario de datos.
- [`database/schema_dump.sql`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/database/schema_dump.sql): Exportación física de la estructura SQL de la base de datos.
- [`tests/Feature/CertificateTemplateDesignerTest.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/tests/Feature/CertificateTemplateDesignerTest.php): Suite de 14 pruebas de integración del diseñador y plantillas.

---

## 8. Guía Rápida para Continuar Mañana

Para reanudar el trabajo al día siguiente sin complicaciones, siga estos pasos directos:

### Paso 1: Iniciar los Servicios en Laragon
Abra la aplicación **Laragon** en Windows y presione **Start All** (Iniciar todo) para levantar los servicios de **MySQL** (puerto 3306) y **Apache / Nginx**.

### Paso 2: Iniciar el Servidor de Desarrollo Laravel
Abra una terminal de PowerShell en la raíz del proyecto (`c:\Users\WINDOWS\OneDrive\Escritorio\inf-2026\sistema-certificado`) y ejecute:

```powershell
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe artisan serve
```

El sistema estará inmediatamente disponible en: **`http://127.0.0.1:8000`**

### Paso 3: Compilar Nuevas Clases si se Modifican Vistas Blade (Opcional)
Si durante la siguiente jornada se agregan nuevas clases CSS de Tailwind en archivos `.blade.php`, ejecute:

```cmd
cmd /c "npm run build"
```

### Paso 4: Comprobación Rápida de Salud del Sistema
Para validar que todo el código y la base de datos continúan al 100% en verde:

```powershell
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe artisan test
```

---

## 9. Tareas y Mejoras Recomendadas para las Siguientes Fases

Con el diseñador visual, el motor de plantillas y el catálogo de recursos estabilizados, las siguientes mejoras recomendadas para continuar son:

1. **Emisión Masiva de Certificados (Importación Excel / CSV)**:
   - Integrar un asistente para cargar nóminas de participantes desde archivos `.xlsx` o `.csv`, mapeando automáticamente columnas a beneficiarios y emitiendo los certificados en lote con barra de progreso.
2. **Envío Automático de Certificados por Correo Electrónico**:
   - Configurar notificaciones por correo (vía SMTP institucional o Mailpit) para enviar el archivo PDF adjunto al correo del participante en el momento de la emisión.
3. **Firmas Digitales Co-organizadoras en Actividades**:
   - Permitir asignar firmas secundarias o adicionales directamente desde la vista de edición de una actividad académica sin necesidad de clonar la plantilla base.
4. **Exportación Masiva en Archivo ZIP**:
   - Añadir una acción en el catálogo de actividades para descargar un archivo comprimido `.zip` con todos los PDFs generados de un evento académico.
5. **Generador de Certificados de Muestra / Pruebas de Carga**:
   - Crear un comando artisan para emitir 500 certificados simulados y validar los tiempos de respuesta del portal de verificación QR público.

---

## 10. Auditoría Integral del Sistema y Corrección de Inconsistencias (Octubre 2026)

Se realizó una auditoría completa de todos los módulos del sistema (servicios, controladores, modelos, vistas Blade, migraciones, volcado SQL y rutas RESTful), identificando y solucionando 7 inconsistencias críticas para asegurar la estabilidad, inmutabilidad documental y calidad del código:

### 10.1 Resumen de Correcciones Aplicadas

1. **Creación de Vista Faltante para Previsualización en Vivo (`livePreview`)**:
   - **Archivo:** [`resources/views/certificates/templates/preview_canvas.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/certificates/templates/preview_canvas.blade.php)
   - **Solución:** Se implementó el lienzo HTML/CSS de previsualización con soporte tanto para el motor dinámico de elementos como para el motor tradicional estructurado, eliminando el error 500 que ocurría al invocar la ruta `templates.live-preview`.
   - **Controlador:** En [`CertificateTemplateController::livePreview`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Http/Controllers/CertificateTemplateController.php) se añadieron interpolación dinámica de tokens con `TemplateEngineService` y generación de código QR simulado.

2. **Inmutabilidad Documental y Congelación de Elementos en Snapshots**:
   - **Archivos:** [`app/Services/CertificateSnapshotService.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Services/CertificateSnapshotService.php) y [`app/Services/CertificatePdfService.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Services/CertificatePdfService.php)
   - **Problema previo:** Los certificados emitidos con plantillas de elementos dinámicos almacenaban los elementos sin resolver el texto ni los recursos Base64, y el renderizador PDF omitía la resolución si el snapshot estaba congelado.
   - **Solución:** Ahora `CertificateSnapshotService::buildImmutableSnapshot()` empaqueta de forma autónoma los activos en Base64 (`logo`, `signature`, `stamp`, `watermark`) e interpola los textos dinámicos (`resolved_content`) antes de guardar el snapshot en base de datos. Asimismo, `CertificatePdfService::render()` procesa los elementos universalmente para certificados en vivo y congelados.

3. **Corrección de Mapeo de Categoría de Activos en Diseñador Visual**:
   - **Archivo:** [`resources/views/templates/designer.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/templates/designer.blade.php)
   - **Problema previo:** El array Blade inyectaba `'category' => $a->category` (inexistente en `InstitutionalAsset`), provocando que JavaScript tratara todos los recursos insertados como `'image'` genérica.
   - **Solución:** Se mapeó `'category' => $a->type`, `'type' => $a->type` y `'format' => extensión`, permitiendo que el lienzo Alpine.js reconozca inmediatamente sellos, logos, firmas y marcas de agua.

4. **Modelos de Reserva en Previsualización PDF (`previewPdf`)**:
   - **Archivo:** [`app/Http/Controllers/CertificateTemplateController.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Http/Controllers/CertificateTemplateController.php)
   - **Solución:** Se corrigieron los atributos de los modelos dummy (`document_number`, `document_type`, `academic_hours`, `status`), garantizando que la previsualización PDF oficial no falle cuando la base de datos esté vacía o en entornos de prueba.

5. **Resolución de URL de Archivos Subidos en el Catálogo de Recursos**:
   - **Archivo:** [`resources/views/assets/index.blade.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/resources/views/assets/index.blade.php)
   - **Solución:** Se actualizó la etiqueta de imagen para usar `$asset->url` en lugar de `asset($asset->file_path)`, resolviendo enlaces rotos (404) para archivos alojados en `storage/app/public/`.

6. **Implementación de Métodos REST Faltantes y Navegación Segura**:
   - **Archivos:** [`app/Http/Controllers/UserController.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Http/Controllers/UserController.php), [`app/Http/Controllers/CertificateTemplateController.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Http/Controllers/CertificateTemplateController.php), [`app/Http/Controllers/CertificateController.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/app/Http/Controllers/CertificateController.php)
   - **Solución:**
     - `UserController::show`: Redirige a edición del usuario.
     - `UserController::destroy`: Protege contra auto-eliminación, protege al último Super Admin, y si el usuario tiene certificados o auditoría histórica vinculada, desactiva la cuenta de manera segura (`is_active = false`) con registro en `AuditLog` en lugar de borrado físico.
     - `CertificateTemplateController::show` y `edit`: Redirigen limpiamente al diseñador visual.
     - Navegación nula segura `?->id` en resolución de plantillas predeterminadas.

7. **Normalización de Codificación de `schema_dump.sql`**:
   - **Archivo:** [`database/schema_dump.sql`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/database/schema_dump.sql)
   - **Solución:** Se convirtió el volcado SQL de UTF-16LE a UTF-8 estándar sin BOM, permitiendo su correcta lectura e inspección en cualquier editor o herramienta.

8. **Batería de Pruebas Automatizadas de Auditoría**:
   - **Archivo:** [`tests/Feature/GeneralSystemAuditFixesTest.php`](file:///c:/Users/WINDOWS/OneDrive/Escritorio/inf-2026/sistema-certificado/tests/Feature/GeneralSystemAuditFixesTest.php)
   - **Resultado:** 9 pruebas adicionales creadas y validadas. La suite completa del sistema cuenta ahora con **39 pruebas unitarias y de integración**, pasando al **100% (39 tests, 145 assertions, 0 failures)**.

