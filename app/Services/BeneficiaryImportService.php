<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateType;
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
            'programa', 'department', 'area', 'área',
        ],
        'email' => [
            'correo', 'email', 'e-mail', 'correo_electronico', 'correo_electrónico',
            'correo_institucional', 'mail', 'gmail',
        ],
        'phone' => [
            'telefono', 'teléfono', 'celular', 'movil', 'móvil', 'phone', 'contacto', 'whatsapp', 'fono', 'cel',
        ],
        'score' => [
            'nota', 'puntaje', 'calificacion', 'calificación', 'score', 'puntos', 'grade',
            'promedio', 'evaluacion', 'evaluación', 'nota_final', 'puntaje_final', 'nota_examen',
        ],
        'score_label' => [
            'mencion', 'mención', 'mencion_honor', 'mención_honor', 'distincion', 'distinción', 'honor', 'estado_academico',
            'observacion_nota', 'calificacion_cualitativa', 'merito', 'mérito', 'mencion_academica',
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
     * Analiza una nómina (.xlsx o .csv) en el contexto de una Actividad Académica específica.
     * Clasifica a cada estudiante en:
     * - new_student: No existe en la base de datos (se creará en el padrón y se emitirá su certificado).
     * - existing_student: Ya registrado en el padrón (se asociará a la actividad y se emitirá su certificado).
     * - already_certified: Ya cuenta con un certificado activo en esta actividad (se omitirá para evitar duplicados).
     * - error: Registro incompleto o inválido (falta CI o nombre).
     */
    public function parseAndPreviewForActivity(UploadedFile $file, Activity $activity, ?int $certificateTypeId = null): array
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
                    'new_students' => 0,
                    'existing_students' => 0,
                    'already_certified' => 0,
                    'errors' => 0,
                    'ready_to_certify' => 0,
                ],
                'error_message' => 'El archivo no contiene registros o está vacío.',
            ];
        }

        // 1. Cabeceras
        $rawHeaders = array_shift($rawRows);
        $mappedColumns = $this->mapHeadersToFields($rawHeaders);

        // 2. Beneficiarios existentes indexados por CI normalizado -> id del beneficiario
        $existingBeneficiariesByCi = Beneficiary::all()->mapWithKeys(function ($ben) {
            return [$this->normalizeDocumentNumber($ben->document_number) => $ben->id];
        })->toArray();

        // 3. Beneficiarios que YA tienen certificado activo en ESTA actividad
        $certQuery = Certificate::where('activity_id', $activity->id)
            ->whereNull('revoked_at');
        if ($certificateTypeId) {
            $certQuery->where('certificate_type_id', $certificateTypeId);
        }
        $alreadyCertifiedBeneficiaryIds = $certQuery->pluck('beneficiary_id')
            ->flip()
            ->toArray();

        $certType = $certificateTypeId ? CertificateType::find($certificateTypeId) : $activity->certificateType;
        $hasScoreRule = $certType && $certType->supportsScore();
        $minScore = (float) ($certType?->min_passing_score ?? 51.0);

        $processedRows = [];
        $newStudentsCount = 0;
        $existingStudentsCount = 0;
        $alreadyCertifiedCount = 0;
        $failingStudentsCount = 0;
        $unscoredStudentsCount = 0;
        $errorCount = 0;
        $seenInFile = [];

        foreach ($rawRows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $extracted = $this->extractRowData($row, $mappedColumns);
            $docNumber = $this->normalizeDocumentNumber($extracted['document_number'] ?? '');

            $rowStatus = 'new_student';
            $errorMsg = null;
            $existingBenId = null;

            if (empty($docNumber)) {
                $rowStatus = 'error';
                $errorMsg = 'Falta el número de documento / CI.';
            } elseif (empty($extracted['first_name'])) {
                $rowStatus = 'error';
                $errorMsg = 'Falta el nombre del estudiante.';
            } elseif (empty($extracted['last_name'])) {
                $rowStatus = 'error';
                $errorMsg = 'Falta el apellido del estudiante.';
            } elseif (isset($seenInFile[$docNumber])) {
                // Repetido en el mismo archivo
                $rowStatus = 'already_certified';
                $errorMsg = 'CI repetido dentro del mismo archivo (se omitirá la repetición).';
            } elseif (isset($existingBeneficiariesByCi[$docNumber])) {
                // El estudiante YA existe en el padrón
                $existingBenId = $existingBeneficiariesByCi[$docNumber];
                if (isset($alreadyCertifiedBeneficiaryIds[$existingBenId])) {
                    // Ya tiene certificado en este evento
                    $rowStatus = 'already_certified';
                    $errorMsg = 'Ya posee certificado emitido en esta actividad (se omitirá automáticamente).';
                } else {
                    // Existe en padrón, pero es nuevo en este evento
                    $rowStatus = 'existing_student';
                    $errorMsg = 'Registrado en padrón. Se asociará a la actividad y se emitirá su certificado.';
                }
            } else {
                // Nuevo estudiante completo
                $rowStatus = 'new_student';
                $errorMsg = 'Nuevo estudiante. Se registrará en el padrón y se emitirá su certificado.';
            }

            // Validación de aprobación si el tipo de certificado evalúa notas
            if ($hasScoreRule && ($rowStatus === 'new_student' || $rowStatus === 'existing_student')) {
                $rawScore = $extracted['score'] ?? null;
                $scoreNum = ($rawScore !== null && $rawScore !== '') ? (float) $rawScore : null;

                if ($scoreNum === null) {
                    $rowStatus = 'unscored_student';
                    $errorMsg = 'Sin calificación registrada. Se agregará a la nómina pero no califica para emisión de certificado hasta calificar.';
                } elseif ($scoreNum < $minScore) {
                    $rowStatus = 'failing_student';
                    $errorMsg = "Estudiante reprobado ({$scoreNum} pts < {$minScore} pts). Se registrará en la nómina como observado sin emitir certificado.";
                }
            }

            if ($rowStatus === 'new_student') {
                $seenInFile[$docNumber] = true;
                $newStudentsCount++;
            } elseif ($rowStatus === 'existing_student') {
                $seenInFile[$docNumber] = true;
                $existingStudentsCount++;
            } elseif ($rowStatus === 'failing_student') {
                $seenInFile[$docNumber] = true;
                $failingStudentsCount++;
            } elseif ($rowStatus === 'unscored_student') {
                $seenInFile[$docNumber] = true;
                $unscoredStudentsCount++;
            } elseif ($rowStatus === 'already_certified') {
                $alreadyCertifiedCount++;
            } else {
                $errorCount++;
            }

            $processedRows[] = [
                'row_number' => $index + 2,
                'data' => $extracted,
                'status' => $rowStatus,
                'beneficiary_id' => $existingBenId,
                'message' => $errorMsg,
            ];
        }

        $readyToCertify = $newStudentsCount + $existingStudentsCount;

        return [
            'headers' => $rawHeaders,
            'mapped_columns' => $mappedColumns,
            'rows' => $processedRows,
            'summary' => [
                'total' => count($processedRows),
                'new_students' => $newStudentsCount,
                'existing_students' => $existingStudentsCount,
                'already_certified' => $alreadyCertifiedCount,
                'failing_students' => $failingStudentsCount,
                'unscored_students' => $unscoredStudentsCount,
                'errors' => $errorCount,
                'ready_to_certify' => $readyToCertify,
            ],
            'can_import' => $readyToCertify > 0,
        ];
    }

    /**
     * Ejecuta la emisión masiva bajo transacción:
     * - Crea a los nuevos beneficiarios en el padrón general
     * - Emite certificados tanto a los nuevos como a los existentes
     * - Omite a los ya certificados y filas con error
     */
    public function executeActivityBulkIssuance(
        array $rows,
        Activity $activity,
        int $certificateTypeId,
        ?int $templateId,
        string $issueDate,
        User $user,
        CertificateService $certificateService
    ): array {
        return DB::transaction(function () use ($rows, $activity, $certificateTypeId, $templateId, $issueDate, $user, $certificateService) {
            $issuedCount = 0;
            $newStudentsCount = 0;
            $existingStudentsCount = 0;
            $skippedCount = 0;
            $failingCount = 0;
            $unscoredCount = 0;

            $certType = CertificateType::find($certificateTypeId);
            $hasScoreRule = $certType && $certType->supportsScore();
            $minScore = (float) ($certType?->min_passing_score ?? 51.0);

            // Cache de beneficiarios existentes
            $existingBeneficiariesByCi = Beneficiary::all()->mapWithKeys(function ($ben) {
                return [$this->normalizeDocumentNumber($ben->document_number) => $ben];
            })->all();

            // Certificados existentes en la actividad
            $alreadyCertifiedBeneficiaryIds = Certificate::where('activity_id', $activity->id)
                ->where('certificate_type_id', $certificateTypeId)
                ->whereNull('revoked_at')
                ->pluck('beneficiary_id')
                ->flip()
                ->toArray();

            foreach ($rows as $item) {
                $status = $item['status'] ?? '';
                if ($status !== 'new_student' && $status !== 'existing_student') {
                    $skippedCount++;

                    continue;
                }

                $data = $item['data'] ?? [];
                $docNumber = $this->normalizeDocumentNumber($data['document_number'] ?? '');

                if (empty($docNumber) || empty($data['first_name']) || empty($data['last_name'])) {
                    $skippedCount++;

                    continue;
                }

                // 1. Obtener o crear el Beneficiario
                $beneficiary = null;
                if ($status === 'existing_student' && isset($existingBeneficiariesByCi[$docNumber])) {
                    $beneficiary = $existingBeneficiariesByCi[$docNumber];
                } elseif (isset($existingBeneficiariesByCi[$docNumber])) {
                    // Por si acaso ya existía en BD aunque haya llegado como new_student
                    $beneficiary = $existingBeneficiariesByCi[$docNumber];
                } else {
                    $beneficiary = Beneficiary::create([
                        'document_type' => $data['document_type'] ?? 'CI',
                        'document_number' => $docNumber,
                        'first_name' => trim($data['first_name']),
                        'last_name' => trim($data['last_name']),
                        'academic_title' => ! empty($data['academic_title']) ? trim($data['academic_title']) : 'Univ.',
                        'institution_or_department' => ! empty($data['institution_or_department']) ? trim($data['institution_or_department']) : 'Facultad de Tecnología - UMSA',
                        'email' => ! empty($data['email']) ? strtolower(trim($data['email'])) : null,
                        'phone' => ! empty($data['phone']) ? trim($data['phone']) : null,
                        'notes' => 'Registrado vía nómina masiva de actividad: '.$activity->name.' ('.now()->format('d/m/Y H:i').')',
                    ]);

                    $existingBeneficiariesByCi[$docNumber] = $beneficiary;
                    $newStudentsCount++;
                }

                if (! $beneficiary) {
                    $skippedCount++;

                    continue;
                }

                if ($status === 'existing_student') {
                    $existingStudentsCount++;
                }

                // 2. Verificar que no tenga certificado ya emitido para esta actividad
                if (isset($alreadyCertifiedBeneficiaryIds[$beneficiary->id])) {
                    $skippedCount++;

                    continue;
                }

                // 3. Validación de aprobación reglamentaria: si exige puntaje, omitir a reprobados o sin nota
                $rawScore = $data['score'] ?? null;
                $scoreNum = ($rawScore !== null && $rawScore !== '') ? (float) $rawScore : null;

                if ($hasScoreRule) {
                    if ($scoreNum === null) {
                        $unscoredCount++;
                        $activity->participants()->syncWithoutDetaching([
                            $beneficiary->id => [
                                'role' => 'Participante',
                                'status' => 'observado',
                                'score' => null,
                                'score_label' => $data['score_label'] ?? null,
                            ],
                        ]);

                        continue;
                    }
                    if ($scoreNum < $minScore) {
                        $failingCount++;
                        $activity->participants()->syncWithoutDetaching([
                            $beneficiary->id => [
                                'role' => 'Participante',
                                'status' => 'reprobado',
                                'score' => $scoreNum,
                                'score_label' => $data['score_label'] ?? null,
                            ],
                        ]);

                        continue;
                    }
                }

                // 4. Emitir el certificado institucional con snapshot inmutable y token QR
                $certificateService->issueCertificate([
                    'beneficiary_id' => $beneficiary->id,
                    'activity_id' => $activity->id,
                    'certificate_type_id' => $certificateTypeId,
                    'certificate_template_id' => $templateId,
                    'issue_date' => $issueDate,
                    'score' => $scoreNum,
                    'score_label' => $data['score_label'] ?? null,
                ], $user);

                $activity->participants()->syncWithoutDetaching([
                    $beneficiary->id => [
                        'role' => 'Participante',
                        'status' => 'certificado',
                        'score' => $scoreNum,
                        'score_label' => $data['score_label'] ?? null,
                    ],
                ]);

                $alreadyCertifiedBeneficiaryIds[$beneficiary->id] = true;
                $issuedCount++;
            }

            // Registrar en auditoría
            AuditLog::log(
                'activity_bulk_certified',
                "Emisión masiva en actividad '{$activity->name}': {$issuedCount} certificados emitidos ({$newStudentsCount} nuevos beneficiarios creados en el padrón, {$existingStudentsCount} beneficiarios existentes asociados, {$failingCount} reprobados omitidos, {$skippedCount} omitidos).",
                $activity,
                null,
                [
                    'activity_id' => $activity->id,
                    'activity_name' => $activity->name,
                    'issued_count' => $issuedCount,
                    'new_students_count' => $newStudentsCount,
                    'existing_students_count' => $existingStudentsCount,
                    'skipped_count' => $skippedCount,
                    'failing_count' => $failingCount,
                    'unscored_count' => $unscoredCount,
                    'executed_by_user_id' => $user->id,
                ]
            );

            return [
                'issued_count' => $issuedCount,
                'new_students_count' => $newStudentsCount,
                'existing_students_count' => $existingStudentsCount,
                'skipped_count' => $skippedCount,
                'failing_count' => $failingCount,
                'unscored_count' => $unscoredCount,
                'total_processed' => count($rows),
            ];
        });
    }

    /**
     * Registra a los participantes de una nómina en la lista de la actividad (activity_participants)
     * sin emitir certificados aún.
     */
    public function enrollParticipantsFromRows(array $rows, Activity $activity, User $user): array
    {
        return DB::transaction(function () use ($rows, $activity) {
            $enrolledCount = 0;
            $newStudentsCount = 0;
            $alreadyEnrolledCount = 0;
            $errorCount = 0;

            $certType = $activity->certificateType;
            $hasScoreRule = $certType && $certType->supportsScore();
            $minScore = (float) ($certType?->min_passing_score ?? 51.0);

            $existingBeneficiariesByCi = Beneficiary::all()->mapWithKeys(function ($ben) {
                return [$this->normalizeDocumentNumber($ben->document_number) => $ben];
            })->all();

            $currentlyEnrolledIds = $activity->participants()->pluck('beneficiaries.id')->flip()->toArray();

            foreach ($rows as $item) {
                $data = $item['data'] ?? [];
                $docNumber = $this->normalizeDocumentNumber($data['document_number'] ?? '');

                if (empty($docNumber) || empty($data['first_name']) || empty($data['last_name'])) {
                    $errorCount++;

                    continue;
                }

                $beneficiary = null;
                if (isset($existingBeneficiariesByCi[$docNumber])) {
                    $beneficiary = $existingBeneficiariesByCi[$docNumber];
                } else {
                    $beneficiary = Beneficiary::create([
                        'document_type' => $data['document_type'] ?? 'CI',
                        'document_number' => $docNumber,
                        'first_name' => trim($data['first_name']),
                        'last_name' => trim($data['last_name']),
                        'academic_title' => ! empty($data['academic_title']) ? trim($data['academic_title']) : 'Univ.',
                        'institution_or_department' => ! empty($data['institution_or_department']) ? trim($data['institution_or_department']) : 'Facultad de Tecnología - UMSA',
                        'email' => ! empty($data['email']) ? strtolower(trim($data['email'])) : null,
                        'phone' => ! empty($data['phone']) ? trim($data['phone']) : null,
                        'notes' => 'Registrado vía nómina de actividad: '.$activity->name.' ('.now()->format('d/m/Y H:i').')',
                    ]);

                    $existingBeneficiariesByCi[$docNumber] = $beneficiary;
                    $newStudentsCount++;
                }

                $rawScore = $data['score'] ?? null;
                $scoreNum = ($rawScore !== null && $rawScore !== '') ? (float) $rawScore : null;
                $isFailing = $hasScoreRule && ($scoreNum !== null && $scoreNum < $minScore);

                if (isset($currentlyEnrolledIds[$beneficiary->id])) {
                    if (isset($data['score']) || isset($data['score_label'])) {
                        $updateData = array_filter([
                            'score' => $scoreNum,
                            'score_label' => ! empty($data['score_label']) ? trim((string) $data['score_label']) : null,
                        ], fn ($v) => $v !== null);

                        if ($isFailing) {
                            $updateData['status'] = 'reprobado';
                        }
                        $activity->participants()->updateExistingPivot($beneficiary->id, $updateData);
                    }
                    $alreadyEnrolledCount++;

                    continue;
                }

                // Verificar si ya cuenta con certificado emitido previamente en esta actividad
                $hasCert = Certificate::where('activity_id', $activity->id)
                    ->where('beneficiary_id', $beneficiary->id)
                    ->whereNull('revoked_at')
                    ->exists();

                $participantStatus = $hasCert ? 'certificado' : ($isFailing ? 'reprobado' : 'inscrito');

                $activity->participants()->attach($beneficiary->id, [
                    'role' => 'Participante',
                    'status' => $participantStatus,
                    'score' => $scoreNum,
                    'score_label' => ! empty($data['score_label']) ? trim((string) $data['score_label']) : null,
                ]);

                $currentlyEnrolledIds[$beneficiary->id] = true;
                $enrolledCount++;
            }

            AuditLog::log(
                'activity_participants_enrolled',
                "Nómina de participantes actualizada en '{$activity->name}': {$enrolledCount} participantes agregados ({$newStudentsCount} nuevos en padrón, {$alreadyEnrolledCount} ya estaban en la lista).",
                $activity
            );

            return [
                'enrolled_count' => $enrolledCount,
                'new_students_count' => $newStudentsCount,
                'already_enrolled_count' => $alreadyEnrolledCount,
                'error_count' => $errorCount,
                'total_processed' => count($rows),
            ];
        });
    }

    /**
     * Emite masivamente los certificados para los participantes registrados en la nómina de la actividad
     * que se encuentren pendientes de certificado, omitiendo automáticamente a estudiantes reprobados.
     */
    public function issueCertificatesToActivityParticipants(
        Activity $activity,
        int $certificateTypeId,
        ?int $templateId,
        string $issueDate,
        User $user,
        CertificateService $certificateService,
        ?array $selectedBeneficiaryIds = null
    ): array {
        return DB::transaction(function () use ($activity, $certificateTypeId, $templateId, $issueDate, $user, $certificateService, $selectedBeneficiaryIds) {
            $query = $activity->participants();
            if (! empty($selectedBeneficiaryIds)) {
                $query->whereIn('beneficiaries.id', $selectedBeneficiaryIds);
            }
            $participants = $query->get();

            $certType = CertificateType::find($certificateTypeId);
            $hasScoreRule = $certType && $certType->supportsScore();
            $minScore = (float) ($certType?->min_passing_score ?? 51.0);

            $issuedCount = 0;
            $skippedCount = 0;
            $failingCount = 0;
            $unscoredCount = 0;

            $alreadyCertifiedBeneficiaryIds = Certificate::where('activity_id', $activity->id)
                ->where('certificate_type_id', $certificateTypeId)
                ->whereNull('revoked_at')
                ->pluck('beneficiary_id')
                ->flip()
                ->toArray();

            foreach ($participants as $participant) {
                if (isset($alreadyCertifiedBeneficiaryIds[$participant->id])) {
                    $skippedCount++;
                    $activity->participants()->updateExistingPivot($participant->id, ['status' => 'certificado']);

                    continue;
                }

                $score = $participant->pivot->score !== null ? (float) $participant->pivot->score : null;

                // Validación de aprobación reglamentaria: si el tipo exige puntaje, omitir reprobados o sin nota
                if ($hasScoreRule) {
                    if ($score === null) {
                        $unscoredCount++;
                        $activity->participants()->updateExistingPivot($participant->id, ['status' => 'observado']);

                        continue;
                    }
                    if ($score < $minScore) {
                        $failingCount++;
                        $activity->participants()->updateExistingPivot($participant->id, ['status' => 'reprobado']);

                        continue;
                    }
                }

                $certificateService->issueCertificate([
                    'beneficiary_id' => $participant->id,
                    'activity_id' => $activity->id,
                    'certificate_type_id' => $certificateTypeId,
                    'certificate_template_id' => $templateId,
                    'issue_date' => $issueDate,
                    'score' => $score,
                    'score_label' => $participant->pivot->score_label ?? null,
                ], $user);

                $activity->participants()->updateExistingPivot($participant->id, ['status' => 'certificado']);
                $alreadyCertifiedBeneficiaryIds[$participant->id] = true;
                $issuedCount++;
            }

            AuditLog::log(
                'activity_bulk_certified',
                "Emisión masiva a la nómina de '{$activity->name}': {$issuedCount} certificados emitidos ({$failingCount} reprobados omitidos, {$skippedCount} ya contaban con certificado).",
                $activity
            );

            return [
                'issued_count' => $issuedCount,
                'skipped_count' => $skippedCount,
                'failing_count' => $failingCount,
                'unscored_count' => $unscoredCount,
                'total_candidates' => $participants->count(),
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

            // 1. Primero intentar coincidencia exacta
            foreach ($this->headerSynonyms as $field => $synonyms) {
                foreach ($synonyms as $synonym) {
                    $cleanSynonym = $this->sanitizeString($synonym);
                    if ($cleanHeader === $cleanSynonym) {
                        $matchedField = $field;
                        break 2;
                    }
                }
            }

            // 2. Segundo intento: coincidencia de palabra completa o subcadena segura (mínimo 4 caracteres)
            if (! $matchedField) {
                foreach ($this->headerSynonyms as $field => $synonyms) {
                    foreach ($synonyms as $synonym) {
                        $cleanSynonym = $this->sanitizeString($synonym);
                        if (strlen($cleanSynonym) >= 4 && str_contains($cleanHeader, $cleanSynonym)) {
                            $matchedField = $field;
                            break 2;
                        } elseif (preg_match('/(^|_|\s)'.preg_quote($cleanSynonym, '/').'($|_|\s)/', $cleanHeader)) {
                            $matchedField = $field;
                            break 2;
                        }
                    }
                }
            }

            // No sobreescribir un campo que ya fue asignado por una columna previa más específica
            if ($matchedField && ! isset($mapped[$matchedField])) {
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
            'score' => null,
            'score_label' => null,
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

        // Procesar y normalizar calificación/puntaje si existe en el archivo
        if (isset($data['score'])) {
            $rawScore = trim((string) $data['score']);
            if ($rawScore !== '') {
                // Verificar si contiene número (ej. "85", "92.5", "85/100")
                if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)/', $rawScore, $matches)) {
                    $numericVal = (float) str_replace(',', '.', $matches[1]);
                    $data['score'] = $numericVal;
                    // Si no había score_label explícito, usar el texto raw si aporta contexto (ej. "85/100")
                    if (empty($data['score_label']) && str_contains($rawScore, '/')) {
                        $data['score_label'] = $rawScore;
                    }
                } else {
                    // Si el contenido era puramente texto cualitativo (ej. "Aprobado con Excelencia")
                    if (empty($data['score_label'])) {
                        $data['score_label'] = $rawScore;
                    }
                    $data['score'] = null;
                }
            } else {
                $data['score'] = null;
            }
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
        $headers = ['CI', 'NOMBRES', 'APELLIDOS', 'TITULO_GRADO', 'CARRERA_UNIDAD', 'CORREO_ELECTRONICO', 'TELEFONO', 'NOTA', 'MENCION'];
        $sampleRows = [
            ['8492019', 'Juan Carlos', 'Mamani Quispe', 'Univ.', 'Licenciatura en Informática', 'juan.mamani@umsa.bo', '71234567', '92.5', 'Aprobado con Excelencia'],
            ['6948201', 'Carla Patricia', 'Flores Choque', 'Univ.', 'Ingeniería Electrónica', 'carla.flores@umsa.bo', '76543210', '85.0', 'Aprobado'],
            ['5123940', 'Rodrigo Fernando', 'Gutiérrez Condori', 'Lic.', 'Química Industrial', 'rodrigo.gutierrez@umsa.bo', '70112233', '98.0', 'Mención de Honor'],
            ['7234912', 'Elena Beatriz', 'Ramos Fernández', 'Univ.', 'Mecánica Automotriz', 'elena.ramos@umsa.bo', '72098765', '75.0', 'Aprobado'],
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
