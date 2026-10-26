<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class BeneficiaryImportService
{
    /**
     * Sinónimos reconocidos para el mapeo inteligente de columnas (sin distinción de mayúsculas/minúsculas ni tildes).
     */
    protected array $headerSynonyms = [
        'document_number' => [
            'ci', 'c.i.', 'carnet', 'documento', 'cedula', 'cédula', 'dni',
            'nro_documento', 'numero_documento', 'número_documento', 'ci_estudiante',
            'identificacion', 'identificación', 'num_doc',
        ],
        'first_name' => [
            'nombre', 'nombres', 'first_name', 'nombres_estudiante', 'nombre_completo',
            'nombres_participante', 'estudiante', 'alumno', 'participante', 'beneficiario', 'postulante',
        ],
        'paterno' => [
            'paterno', 'apellido_paterno', 'primer_apellido',
        ],
        'materno' => [
            'materno', 'apellido_materno', 'segundo_apellido',
        ],
        'last_name' => [
            'apellido', 'apellidos', 'last_name', 'apellidos_estudiante',
        ],
        'academic_title' => [
            'titulo', 'título', 'grado', 'profesion', 'profesión', 'academico', 'académico',
            'prefijo', 'academic_title',
        ],
        'institution_or_department' => [
            'carrera', 'departamento', 'unidad', 'facultad', 'institucion', 'institución',
            'mencion', 'mención', 'programa', 'department', 'area', 'área',
        ],
        'email' => [
            'correo', 'email', 'e-mail', 'correo_electronico', 'correo_electrónico',
            'correo_institucional', 'mail', 'gmail',
        ],
        'phone' => [
            'telefono', 'teléfono', 'celular', 'movil', 'móvil', 'phone', 'contacto', 'whatsapp', 'fono', 'cel',
        ],
    ];

    /**
     * Analiza el archivo subido (.csv o .xlsx) y genera el diagnóstico previo de importación.
     */
    public function parseAndPreview(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rawRows = match ($extension) {
            'csv', 'txt' => $this->parseCsv($file->getRealPath()),
            'xlsx' => $this->parseXlsx($file->getRealPath()),
            default => throw new \InvalidArgumentException("Formato no soportado (.{$extension}). Utilice archivos .xlsx o .csv."),
        };

        if (empty($rawRows)) {
            return [
                'headers' => [],
                'mapped_columns' => [],
                'rows' => [],
                'summary' => [
                    'total' => 0,
                    'ready' => 0,
                    'duplicate_skip' => 0,
                    'error' => 0,
                ],
                'error_message' => 'El archivo no contiene registros o está vacío.',
            ];
        }

        // 1. La primera fila se asume como encabezados
        $rawHeaders = array_shift($rawRows);
        $mappedColumns = $this->mapHeadersToFields($rawHeaders);

        // 2. Extraer CIs existentes en el padrón para detección rápida en memoria
        $existingCis = Beneficiary::pluck('document_number')
            ->map(fn ($doc) => $this->normalizeDocumentNumber($doc))
            ->flip()
            ->toArray();

        $processedRows = [];
        $readyCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        $seenInFile = [];

        foreach ($rawRows as $index => $row) {
            // Omitir filas completamente vacías
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $extracted = $this->extractRowData($row, $mappedColumns);
            $docNumber = $this->normalizeDocumentNumber($extracted['document_number'] ?? '');

            $rowStatus = 'ready';
            $errorMsg = null;

            // Validación de campos mínimos obligatorios
            if (empty($docNumber)) {
                $rowStatus = 'error';
                $errorMsg = 'Falta el número de documento / CI.';
            } elseif (empty($extracted['first_name'])) {
                $rowStatus = 'error';
                $errorMsg = 'Falta el nombre del estudiante.';
            } elseif (empty($extracted['last_name'])) {
                $rowStatus = 'error';
                $errorMsg = 'Falta el apellido del estudiante.';
            } elseif (isset($existingCis[$docNumber])) {
                // Ya existe en la base de datos -> Omitir por defecto
                $rowStatus = 'duplicate_skip';
                $errorMsg = 'Ya registrado previamente en el padrón (se omitirá).';
            } elseif (isset($seenInFile[$docNumber])) {
                // Duplicado dentro del mismo archivo Excel
                $rowStatus = 'duplicate_skip';
                $errorMsg = 'CI repetido dentro del mismo archivo (se omitirá la repetición).';
            }

            if ($rowStatus === 'ready') {
                $seenInFile[$docNumber] = true;
                $readyCount++;
            } elseif ($rowStatus === 'duplicate_skip') {
                $duplicateCount++;
            } else {
                $errorCount++;
            }

            $processedRows[] = [
                'row_number' => $index + 2, // Fila humana en Excel (1=cabecera, 2=primer dato)
                'data' => $extracted,
                'status' => $rowStatus,
                'message' => $errorMsg,
            ];
        }

        return [
            'headers' => $rawHeaders,
            'mapped_columns' => $mappedColumns,
            'rows' => $processedRows,
            'summary' => [
                'total' => count($processedRows),
                'ready' => $readyCount,
                'duplicate_skip' => $duplicateCount,
                'error' => $errorCount,
            ],
            'can_import' => $readyCount > 0,
        ];
    }

    /**
     * Ejecuta la inserción definitiva de los beneficiarios validados en base de datos bajo transacción.
     */
    public function executeImport(array $rows, User $user): array
    {
        return DB::transaction(function () use ($rows, $user) {
            $createdCount = 0;
            $skippedCount = 0;
            $createdBeneficiaries = [];

            // Obtener CIs actuales para garantizar idempotencia durante la transacción
            $existingCis = Beneficiary::pluck('document_number')
                ->map(fn ($doc) => $this->normalizeDocumentNumber($doc))
                ->flip()
                ->toArray();

            foreach ($rows as $item) {
                if (($item['status'] ?? '') !== 'ready') {
                    $skippedCount++;

                    continue;
                }

                $data = $item['data'] ?? [];
                $docNumber = $this->normalizeDocumentNumber($data['document_number'] ?? '');

                if (empty($docNumber) || empty($data['first_name']) || empty($data['last_name'])) {
                    $skippedCount++;

                    continue;
                }

                if (isset($existingCis[$docNumber])) {
                    $skippedCount++;

                    continue;
                }

                $beneficiary = Beneficiary::create([
                    'document_type' => $data['document_type'] ?? 'CI',
                    'document_number' => $docNumber,
                    'first_name' => trim($data['first_name']),
                    'last_name' => trim($data['last_name']),
                    'academic_title' => ! empty($data['academic_title']) ? trim($data['academic_title']) : 'Univ.',
                    'institution_or_department' => ! empty($data['institution_or_department']) ? trim($data['institution_or_department']) : 'Facultad de Tecnología - UMSA',
                    'email' => ! empty($data['email']) ? strtolower(trim($data['email'])) : null,
                    'phone' => ! empty($data['phone']) ? trim($data['phone']) : null,
                    'notes' => 'Registrado mediante importación masiva el '.now()->format('d/m/Y H:i'),
                ]);

                $existingCis[$docNumber] = true;
                $createdBeneficiaries[] = $beneficiary;
                $createdCount++;
            }

            // Registrar en la bitácora oficial de auditoría institucional
            AuditLog::log(
                'beneficiaries_bulk_imported',
                "Importación masiva: {$createdCount} nuevos beneficiarios registrados en el padrón, {$skippedCount} omitidos.",
                null,
                null,
                [
                    'created_count' => $createdCount,
                    'skipped_count' => $skippedCount,
                    'imported_by_user_id' => $user->id,
                ]
            );

            return [
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'total_processed' => count($rows),
            ];
        });
    }

    /**
     * Mapea inteligentemente las cabeceras leídas contra los atributos del modelo Beneficiary.
     */
    public function mapHeadersToFields(array $headers): array
    {
        $mapped = [];

        foreach ($headers as $colIndex => $headerText) {
            $cleanHeader = $this->sanitizeString($headerText);
            $matchedField = null;

            foreach ($this->headerSynonyms as $field => $synonyms) {
                foreach ($synonyms as $synonym) {
                    $cleanSynonym = $this->sanitizeString($synonym);
                    if ($cleanHeader === $cleanSynonym || str_contains($cleanHeader, $cleanSynonym)) {
                        $matchedField = $field;
                        break 2;
                    }
                }
            }

            if ($matchedField) {
                $mapped[$matchedField] = $colIndex;
            }
        }

        return $mapped;
    }

    /**
     * Extrae los datos de una fila utilizando las columnas mapeadas.
     */
    protected function extractRowData(array $row, array $mappedColumns): array
    {
        $data = [
            'document_type' => 'CI',
            'document_number' => '',
            'first_name' => '',
            'last_name' => '',
            'academic_title' => 'Univ.',
            'institution_or_department' => 'Facultad de Tecnología - UMSA',
            'email' => null,
            'phone' => null,
        ];

        foreach ($mappedColumns as $field => $colIndex) {
            if (isset($row[$colIndex])) {
                $val = trim((string) $row[$colIndex]);
                if ($val !== '') {
                    $data[$field] = $val;
                }
            }
        }

        // Si el archivo viene con columnas separadas de Paterno y/o Materno
        if (isset($data['paterno']) || isset($data['materno'])) {
            $paterno = trim((string) ($data['paterno'] ?? ''));
            $materno = trim((string) ($data['materno'] ?? ''));
            $combined = trim($paterno.' '.$materno);
            if ($combined !== '') {
                $data['last_name'] = $combined;
            }
            unset($data['paterno'], $data['materno']);
        }

        // Si el archivo contiene el nombre completo en una sola columna y no vino columna separada de apellidos
        if (empty($data['last_name']) && ! empty($data['first_name'])) {
            [$firstName, $lastName] = $this->splitFullName($data['first_name']);
            $data['first_name'] = $firstName;
            $data['last_name'] = $lastName;
        }

        return $data;
    }

    /**
     * Divide inteligentemente un nombre completo en Nombres y Apellidos respetando convenciones hispanas.
     */
    public function splitFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

        if (empty($fullName)) {
            return ['', ''];
        }

        // Si viene con formato "Apellidos, Nombres" (separado por coma)
        if (str_contains($fullName, ',')) {
            $parts = explode(',', $fullName, 2);
            $lastName = trim($parts[0]);
            $firstName = trim($parts[1]);

            return [$firstName ?: $lastName, $lastName ?: '-'];
        }

        $words = explode(' ', $fullName);
        $totalWords = count($words);

        if ($totalWords === 1) {
            return [$words[0], '-'];
        }

        if ($totalWords === 2) {
            // Ejemplo: Juan Pérez -> Nombres: Juan, Apellidos: Pérez
            return [$words[0], $words[1]];
        }

        if ($totalWords === 3) {
            // Ejemplo: Juan Pérez Choque -> Nombres: Juan, Apellidos: Pérez Choque
            return [$words[0], $words[1].' '.$words[2]];
        }

        // 4 o más palabras: por estándar en Bolivia / Latinoamérica, los últimos 2 son los apellidos (Paterno y Materno)
        // Ejemplo: Ana Lucía Quispe Mamani -> Nombres: Ana Lucía, Apellidos: Quispe Mamani
        $lastName = implode(' ', array_slice($words, -2));
        $firstName = implode(' ', array_slice($words, 0, $totalWords - 2));

        return [$firstName, $lastName];
    }

    /**
     * Parser nativo para archivos CSV con detección de delimitador (, ; \t) y codificación UTF-8.
     */
    public function parseCsv(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [];
        }

        $rawContent = file_get_contents($filePath);
        if ($rawContent === false || trim($rawContent) === '') {
            return [];
        }

        // Eliminar BOM UTF-8 (\xEF\xBB\xBF) si está presente
        if (str_starts_with($rawContent, "\xEF\xBB\xBF")) {
            $rawContent = substr($rawContent, 3);
        }

        // Asegurar codificación UTF-8 limpia si viniera en Windows-1252 o ISO-8859-1
        if (! mb_check_encoding($rawContent, 'UTF-8')) {
            $rawContent = mb_convert_encoding($rawContent, 'UTF-8', ['ISO-8859-1', 'Windows-1252', 'ASCII']);
        }

        // Detectar delimitador inspeccionando las primeras líneas
        $firstLines = explode("\n", substr($rawContent, 0, 4096));
        $delimiter = ',';
        $candidates = [',', ';', "\t"];
        $maxCount = 0;

        foreach ($candidates as $cand) {
            $count = substr_count($firstLines[0] ?? '', $cand);
            if ($count > $maxCount) {
                $maxCount = $count;
                $delimiter = $cand;
            }
        }

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $rawContent);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
            $cleaned = array_map(fn ($item) => is_string($item) ? trim($item) : '', $row);
            $rows[] = $cleaned;
        }

        fclose($stream);

        return $rows;
    }

    /**
     * Parser nativo para archivos Excel (.xlsx) usando ZipArchive y SimpleXML de PHP 8.3.
     */
    public function parseXlsx(string $filePath): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('La extensión PHP ZipArchive no está disponible.');
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo Excel (.xlsx). Compruebe que no esté dañado.');
        }

        // 1. Leer tabla de cadenas compartidas (sharedStrings.xml) si existe
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Leer la primera hoja de cálculo (sheet1.xml)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Intentar buscar la primera hoja disponible
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (preg_match('#xl/worksheets/sheet\d+\.xml#', $stat['name'])) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false || ! isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $currentCol = 0;

            foreach ($row->c as $cell) {
                // Calcular índice de columna a partir de referencia tipo 'A1', 'B1', etc.
                $cellRef = (string) $cell['r'];
                $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                $targetCol = $this->columnIndexFromLetters($colLetters);

                // Rellenar celdas intermedias vacías si las hubiera
                while ($currentCol < $targetCol) {
                    $rowData[] = '';
                    $currentCol++;
                }

                $type = (string) $cell['t'];
                $val = isset($cell->v) ? (string) $cell->v : '';

                if ($type === 's' && isset($sharedStrings[(int) $val])) {
                    $val = $sharedStrings[(int) $val];
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $val = (string) $cell->is->t;
                }

                $rowData[] = trim($val);
                $currentCol++;
            }

            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Convierte letras de columna de Excel (A, B, AA, etc.) a índice base cero.
     */
    protected function columnIndexFromLetters(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $col = 0;
        for ($i = 0; $i < $len; $i++) {
            $col = $col * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $col - 1);
    }

    /**
     * Genera el contenido de una plantilla CSV oficial con datos de ejemplo de la FT-UMSA.
     */
    public function generateTemplateCsv(): string
    {
        $headers = ['CI', 'NOMBRES', 'APELLIDOS', 'TITULO_GRADO', 'CARRERA_UNIDAD', 'CORREO_ELECTRONICO', 'TELEFONO'];
        $sampleRows = [
            ['8492019', 'Juan Carlos', 'Mamani Quispe', 'Univ.', 'Licenciatura en Informática', 'juan.mamani@umsa.bo', '71234567'],
            ['6948201', 'Carla Patricia', 'Flores Choque', 'Univ.', 'Ingeniería Electrónica', 'carla.flores@umsa.bo', '76543210'],
            ['5123940', 'Rodrigo Fernando', 'Gutiérrez Condori', 'Lic.', 'Química Industrial', 'rodrigo.gutierrez@umsa.bo', '70112233'],
            ['7234912', 'Elena Beatriz', 'Ramos Fernández', 'Univ.', 'Mecánica Automotriz', 'elena.ramos@umsa.bo', '72098765'],
        ];

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM para que Microsoft Excel lo abra con tildes y caracteres en español
        $stream = fopen('php://memory', 'r+');

        fputcsv($stream, $headers, ';');
        foreach ($sampleRows as $row) {
            fputcsv($stream, $row, ';');
        }

        rewind($stream);
        $output .= stream_get_contents($stream);
        fclose($stream);

        return $output;
    }

    /**
     * Normaliza el carnet de identidad eliminando espacios redundantes y homogeneizando a mayúsculas.
     */
    public function normalizeDocumentNumber(string $doc): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($doc));

        return strtoupper($clean);
    }

    /**
     * Sanea un texto para comparaciones flexibles (elimina tildes, caracteres especiales y espacios extras).
     */
    protected function sanitizeString(string $str): string
    {
        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ñ' => 'n', 'Ñ' => 'n', 'ü' => 'u', 'Ü' => 'u',
        ];
        $str = strtr($str, $unwanted);
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9]/', '', $str);

        return trim($str);
    }

    /**
     * Verifica si una fila está completamente vacía.
     */
    protected function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
