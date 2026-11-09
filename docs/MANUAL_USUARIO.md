# Manual de Usuario y Guía Operativa

**Sistema:** Plataforma Web de Gestión, Generación, Diseño, Emisión y Verificación de Certificados Académicos con Código QR  
**Institución:** Facultad de Tecnología — Universidad Mayor de San Andrés (UMSA)  
**Versión del Sistema:** 2.5.0 (Arquitectura Multirrol, Multitenancy de Carreras y Control de Resoluciones)  
**Fecha:** Noviembre 2026  

---

## 📌 Tabla de Contenidos

1. [Introducción y Objetivos del Sistema](#1-introducción-y-objetivos-del-sistema)
2. [Roles de Usuario y Matriz de Permisos Institucionales](#2-roles-de-usuario-y-matriz-de-permisos-institucionales)
3. [Acceso al Sistema y Cuentas por Defecto](#3-acceso-al-sistema-y-cuentas-por-defecto)
4. [Dashboard Principal y Métricas Operativas](#4-dashboard-principal-y-métricas-operativas)
5. [Módulo de Carreras y Departamentos (Exclusivo Super Administrador)](#5-módulo-de-carreras-y-departamentos-exclusivo-super-administrador)
   - [5.1 Registro y Edición de Carreras](#51-registro-y-edición-de-carreras)
   - [5.2 Desactivación / Reactivación Segura de Unidades](#52-desactivación--reactivación-segura-de-unidades)
6. [Padrón Único y Portafolio Académico 360° del Estudiante](#6-padrón-único-y-portafolio-académico-360-del-estudiante)
7. [Gestión de Actividades Académicas y Resoluciones Facultativas](#7-gestión-de-actividades-académicas-y-resoluciones-facultativas)
   - [7.1 Registro de Actividades con N° de Resolución](#71-registro-de-actividades-con-n-de-resolución)
   - [7.2 Delimitación de Audiencia (Propia Carrera, Toda la Facultad o Inter-Carreras)](#72-delimitación-de-audiencia-propia-carrera-toda-la-facultad-o-inter-carreras)
   - [7.3 Detección Automática de Estudiantes No Autorizados](#73-detección-automática-de-estudiantes-no-autorizados)
8. [Nota de aprobación por actividad](#8-nota-de-aprobación-por-actividad)
9. [Catálogo y Diseñador Visual de Plantillas (WYSIWYG)](#9-catálogo-y-diseñador-visual-de-plantillas-wysiwyg)
   - [9.1 Herramientas y Elementos del Diseñador](#91-herramientas-y-elementos-del-diseñador)
   - [9.2 Diccionario de Variables Interpolables](#92-diccionario-de-variables-interpolables)
   - [9.3 Co-firmantes Oficiales con Nombres y Cargos](#93-co-firmantes-oficiales-con-nombres-y-cargos)
10. [Emisión de Certificados](#10-emisión-de-certificados)
    - [10.1 Emisión Individual con Previsualización de Borrador PDF](#101-emisión-individual-con-previsualización-de-borrador-pdf)
    - [10.2 Emisión Masiva por Nómina de Participantes (Excel / CSV)](#102-emisión-masiva-por-nómina-de-participantes-excel--csv)
11. [Garantía de Inmutabilidad y Congelación Documental (Regla 40)](#11-garantía-de-inmutabilidad-y-congelación-documental-regla-40)
12. [Portal Público de Verificación (Código QR y Correlativo)](#12-portal-público-de-verificación-código-qr-y-correlativo)
13. [Módulo de Reportes Estadísticos e Informes](#13-módulo-de-reportes-estadísticos-e-informes)
14. [Auditoría Administrativa y Trazabilidad](#14-auditoría-administrativa-y-trazabilidad)
15. [Preguntas Frecuentes (FAQ)](#15-preguntas-frecuentes-faq)

---

## 1. Introducción y Objetivos del Sistema

El **Sistema de Certificados de la Facultad de Tecnología de la UMSA** es una plataforma universitaria diseñada para centralizar, autenticar y certificar los méritos académicos (aprobación, asistencia, docencia y disertación) garantizando:

- **Aislamiento Seguro por Carrera**: Cada una de las 9 carreras oficiales de la Facultad y sus departamentos cuentan con su propio espacio de trabajo sin interferir en los registros de las demás carreras.
- **Rigor Normativo por Resolución**: Ningún certificado se emite sin respaldo institucional y delimitación estricta de las carreras participantes.
- Los certificados de aprobación se emiten a quienes alcanzan el mínimo de su actividad y cumplen los demás requisitos.
- **Validez Legal Permanente con Código QR**: Verificación instantánea e infalsificable desde cualquier dispositivo móvil o navegador web.

---

## 2. Roles de Usuario y Matriz de Permisos Institucionales

| Funcionalidad / Módulo | 👑 Super Admin (Cómputo) | 🎓 Jefe de Carrera | 💼 Secretaria | ⚙️ Operador |
| :--- | :---: | :---: | :---: | :---: |
| **Administración de Carreras y Deptos.** | ✅ Total | ❌ Acceso Denegado (403) | ❌ Acceso Denegado (403) | ❌ |
| **Gestión de Usuarios y Roles** | ✅ Total | ❌ | ❌ | ❌ |
| **Dashboard Ejecutivo** | ✅ Toda la Facultad | ✅ Su propia carrera | ✅ Su propia carrera | ✅ Global |
| **Actividades Académicas** | ✅ Ver/Crear todas | ✅ Su propia carrera | ✅ Su propia carrera | ✅ |
| **Certificados Institucionales** | ✅ Ver/Auditar todos | ✅ Su propia carrera | ✅ Su propia carrera | ✅ |
| **Padrón de Beneficiarios** | ✅ Padrón Único | ✅ Portafolio 360° | ✅ Portafolio 360° | ✅ |
| **Diseñador de Plantillas** | ✅ Globales y locales | ✅ De su carrera | ✅ De su carrera | ❌ Solo lectura |
| **Módulo de Reportes Estadísticos** | ✅ Toda la Facultad | ✅ Su propia carrera | ❌ Acceso Denegado (403) | ❌ |
| **Biblioteca de Recursos (Logos/Firmas)**| ✅ Globales y locales | ✅ De su carrera | ✅ De su carrera | ❌ |
| **Anular / Revocar Certificados** | ✅ | ✅ | ❌ | ❌ |

---

## 3. Acceso al Sistema y Cuentas por Defecto

1. Ingrese con su navegador a la dirección del sistema: `http://localhost:8000/login`
2. Digite sus credenciales institucionales:
   - **Super Administrador (Cómputo):** `admin@universidad.edu` | Contraseña: `Admin*2026`
   - **Jefe de Carrera (Ej. Química):** `jefe.quimica@umsa.bo` | Contraseña: `password`
   - **Secretaria (Ej. Química):** `secretaria.quimica@umsa.bo` | Contraseña: `password`
   - **Operador:** `operador@universidad.edu` | Contraseña: `Admin*2026`
3. Presione **"Iniciar Sesión Segura"**.

---

## 4. Dashboard Principal y Métricas Operativas

Al ingresar, el sistema adapta automáticamente las métricas e indicadores al rol del usuario:
- **Para Jefes y Secretarias de Carrera**:
  - Muestra únicamente el total de certificados, actividades y beneficiarios de **su propia carrera**.
  - En la esquina superior e inferior izquierda de la pantalla se visualiza la insignia oficial de su carrera (ej.: `QUI - Química Industrial`).
- **Para el Super Administrador**:
  - Presenta las estadísticas consolidadas de toda la Facultad de Tecnología, con desglose de certificados emitidos por carrera y volumen de verificaciones QR públicas.

---

## 5. Módulo de Carreras y Departamentos (Exclusivo Super Administrador)

Ubicado en el menú lateral: **Administración Central $\rightarrow$ Carreras y Departamentos** (`/carreras-departamentos`).

### 5.1 Registro y Edición de Carreras
El Super Administrador puede crear nuevas carreras o departamentos con los siguientes datos:
- **Nombre Oficial**: Nombre de la carrera (ej.: *Carrera de Electromecánica Industrial*).
- **Código / Sigla**: Sigla de 3 letras que formará parte de los códigos correlativos (ej.: `ELM`).
- **Tipo de Unidad**: Carrera, Departamento, Instituto o Administrativo.
- **Autoridad Responsable**: Título y nombre de la autoridad (ej.: *Director de Carrera*, *Ing. Carlos Mendoza*).

### 5.2 Desactivación / Reactivación Segura de Unidades
Si una carrera concluye su ciclo académico o suspende temporalmente la emisión de certificados, el Super Administrador puede presionar el botón **"Desactivar"**:
- **Efecto de la Desactivación**:
  - Sus usuarios no podrán crear nuevos eventos ni emitir nuevos certificados.
- **Preservación Histórica Garantizada**:
  - **Ningún certificado emitido en el pasado pierde validez**.
  - Los códigos QR impresos en los certificados continúan respondiendo con estado **VIGENTE** en el portal público de verificación.
  - Al reactivar la carrera, todo su historial de actividades y plantillas queda inmediatamente disponible.

---

## 6. Padrón Único y Portafolio Académico 360° del Estudiante

El sistema cuenta con un **Padrón Único Institucional**:
- Cada estudiante se registra una sola vez en el sistema mediante su número de C.I.
- Al acceder a la ficha de un estudiante en **Beneficiarios $\rightarrow$ Ver Portafolio** (`beneficiaries/{id}`), se despliega el **Portafolio Académico 360°**, donde se muestran cronológicamente todos los certificados que el estudiante ha obtenido en la Facultad de Tecnología:
  - 🏷️ **Certificados de su Propia Carrera**: Emitidos directamente por su carrera de origen.
  - 🏛️ **Certificados de la Facultad**: Emitidos por Vicedecanato o Cómputo (ej. Jornadas de Inducción).
  - 📜 **Certificados Inter-carreras**: Emitidos en eventos conjuntos respaldados por Resolución Facultativa.

---

## 7. Gestión de Actividades Académicas y Resoluciones Facultativas

### 7.1 Registro de Actividades con N° de Resolución
Al crear un evento académico en **Actividades Académicas $\rightarrow$ Nueva Actividad**, se debe consignar obligatoriamente:
- Título del evento, fechas de realización y carga horaria académica (horas aula).
- **Número de Resolución Facultativa** (`resolution_number`, ej.: *RF-045/2026*).
- **Tipo de Certificado**: Aprobación, Asistencia, Participación, Reconocimiento, etc.
- **Esquema de Vigencia**: Permanente (por vida) o Temporal (con fecha de expiración o años de validez).

### 7.2 Delimitación de Audiencia
Se debe seleccionar una de las tres opciones de alcance:
1. **Exclusivo para la propia Carrera (`own_unit`)**:
   El evento es cerrado y solo permite la certificación de estudiantes de la carrera organizadora.
2. **Toda la Facultad (`all_faculty`)**:
   Evento institucional abierto a estudiantes de cualquiera de las 9 carreras de la Facultad de Tecnología.
3. **Carreras específicas autorizadas por Resolución (`specific_units`)**:
   Despliega una lista con las 9 carreras oficiales para marcar con casilla de verificación cuáles fueron expresamente autorizadas en la Resolución Facultativa (ej. Taller conjunto de *Química Industrial* y *Mecánica Industrial*).

### 7.3 Detección Automática de Estudiantes No Autorizados
Si un estudiante perteneciente a una carrera no autorizada intenta ser agregado manualmente o figura en el archivo Excel de la nómina:
- El sistema lo clasifica inmediatamente como **`Observado`**.
- Muestra la advertencia: `Carrera no autorizada (según Resolución RF-045/2026)`.
- **El sistema bloquea la emisión de su certificado**, impidiendo la emisión irregular de certificados fuera de su carrera.

---

## 8. Nota de aprobación por actividad

Para actividades asociadas a un **Certificado de Aprobación** (o que tengan activada la opción de calificación):
1. Al crear el evento, indica la **nota mínima de aprobación** en la escala que uses para evaluarlo.
2. La nómina compara la nota registrada con el mínimo del evento.
   - Quien queda por debajo del mínimo aparece como **`Reprobado`**.
   - El estudiante sin calificación registrada queda marcado como **`Observado`**.
   - Al ejecutar la emisión masiva de certificados, **el sistema omite automáticamente a los reprobados y a los no calificados**, emitiendo certificados **única y exclusivamente para los estudiantes aprobados**.
   - En pantalla se presenta un resumen indicando cuántos certificados fueron emitidos y cuántos reprobados fueron omitidos éticamente.

---

## 9. Catálogo y Diseñador Visual de Plantillas (WYSIWYG)

Acceda mediante el menú lateral: **Plantillas y Diseñador**.

### 9.1 Herramientas y Elementos del Diseñador
- **Lienzo A4 Proporcional**: Visualización exacta de lo que se imprimirá en el documento PDF.
- **Arrastre de Elementos (Drag & Drop)**: Desplace títulos, textos, firmas y el código QR con el mouse o con las flechas del teclado para un ajuste milimétrico.
- **Inspector Lateral de Propiedades**: Permite modificar tamaño de tipografía (px), color institucional, peso de la fuente, alineación y orden de capas.

### 9.2 Diccionario de Variables Interpolables
Escriba estas etiquetas dentro de los textos para que el sistema inserte los datos automáticamente:
- `{{beneficiario.nombre}}`: Nombres y apellidos completos del estudiante o participante.
- `{{beneficiario.documento}}`: Carnet de Identidad (ej. `CI 8345291 LP`).
- `{{beneficiario.carrera}}`: Carrera universitaria del estudiante.
- `{{actividad.nombre}}`: Nombre oficial del curso o seminario.
- `{{actividad.horas}}`: Carga horaria en horas académicas.
- `{{actividad.resolucion}}`: Número de Resolución Facultativa.
- `{{certificado.puntaje}}`: Calificación numérica aprobatoria obtenida (ej. `88.5 pts`).
- `{{certificado.mencion}}`: Mención cualitativa (ej. `Aprobado con Excelencia`).
- `{{certificado.codigo}}`: Código correlativo único (ej. `CERT-QUI-2026-0001`).

### 9.3 Co-firmantes Oficiales con Nombres y Cargos
En el panel de Firmas se pueden insertar los sellos y firmas digitales con el nombre y cargo de cada autoridad (Director de Carrera, Docente Responsable, Vicedecano).

---

## 10. Emisión de Certificados

### 10.1 Emisión Individual con Previsualización de Borrador PDF
1. Diríjase a **Certificados $\rightarrow$ Emitir Certificado**.
2. Seleccione al beneficiario por su C.I. o nombre.
3. Seleccione la actividad académica y la plantilla a emplear.
4. Presione el botón **"Previsualizar Borrador PDF"** para revisar en pantalla el documento antes de emitirlo.
5. Ingrese la calificación (si la actividad es evaluada) y presione **"Emitir Certificado Oficial"**.

### 10.2 Emisión Masiva por Nómina de Participantes (Excel / CSV)
1. Ingrese a la actividad académica en **Actividades Académicas $\rightarrow$ [Ver Actividad]**.
2. En la pestaña **"Importar Nómina"**, suba el archivo `.xlsx` o `.csv`.
3. El sistema realizará un diagnóstico previo indicando:
   - Cuántos alumnos están listos para ser certificados.
   - Cuántos estudiantes alcanzan el mínimo de la actividad y cuántos quedan por debajo.
   - Cuántos estudiantes pertenecen a carreras no autorizadas por la resolución (`observados`).
4. Presione **"Emitir Certificados a la Nómina"**: el sistema emitirá en lote solo a los aprobados y habilitados por resolución, descartando reprobados y observados.

---

## 11. Garantía de Inmutabilidad y Congelación Documental (Regla 40)

Una vez que un certificado es emitido:
- El sistema genera un **Snapshot Documental Inmutable** que almacena en la base de datos la copia idéntica del texto, diseño, firmas, sellos y calificaciones de ese momento.
- Si en el futuro la carrera cambia de Director, modifica el diseño de la plantilla o actualiza su logotipo, **los certificados emitidos en el pasado conservan sus firmas y diseño originales sin ninguna alteración**, garantizando seguridad jurídica ante auditorías universitarias.

---

## 12. Portal Público de Verificación (Código QR y Correlativo)

Cualquier persona, empleador o entidad pública puede verificar la autenticidad de un certificado sin necesidad de iniciar sesión:
1. **Mediante Código QR**:
   - Escanee con la cámara de su celular el código QR impreso en la esquina del certificado.
   - El enlace abrirá inmediatamente la página oficial de verificación: `http://localhost:8000/verificar/{token}`.
2. **Mediante Búsqueda Manual**:
   - Ingrese a `http://localhost:8000/verificar`.
   - Digite el código correlativo (ej. `CERT-QUI-2026-0001`) o el número de C.I. del estudiante.
3. **Resultado en Pantalla**:
   - Muestra el estado del certificado (**VIGENTE**, **EXPIRADO** o **REVOCADO**).
   - Datos del beneficiario, actividad, carrera organizadora, resolución y calificación obtenida.
   - Sello de integridad criptográfica SHA-256 validado.

---

## 13. Módulo de Reportes Estadísticos e Informes

Acceda mediante el menú lateral: **Reportes** (Exclusivo para Super Administrador y Jefes de Carrera; no visible para Secretarias).
- Permite filtrar la emisión por rango de fechas, carrera/departamento y modalidad de certificación.
- Genera métricas de total de certificados emitidos, distribución por carreras y gráficos comparativos.
- Exportación en formato imprimible y PDF formal para informes de gestión ante el Honorable Consejo Facultativo.

---

## 14. Auditoría Administrativa y Trazabilidad

En **Auditoría**, el Super Administrador y los Jefes de Carrera pueden auditar cada evento:
- Quién emitió cada certificado, desde qué dirección IP y a qué hora exacta.
- Registro de modificaciones de actividades y cambios de estado en carreras.
- Registro de consultas y escaneos QR realizados por el público.

---

## 15. Preguntas Frecuentes (FAQ)

### ¿Qué sucede si desactivo una carrera en el sistema?
La desactivación impide que sus usuarios emitan nuevos certificados. Sin embargo, **todos los certificados históricos emitidos por esa carrera siguen siendo válidos y verificables vía QR** en el portal público.

### ¿Puede una secretaria de una carrera ver las actividades de otra carrera?
**No.** El sistema aplica aislamiento estricto por unidad organizativa (`isUnitScoped`). Las secretarias y jefes solo tienen acceso a los registros de su respectiva carrera.

### ¿Se le puede emitir un certificado de aprobación a un alumno con nota 50?
Depende del mínimo definido para la actividad. El sistema omite la emisión cuando la nota del participante queda por debajo de ese valor.

### ¿Cómo se corrige una nota después de emitir el certificado?
La nota permanece bloqueada mientras el participante tenga un certificado sin anular en esa actividad. Solo el superadministrador puede anularlo, indicando el motivo. Si existen varios certificados del participante en la misma actividad, deben anularse todos antes de cambiar la nota. Después se puede corregir desde la nómina o una nueva planilla y emitir otro certificado, siempre que se cumplan el mínimo del evento, las carreras habilitadas y la confirmación manual cuando la actividad tiene costo. Los certificados anulados conservan su nota original e historial y siguen apareciendo como revocados al verificarlos.

### ¿Puede una carrera emitir certificados para estudiantes de otra carrera?
Según el alcance configurado para la actividad. La carrera organizadora siempre está habilitada y puede agregar carreras invitadas o habilitar toda la facultad. La resolución se registra cuando existe. Los estudiantes de carreras no autorizadas quedan como *Observados*, sin certificado, y pueden retirarse manualmente de la nómina sin eliminar su registro del padrón.
