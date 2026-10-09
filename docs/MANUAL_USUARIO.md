# Manual de Usuario: Sistema de Certificados Académicos con Diseñador Institucional y Código QR

**Institución:** Vicedecanato de Facultad  
**Versión del Sistema:** 2.0.0 (Release con Diseñador Flexible de Plantillas)  
**Fecha de Publicación:** Octubre 2026  

---

## 📌 Tabla de Contenidos
1. [Introducción y Objetivos](#1-introducción-y-objetivos)
2. [Roles de Usuario y Matriz de Permisos](#2-roles-de-usuario-y-matriz-de-permisos)
3. [Acceso al Sistema y Seguridad](#3-acceso-al-sistema-y-seguridad)
4. [Dashboard Principal (Métricas y Estados)](#4-dashboard-principal-métricas-y-estados)
5. [Gestión y Diseñador Visual de Plantillas Institucionales](#5-gestión-y-diseñador-visual-de-plantillas-institucionales)
   - [5.1 Catálogo de Plantillas y Plantilla Predeterminada](#51-catálogo-de-plantillas-y-plantilla-predeterminada)
   - [5.2 Creación y Duplicación de Plantillas](#52-creación-y-duplicación-de-plantillas)
   - [5.3 Uso del Diseñador Visual de Certificados](#53-uso-del-diseñador-visual-de-certificados)
   - [5.4 Versionado de Plantillas (v1, v2, v3)](#54-versionado-de-plantillas-v1-v2-v3)
6. [Biblioteca de Recursos Institucionales (Logos, Firmas y Sellos)](#6-biblioteca-de-recursos-institucionales-logos-firmas-y-sellos)
   - [6.1 Subida y Administración de Escudos y Logos](#61-subida-y-administración-de-escudos-y-logos)
   - [6.2 Registro de Firmas Digitalizadas con Cargos Institucionales](#62-registro-de-firmas-digitalizadas-con-cargos-institucionales)
   - [6.3 Sellos Oficiales y Marcas de Agua](#63-sellos-oficiales-y-marcas-de-agua)
7. [Gestión del Padrón de Beneficiarios](#7-gestión-del-padrón-de-beneficiarios)
8. [Gestión de Actividades Académicas](#8-gestión-de-actividades-académicas)
   - [8.1 Registro de Eventos Académicos](#81-registro-de-eventos-académicos)
   - [8.2 Personalización de Certificados por Actividad Académica](#82-personalización-de-certificados-por-actividad-académica)
9. [Emisión de Certificados](#9-emisión-de-certificados)
   - [9.1 Emisión Individual con Selección de Plantilla](#91-emisión-individual-con-selección-de-plantilla)
   - [9.2 Previsualización en Vivo del Borrador PDF](#92-previsualización-en-vivo-del-borrador-pdf)
   - [9.3 Emisión Masiva en Lote para Cursos y Talleres](#93-emisión-masiva-en-lote-para-cursos-y-talleres)
10. [Garantía de Inmutabilidad y Congelación Documental](#10-garantía-de-inmutabilidad-y-congelación-documental)
11. [Consulta, Vista Previa y Descarga de Certificados](#11-consulta-vista-previa-y-descarga-de-certificados)
12. [Regla Estricta de Vigencia Institucional (3 Años)](#12-regla-estricta-de-vigencia-institucional-3-años)
13. [Anulación y Revocación de Certificados](#13-anulación-y-revocación-de-certificados)
14. [Portal Público de Verificación (QR y Búsqueda Manual)](#14-portal-público-de-verificación-qr-y-búsqueda-manual)
15. [Módulo de Reportes e Informes Imprimibles](#15-módulo-de-reportes-e-informes-imprimibles)
16. [Auditoría de Acciones Administrativas](#16-auditoría-de-acciones-administrativas)
17. [Configuración de Parámetros Globales](#17-configuración-de-parámetros-globales)
18. [Preguntas Frecuentes (FAQ)](#18-preguntas-frecuentes-faq)

---

## 1. Introducción y Objetivos

El **Sistema Web de Gestión, Generación, Diseño y Verificación de Certificados** del Vicedecanato es una plataforma universitaria integral diseñada para automatizar, personalizar y blindar jurídicamente la emisión de certificados de mérito académico, asistencia, aprobación, docencia y reconocimiento institucional.

### Avances y Capacidades de la Versión 2.0:
- **Diseño Flexible sin Modificar Código**: Cada evento académico puede tener un diseño visual, paleta de colores, redacción y conjunto de firmas diferente, configurables desde el sistema.
- **Biblioteca de Recursos Gráficos**: Banco centralizado de escudos, logos co-organizadores, sellos y firmas con nombres y cargos de autoridades.
- **Interpolación de Variables Dinámicas**: Posibilidad de redactar textos institucionales que insertan automáticamente nombres, horas, fechas y resoluciones mediante etiquetas como `{{beneficiario.nombre}}` o `{{actividad.horas}}`.
- **Previsualizador de Borradores**: Los operadores pueden inspeccionar visualmente el certificado en PDF antes de emitirlo formalmente.
- **Congelación Documental Inmutable**: Los certificados emitidos quedan guardados con una copia histórica inalterable. Los cambios futuros en plantillas jamás modifican los certificados ya emitidos.
- **Verificación Pública Instantánea mediante QR Vectorial**: Cualquier tercero puede comprobar la validez y vigencia del documento escaneando el código QR con un teléfono inteligente o ingresando el código correlativo en la web pública.
- **Control Autónomo de Vigencia**: Regla estricta de 3 años calendario con cambio dinámico de estado (*Vigente* $\rightarrow$ *Expirado*).

---

## 2. Roles de Usuario y Matriz de Permisos

| Módulo / Funcionalidad | 👑 Administrador (Vicedecano / Jefe de Unidad) | 💼 Operador (Personal Administrativo) | 🌐 Visitante / Público General |
| :--- | :---: | :---: | :---: |
| **Acceso al Panel Privado** | ✅ | ✅ | ❌ |
| **Diseñador y Catálogo de Plantillas** | ✅ (Crear, Editar, Duplicar, Versiones) | ❌ (Solo utiliza las aprobadas) | ❌ |
| **Biblioteca de Recursos (Logos/Firmas)** | ✅ (Subir, Activar, Desactivar) | ❌ | ❌ |
| **Padrón de Beneficiarios** | ✅ | ✅ | ❌ |
| **Actividades y Emisión Masiva** | ✅ | ✅ | ❌ |
| **Configurar Diseño por Actividad** | ✅ | ✅ | ❌ |
| **Emisión Individual con Borrador** | ✅ | ✅ | ❌ |
| **Descarga e Impresión de PDF** | ✅ | ✅ | ❌ |
| **Anular / Revocar Certificados** | ✅ | ❌ | ❌ |
| **Auditoría y Trazabilidad** | ✅ | ❌ | ❌ |
| **Configuración Institucional** | ✅ | ❌ | ❌ |
| **Escanear QR y Verificar en Web** | ✅ | ✅ | ✅ (Sin iniciar sesión) |

---

## 3. Acceso al Sistema y Seguridad

1. Abra su navegador web e ingrese a: `http://localhost:8000/login` (o el dominio institucional).
2. Introduzca su correo institucional y contraseña.
3. Marque **"Recordar mi sesión"** únicamente en computadoras de confianza de la oficina.
4. Presione **"Iniciar Sesión Segura"**.

### Credenciales por Defecto del Sistema:
- **Administrador:** `admin@universidad.edu` | Contraseña: `Admin*2026`
- **Operador:** `operador@universidad.edu` | Contraseña: `Operador*2026`

---

## 4. Dashboard Principal (Métricas y Estados)

Al autenticarse, accederá a la pantalla ejecutiva de control con indicadores sincronizados en tiempo real:

- **Total Certificados**: Histórico consolidado de documentos emitidos por la institución.
- **Certificados Vigentes (Verde)**: Documentos válidos cuyo periodo legal de 3 años está en curso.
- **Certificados Expirados (Amarillo)**: Documentos que han superado los 3 años desde su fecha de emisión.
- **Certificados Revocados (Rojo)**: Documentos anulados formalmente por el Vicedecanato.
- **Últimos Certificados Emitidos**: Acceso inmediato a vista previa rápida (`👁️`), descarga en PDF (`📥`) y detalle (`🔍`).
- **Monitoreo de Consultas QR**: Registro de verificaciones realizadas recientemente por empleadores o instituciones externas.

---

## 5. Gestión y Diseñador Visual de Plantillas Institucionales

> **Acceso:** Menú lateral $\rightarrow$ **"Plantillas y Diseñador"** (Exclusivo Administrador).

### 5.1 Catálogo de Plantillas y Plantilla Predeterminada
El catálogo muestra las plantillas maestras disponibles en el Vicedecanato, cada una con su categoría, orientación, número de versión activa y estado operativo.
- **Hacer Predeterminada**: La plantilla marcada como predeterminada será la seleccionada automáticamente en las emisiones individuales y por lotes si el evento no especifica otra.
- **Activar / Desactivar**: Permite pausar una plantilla para que no aparezca en los formularios de emisión sin perder su historial.

### 5.2 Creación y Duplicación de Plantillas
- **Crear Nueva Plantilla**: Pulse el botón **"+ Nueva Plantilla"**, asigne un nombre (ej. *Congreso Internacional de Tecnología 2026*), seleccione la categoría (*Seminario, Institucional, Capacitación, Reconocimiento*) y la orientación (*A4 Horizontal* o *A4 Vertical*). Puede basarse en una plantilla existente para heredar su estilo.
- **Duplicar Plantilla**: En cualquier tarjeta del catálogo, pulse **"Duplicar"**. Esto crea una réplica idéntica lista para ser adaptada a un nuevo evento sin alterar la original.

### 5.3 Uso del Diseñador Visual de Certificados
Al ingresar a **"Diseñar"** en una plantilla, se despliega el editor gráfico interactivo:
1. **Identidad Visual y Colores**:
   - *Color Primario*: Aplica al título del certificado, textos destacados y elementos decorativos principales.
   - *Color Secundario / Filigrana*: Aplica a los marcos perimetrales y acentos dorados.
2. **Estilo de Marco y Bordes**:
   - *Doble Ornamental*: Clásico marco solemne universitario con doble línea.
   - *Filigrana Dorada*: Marco de lujo con detalle punteado exterior para distinciones de honor.
   - *Línea Moderna*: Marco limpio y minimalista.
3. **Encabezados Institucionales**:
   - Configuración de la jerarquía universitaria: *Universidad*, *Facultad*, *Unidad Emisora*.
4. **Textos y Redacción Base**:
   - Ajuste del texto de otorgación por defecto y título del mérito.
5. **Previsualización en Vivo**:
   - Permite verificar inmediatamente cómo se verá el documento compilado en PDF.

### 5.4 Versionado de Plantillas (v1, v2, v3)
Al guardar modificaciones en el Diseñador Visual, el sistema ofrece:
- **Guardar como Nueva Versión (Recomendado)**: Incrementa el número de versión (ej. de v1 a v2) y solicita un breve resumen del cambio (ej. *Actualización de firma y cambio a azul institucional*).
- Esto garantiza trazabilidad completa de quién y cuándo modificó cada diseño.

---

## 6. Biblioteca de Recursos Institucionales (Logos, Firmas y Sellos)

> **Acceso:** Menú lateral $\rightarrow$ **"Recursos Institucionales"** (Exclusivo Administrador).

La biblioteca centraliza los activos gráficos oficiales del Vicedecanato para garantizar máxima calidad visual e impedir distorsiones:

### 6.1 Subida y Administración de Escudos y Logos
- Permite subir logotipos en alta resolución (formatos PNG con transparencia, SVG o JPG).
- Puede marcar un logo como **"Obligatorio"** para forzar su aparición en todas las plantillas del Vicedecanato (ej. *Escudo Oficial UMSA*).

### 6.2 Registro de Firmas Digitalizadas con Cargos Institucionales
Al registrar una firma digitalizada, se completan los metadatos de la autoridad:
- **Nombre de la Autoridad**: Ej. *Dr. Carlos Mendoza Fernández*.
- **Cargo Oficial**: Ej. *Vicedecano de Facultad*.
- **Institución / Unidad**: Ej. *Facultad de Ciencias Puras y Naturales*.
El sistema genera automáticamente el pie de firma profesional con la línea separadora, nombre, cargo e institución tipográficamente alineados.

### 6.3 Sellos Oficiales y Marcas de Agua
- **Sellos Institucionales**: Se colocan en la esquina inferior para autenticar la validez formal del documento.
- **Marcas de Agua**: Escudos o emblemas que se estampan en el centro del certificado con opacidad controlada (10% a 15%) para no entorpecer la legibilidad de los textos.

---

## 7. Gestión del Padrón de Beneficiarios

> **Acceso:** Menú lateral $\rightarrow$ **"Beneficiarios"**.

1. **Registrar Participante**:
   - Tipo y Número de Documento (CI, Pasaporte, DNI). El sistema previene automáticamente la duplicidad de documentos.
   - Grado / Título Académico (`Univ.`, `Lic.`, `Ing.`, `Dr.`, `Mg.`, etc.).
   - Nombres y Apellidos completos.
   - Correo electrónico y teléfono de contacto.
   - Carrera o institución de procedencia.
2. **Historial del Beneficiario**:
   - Al hacer clic en el nombre o en el icono `👁️`, se muestra la ficha del participante con todos los certificados que le han sido emitidos a lo largo del tiempo, sus estados de vigencia y los enlaces de descarga directa.

---

## 8. Gestión de Actividades Académicas

> **Acceso:** Menú lateral $\rightarrow$ **"Actividades Académicas"**.

### 8.1 Registro de Eventos Académicos
Complete los datos del evento:
- Nombre oficial del curso, taller, seminario o congreso.
- Tipo de actividad, carga horaria en horas académicas, rango de fechas y lugar.
- Autoridad responsable de la actividad.

### 8.2 Personalización de Certificados por Actividad Académica
Al abrir el detalle de cualquier actividad, encontrará el panel **"Configuración del Certificado para esta Actividad"**:
- **Asignación de Plantilla**: Seleccione la plantilla visual que mejor represente la temática del evento.
- **Título Especial**: Ej. *DIPLOMA DE RECONOCIMIENTO ESPECIAL*.
- **Colores Propios del Evento**: Sobrescriba los colores primario y secundario para que coincidan con la identidad gráfica del congreso o curso.
- **Redacción Personalizada con Variables Dinámicas**: Escriba el cuerpo del certificado utilizando etiquetas como:
  ```text
  Por su sobresaliente participación y aprobación del {{actividad.nombre}}, realizado del {{actividad.fechas}} con una carga horaria de {{actividad.horas}} horas académicas.
  ```
- **Campos Especiales**: Ingrese el N° de Resolución Facultativa (ej. *Resol. Fac. N° 451/2026*) o la Modalidad (*Presencial / Virtual*).

---

## 9. Emisión de Certificados

### 9.1 Emisión Individual con Selección de Plantilla
1. Ingrese a **"Certificados"** $\rightarrow$ **"+ Emitir Nuevo Certificado"**.
2. **Paso 1: Beneficiario**: Seleccione al participante del padrón.
3. **Paso 2: Actividad Académica**: Seleccione el evento. Si la actividad tiene plantilla y configuración asignada, el sistema la cargará automáticamente.
4. **Paso 3: Tipo de Certificado**: Elija entre *Aprobación, Asistencia, Disertante, Organizador o Reconocimiento*.
5. **Paso 4: Plantilla de Diseño**: Puede conservar la asignada a la actividad o seleccionar otra alternativa del catálogo.
6. **Paso 5: Fecha de Emisión**: El sistema calcula y muestra de forma automática la **Fecha de Vencimiento legal (+3 años exactos)**.
7. **Paso 6 (Opcional): Aclaración Especial**: Ingrese menciones de honor particulares exclusivas para este participante.

### 9.2 Previsualización en Vivo del Borrador PDF
Antes de generar el certificado definitivo:
- Haga clic en el botón **"[ 👁️ PREVISUALIZAR ]"**.
- El sistema compilará en una nueva pestaña del navegador el documento PDF tal como se imprimiría, con su marco, logos, textos interpolados, firmas y código QR de muestra.
- **Ventaja:** Esta acción no crea registros en la base de datos ni consume números correlativos oficiales, permitiendo corregir errores tipográficos con total seguridad.

### 9.3 Emisión Masiva en Lote para Cursos y Talleres
Para emitir decenas de certificados en una sola acción tras concluir un curso:
1. Abra la ficha de la actividad académica.
2. Pulse el botón superior **"⚡ Emisión Masiva de Certificados"**.
3. Seleccione el tipo de mérito (*Asistencia* o *Aprobación*).
4. Marque las casillas de los participantes aprobados (o utilice el botón *"Seleccionar Todos"*).
5. Pulse **"Emitir Certificados Seleccionados"**.
6. El sistema generará en lote los códigos correlativos, tokens QR y copias inmutables, omitiendo automáticamente a participantes que ya cuenten con certificado activo en dicho evento.

---

## 10. Garantía de Inmutabilidad y Congelación Documental

Como documento oficial emitido por una universidad, un certificado es un instrumento público de fe probatoria.

### ¿Cómo protege el sistema la validez de los certificados antiguos?
- Al momento exacto de emitir un certificado, el sistema genera un **Snapshot Documental Inmutable (JSON)** que almacena:
  - Todo el esquema gráfico, fuentes y colores utilizados.
  - La redacción textual completa con las variables ya sustituidas.
  - Las imágenes de los escudos, logos y sellos institucionales.
  - Los nombres, cargos e instituciones de las autoridades firmantes al momento de la emisión.
- **Regla Estricta:** Si un administrador rediseña una plantilla, cambia los logos de la facultad o asume un nuevo Vicedecano el año siguiente, **los certificados emitidos en el pasado conservan permanentemente su diseño, firmas y textos originales**.

---

## 11. Consulta, Vista Previa y Descarga de Certificados

Desde el listado general de **"Certificados"**:
- **Búsqueda y Filtros**: Busque por código correlativo (`CERT-2026-000001`), nombre del beneficiario, cédula de identidad, actividad académica o estado de vigencia (*Vigente, Expirado, Revocado*).
- **Vista Previa (`👁️`)**: Visualice el documento en el visor PDF del navegador.
- **Descarga Directa (`📥`)**: Descarga el archivo PDF A4 listo para impresión en papel membretado o cartulina de graduación.
- **Ficha de Detalle (`🔍`)**: Muestra el código QR ampliado, enlace de verificación pública, hash criptográfico SHA-256 y bitácora de escaneos recibidos.

---

## 12. Regla Estricta de Vigencia Institucional (3 Años)

Por normativa universitaria, todo certificado emitido goza de **3 años calendario de validez oficial**:
- **Fecha de Emisión:** `15 de Septiembre de 2026`
- **Fecha de Vencimiento:** `15 de Septiembre de 2029` (calculada automáticamente).
- **Cálculo Autónomo:** El sistema evalúa diariamente la fecha del calendario contra la de vencimiento:
  - Mientras $\text{Fecha Actual} \le \text{Fecha de Vencimiento}$ $\rightarrow$ Se muestra en **VERDE (VIGENTE)**.
  - Al superar los 3 años $\rightarrow$ Cambia automáticamente a **AMARILLO (EXPIRADO)** en todo el sistema y en el portal público.

---

## 13. Anulación y Revocación de Certificados

Si un certificado fue emitido por error de identidad o debe anularse por resolución administrativa:
1. Acción reservada exclusivamente al **Administrador**.
2. Abra la ficha del certificado (`Certificados` $\rightarrow$ Detalle).
3. Pulse el botón rojo **"🚫 Anular / Revocar"**.
4. Ingrese obligatoriamente la **justificación formal de la anulación** (ej. *Error en el número de cédula de identidad del participante según informe de secretaría*).
5. Confirme la operación.
6. El certificado quedará marcado permanentemente como **REVOCADO (ROJO)**.
7. Al escanear el código QR del documento físico, el portal público mostrará de inmediato una advertencia roja indicando que el certificado ha sido revocado y carece de validez legal.

---

## 14. Portal Público de Verificación (QR y Búsqueda Manual)

El portal público está disponible para cualquier persona las 24 horas del día **sin necesidad de credenciales ni inicio de sesión**:

### Opción A: Mediante Escaneo de Código QR
1. El empleador o evaluador apunta la cámara de su teléfono celular al código QR impreso en el certificado.
2. El teléfono abre la URL oficial: `https://certificados.vicedecanato.edu/verificar/{token}`.
3. Se despliega la tarjeta de validación institucional con el estado del documento, nombre del titular, actividad, fechas y autoridad emisora.

### Opción B: Mediante Búsqueda Manual de Código Correlativo
1. Ingrese a la dirección web: `http://localhost:8000/verificar`.
2. Escriba el código que figura en el certificado (ej. `CERT-2026-000001`).
3. Presione **"🔍 Verificar Autenticidad"**.
4. Si el certificado figura en el padrón oficial del Vicedecanato, se mostrará su ficha de validez; si el código no existe o fue alterado, se notificará que el documento no es auténtico.

---

## 15. Módulo de Reportes e Informes Imprimibles

> **Acceso:** Menú lateral $\rightarrow$ **"Reportes"**.

- Filtre por rango de fechas (*Desde / Hasta*), actividad académica, tipo de certificado o estado de vigencia.
- Visualice los totales consolidados (Total emitidos, Vigentes, Expirados, Revocados).
- Utilice el botón **🖨️ Imprimir Reporte** para generar una nómina física en formato ejecutivo para archivos de gestión y rendición de cuentas.

---

## 16. Auditoría de Acciones Administrativas

> **Acceso:** Menú lateral $\rightarrow$ **"Auditoría de Acciones"** (Exclusivo Administrador).

- Registra cada acción relevante: inicio de sesión, creación de plantillas, modificaciones de diseño, emisiones individuales y masivas, descargas de PDFs, revocaciones y cambios en la configuración.
- Permite filtrar por usuario responsable, tipo de acción y fecha, garantizando transparencia absoluta.

---

## 17. Configuración de Parámetros Globales

> **Acceso:** Menú lateral $\rightarrow$ **"Configuración"** (Exclusivo Administrador).

Permite actualizar la información institucional del Vicedecanato:
- Nombre de la Universidad (ej. `UNIVERSIDAD MAYOR DE SAN ANDRÉS`).
- Nombre de la Facultad (ej. `FACULTAD DE CIENCIAS PURAS Y NATURALES`).
- Unidad emisora (ej. `VICEDECANATO`).
- Autoridad en ejercicio y cargo oficial.
- Ciudad y País.
- Años de vigencia legal estándar (`3`).

---

## 18. Preguntas Frecuentes (FAQ)

**¿Qué sucede si modifico una plantilla después de haber emitido 100 certificados?**  
Gracias al motor de *Snapshot Documental Inmutable*, los 100 certificados ya emitidos conservarán de por vida su diseño, colores, firmas y textos originales. Las modificaciones de la plantilla aplicarán exclusivamente a las emisiones que se realicen a partir de ese momento.

**¿Puedo previsualizar cómo se verá un certificado antes de emitirlo?**  
Sí. En el formulario de emisión individual, pulse el botón `[ 👁️ PREVISUALIZAR ]`. Esto abrirá una vista previa en PDF con los datos reales del participante sin guardar nada en la base de datos.

**¿El código QR expone información privada sensible del beneficiario?**  
No. El código QR almacena únicamente una dirección web segura protegida con un token UUID criptográfico. La página pública muestra únicamente el nombre, la actividad académica y el estado de vigencia, resguardando la privacidad de datos personales.

**¿Se pueden emitir certificados duplicados para un mismo evento?**  
No. El sistema cuenta con validaciones que impiden emitir dos veces un certificado del mismo tipo a un mismo beneficiario dentro de una misma actividad académica.
