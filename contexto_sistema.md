Actúa como un arquitecto de software senior, analista de sistemas y desarrollador Full Stack especializado en sistemas administrativos universitarios.

Quiero desarrollar un SISTEMA WEB DE GESTIÓN, GENERACIÓN Y VERIFICACIÓN DE CERTIFICADOS para el Vicedecanato de una institución universitaria.

El sistema será utilizado principalmente por el Vicedecano y personal autorizado para registrar información de actividades, generar certificados automáticamente y permitir posteriormente la verificación de autenticidad de dichos certificados mediante un código QR.

==================================================
1. CONTEXTO DEL PROYECTO
==================================================

Actualmente la generación de certificados se realiza de forma manual mediante documentos de Word u otras herramientas, lo que implica repetir información, editar documentos individualmente y aumenta la posibilidad de errores.

El objetivo es reemplazar este proceso manual por un sistema web centralizado que permita:

- Registrar los datos necesarios para un certificado.
- Generar certificados automáticamente a partir de una plantilla.
- Evitar editar manualmente cada certificado.
- Generar un código QR único para cada certificado.
- Incorporar el código QR dentro del certificado.
- Permitir que el beneficiario o cualquier tercero escanee el QR.
- Mostrar una página pública de verificación.
- Indicar si el certificado está vigente o expirado.
- Considerar una vigencia estándar de 3 años desde la fecha de emisión.
- Permitir al personal autorizado consultar y administrar los certificados generados.

El sistema será utilizado principalmente por el Vicedecano y personal administrativo autorizado.

==================================================
2. OBJETIVO GENERAL
==================================================

Desarrollar una plataforma web que automatice el proceso de creación, gestión, almacenamiento y verificación de certificados emitidos por el Vicedecanato.

==================================================
3. OBJETIVOS ESPECÍFICOS
==================================================

El sistema debe permitir:

1. Registrar beneficiarios.
2. Registrar actividades, cursos, eventos, talleres u otras actividades que requieran certificados.
3. Crear y administrar certificados.
4. Generar automáticamente certificados a partir de una plantilla.
5. Generar automáticamente un identificador único para cada certificado.
6. Generar un código QR único para cada certificado.
7. Insertar el código QR dentro del certificado generado.
8. Descargar el certificado en formato PDF.
9. Consultar certificados emitidos.
10. Buscar certificados por diferentes criterios.
11. Verificar públicamente un certificado mediante QR.
12. Determinar automáticamente si el certificado está vigente o expirado.
13. Registrar la fecha de emisión y fecha de vencimiento.
14. Mantener un historial de certificados generados.
15. Permitir la anulación/revocación de certificados cuando corresponda.
16. Mantener un registro básico de las acciones administrativas importantes.
17. Proteger el acceso al sistema mediante autenticación y autorización.

==================================================
4. USUARIOS DEL SISTEMA
==================================================

El sistema debe contemplar inicialmente los siguientes roles:

A. ADMINISTRADOR
- Gestionar usuarios.
- Gestionar configuraciones.
- Gestionar plantillas.
- Gestionar certificados.
- Consultar reportes.
- Anular o revocar certificados.
- Consultar historial de acciones.

B. OPERADOR / PERSONAL AUTORIZADO
- Registrar beneficiarios.
- Registrar actividades.
- Generar certificados.
- Consultar certificados.
- Descargar certificados.
- Consultar el estado de los certificados.

C. VISITANTE / USUARIO PÚBLICO
No necesita iniciar sesión.
Debe poder:
- Escanear el código QR.
- Acceder a la página de verificación.
- Introducir manualmente un código de certificado.
- Consultar únicamente la información pública necesaria para comprobar la autenticidad.
- Ver si el certificado está vigente, expirado o revocado.

==================================================
5. FUNCIONALIDADES PRINCIPALES
==================================================

5.1 AUTENTICACIÓN

Implementar:

- Inicio de sesión.
- Cierre de sesión.
- Recuperación de contraseña.
- Control de acceso por roles.
- Protección de rutas.
- Gestión de sesiones.

5.2 DASHBOARD

El panel principal debe mostrar información resumida como:

- Total de certificados emitidos.
- Certificados vigentes.
- Certificados expirados.
- Certificados revocados.
- Certificados emitidos recientemente.
- Actividades registradas.
- Estadísticas básicas.

La interfaz debe ser limpia, profesional y adecuada para un entorno universitario.

5.3 GESTIÓN DE BENEFICIARIOS

Permitir registrar:

- Nombres.
- Apellidos.
- Tipo de documento.
- Número de documento.
- Correo electrónico.
- Teléfono opcional.
- Otros datos necesarios.

Debe existir:

- Crear.
- Editar.
- Ver.
- Buscar.
- Filtrar.
- Historial de certificados asociados.

5.4 GESTIÓN DE ACTIVIDADES

Permitir registrar actividades como:

- Cursos.
- Talleres.
- Seminarios.
- Congresos.
- Capacitaciones.
- Eventos académicos.
- Otras actividades institucionales.

Campos sugeridos:

- Nombre de la actividad.
- Descripción.
- Tipo de actividad.
- Fecha de inicio.
- Fecha de finalización.
- Duración/cantidad de horas.
- Lugar.
- Organizador.
- Responsable.
- Estado.

5.5 GESTIÓN DE CERTIFICADOS

Cada certificado debe almacenar como mínimo:

- ID interno.
- Código único del certificado.
- Beneficiario.
- Actividad.
- Tipo de certificado.
- Fecha de emisión.
- Fecha de vencimiento.
- Estado.
- Usuario que generó el certificado.
- Fecha de generación.
- Ruta o referencia al archivo PDF.
- Identificador único para la verificación.
- Datos necesarios para generar el QR.

Estados posibles:

- VIGENTE
- EXPIRADO
- REVOCADO

La aplicación debe determinar automáticamente el estado según la fecha actual y la fecha de vencimiento.

==================================================
6. REGLA DE VIGENCIA
==================================================

Cada certificado tendrá una vigencia de 3 años desde su fecha de emisión.

Ejemplo:

Fecha de emisión:
15/09/2026

Fecha de vencimiento:
15/09/2029

La fecha de vencimiento debe calcularse automáticamente.

No depender únicamente de un valor escrito manualmente.

El sistema debe comparar automáticamente:

FECHA_ACTUAL <= FECHA_VENCIMIENTO

Si se cumple:
Estado = VIGENTE

Si la fecha actual supera la fecha de vencimiento:
Estado = EXPIRADO

Si el administrador anuló el certificado:
Estado = REVOCADO

La lógica de estado debe centralizarse en el backend para evitar inconsistencias.

==================================================
7. GENERACIÓN AUTOMÁTICA DEL CERTIFICADO
==================================================

El sistema debe generar automáticamente un documento PDF utilizando una plantilla institucional.

La plantilla debe permitir incorporar dinámicamente información como:

- Logo institucional.
- Nombre de la institución.
- Facultad.
- Carrera o unidad.
- Nombre completo del beneficiario.
- Documento de identidad si corresponde.
- Nombre de la actividad.
- Descripción.
- Duración.
- Fecha.
- Lugar.
- Nombre y cargo de la autoridad que firma.
- Código del certificado.
- Código QR.

El usuario administrativo NO debe editar manualmente un archivo Word para cada certificado.

El sistema debe tomar la información registrada en la base de datos y colocarla automáticamente en la plantilla.

==================================================
8. CÓDIGO QR
==================================================

Cada certificado debe tener un código QR único.

El QR NO debe contener directamente toda la información sensible del certificado.

Preferentemente debe contener una URL de verificación segura, por ejemplo:

https://dominio-institucional.edu/verificar/{token}

Ejemplo conceptual:

https://certificados.institucion.edu/verificar/8f4a9c...

El token debe ser único y difícil de adivinar.

Al escanear el QR:

1. El usuario accede a la URL.
2. El sistema identifica el certificado.
3. Consulta su estado en la base de datos.
4. Muestra una página pública de verificación.

==================================================
9. PÁGINA PÚBLICA DE VERIFICACIÓN
==================================================

Crear una página pública accesible sin iniciar sesión.

Ejemplo:

/verificar/{token}

La página debe mostrar información suficiente para confirmar autenticidad, por ejemplo:

-----------------------------------
CERTIFICADO VERIFICADO

Estado:
VIGENTE

Código:
CERT-2026-000123

Beneficiario:
Nombre Apellido

Actividad:
Nombre de la actividad

Fecha de emisión:
15/09/2026

Fecha de vencimiento:
15/09/2029

Emitido por:
Vicedecanato

[ Certificado válido ]
-----------------------------------

Si está vencido:

CERTIFICADO EXPIRADO

Si fue revocado:

CERTIFICADO REVOCADO

No revelar información personal innecesaria.

==================================================
10. BÚSQUEDA MANUAL
==================================================

Además del QR, permitir introducir manualmente el código del certificado.

Ejemplo:

[ CERT-2026-000123 ]

[ VERIFICAR ]

Esto permitirá verificar el certificado incluso cuando el QR no pueda escanearse.

==================================================
11. PLANTILLAS DE CERTIFICADOS
==================================================

Crear un módulo para administrar plantillas.

Cada plantilla puede tener:

- Nombre.
- Descripción.
- Tipo.
- Diseño.
- Estado activo/inactivo.

Debe existir la posibilidad de seleccionar una plantilla al generar el certificado.

El diseño debe permitir reemplazar posteriormente la plantilla institucional sin modificar toda la lógica del sistema.

==================================================
12. PDF
==================================================

El sistema debe generar un PDF profesional y listo para impresión.

El PDF debe:

- Mantener el diseño institucional.
- Tener buena resolución.
- Incluir el QR.
- Incluir el código del certificado.
- Mantener una presentación formal.
- Poder descargarse desde el sistema.
- Poder visualizarse antes de descargarlo.

==================================================
13. BASE DE DATOS
==================================================

Utilizar una base de datos relacional.

Base de datos:

MySQL

Diseñar correctamente las relaciones.

Entidades iniciales sugeridas:

users
roles
beneficiaries
activities
certificate_types
certificate_templates
certificates
certificate_verifications
audit_logs
settings

No crear tablas innecesarias.

Las relaciones deben estar normalizadas.

Usar:

- Primary Keys.
- Foreign Keys.
- Unique Constraints.
- Indexes.
- Timestamps.
- Soft Deletes cuando tenga sentido.

==================================================
14. ARQUITECTURA
==================================================

Utilizar una arquitectura organizada y mantenible.

Propuesta:

Frontend:
- Blade
- Livewire
- Tailwind CSS
- Alpine.js

Backend:
- PHP
- Laravel

Base de datos:
- MySQL

Generación de PDF:
- Librería compatible con Laravel para generación de PDF.

Generación QR:
- Librería PHP/Laravel especializada en códigos QR.

Autenticación:
- Laravel Fortify/Breeze o solución oficial compatible.

El sistema debe seguir buenas prácticas de arquitectura MVC.

Separar correctamente:

- Models
- Controllers
- Services
- Requests
- Policies
- Jobs cuando corresponda
- Views
- Components
- Routes

La lógica compleja no debe colocarse directamente dentro de las vistas.

==================================================
15. TECNOLOGÍAS
==================================================

Usar preferentemente:

- PHP 8.5
- Laravel 13
- MySQL 8+
- Blade
- Livewire
- Tailwind CSS
- Alpine.js
- Composer
- Node.js
- NPM
- Git
- GitHub

La selección de versiones debe mantenerse en versiones estables y compatibles entre sí.

==================================================
16. SEGURIDAD
==================================================

Implementar como mínimo:

- Hash seguro de contraseñas.
- Protección CSRF.
- Validación de formularios.
- Protección contra SQL Injection mediante ORM/Query Builder.
- Control de autorización mediante roles y políticas.
- Rate limiting en la verificación pública cuando sea necesario.
- Protección de rutas administrativas.
- No exponer información privada innecesaria.
- Tokens de verificación impredecibles.
- Registro de acciones importantes.
- Protección de archivos generados.
- Separar claramente las rutas públicas de las administrativas.

==================================================
17. AUDITORÍA
==================================================

Registrar acciones administrativas importantes:

- Inicio de sesión.
- Creación de certificado.
- Modificación de certificado.
- Revocación.
- Descarga.
- Cambios de plantilla.
- Creación/modificación de usuarios.

Registrar:

- Usuario.
- Acción.
- Fecha y hora.
- IP cuando corresponda.
- Información relevante de la acción.

==================================================
18. INTERFAZ DE USUARIO
==================================================

La interfaz debe ser:

- Profesional.
- Moderna.
- Sobria.
- Institucional.
- Responsiva.
- Fácil de utilizar.
- Adaptada a computadoras y tablets.

Utilizar un dashboard administrativo con:

Sidebar:
- Dashboard
- Beneficiarios
- Actividades
- Certificados
- Plantillas
- Usuarios
- Reportes
- Configuración
- Auditoría

La sección principal debe mostrar tablas con:

- Búsqueda.
- Filtros.
- Paginación.
- Acciones.
- Estados mediante indicadores visuales.

==================================================
19. REPORTES
==================================================

Inicialmente implementar reportes básicos:

- Certificados emitidos por periodo.
- Certificados vigentes.
- Certificados expirados.
- Certificados revocados.
- Certificados por actividad.
- Certificados por beneficiario.

Permitir exportar información cuando sea conveniente.

==================================================
20. FLUJO PRINCIPAL
==================================================

El flujo esperado es:

1. El administrador inicia sesión.
2. Registra una actividad.
3. Registra al beneficiario.
4. Selecciona el tipo de certificado.
5. Selecciona la plantilla.
6. Presiona "Generar certificado".
7. El sistema genera automáticamente:
   - Código único.
   - Fecha de emisión.
   - Fecha de vencimiento (+3 años).
   - Token de verificación.
   - Código QR.
   - PDF.
8. El certificado queda almacenado.
9. El usuario administrativo puede descargarlo.
10. El beneficiario recibe/imprime el certificado.
11. Una tercera persona escanea el QR.
12. El sistema consulta la base de datos.
13. Muestra la autenticidad y estado del certificado.

==================================================
21. REGLAS IMPORTANTES
==================================================

Nunca generar certificados duplicados con el mismo código.

Cada certificado debe tener:

- Código único.
- Token único.
- QR único.

El QR debe apuntar a una ruta de verificación.

La verificación debe consultar el estado actual del certificado en la base de datos y NO confiar únicamente en información almacenada dentro del QR.

Si un certificado es revocado, la página pública debe reflejar inmediatamente el estado REVOCADO.

Si el certificado supera los 3 años desde su emisión, debe mostrarse como EXPIRADO.

==================================================
22. ESTRUCTURA DEL PROYECTO
==================================================

Proponer una estructura Laravel organizada.

Por ejemplo:

app/
 ├── Models/
 ├── Http/
 │   ├── Controllers/
 │   ├── Requests/
 │   └── Middleware/
 ├── Services/
 ├── Policies/
 └── Jobs/

resources/
 └── views/

routes/
 ├── web.php
 └── ...

database/
 ├── migrations/
 ├── seeders/
 └── factories/

public/

storage/

Tests/

No generar todo el sistema de una sola vez.

==================================================
23. METODOLOGÍA DE DESARROLLO
==================================================

Desarrollar el sistema por fases.

FASE 1:
- Análisis.
- Requisitos.
- Arquitectura.
- Modelo de datos.

FASE 2:
- Configuración del proyecto.
- Laravel.
- MySQL.
- Autenticación.
- Estructura base.

FASE 3:
- Usuarios y roles.
- Beneficiarios.
- Actividades.

FASE 4:
- Certificados.
- Tipos.
- Plantillas.

FASE 5:
- Generación PDF.
- Generación QR.

FASE 6:
- Verificación pública.
- Control de vigencia.

FASE 7:
- Dashboard.
- Reportes.
- Auditoría.

FASE 8:
- Seguridad.
- Pruebas.
- Optimización.
- Despliegue.

==================================================
24. FORMA DE TRABAJAR
==================================================

Quiero que actúes como arquitecto y desarrollador senior.

No generes código innecesario.

Antes de implementar una funcionalidad:

1. Explica brevemente qué se va a construir.
2. Indica qué archivos se modificarán o crearán.
3. Explica las dependencias necesarias.
4. Genera el código completo necesario.
5. Explica cómo ejecutarlo.
6. Indica cómo probarlo.
7. Verifica que no se rompan funcionalidades anteriores.

No asumir requisitos que no hayan sido definidos.

Cuando exista una decisión de diseño que todavía no haya sido especificada, proponla como una recomendación y explica brevemente sus ventajas.

El código debe ser limpio, modular, seguro y mantenible.

==================================================
25. RESULTADO ESPERADO
==================================================

Al finalizar debe existir un sistema web funcional capaz de:

- Autenticar usuarios administrativos.
- Gestionar beneficiarios.
- Gestionar actividades.
- Generar certificados automáticamente.
- Generar PDFs.
- Generar códigos QR únicos.
- Asociar cada QR con un certificado.
- Verificar certificados públicamente.
- Determinar automáticamente si están vigentes o expirados.
- Manejar certificados revocados.
- Consultar y buscar certificados.
- Mantener registros administrativos.
- Presentar una interfaz profesional para el Vicedecanato.

El sistema debe estar preparado para crecer posteriormente con funcionalidades adicionales, sin necesidad de rehacer la arquitectura principal.

IMPORTANTE:
Primero realiza el análisis funcional y técnico del sistema.
Después propone la arquitectura.
Luego diseña la base de datos.
Finalmente inicia la implementación por fases.
No comiences generando cientos de archivos de código de una sola vez.