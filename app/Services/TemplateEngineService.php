<?php

namespace App\Services;

use Carbon\Carbon;

class TemplateEngineService
{
    /**
     * Reemplaza variables dinámicas en el texto del certificado.
     * Ejemplo: {{beneficiario.nombre}}, {{actividad.horas}}, etc.
     */
    public function interpolate(string $text, array $context): string
    {
        $replacements = [];

        // Beneficiario
        if (isset($context['beneficiary'])) {
            $b = $context['beneficiary'];
            $replacements['{{beneficiario.nombre}}'] = is_array($b) ? ($b['name'] ?? '') : ($b->full_title_name ?? $b->full_name);
            $replacements['{{beneficiario.documento}}'] = is_array($b) ? ($b['document'] ?? '') : ($b->formatted_document ?? $b->document_number);
            $replacements['{{beneficiario.email}}'] = is_array($b) ? ($b['email'] ?? '') : ($b->email ?? '');
        }

        // Actividad
        if (isset($context['activity'])) {
            $a = $context['activity'];
            $replacements['{{actividad.nombre}}'] = is_array($a) ? ($a['name'] ?? '') : $a->name;
            $replacements['{{actividad.tipo}}'] = is_array($a) ? ($a['type'] ?? '') : ($a->activity_type ?? '');
            $replacements['{{actividad.horas}}'] = is_array($a) ? ($a['hours'] ?? '') : ($a->academic_hours ?? 0);
            $replacements['{{actividad.fechas}}'] = is_array($a) ? ($a['dates'] ?? '') : ($a->formatted_date_range ?? '');
            $replacements['{{actividad.lugar}}'] = is_array($a) ? ($a['location'] ?? '') : ($a->location ?? '');
            $replacements['{{actividad.organizador}}'] = is_array($a) ? ($a['organizer'] ?? '') : ($a->organizer ?? 'Vicedecanato');
            $replacements['{{actividad.autoridad}}'] = is_array($a) ? ($a['authority'] ?? '') : ($a->responsible_authority ?? '');
        }

        // Certificado
        if (isset($context['certificate'])) {
            $c = $context['certificate'];
            $rawType = is_array($c) ? ($c['type'] ?? '') : ($c->certificateType->name ?? 'CERTIFICADO');
            $modality = trim(preg_replace('/^Certificado de\s+/i', '', $rawType));
            $role = match (mb_strtolower($modality)) {
                'asistencia' => 'Asistente',
                'aprobación', 'aprobacion' => 'Aprobante',
                'disertante / ponente' => 'Disertante / Ponente',
                'organizador' => 'Organizador',
                default => $modality,
            };

            $rawIssueDate = is_array($c) ? ($c['issue_date'] ?? null) : ($c->issue_date ?? null);
            $parsedDate = Carbon::now();
            if ($rawIssueDate instanceof Carbon) {
                $parsedDate = $rawIssueDate;
            } elseif (is_string($rawIssueDate) && ! empty($rawIssueDate)) {
                try {
                    $parsedDate = Carbon::parse($rawIssueDate);
                } catch (\Throwable) {
                    $parsedDate = Carbon::now();
                }
            }

            $day = $parsedDate->day;
            $month = $parsedDate->translatedFormat('F');
            $year = $parsedDate->year;
            $city = $context['settings']['institution_city'] ?? 'La Paz - Bolivia';

            $replacements['{{certificado.codigo}}'] = is_array($c) ? ($c['code'] ?? '') : ($c->certificate_code ?? '');

            if (is_array($c)) {
                $replacements['{{certificado.fecha_emision}}'] = $c['issue_date'] ?? '';
            } elseif (isset($c->issue_date)) {
                if ($c->issue_date instanceof Carbon) {
                    $replacements['{{certificado.fecha_emision}}'] = $c->issue_date->translatedFormat('d \d\e F \d\e Y');
                } else {
                    try {
                        $replacements['{{certificado.fecha_emision}}'] = Carbon::parse($c->issue_date)->translatedFormat('d \d\e F \d\e Y');
                    } catch (\Throwable) {
                        $replacements['{{certificado.fecha_emision}}'] = (string) $c->issue_date;
                    }
                }
            } else {
                $replacements['{{certificado.fecha_emision}}'] = '';
            }

            if (is_array($c)) {
                $replacements['{{certificado.fecha_vencimiento}}'] = $c['expiration_date'] ?? '';
            } elseif (isset($c->expiration_date)) {
                if ($c->expiration_date instanceof Carbon) {
                    $replacements['{{certificado.fecha_vencimiento}}'] = $c->expiration_date->translatedFormat('d \d\e F \d\e Y');
                } else {
                    try {
                        $replacements['{{certificado.fecha_vencimiento}}'] = Carbon::parse($c->expiration_date)->translatedFormat('d \d\e F \d\e Y');
                    } catch (\Throwable) {
                        $replacements['{{certificado.fecha_vencimiento}}'] = (string) $c->expiration_date;
                    }
                }
            } else {
                $replacements['{{certificado.fecha_vencimiento}}'] = '';
            }

            $replacements['{{certificado.tipo}}'] = $rawType;
            $replacements['{{certificado.modalidad}}'] = $modality;
            $replacements['{{participante.rol}}'] = $role;
            $replacements['{{certificado.lugar_fecha_emision}}'] = "Dado en la ciudad de {$city}, a los {$day} días del mes de {$month} de {$year}.";
            $replacements['{{certificado.fecha_emision_larga}}'] = "a los {$day} días del mes de {$month} del año {$year}";

            // Resolución Facultativa
            $resolution = '';
            if (isset($context['activity'])) {
                $a = $context['activity'];
                $actConfig = is_array($a) ? null : ($a->certificateConfig ?? null);
                if ($actConfig && ! empty($actConfig->custom_fields_schema['resolucion'])) {
                    $resolution = $actConfig->custom_fields_schema['resolucion'];
                }
            }
            if (empty($resolution)) {
                $resolution = 'Resolución del H. Consejo Facultativo N° 342/2026';
            }
            $replacements['{{actividad.resolucion}}'] = $resolution;
            $replacements['{{acreditacion.resolucion}}'] = "Acreditado bajo {$resolution}";

            $replacements['en calidad de {{certificado.tipo}}'] = match (mb_strtolower($modality)) {
                'asistencia' => 'en calidad de Asistente',
                'aprobación', 'aprobacion' => 'habiendo aprobado satisfactoriamente',
                'disertante / ponente' => 'en calidad de Disertante / Ponente',
                'organizador' => 'en calidad de Organizador',
                default => 'en calidad de '.$modality,
            };
        }

        // Autoridades y Ajustes Globales
        if (isset($context['settings'])) {
            $s = $context['settings'];
            $replacements['{{institucion.nombre}}'] = $s['institution_name'] ?? 'UNIVERSIDAD MAYOR DE SAN ANDRÉS';
            $replacements['{{facultad.nombre}}'] = $s['faculty_name'] ?? 'FACULTAD DE TECNOLOGÍA';
            $replacements['{{vicedecanato.nombre}}'] = $s['department_name'] ?? 'VICEDECANATO';
            $replacements['{{autoridad.nombre}}'] = $s['authority_name'] ?? 'Ing. Aniceto Velarde Forest';
            $replacements['{{autoridad.cargo}}'] = $s['authority_position'] ?? 'Vicedecano de Facultad';
            $replacements['{{institucion.ciudad}}'] = $s['institution_city'] ?? 'La Paz - Bolivia';
        }

        // Campos Adicionales Personalizados
        if (isset($context['custom_fields']) && is_array($context['custom_fields'])) {
            foreach ($context['custom_fields'] as $key => $val) {
                $replacements['{{campos.'.$key.'}}'] = (string) $val;
                $replacements['{{'.$key.'}}'] = (string) $val;
            }
        }

        return strtr($text, $replacements);
    }

    /**
     * Definición de las 8 plantillas iniciales profesionales solicitadas.
     */
    public function getInitialTemplateDefinitions(): array
    {
        return [
            // 1. Institucional Clásica (Horizontal) - Facultad de Tecnología Oficial
            [
                'name' => 'Institucional Clásica (Facultad de Tecnología - UMSA)',
                'slug' => 'institucional-clasica',
                'category' => 'institucional',
                'orientation' => 'landscape',
                'description' => 'Diseño solemne y tradicional con marco doble ornamental oro y azul petróleo, escudos oficiales de la UMSA y Facultad de Tecnología, sello de Vicedecanato.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'classic',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#d4af37',
                    'text_color' => '#1e293b',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'ornamental-double',
                    'border_width' => '3px',
                    'margins' => ['top' => 15, 'bottom' => 15, 'left' => 15, 'right' => 15],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA',
                    'header_department' => 'VICEDECANATO',
                    'award_intro' => 'Otorgado a favor de:',
                    'body_text' => 'Por su destacada participación en calidad de {{certificado.tipo}} en la actividad académica "{{actividad.nombre}}", realizado {{actividad.fechas}} con una carga horaria de {{actividad.horas}} horas académicas curriculares.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.05,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                        [
                            'name' => 'Lic. Jorge Velasco Orellanos',
                            'position' => 'Decano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_decano_ft.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '72px'],
                    ],
                ],
            ],

            // 2. Institucional Solemne (Vertical A4)
            [
                'name' => 'Institucional Solemne (FT-UMSA - Vertical)',
                'slug' => 'institucional-vertical',
                'category' => 'institucional',
                'orientation' => 'portrait',
                'description' => 'Diseño vertical de honor para grados académicos y diplomas solemnes de la Facultad de Tecnología.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'classic',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#d4af37',
                    'text_color' => '#1e293b',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'ornamental-double',
                    'border_width' => '3px',
                    'margins' => ['top' => 15, 'bottom' => 15, 'left' => 15, 'right' => 15],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA',
                    'header_department' => 'VICEDECANATO',
                    'award_intro' => 'CONFIERE EL PRESENTE',
                    'body_text' => 'Por su destacada participación en calidad de {{certificado.tipo}} en la actividad académica "{{actividad.nombre}}", realizado {{actividad.fechas}} con una carga horaria de {{actividad.horas}} horas académicas curriculares.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.06,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                        [
                            'name' => 'Lic. Jorge Velasco Orellanos',
                            'position' => 'Decano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_decano_ft.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '72px'],
                    ],
                ],
            ],

            // 3. Institucional Moderna (Tecnológica y Minimalista)
            [
                'name' => 'Innovación Tecnológica (Minimalista FT)',
                'slug' => 'institucional-moderna',
                'category' => 'institucional',
                'orientation' => 'landscape',
                'description' => 'Líneas limpias, tipografía contemporánea, cabecera geométrica sofisticada en azul institucional y rojo UMSA.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'modern',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#32639e',
                    'text_color' => '#0f172a',
                    'font_family' => 'Helvetica',
                    'border_style' => 'modern-clean',
                    'border_width' => '2px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 14, 'right' => 14],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA • VICEDECANATO',
                    'award_intro' => 'Se confiere el presente certificado a:',
                    'body_text' => 'En reconocimiento a su participación y compromiso en "{{actividad.nombre}}", desarrollado del {{actividad.fechas}}, acreditando {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.04,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'left_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '70px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '70px'],
                    ],
                ],
            ],

            // 4. Académica Elegante (Alta Distinción Facultativa)
            [
                'name' => 'Académica Elegante (Alta Distinción FT)',
                'slug' => 'academica-elegante',
                'category' => 'academica',
                'orientation' => 'landscape',
                'description' => 'Diseño distinguido para eventos magistrales, honores facultativos y ceremonias de tecnología.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'academic-gold',
                    'primary_color' => '#024966',
                    'secondary_color' => '#b45309',
                    'accent_color' => '#bb2026',
                    'text_color' => '#1e293b',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'gold-filigree',
                    'border_width' => '4px',
                    'margins' => ['top' => 14, 'bottom' => 14, 'left' => 16, 'right' => 16],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA',
                    'header_department' => 'VICEDECANATO',
                    'award_intro' => 'El Vicedecanato de la Facultad de Tecnología otorga a:',
                    'body_text' => 'El presente testimonio de reconocimiento por su destacada actuación como {{certificado.tipo}} en "{{actividad.nombre}}", realizado {{actividad.fechas}} con una duración de {{actividad.horas}} horas lectivas.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.05,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                        [
                            'name' => 'Lic. Jorge Velasco Orellanos',
                            'position' => 'Decano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_decano_ft.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '75px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '75px'],
                    ],
                ],
            ],

            // 5. Académica de Honor (Vertical A4)
            [
                'name' => 'Académica de Honor (Vertical A4 FT)',
                'slug' => 'academica-honor-vertical',
                'category' => 'academica',
                'orientation' => 'portrait',
                'description' => 'Diploma de distinción académica en formato vertical para menciones de excelencia y mérito técnico.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'academic-gold',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#d4af37',
                    'text_color' => '#1e293b',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'gold-filigree',
                    'border_width' => '4px',
                    'margins' => ['top' => 14, 'bottom' => 14, 'left' => 16, 'right' => 16],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA',
                    'header_department' => 'VICEDECANATO',
                    'award_intro' => 'CONFIERE EL PRESENTE TESTIMONIO DE HONOR A:',
                    'body_text' => 'En testimonio de reconocimiento por su destacada actuación y excelencia académica demostrada en "{{actividad.nombre}}", realizado {{actividad.fechas}} con una duración de {{actividad.horas}} horas lectivas.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.06,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                        [
                            'name' => 'Lic. Jorge Velasco Orellanos',
                            'position' => 'Decano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_decano_ft.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '75px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '75px'],
                    ],
                ],
            ],

            // 6. Seminario y Congreso Internacional
            [
                'name' => 'Seminario y Congreso Tecnológico Internacional',
                'slug' => 'seminario-congreso',
                'category' => 'seminario',
                'orientation' => 'landscape',
                'description' => 'Configurado para simposios, conferencias científicas y seminarios internacionales de tecnología con doble firma.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'seminar',
                    'primary_color' => '#024966',
                    'secondary_color' => '#32639e',
                    'accent_color' => '#bb2026',
                    'text_color' => '#0f172a',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'geometric-frame',
                    'border_width' => '3px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 14, 'right' => 14],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA • VICEDECANATO',
                    'award_intro' => 'Certifica que:',
                    'body_text' => 'Ha participado activamente en el seminario académico especializado "{{actividad.nombre}}", llevado a cabo {{actividad.fechas}}, con una carga total de {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.05,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                        [
                            'name' => 'M.Sc. Coordinador General',
                            'position' => 'Comisión Académica',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_coordinador.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '70px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '70px'],
                    ],
                ],
            ],

            // 7. [NUEVA] Carreras de Ingeniería y Tecnología Industrial
            [
                'name' => 'Carreras de Ingeniería y Tecnología Industrial',
                'slug' => 'tecnologia-ingenieria-industrial',
                'category' => 'capacitacion',
                'orientation' => 'landscape',
                'description' => 'Especializada para Electromecánica, Mecánica Automotriz, Mecánica Industrial, Electricidad Industrial y Construcciones Civiles.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'workshop',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#32639e',
                    'text_color' => '#0f172a',
                    'font_family' => 'Helvetica',
                    'border_style' => 'geometric-frame',
                    'border_width' => '3.5px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 14, 'right' => 14],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA • ÁREA INDUSTRIAL Y CONSTRUCCIONES',
                    'award_intro' => 'Otorga el presente certificado a:',
                    'body_text' => 'Por haber cursado y aprobado satisfactoriamente los módulos técnico-prácticos en laboratorios y talleres de "{{actividad.nombre}}", realizado {{actividad.fechas}}, con una intensidad de {{actividad.horas}} horas prácticas.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.05,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'left_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                        [
                            'name' => 'Director de Carrera',
                            'position' => 'Área Técnica Industrial',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_coordinador.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '72px'],
                    ],
                ],
            ],

            // 8. [NUEVA] Telecomunicaciones, Electrónica y Tecnologías Digitales
            [
                'name' => 'Electrónica, Telecomunicaciones y Ciencias Digitales',
                'slug' => 'tecnologia-telecom-digital',
                'category' => 'capacitacion',
                'orientation' => 'landscape',
                'description' => 'Orientada a Electrónica, Telecomunicaciones, Geodesia y Geomática, y competencias de la era digital.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'modern',
                    'primary_color' => '#024966',
                    'secondary_color' => '#32639e',
                    'accent_color' => '#bb2026',
                    'text_color' => '#0f172a',
                    'font_family' => 'Helvetica',
                    'border_style' => 'modern-clean',
                    'border_width' => '3px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 14, 'right' => 14],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA • CIENCIAS TECNOLÓGICAS Y DIGITALES',
                    'award_intro' => 'Hace constar que:',
                    'body_text' => 'Ha completado con éxito la capacitación técnica en "{{actividad.nombre}}", desarrollada {{actividad.fechas}}, con una carga total de {{actividad.horas}} horas lectivas y de laboratorio.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.05,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '70px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '70px'],
                    ],
                ],
            ],

            // 9. [NUEVA] Instituto de Investigaciones y Aplicaciones Tecnológicas (IIAT)
            [
                'name' => 'Investigación y Desarrollo Tecnológico (IIAT - FT)',
                'slug' => 'investigacion-iiat',
                'category' => 'seminario',
                'orientation' => 'landscape',
                'description' => 'Plantilla oficial del Instituto de Investigaciones y Aplicaciones Tecnológicas de la Facultad de Tecnología.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'academic-gold',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#d4af37',
                    'text_color' => '#0f172a',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'gold-filigree',
                    'border_width' => '3.5px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 15, 'right' => 15],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA • INSTITUTO DE INVESTIGACIONES (IIAT)',
                    'award_intro' => 'El Instituto de Investigaciones confiere el presente testimonio a:',
                    'body_text' => 'Por su notable aporte como {{certificado.tipo}} en el marco del proyecto científico y tecnológico "{{actividad.nombre}}", realizado {{actividad.fechas}} con {{actividad.horas}} horas de investigación y desarrollo.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.05,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                        [
                            'name' => 'Director IIAT',
                            'position' => 'Instituto de Investigaciones',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_coordinador.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '72px'],
                    ],
                ],
            ],

            // 10. [NUEVA] Unidad de Postgrado de la Facultad de Tecnología (UPG - FT)
            [
                'name' => 'Unidad de Postgrado (UPG - FT Tecnología)',
                'slug' => 'postgrado-upg-tecnologia',
                'category' => 'academica',
                'orientation' => 'landscape',
                'description' => 'Diploma de postgrado y educación continua para diplomados, cursos de especialización y formación superior.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'academic-gold',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#d4af37',
                    'text_color' => '#1e293b',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'ornamental-double',
                    'border_width' => '4px',
                    'margins' => ['top' => 14, 'bottom' => 14, 'left' => 16, 'right' => 16],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA • UNIDAD DE POSTGRADO',
                    'award_intro' => 'OTORGA EL PRESENTE DIPLOMA A:',
                    'body_text' => 'Por haber cumplido a cabalidad con el plan de estudios y requisitos de graduación del programa "{{actividad.nombre}}", realizado {{actividad.fechas}}, con una carga académica de {{actividad.horas}} horas créditos.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.06,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Lic. Jorge Velasco Orellanos',
                            'position' => 'Decano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_decano_ft.svg',
                        ],
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '75px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '75px'],
                    ],
                ],
            ],

            // 11. [NUEVA] Curso Pre-Universitario (C.P.U. Facultad de Tecnología)
            [
                'name' => 'Curso Pre-Universitario (C.P.U. - FT)',
                'slug' => 'curso-preuniversitario-cpu',
                'category' => 'aprobacion',
                'orientation' => 'landscape',
                'description' => 'Certificado oficial de admisión y aprobación de los Cursos Pre-Universitarios de la Facultad de Tecnología.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'approved',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#32639e',
                    'text_color' => '#0f172a',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'ornamental-double',
                    'border_width' => '3px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 14, 'right' => 14],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA • CURSO PRE-UNIVERSITARIO',
                    'award_intro' => 'Certifica formalmente que el(la) postulante:',
                    'body_text' => 'Ha cursado y APROBADO satisfactoriamente el {{actividad.nombre}}, realizado {{actividad.fechas}}, con una carga académica de {{actividad.horas}} horas reglamentarias para la admisión facultativa.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.05,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                        [
                            'name' => 'Coordinador C.P.U.',
                            'position' => 'Comisión de Admisión',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_coordinador.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '72px'],
                    ],
                ],
            ],

            // 12. Disertante / Ponente Magistral
            [
                'name' => 'Disertante / Ponente Magistral FT',
                'slug' => 'disertante-ponente',
                'category' => 'disertante',
                'orientation' => 'landscape',
                'description' => 'Para conferencistas internacionales, docentes invitados, ponentes y expositores de investigaciones.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'speaker',
                    'primary_color' => '#024966',
                    'secondary_color' => '#bb2026',
                    'accent_color' => '#d4af37',
                    'text_color' => '#0f172a',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'geometric-frame',
                    'border_width' => '3px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 15, 'right' => 15],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE TECNOLOGÍA • VICEDECANATO',
                    'award_intro' => 'Certificado de Disertante concedido a:',
                    'body_text' => 'En testimonio de reconocimiento por su brillante exposición y aporte científico en calidad de DISERTANTE MAGISTRAL en el evento "{{actividad.nombre}}", realizado {{actividad.fechas}} con una carga horaria de {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_type' => 'image',
                    'watermark_opacity' => 0.05,
                    'watermark_asset_path' => 'assets/institutional/marca_agua_ft.svg',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Ing. Aniceto Velarde Forest',
                            'position' => 'Vicedecano',
                            'institution' => 'Facultad de Tecnología - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_ft_umsa.svg', 'position' => 'right', 'width' => '72px'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Normaliza un esquema de diseño asegurando que contenga la estructura
     * basada en elementos independientes para el diseñador visual y el motor PDF.
     */
    public function normalizeSchema(array $schema, string $orientation = 'landscape'): array
    {
        // Si ya cuenta con el árbol de elementos configurables, validar y asegurar propiedades de página
        if (isset($schema['elements']) && is_array($schema['elements'])) {
            foreach ($schema['elements'] as &$el) {
                if (! isset($el['styles']) || ! is_array($el['styles'])) {
                    $el['styles'] = [];
                }
                if (isset($el['font_size'])) {
                    $el['styles']['font_size'] = (int) $el['font_size'];
                }
                if (isset($el['font_weight'])) {
                    $el['styles']['font_weight'] = $el['font_weight'];
                }
                if (isset($el['font_style'])) {
                    $el['styles']['font_style'] = $el['font_style'];
                }
                if (isset($el['text_align'])) {
                    $el['styles']['text_align'] = $el['text_align'];
                }
                if (isset($el['text_color'])) {
                    $el['styles']['text_color'] = $el['text_color'];
                }
                if (isset($el['font_family'])) {
                    $el['styles']['font_family'] = $el['font_family'];
                }
                if (isset($el['object_fit'])) {
                    $el['styles']['object_fit'] = $el['object_fit'];
                }

                if (empty($el['asset_url']) && ! empty($el['asset_path'])) {
                    $el['asset_url'] = asset($el['asset_path']);
                }
            }
            unset($el);

            $schema['page'] = array_merge([
                'orientation' => $orientation,
                'width_mm' => ($orientation === 'portrait') ? 210 : 297,
                'height_mm' => ($orientation === 'portrait') ? 297 : 210,
                'background_color' => $schema['page']['background_color'] ?? '#ffffff',
                'background_asset_id' => $schema['page']['background_asset_id'] ?? null,
                'border_style' => $schema['border_style'] ?? ($schema['page']['border_style'] ?? 'ornamental-double'),
                'border_primary_color' => $schema['primary_color'] ?? ($schema['page']['border_primary_color'] ?? '#024966'),
                'border_secondary_color' => $schema['secondary_color'] ?? ($schema['page']['border_secondary_color'] ?? '#d4af37'),
                'border_width_px' => $schema['page']['border_width_px'] ?? 3,
                'border_inset_mm' => $schema['page']['border_inset_mm'] ?? 8,
            ], $schema['page'] ?? []);

            return $schema;
        }

        // Si es un esquema plano legado, convertirlo automáticamente a elementos configurables
        $converted = $this->convertFlatSchemaToElements($schema, $orientation);

        return $this->normalizeSchema($converted, $orientation);
    }

    /**
     * Transforma un esquema plano tradicional en una colección de elementos independientes con coordenadas %.
     */
    public function convertFlatSchemaToElements(array $flat, string $orientation = 'landscape'): array
    {
        $primaryColor = $flat['primary_color'] ?? '#024966';
        $secondaryColor = $flat['secondary_color'] ?? '#d4af37';
        $accentColor = $flat['accent_color'] ?? '#b45309';
        $textColor = $flat['text_color'] ?? '#1e293b';
        $fontFamily = $flat['font_family'] ?? 'DejaVu Serif';
        $borderStyle = $flat['border_style'] ?? 'ornamental-double';

        $isPortrait = ($orientation === 'portrait');
        $widthMm = $isPortrait ? 210 : 297;
        $heightMm = $isPortrait ? 297 : 210;

        $hasSecondSig = count($flat['signatures'] ?? []) >= 2;
        $elements = [];

        if ($isPortrait) {
            // ==============================================================
            // DISEÑO PORTRAIT (A4 VERTICAL: 210 x 297 mm) - DISTRIBUCIÓN SOLEMNE
            // ==============================================================

            // 1. Marca de agua heráldica central
            if (! isset($flat['show_watermark']) || $flat['show_watermark']) {
                $elements[] = [
                    'id' => 'el_watermark',
                    'type' => 'watermark',
                    'name' => 'Marca de Agua Heráldica',
                    'watermark_type' => $flat['watermark_type'] ?? 'image',
                    'watermark_text' => $flat['watermark_text'] ?? 'UMSA',
                    'asset_path' => $flat['watermark_asset_path'] ?? 'assets/institutional/marca_agua_ft.svg',
                    'asset_id' => $flat['watermark_asset_id'] ?? null,
                    'x' => 20.0,
                    'y' => 25.0,
                    'width' => 60.0,
                    'height' => 45.0,
                    'opacity' => (float) ($flat['watermark_opacity'] ?? 0.06),
                    'rotation' => 0,
                    'z_index' => 1,
                    'is_locked' => false,
                    'is_visible' => true,
                    'is_mandatory' => false,
                ];
            }

            // 2. Logo Izquierdo (UMSA)
            $elements[] = [
                'id' => 'el_logo_left',
                'type' => 'logo',
                'name' => 'Logo Izquierdo (UMSA)',
                'asset_id' => $flat['logo_left_id'] ?? 1,
                'asset_path' => $flat['logo_left_path'] ?? 'assets/institutional/logo_umsa.svg',
                'x' => 6.0,
                'y' => 4.5,
                'width' => 14.0,
                'height' => 10.5,
                'opacity' => 1.0,
                'object_fit' => 'contain',
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 3. Logo Derecho (Facultad de Tecnología)
            $elements[] = [
                'id' => 'el_logo_right',
                'type' => 'logo',
                'name' => 'Logo Derecho (Facultad)',
                'asset_id' => $flat['logo_right_id'] ?? 2,
                'asset_path' => $flat['logo_right_path'] ?? 'assets/institutional/logo_ft_umsa.svg',
                'x' => 80.0,
                'y' => 4.5,
                'width' => 14.0,
                'height' => 10.5,
                'opacity' => 1.0,
                'object_fit' => 'contain',
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 4. Cabecera Institucional
            $elements[] = [
                'id' => 'el_header_title',
                'type' => 'text',
                'name' => 'Institución Superior',
                'content' => $flat['header_title'] ?? 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                'font_family' => $fontFamily,
                'font_size' => 13,
                'font_weight' => 'bold',
                'text_color' => $primaryColor,
                'text_align' => 'center',
                'letter_spacing' => 1.5,
                'line_height' => 1.2,
                'x' => 14.0,
                'y' => 5.0,
                'width' => 72.0,
                'height' => 3.5,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 5. Facultad
            $elements[] = [
                'id' => 'el_header_subtitle',
                'type' => 'text',
                'name' => 'Facultad',
                'content' => $flat['header_subtitle'] ?? 'FACULTAD DE TECNOLOGÍA',
                'font_family' => $fontFamily,
                'font_size' => 10.5,
                'font_weight' => 'bold',
                'text_color' => '#475569',
                'text_align' => 'center',
                'letter_spacing' => 1,
                'line_height' => 1.2,
                'x' => 14.0,
                'y' => 8.6,
                'width' => 72.0,
                'height' => 2.8,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 6. Unidad Emisora
            $elements[] = [
                'id' => 'el_header_department',
                'type' => 'text',
                'name' => 'Unidad Emisora',
                'content' => $flat['header_department'] ?? 'VICEDECANATO',
                'font_family' => $fontFamily,
                'font_size' => 11.5,
                'font_weight' => 'bold',
                'text_color' => $accentColor,
                'text_align' => 'center',
                'letter_spacing' => 1.5,
                'line_height' => 1.2,
                'x' => 14.0,
                'y' => 11.5,
                'width' => 72.0,
                'height' => 3.0,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 7. Divisor Ornamental
            $elements[] = [
                'id' => 'el_divider',
                'type' => 'line',
                'name' => 'Línea Divisoria',
                'color' => $secondaryColor,
                'thickness' => 2,
                'x' => 30.0,
                'y' => 15.0,
                'width' => 40.0,
                'height' => 0.4,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 8. Preámbulo Solemne
            $elements[] = [
                'id' => 'el_preamble',
                'type' => 'text',
                'name' => 'Preámbulo Institucional',
                'content' => 'EL VICEDECANATO DE LA FACULTAD DE CIENCIAS PURAS Y NATURALES',
                'font_family' => $fontFamily,
                'font_size' => 9.5,
                'font_weight' => 'bold',
                'text_color' => '#475569',
                'text_align' => 'center',
                'letter_spacing' => 0.8,
                'x' => 10.0,
                'y' => 16.6,
                'width' => 80.0,
                'height' => 2.5,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 9. Encabezado de Otorgación
            $elements[] = [
                'id' => 'el_award_intro',
                'type' => 'text',
                'name' => 'Fórmula de Concesión',
                'content' => $flat['award_intro'] ?? 'CONFIERE EL PRESENTE',
                'font_family' => $fontFamily,
                'font_size' => 10.5,
                'font_style' => 'italic',
                'text_color' => '#64748b',
                'text_align' => 'center',
                'letter_spacing' => 1,
                'x' => 18.0,
                'y' => 19.5,
                'width' => 64.0,
                'height' => 2.5,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 10. Título Monumental de Otorgación
            $elements[] = [
                'id' => 'el_award_title',
                'type' => 'text',
                'name' => 'Título de Otorgación',
                'content' => '{{certificado.tipo}}',
                'font_family' => $fontFamily,
                'font_size' => 24,
                'font_weight' => '900',
                'text_color' => $primaryColor,
                'text_align' => 'center',
                'letter_spacing' => 2,
                'x' => 8.0,
                'y' => 22.4,
                'width' => 84.0,
                'height' => 5.5,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 11. Introducción al Beneficiario
            $elements[] = [
                'id' => 'el_beneficiary_intro',
                'type' => 'text',
                'name' => 'A Favor De',
                'content' => 'A favor de:',
                'font_family' => $fontFamily,
                'font_size' => 10,
                'font_style' => 'italic',
                'text_color' => '#64748b',
                'text_align' => 'center',
                'x' => 20.0,
                'y' => 28.5,
                'width' => 60.0,
                'height' => 2.4,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 12. Nombre del Beneficiario
            $elements[] = [
                'id' => 'el_beneficiary_name',
                'type' => 'text',
                'name' => 'Nombre del Beneficiario',
                'content' => '{{beneficiario.nombre}}',
                'font_family' => $fontFamily,
                'font_size' => 20,
                'font_weight' => 'bold',
                'text_color' => '#0f172a',
                'text_align' => 'center',
                'letter_spacing' => 0.5,
                'border_bottom' => true,
                'border_bottom_color' => $secondaryColor,
                'x' => 8.0,
                'y' => 31.2,
                'width' => 84.0,
                'height' => 5.5,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 13. Documento de Identidad
            $elements[] = [
                'id' => 'el_beneficiary_doc',
                'type' => 'text',
                'name' => 'Cédula de Identidad',
                'content' => 'Documento de Identidad: {{beneficiario.documento}}',
                'font_family' => $fontFamily,
                'font_size' => 9.5,
                'text_color' => '#64748b',
                'text_align' => 'center',
                'x' => 15.0,
                'y' => 37.2,
                'width' => 70.0,
                'height' => 2.6,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 14. Cuerpo de Certificación
            $elements[] = [
                'id' => 'el_body_text',
                'type' => 'text',
                'name' => 'Cuerpo de Certificación',
                'content' => $flat['body_text'] ?? 'Por su destacada participación en calidad de {{certificado.tipo}} en la actividad académica "{{actividad.nombre}}", realizado {{actividad.fechas}} con una carga horaria de {{actividad.horas}} horas académicas.',
                'font_family' => $fontFamily,
                'font_size' => 11.5,
                'line_height' => 1.5,
                'text_color' => $textColor,
                'text_align' => 'center',
                'x' => 10.0,
                'y' => 40.5,
                'width' => 80.0,
                'height' => 12.0,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 15. Respaldo Institucional y Resolución
            $elements[] = [
                'id' => 'el_resolution',
                'type' => 'text',
                'name' => 'Acreditación y Resolución',
                'content' => '{{acreditacion.resolucion}}',
                'font_family' => $fontFamily,
                'font_size' => 8.5,
                'font_style' => 'italic',
                'text_color' => '#64748b',
                'text_align' => 'center',
                'x' => 12.0,
                'y' => 53.2,
                'width' => 76.0,
                'height' => 2.8,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 16. Lugar y Fecha Solemne de Emisión
            $elements[] = [
                'id' => 'el_date_place',
                'type' => 'text',
                'name' => 'Lugar y Fecha Solemne',
                'content' => '{{certificado.lugar_fecha_emision}}',
                'font_family' => $fontFamily,
                'font_size' => 9.5,
                'font_weight' => 'bold',
                'text_color' => '#334155',
                'text_align' => 'center',
                'x' => 10.0,
                'y' => 56.6,
                'width' => 80.0,
                'height' => 3.2,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 17. Divisor hacia Autoridades
            $elements[] = [
                'id' => 'el_divider_signatures',
                'type' => 'line',
                'name' => 'Separador de Firmas',
                'color' => '#cbd5e1',
                'thickness' => 1,
                'x' => 35.0,
                'y' => 60.5,
                'width' => 30.0,
                'height' => 0.2,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 18. Fila de Firmas (Firma 1 y Firma 2)
            $sig1X = $hasSecondSig ? 10.0 : 30.0;
            $sig1W = $hasSecondSig ? 38.0 : 40.0;

            $elements[] = [
                'id' => 'el_signature_1',
                'type' => 'signature',
                'name' => 'Firma 1 (Vicedecano)',
                'authority_name' => $flat['signatures'][0]['name'] ?? 'Ing. Aniceto Velarde Forest',
                'authority_title' => $flat['signatures'][0]['position'] ?? 'Vicedecano de Facultad',
                'institution' => $flat['signatures'][0]['institution'] ?? 'Facultad de Tecnología - UMSA',
                'asset_path' => $flat['signatures'][0]['asset_path'] ?? 'assets/institutional/firma_vicedecano_ft.svg',
                'x' => $sig1X,
                'y' => 62.5,
                'width' => $sig1W,
                'height' => 15.0,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            $elements[] = [
                'id' => 'el_signature_2',
                'type' => 'signature',
                'name' => 'Firma 2 (Coordinador)',
                'authority_name' => $flat['signatures'][1]['name'] ?? 'Lic. Jorge Velasco Orellanos',
                'authority_title' => $flat['signatures'][1]['position'] ?? 'Decano de Facultad',
                'institution' => $flat['signatures'][1]['institution'] ?? 'Facultad de Tecnología - UMSA',
                'asset_path' => $flat['signatures'][1]['asset_path'] ?? 'assets/institutional/firma_decano_ft.svg',
                'x' => 52.0,
                'y' => 62.5,
                'width' => 38.0,
                'height' => 15.0,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => $hasSecondSig,
                'is_mandatory' => false,
            ];

            // 19. Fila de Seguridad Institucional (Sello Seco y Placa QR Oficial)
            $elements[] = [
                'id' => 'el_stamp',
                'type' => 'stamp',
                'name' => 'Sello Seco Oficial',
                'asset_path' => 'assets/institutional/sello_vicedecanato_ft.svg',
                'x' => 16.0,
                'y' => 79.0,
                'width' => 26.0,
                'height' => 14.5,
                'opacity' => 0.95,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => isset($flat['show_stamp']) ? (bool) $flat['show_stamp'] : true,
                'is_mandatory' => false,
            ];

            $elements[] = [
                'id' => 'el_qr',
                'type' => 'qr',
                'name' => 'Código QR Oficial',
                'show_code' => true,
                'x' => 46.0,
                'y' => 78.0,
                'width' => 44.0,
                'height' => 16.0,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => isset($flat['show_qr']) ? (bool) $flat['show_qr'] : true,
                'is_mandatory' => true,
            ];

        } else {
            // ==============================================================
            // DISEÑO LANDSCAPE (A4 HORIZONTAL: 297 x 210 mm) - EQUILIBRIO COMPLETO
            // ==============================================================

            // 1. Marca de agua heráldica central
            if (! isset($flat['show_watermark']) || $flat['show_watermark']) {
                $elements[] = [
                    'id' => 'el_watermark',
                    'type' => 'watermark',
                    'name' => 'Marca de Agua Heráldica',
                    'watermark_type' => $flat['watermark_type'] ?? 'image',
                    'watermark_text' => $flat['watermark_text'] ?? 'UMSA',
                    'asset_path' => $flat['watermark_asset_path'] ?? 'assets/institutional/marca_agua_ft.svg',
                    'asset_id' => $flat['watermark_asset_id'] ?? null,
                    'x' => 32.0,
                    'y' => 22.0,
                    'width' => 36.0,
                    'height' => 52.0,
                    'opacity' => (float) ($flat['watermark_opacity'] ?? 0.05),
                    'rotation' => 0,
                    'z_index' => 1,
                    'is_locked' => false,
                    'is_visible' => true,
                    'is_mandatory' => false,
                ];
            }

            // 2. Logo Izquierdo (UMSA)
            $elements[] = [
                'id' => 'el_logo_left',
                'type' => 'logo',
                'name' => 'Logo Izquierdo (UMSA)',
                'asset_id' => $flat['logo_left_id'] ?? 1,
                'asset_path' => $flat['logo_left_path'] ?? 'assets/institutional/logo_umsa.svg',
                'x' => 5.5,
                'y' => 5.0,
                'width' => 11.0,
                'height' => 15.0,
                'opacity' => 1.0,
                'object_fit' => 'contain',
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 3. Logo Derecho (Facultad de Tecnología)
            $elements[] = [
                'id' => 'el_logo_right',
                'type' => 'logo',
                'name' => 'Logo Derecho (Facultad)',
                'asset_id' => $flat['logo_right_id'] ?? 2,
                'asset_path' => $flat['logo_right_path'] ?? 'assets/institutional/logo_ft_umsa.svg',
                'x' => 83.5,
                'y' => 5.0,
                'width' => 11.0,
                'height' => 15.0,
                'opacity' => 1.0,
                'object_fit' => 'contain',
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 4. Cabecera Institucional
            $elements[] = [
                'id' => 'el_header_title',
                'type' => 'text',
                'name' => 'Institución Superior',
                'content' => $flat['header_title'] ?? 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                'font_family' => $fontFamily,
                'font_size' => 14.5,
                'font_weight' => 'bold',
                'text_color' => $primaryColor,
                'text_align' => 'center',
                'letter_spacing' => 2,
                'line_height' => 1.2,
                'x' => 16.0,
                'y' => 5.2,
                'width' => 68.0,
                'height' => 4.5,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 5. Facultad
            $elements[] = [
                'id' => 'el_header_subtitle',
                'type' => 'text',
                'name' => 'Facultad',
                'content' => $flat['header_subtitle'] ?? 'FACULTAD DE TECNOLOGÍA',
                'font_family' => $fontFamily,
                'font_size' => 11.5,
                'font_weight' => 'bold',
                'text_color' => '#475569',
                'text_align' => 'center',
                'letter_spacing' => 1,
                'line_height' => 1.2,
                'x' => 16.0,
                'y' => 9.8,
                'width' => 68.0,
                'height' => 3.5,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 6. Unidad Emisora
            $elements[] = [
                'id' => 'el_header_department',
                'type' => 'text',
                'name' => 'Unidad Emisora',
                'content' => $flat['header_department'] ?? 'VICEDECANATO',
                'font_family' => $fontFamily,
                'font_size' => 12.5,
                'font_weight' => 'bold',
                'text_color' => $accentColor,
                'text_align' => 'center',
                'letter_spacing' => 1.5,
                'line_height' => 1.2,
                'x' => 16.0,
                'y' => 13.2,
                'width' => 68.0,
                'height' => 4.0,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 7. Divisor Ornamental
            $elements[] = [
                'id' => 'el_divider',
                'type' => 'line',
                'name' => 'Línea Divisoria',
                'color' => $secondaryColor,
                'thickness' => 2,
                'x' => 35.0,
                'y' => 17.6,
                'width' => 30.0,
                'height' => 0.5,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 8. Título de Otorgación
            $elements[] = [
                'id' => 'el_award_title',
                'type' => 'text',
                'name' => 'Título de Otorgación',
                'content' => '{{certificado.tipo}}',
                'font_family' => $fontFamily,
                'font_size' => 23,
                'font_weight' => '900',
                'text_color' => $primaryColor,
                'text_align' => 'center',
                'letter_spacing' => 2,
                'x' => 10.0,
                'y' => 19.6,
                'width' => 80.0,
                'height' => 6.8,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 9. Encabezado de Otorgación
            $elements[] = [
                'id' => 'el_award_intro',
                'type' => 'text',
                'name' => 'Encabezado de Otorgación',
                'content' => $flat['award_intro'] ?? 'Otorgado a favor de:',
                'font_family' => $fontFamily,
                'font_size' => 10.5,
                'font_style' => 'italic',
                'text_color' => '#64748b',
                'text_align' => 'center',
                'x' => 25.0,
                'y' => 26.6,
                'width' => 50.0,
                'height' => 3.2,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 10. Beneficiario
            $elements[] = [
                'id' => 'el_beneficiary_name',
                'type' => 'text',
                'name' => 'Nombre del Beneficiario',
                'content' => '{{beneficiario.nombre}}',
                'font_family' => $fontFamily,
                'font_size' => 21,
                'font_weight' => 'bold',
                'text_color' => '#0f172a',
                'text_align' => 'center',
                'letter_spacing' => 0.5,
                'border_bottom' => true,
                'border_bottom_color' => $secondaryColor,
                'x' => 10.0,
                'y' => 30.0,
                'width' => 80.0,
                'height' => 6.8,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 11. Documento
            $elements[] = [
                'id' => 'el_beneficiary_doc',
                'type' => 'text',
                'name' => 'Cédula de Identidad',
                'content' => 'Documento de Identidad: {{beneficiario.documento}}',
                'font_family' => $fontFamily,
                'font_size' => 9.5,
                'text_color' => '#64748b',
                'text_align' => 'center',
                'x' => 25.0,
                'y' => 37.0,
                'width' => 50.0,
                'height' => 3.2,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 12. Cuerpo de Certificación
            $elements[] = [
                'id' => 'el_body_text',
                'type' => 'text',
                'name' => 'Cuerpo de Certificación',
                'content' => $flat['body_text'] ?? 'Por su destacada participación en calidad de {{certificado.tipo}} en la actividad académica "{{actividad.nombre}}", realizado {{actividad.fechas}} con una carga horaria de {{actividad.horas}} horas académicas.',
                'font_family' => $fontFamily,
                'font_size' => 11.5,
                'line_height' => 1.5,
                'text_color' => $textColor,
                'text_align' => 'center',
                'x' => 10.0,
                'y' => 40.8,
                'width' => 80.0,
                'height' => 11.8,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => true,
            ];

            // 13. Lugar, Fecha y Respaldo de Resolución
            $elements[] = [
                'id' => 'el_date_place',
                'type' => 'text',
                'name' => 'Lugar, Fecha y Acreditación',
                'content' => '{{certificado.lugar_fecha_emision}} • {{acreditacion.resolucion}}',
                'font_family' => $fontFamily,
                'font_size' => 9.5,
                'font_weight' => 'bold',
                'text_color' => '#334155',
                'text_align' => 'center',
                'x' => 8.0,
                'y' => 53.5,
                'width' => 84.0,
                'height' => 3.8,
                'z_index' => 10,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];

            // 14. Bloque Inferior Balanceado (Firmas, Sello Seco y QR)
            if ($hasSecondSig) {
                $elements[] = [
                    'id' => 'el_signature_1',
                    'type' => 'signature',
                    'name' => 'Firma 1 (Vicedecano)',
                    'authority_name' => $flat['signatures'][0]['name'] ?? 'Ing. Aniceto Velarde Forest',
                    'authority_title' => $flat['signatures'][0]['position'] ?? 'Vicedecano de Facultad',
                    'institution' => $flat['signatures'][0]['institution'] ?? 'Facultad de Tecnología - UMSA',
                    'asset_path' => $flat['signatures'][0]['asset_path'] ?? 'assets/institutional/firma_vicedecano_ft.svg',
                    'x' => 8.0,
                    'y' => 60.5,
                    'width' => 26.0,
                    'height' => 25.0,
                    'z_index' => 10,
                    'is_locked' => false,
                    'is_visible' => true,
                    'is_mandatory' => true,
                ];

                $elements[] = [
                    'id' => 'el_signature_2',
                    'type' => 'signature',
                    'name' => 'Firma 2 (Coordinador)',
                    'authority_name' => $flat['signatures'][1]['name'] ?? 'Lic. Jorge Velasco Orellanos',
                    'authority_title' => $flat['signatures'][1]['position'] ?? 'Decano de Facultad',
                    'institution' => $flat['signatures'][1]['institution'] ?? 'Facultad de Tecnología - UMSA',
                    'asset_path' => $flat['signatures'][1]['asset_path'] ?? 'assets/institutional/firma_decano_ft.svg',
                    'x' => 34.0,
                    'y' => 60.5,
                    'width' => 26.0,
                    'height' => 25.0,
                    'z_index' => 10,
                    'is_locked' => false,
                    'is_visible' => true,
                    'is_mandatory' => false,
                ];

                $elements[] = [
                    'id' => 'el_stamp',
                    'type' => 'stamp',
                    'name' => 'Sello Seco Oficial',
                    'asset_path' => 'assets/institutional/sello_vicedecanato_ft.svg',
                    'x' => 61.0,
                    'y' => 60.5,
                    'width' => 14.0,
                    'height' => 25.0,
                    'opacity' => 0.95,
                    'z_index' => 10,
                    'is_locked' => false,
                    'is_visible' => isset($flat['show_stamp']) ? (bool) $flat['show_stamp'] : true,
                    'is_mandatory' => false,
                ];

                $elements[] = [
                    'id' => 'el_qr',
                    'type' => 'qr',
                    'name' => 'Código QR Oficial',
                    'show_code' => true,
                    'x' => 75.0,
                    'y' => 58.0,
                    'width' => 18.0,
                    'height' => 28.0,
                    'z_index' => 10,
                    'is_locked' => false,
                    'is_visible' => isset($flat['show_qr']) ? (bool) $flat['show_qr'] : true,
                    'is_mandatory' => true,
                ];
            } else {
                $elements[] = [
                    'id' => 'el_signature_1',
                    'type' => 'signature',
                    'name' => 'Firma 1 (Vicedecano)',
                    'authority_name' => $flat['signatures'][0]['name'] ?? 'Ing. Aniceto Velarde Forest',
                    'authority_title' => $flat['signatures'][0]['position'] ?? 'Vicedecano de Facultad',
                    'institution' => $flat['signatures'][0]['institution'] ?? 'Facultad de Tecnología - UMSA',
                    'asset_path' => $flat['signatures'][0]['asset_path'] ?? 'assets/institutional/firma_vicedecano_ft.svg',
                    'x' => 14.0,
                    'y' => 60.5,
                    'width' => 30.0,
                    'height' => 25.0,
                    'z_index' => 10,
                    'is_locked' => false,
                    'is_visible' => true,
                    'is_mandatory' => true,
                ];

                $elements[] = [
                    'id' => 'el_stamp',
                    'type' => 'stamp',
                    'name' => 'Sello Seco Oficial',
                    'asset_path' => 'assets/institutional/sello_vicedecanato_ft.svg',
                    'x' => 48.0,
                    'y' => 60.5,
                    'width' => 17.0,
                    'height' => 25.0,
                    'opacity' => 0.95,
                    'z_index' => 10,
                    'is_locked' => false,
                    'is_visible' => isset($flat['show_stamp']) ? (bool) $flat['show_stamp'] : true,
                    'is_mandatory' => false,
                ];

                $elements[] = [
                    'id' => 'el_qr',
                    'type' => 'qr',
                    'name' => 'Código QR Oficial',
                    'show_code' => true,
                    'x' => 68.0,
                    'y' => 58.0,
                    'width' => 20.0,
                    'height' => 28.0,
                    'z_index' => 10,
                    'is_locked' => false,
                    'is_visible' => isset($flat['show_qr']) ? (bool) $flat['show_qr'] : true,
                    'is_mandatory' => true,
                ];
            }
        }

        return array_merge($flat, [
            'page' => [
                'orientation' => $orientation,
                'width_mm' => $widthMm,
                'height_mm' => $heightMm,
                'background_color' => '#ffffff',
                'background_asset_id' => null,
                'border_style' => $borderStyle,
                'border_primary_color' => $primaryColor,
                'border_secondary_color' => $secondaryColor,
                'border_width_px' => 3,
                'border_inset_mm' => 8,
            ],
            'elements' => $elements,
        ]);
    }

    /**
     * Genera un esquema base en blanco para la creación de plantillas desde cero.
     */
    public function createBlankSchema(string $orientation = 'landscape'): array
    {
        $widthMm = ($orientation === 'portrait') ? 210 : 297;
        $heightMm = ($orientation === 'portrait') ? 297 : 210;

        return [
            'primary_color' => '#024966',
            'secondary_color' => '#d4af37',
            'accent_color' => '#b45309',
            'text_color' => '#1e293b',
            'font_family' => 'Helvetica',
            'border_style' => 'ornamental-double',
            'page' => [
                'orientation' => $orientation,
                'width_mm' => $widthMm,
                'height_mm' => $heightMm,
                'background_color' => '#ffffff',
                'background_asset_id' => null,
                'border_style' => 'ornamental-double',
                'border_primary_color' => '#024966',
                'border_secondary_color' => '#d4af37',
                'border_width_px' => 3,
                'border_inset_mm' => 8,
            ],
            'elements' => [],
        ];
    }
}
