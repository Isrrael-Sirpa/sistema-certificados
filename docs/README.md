# Índice de Documentación del Sistema

Bienvenido a la documentación oficial del **Sistema Web de Gestión, Generación, Diseño y Verificación de Certificados Académicos con Código QR** (Versión 2.0.0) desarrollado para el Vicedecanato de Facultad.

---

## 📚 Documentos Disponibles

### 1. 👥 [Manual de Usuario y Guía para el Cliente](MANUAL_USUARIO.md)
Documento dirigido al **Vicedecano, autoridades universitarias, secretaría académica y personal administrativo**:
- Introducción al sistema y matriz de roles/permisos (Administrador vs Operador).
- Guía del **Gestor y Diseñador Visual de Plantillas**: creación, personalización de colores/márgenes, duplicación y versionado (v1, v2).
- Uso de la **Biblioteca de Recursos Institucionales**: subida de escudos, logos, firmas de autoridades con sus cargos y sellos oficiales.
- **Personalización por Actividad Académica**: redacción con variables dinámicas (`{{beneficiario.nombre}}`, `{{actividad.horas}}`, etc.), co-firmantes y campos como N° de Resolución Facultativa.
- Flujo de **Emisión Individual con Previsualización de Borrador PDF** y **Emisión Masiva en Lote**.
- Principio de **Garantía de Inmutabilidad y Congelación Documental**.
- Portal público de verificación mediante escaneo QR o búsqueda manual de código correlativo.
- Regla legal de 3 años de vigencia, revocación formal y reportes imprimibles.

---

### 2. 🏛️ [Documentación Técnica, Arquitectura y Guía de Soporte](ARQUITECTURA_Y_SISTEMA.md)
Documento dirigido a **desarrolladores, arquitectos de software, administradores de TI y soporte técnico**:
- Visión general de la arquitectura desacoplada en capas (MVC + Capa de Servicios).
- Stack tecnológico y compatibilidad con PHP 8.3+ / 8.5 en **Laragon** sobre Windows.
- Diagrama Entidad-Relación (ERD) completo y detalle de las 12 tablas de la base de datos MySQL.
- Capa de Servicios: `CertificateService`, `TemplateEngineService`, `CertificateSnapshotService`, `AssetManagerService`, `CertificatePdfService` y `QRCodeService`.
- Diccionario de variables dinámicas e interpolación en tiempo real.
- Seguridad criptográfica (Hash SHA-256 de integridad, UUID v4, protección CSRF y control RBAC).
- Estructura completa de archivos del proyecto.
- Suite de pruebas automatizadas (24 tests, 77 aserciones validadas al 100%).
- Guía paso a paso para puesta en marcha, migraciones, seeders y comandos en Laragon.
- Mantenimiento, respaldos periódicos y hoja de ruta futura.

---

## 🚀 Inicio Rápido para Pruebas Locales (Laragon)

### Opción A: Lanzador Automático de 1 Clic (Recomendado)
Haz doble clic en el archivo `iniciar_proyecto.bat` ubicado en la raíz del proyecto. Podrás elegir entre:
- **Modo Autónomo:** Limpia temporales y levanta `php artisan serve` con estilos compilados fijos.
- **Modo Desarrollo:** Levanta `php artisan serve` y `npm run dev` en paralelo para recarga en vivo.

---

### Opción B: Ejecución Manual en Terminal de Laragon

```powershell
# 1. Asegurar que Apache y MySQL estén iniciados en el panel de Laragon

# 2. Compilar estilos para modo autónomo (solo una vez o al modificar CSS):
npm run build

# 3. Iniciar el servidor Laravel:
php artisan serve --port=8000

# 4. Acceso en el navegador:
# Panel de Control:       http://localhost:8000/login
# Verificador Público QR: http://localhost:8000/verificar
```

> [!IMPORTANT]
> **¿Por qué la página se ve sin estilos (en blanco y texto plano)?**
> Si ejecutaste previamente `npm run dev` y lo cerraste, se genera un archivo temporal en `public/hot`. Al iniciar únicamente `php artisan serve`, Laravel intentará buscar los estilos en el puerto 5173. Si dicho puerto está cerrado, la web no cargará CSS.  
> **Solución inmediata:** Elimina el archivo `public/hot` ejecutando `del public\hot` o corre `npm run build`.

---

### Credenciales de Acceso

- **Administrador:** `admin@universidad.edu` | Contraseña: `Admin*2026`
- **Operador:** `operador@universidad.edu` | Contraseña: `Admin*2026`

