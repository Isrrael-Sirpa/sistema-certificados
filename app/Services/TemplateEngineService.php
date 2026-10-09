<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\InstitutionalAsset;
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
            $role = match(mb_strtolower($modality)) {
                'asistencia' => 'Asistente',
                'aprobación', 'aprobacion' => 'Aprobante',
                'disertante / ponente' => 'Disertante / Ponente',
                'organizador' => 'Organizador',
                default => $modality,
            };

            $replacements['{{certificado.codigo}}'] = is_array($c) ? ($c['code'] ?? '') : ($c->certificate_code ?? '');
            $replacements['{{certificado.fecha_emision}}'] = is_array($c) ? ($c['issue_date'] ?? '') : (isset($c->issue_date) ? Carbon::parse($c->issue_date)->translatedFormat('d \d\e F \d\e Y') : '');
            $replacements['{{certificado.fecha_vencimiento}}'] = is_array($c) ? ($c['expiration_date'] ?? '') : (isset($c->expiration_date) ? Carbon::parse($c->expiration_date)->translatedFormat('d \d\e F \d\e Y') : '');
            $replacements['{{certificado.tipo}}'] = $rawType;
            $replacements['{{certificado.modalidad}}'] = $modality;
            $replacements['{{participante.rol}}'] = $role;
            $replacements['en calidad de {{certificado.tipo}}'] = match(mb_strtolower($modality)) {
                'asistencia' => 'en calidad de Asistente',
                'aprobación', 'aprobacion' => 'habiendo aprobado satisfactoriamente',
                'disertante / ponente' => 'en calidad de Disertante / Ponente',
                'organizador' => 'en calidad de Organizador',
                default => 'en calidad de ' . $modality,
            };
        }

        // Autoridades y Ajustes Globales
        if (isset($context['settings'])) {
            $s = $context['settings'];
            $replacements['{{institucion.nombre}}'] = $s['institution_name'] ?? 'UNIVERSIDAD MAYOR DE SAN ANDRÉS';
            $replacements['{{facultad.nombre}}'] = $s['faculty_name'] ?? 'FACULTAD DE CIENCIAS PURAS Y NATURALES';
            $replacements['{{vicedecanato.nombre}}'] = $s['department_name'] ?? 'VICEDECANATO';
            $replacements['{{autoridad.nombre}}'] = $s['authority_name'] ?? 'Dr. Carlos Mendoza Fernández';
            $replacements['{{autoridad.cargo}}'] = $s['authority_position'] ?? 'Vicedecano de Facultad';
            $replacements['{{institucion.ciudad}}'] = $s['institution_city'] ?? 'La Paz - Bolivia';
        }

        // Campos Adicionales Personalizados
        if (isset($context['custom_fields']) && is_array($context['custom_fields'])) {
            foreach ($context['custom_fields'] as $key => $val) {
                $replacements['{{campos.' . $key . '}}'] = (string) $val;
                $replacements['{{' . $key . '}}'] = (string) $val;
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
            // 1. Institucional Clásica
            [
                'name' => 'Institucional Clásica (Oficial UMSA)',
                'slug' => 'institucional-clasica',
                'category' => 'institucional',
                'orientation' => 'landscape',
                'description' => 'Diseño solemne y tradicional con marco doble ornamental oro y azul marino, escudo universitario y sello oficial.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'classic',
                    'primary_color' => '#024966',
                    'secondary_color' => '#d4af37',
                    'accent_color' => '#b45309',
                    'text_color' => '#1e293b',
                    'font_family' => 'Helvetica',
                    'border_style' => 'ornamental-double',
                    'border_width' => '3px',
                    'margins' => ['top' => 15, 'bottom' => 15, 'left' => 15, 'right' => 15],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE CIENCIAS PURAS Y NATURALES',
                    'header_department' => 'VICEDECANATO',
                    'award_intro' => 'Otorgado a:',
                    'body_text' => 'Por su destacada participación en calidad de {{certificado.tipo}} en la actividad académica "{{actividad.nombre}}", realizado {{actividad.fechas}} con una carga horaria de {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_opacity' => 0.05,
                    'watermark_text' => 'VICEDECANATO',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Dr. Carlos Mendoza Fernández',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'Facultad de Ciencias Puras y Naturales - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_fcpn.svg', 'position' => 'right', 'width' => '68px'],
                    ],
                ],
            ],

            // 2. Institucional Moderna
            [
                'name' => 'Institucional Moderna (Minimalista)',
                'slug' => 'institucional-moderna',
                'category' => 'institucional',
                'orientation' => 'landscape',
                'description' => 'Líneas limpias, tipografía contemporánea, cabecera geométrica sofisticada y contraste armónico.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'modern',
                    'primary_color' => '#0b1f44',
                    'secondary_color' => '#0284c7',
                    'accent_color' => '#f59e0b',
                    'text_color' => '#0f172a',
                    'font_family' => 'Helvetica',
                    'border_style' => 'modern-clean',
                    'border_width' => '2px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 14, 'right' => 14],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'VICEDECANATO DE CIENCIAS PURAS Y NATURALES',
                    'award_intro' => 'Se confiere el presente certificado a:',
                    'body_text' => 'En reconocimiento a su participación y compromiso en "{{actividad.nombre}}", desarrollado del {{actividad.fechas}}, acreditando {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_opacity' => 0.04,
                    'watermark_text' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'left_bottom',
                    'show_stamp' => false,
                    'signatures' => [
                        [
                            'name' => 'Dr. Carlos Mendoza Fernández',
                            'position' => 'Vicedecano',
                            'institution' => 'FCPN - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '70px'],
                        ['path' => 'assets/institutional/logo_vicedecanato.svg', 'position' => 'right', 'width' => '65px'],
                    ],
                ],
            ],

            // 3. Académica Elegante
            [
                'name' => 'Académica Elegante (Alta Distinción)',
                'slug' => 'academica-elegante',
                'category' => 'academica',
                'orientation' => 'landscape',
                'description' => 'Diseño distinguido para eventos magistrales, honores de facultad y ceremonias solemnes.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'academic-gold',
                    'primary_color' => '#1e1b4b',
                    'secondary_color' => '#b45309',
                    'accent_color' => '#d97706',
                    'text_color' => '#1e293b',
                    'font_family' => 'DejaVu Serif',
                    'border_style' => 'gold-filigree',
                    'border_width' => '4px',
                    'margins' => ['top' => 14, 'bottom' => 14, 'left' => 16, 'right' => 16],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE CIENCIAS PURAS Y NATURALES',
                    'header_department' => 'VICEDECANATO',
                    'award_intro' => 'El Vicedecanato de la Facultad otorga a:',
                    'body_text' => 'El presente testimonio de reconocimiento por su destacada actuación como {{certificado.tipo}} en "{{actividad.nombre}}", realizado {{actividad.fechas}} con una duración de {{actividad.horas}} horas lectivas.',
                    'show_watermark' => true,
                    'watermark_opacity' => 0.05,
                    'watermark_text' => 'EXCELLENTIA',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Dr. Carlos Mendoza Fernández',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'FCPN - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                        ],
                        [
                            'name' => 'M.Sc. Roberto Aguilar Paredes',
                            'position' => 'Coordinador Académico',
                            'institution' => 'Comisión de Grado',
                            'asset_path' => 'assets/institutional/firma_coordinador.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '75px'],
                        ['path' => 'assets/institutional/logo_fcpn.svg', 'position' => 'right', 'width' => '70px'],
                    ],
                ],
            ],

            // 4. Seminario
            [
                'name' => 'Seminario y Congreso Internacional',
                'slug' => 'seminario-congreso',
                'category' => 'seminario',
                'orientation' => 'landscape',
                'description' => 'Configurado para simposios, conferencias científicas y seminarios internacionales con doble firma.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'seminar',
                    'primary_color' => '#0c4a6e',
                    'secondary_color' => '#0284c7',
                    'accent_color' => '#eab308',
                    'text_color' => '#0f172a',
                    'font_family' => 'Helvetica',
                    'border_style' => 'geometric-frame',
                    'border_width' => '3px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 14, 'right' => 14],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'VICEDECANATO • FACULTAD DE CIENCIAS PURAS Y NATURALES',
                    'award_intro' => 'Certifica que:',
                    'body_text' => 'Ha participado activamente en el seminario académico especializado "{{actividad.nombre}}", llevado a cabo {{actividad.fechas}}, con una carga total de {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_opacity' => 0.04,
                    'watermark_text' => 'SEMINARIO',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'stamp_position' => 'right_bottom',
                    'signatures' => [
                        [
                            'name' => 'Dr. Carlos Mendoza Fernández',
                            'position' => 'Vicedecano',
                            'institution' => 'FCPN - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                        ],
                        [
                            'name' => 'M.Sc. Roberto Aguilar Paredes',
                            'position' => 'Coordinador General',
                            'institution' => 'Comisión Organizadora',
                            'asset_path' => 'assets/institutional/firma_coordinador.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '70px'],
                        ['path' => 'assets/institutional/logo_fcpn.svg', 'position' => 'center', 'width' => '65px'],
                        ['path' => 'assets/institutional/logo_vicedecanato.svg', 'position' => 'right', 'width' => '65px'],
                    ],
                ],
            ],

            // 5. Capacitación
            [
                'name' => 'Capacitación y Taller Intensivo',
                'slug' => 'capacitacion-taller',
                'category' => 'capacitacion',
                'orientation' => 'landscape',
                'description' => 'Especial para cursos técnicos, workshops, laboratorios y programas de actualización docente y estudiantil.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'workshop',
                    'primary_color' => '#14532d',
                    'secondary_color' => '#15803d',
                    'accent_color' => '#d97706',
                    'text_color' => '#1c1917',
                    'font_family' => 'Helvetica',
                    'border_style' => 'modern-clean',
                    'border_width' => '2.5px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 15, 'right' => 15],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE CIENCIAS PURAS Y NATURALES • VICEDECANATO',
                    'award_intro' => 'Se otorga a:',
                    'body_text' => 'Por haber completado satisfactoriamente el programa de capacitación "{{actividad.nombre}}", realizado {{actividad.fechas}} con una evaluación curricular de {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_opacity' => 0.04,
                    'watermark_text' => 'CAPACITACIÓN',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Dr. Carlos Mendoza Fernández',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'FCPN - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '70px'],
                        ['path' => 'assets/institutional/logo_fcpn.svg', 'position' => 'right', 'width' => '65px'],
                    ],
                ],
            ],

            // 6. Reconocimiento
            [
                'name' => 'Reconocimiento y Honor al Mérito',
                'slug' => 'reconocimiento-honorifico',
                'category' => 'reconocimiento',
                'orientation' => 'landscape',
                'description' => 'Certificado de mérito especial, trayectorias destacadas, olimpiadas científicas y premios universitarios.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'distinction',
                    'primary_color' => '#4c0519',
                    'secondary_color' => '#b45309',
                    'accent_color' => '#d97706',
                    'text_color' => '#1e1b4b',
                    'font_family' => 'Helvetica',
                    'border_style' => 'ornamental-double',
                    'border_width' => '3.5px',
                    'margins' => ['top' => 14, 'bottom' => 14, 'left' => 16, 'right' => 16],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'VICEDECANATO DE CIENCIAS PURAS Y NATURALES',
                    'award_intro' => 'CONFIERE EL PRESENTE RECONOCIMIENTO A:',
                    'body_text' => 'Por su notable excelencia, dedicación e invalorable aporte institucional demostrado en "{{actividad.nombre}}", celebrado en La Paz, {{actividad.fechas}}.',
                    'show_watermark' => true,
                    'watermark_opacity' => 0.05,
                    'watermark_text' => 'HONOR AL MÉRITO',
                    'show_qr' => true,
                    'qr_size' => 85,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Dr. Carlos Mendoza Fernández',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'FCPN - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '75px'],
                        ['path' => 'assets/institutional/logo_fcpn.svg', 'position' => 'right', 'width' => '70px'],
                    ],
                ],
            ],

            // 7. Disertante
            [
                'name' => 'Disertante / Ponente Magistral',
                'slug' => 'disertante-ponente',
                'category' => 'disertante',
                'orientation' => 'landscape',
                'description' => 'Para conferencistas internacionales, docentes invitados, ponentes y expositores de investigaciones.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'speaker',
                    'primary_color' => '#1e1b4b',
                    'secondary_color' => '#4338ca',
                    'accent_color' => '#d4af37',
                    'text_color' => '#0f172a',
                    'font_family' => 'Helvetica',
                    'border_style' => 'geometric-frame',
                    'border_width' => '3px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 15, 'right' => 15],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE CIENCIAS PURAS Y NATURALES • VICEDECANATO',
                    'award_intro' => 'Certificado de Disertante concedido a:',
                    'body_text' => 'En testimonio de reconocimiento por su brillante exposición y aporte científico en calidad de DISERTANTE MAGISTRAL en el evento "{{actividad.nombre}}", realizado {{actividad.fechas}} con una carga horaria de {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_opacity' => 0.05,
                    'watermark_text' => 'DISERTANTE',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Dr. Carlos Mendoza Fernández',
                            'position' => 'Vicedecano',
                            'institution' => 'FCPN - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                        ],
                        [
                            'name' => 'M.Sc. Roberto Aguilar Paredes',
                            'position' => 'Coordinador del Evento',
                            'institution' => 'Comisión de Postgrado',
                            'asset_path' => 'assets/institutional/firma_coordinador.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_fcpn.svg', 'position' => 'right', 'width' => '68px'],
                    ],
                ],
            ],

            // 8. Aprobación
            [
                'name' => 'Aprobación Curricular Académica',
                'slug' => 'aprobacion-academica',
                'category' => 'aprobacion',
                'orientation' => 'landscape',
                'description' => 'Certifica aprobación rigurosa de evaluaciones curriculares, cumplimiento de carga horaria y requisitos académicos.',
                'view_path' => 'certificates.templates.engine',
                'configuration' => [
                    'theme' => 'approved',
                    'primary_color' => '#1e3a8a',
                    'secondary_color' => '#0284c7',
                    'accent_color' => '#15803d',
                    'text_color' => '#1e293b',
                    'font_family' => 'Helvetica',
                    'border_style' => 'ornamental-double',
                    'border_width' => '3px',
                    'margins' => ['top' => 12, 'bottom' => 12, 'left' => 15, 'right' => 15],
                    'header_title' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                    'header_subtitle' => 'FACULTAD DE CIENCIAS PURAS Y NATURALES',
                    'header_department' => 'VICEDECANATO',
                    'award_intro' => 'Hace constar que:',
                    'body_text' => 'Ha cursado y APROBADO satisfactoriamente las evaluaciones correspondientes a "{{actividad.nombre}}", realizado {{actividad.fechas}}, habiendo cumplido una carga horaria reglamentaria de {{actividad.horas}} horas académicas.',
                    'show_watermark' => true,
                    'watermark_opacity' => 0.05,
                    'watermark_text' => 'APROBADO',
                    'show_qr' => true,
                    'qr_size' => 80,
                    'qr_position' => 'center_bottom',
                    'show_stamp' => true,
                    'signatures' => [
                        [
                            'name' => 'Dr. Carlos Mendoza Fernández',
                            'position' => 'Vicedecano de Facultad',
                            'institution' => 'FCPN - UMSA',
                            'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                        ],
                    ],
                    'logos' => [
                        ['path' => 'assets/institutional/logo_umsa.svg', 'position' => 'left', 'width' => '72px'],
                        ['path' => 'assets/institutional/logo_fcpn.svg', 'position' => 'right', 'width' => '68px'],
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
                if (!isset($el['styles']) || !is_array($el['styles'])) {
                    $el['styles'] = [];
                }
                if (isset($el['font_size'])) $el['styles']['font_size'] = (int) $el['font_size'];
                if (isset($el['font_weight'])) $el['styles']['font_weight'] = $el['font_weight'];
                if (isset($el['font_style'])) $el['styles']['font_style'] = $el['font_style'];
                if (isset($el['text_align'])) $el['styles']['text_align'] = $el['text_align'];
                if (isset($el['text_color'])) $el['styles']['text_color'] = $el['text_color'];
                if (isset($el['font_family'])) $el['styles']['font_family'] = $el['font_family'];
                if (isset($el['object_fit'])) $el['styles']['object_fit'] = $el['object_fit'];

                if (empty($el['asset_url']) && !empty($el['asset_path'])) {
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
        $fontFamily = $flat['font_family'] ?? 'Helvetica';
        $borderStyle = $flat['border_style'] ?? 'ornamental-double';

        $isPortrait = ($orientation === 'portrait');
        $widthMm = $isPortrait ? 210 : 297;
        $heightMm = $isPortrait ? 297 : 210;

        $elements = [];

        // 1. Marca de agua (central con opacidad sutil)
        if (!isset($flat['show_watermark']) || $flat['show_watermark']) {
            $elements[] = [
                'id' => 'el_watermark',
                'type' => 'watermark',
                'name' => 'Marca de Agua Central',
                'watermark_type' => $flat['watermark_type'] ?? 'text',
                'watermark_text' => $flat['watermark_text'] ?? 'VICEDECANATO',
                'asset_id' => $flat['watermark_asset_id'] ?? null,
                'x' => 30.0,
                'y' => 26.0,
                'width' => 40.0,
                'height' => 45.0,
                'opacity' => (float) ($flat['watermark_opacity'] ?? 0.05),
                'rotation' => -25,
                'z_index' => 1,
                'is_locked' => false,
                'is_visible' => true,
                'is_mandatory' => false,
            ];
        }

        // 2. Logo Izquierdo
        $elements[] = [
            'id' => 'el_logo_left',
            'type' => 'logo',
            'name' => 'Logo Izquierdo (UMSA)',
            'asset_id' => $flat['logo_left_id'] ?? 1,
            'asset_path' => $flat['logo_left_path'] ?? 'assets/institutional/logo_umsa.svg',
            'x' => 5.5,
            'y' => 5.5,
            'width' => 12.0,
            'height' => 14.0,
            'opacity' => 1.0,
            'object_fit' => 'contain',
            'z_index' => 10,
            'is_locked' => false,
            'is_visible' => true,
            'is_mandatory' => true,
        ];

        // 3. Logo Derecho
        $elements[] = [
            'id' => 'el_logo_right',
            'type' => 'logo',
            'name' => 'Logo Derecho (Facultad)',
            'asset_id' => $flat['logo_right_id'] ?? 2,
            'asset_path' => $flat['logo_right_path'] ?? 'assets/institutional/logo_fcpn.svg',
            'x' => 82.5,
            'y' => 5.5,
            'width' => 12.0,
            'height' => 14.0,
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
            'font_size' => 14,
            'font_weight' => 'bold',
            'text_color' => $primaryColor,
            'text_align' => 'center',
            'letter_spacing' => 2,
            'line_height' => 1.2,
            'x' => 18.0,
            'y' => 6.0,
            'width' => 64.0,
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
            'content' => $flat['header_subtitle'] ?? 'FACULTAD DE CIENCIAS PURAS Y NATURALES',
            'font_family' => $fontFamily,
            'font_size' => 11,
            'font_weight' => 'bold',
            'text_color' => '#475569',
            'text_align' => 'center',
            'letter_spacing' => 1,
            'line_height' => 1.2,
            'x' => 18.0,
            'y' => 10.5,
            'width' => 64.0,
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
            'font_size' => 12,
            'font_weight' => 'bold',
            'text_color' => $accentColor,
            'text_align' => 'center',
            'letter_spacing' => 1.5,
            'line_height' => 1.2,
            'x' => 18.0,
            'y' => 14.0,
            'width' => 64.0,
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
            'y' => 19.0,
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
            'font_size' => 22,
            'font_weight' => '900',
            'text_color' => $primaryColor,
            'text_align' => 'center',
            'letter_spacing' => 2,
            'x' => 10.0,
            'y' => 21.5,
            'width' => 80.0,
            'height' => 6.5,
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
            'content' => $flat['award_intro'] ?? 'Otorgado a:',
            'font_family' => $fontFamily,
            'font_size' => 11,
            'font_style' => 'italic',
            'text_color' => '#64748b',
            'text_align' => 'center',
            'x' => 25.0,
            'y' => 28.5,
            'width' => 50.0,
            'height' => 3.5,
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
            'font_size' => 20,
            'font_weight' => 'bold',
            'text_color' => '#0f172a',
            'text_align' => 'center',
            'border_bottom' => true,
            'border_bottom_color' => $secondaryColor,
            'x' => 10.0,
            'y' => 32.5,
            'width' => 80.0,
            'height' => 6.5,
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
            'content' => 'Documento: {{beneficiario.documento}}',
            'font_family' => $fontFamily,
            'font_size' => 10,
            'text_color' => '#64748b',
            'text_align' => 'center',
            'x' => 25.0,
            'y' => 39.5,
            'width' => 50.0,
            'height' => 3.5,
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
            'font_size' => 12,
            'line_height' => 1.5,
            'text_color' => $textColor,
            'text_align' => 'center',
            'x' => 12.0,
            'y' => 44.5,
            'width' => 76.0,
            'height' => 14.0,
            'z_index' => 10,
            'is_locked' => false,
            'is_visible' => true,
            'is_mandatory' => true,
        ];

        // 13. Firma Principal
        $elements[] = [
            'id' => 'el_signature_1',
            'type' => 'signature',
            'name' => 'Firma 1 (Vicedecano)',
            'authority_name' => $flat['signatures'][0]['name'] ?? 'Dr. Carlos Mendoza Fernández',
            'authority_title' => $flat['signatures'][0]['position'] ?? 'Vicedecano de Facultad',
            'institution' => $flat['signatures'][0]['institution'] ?? 'Facultad de Ciencias Puras y Naturales - UMSA',
            'asset_path' => $flat['signatures'][0]['asset_path'] ?? 'assets/institutional/firma_vicedecano.svg',
            'x' => 12.0,
            'y' => 65.0,
            'width' => 26.0,
            'height' => 18.0,
            'z_index' => 10,
            'is_locked' => false,
            'is_visible' => true,
            'is_mandatory' => true,
        ];

        // 14. Firma Co-firmante (si aplica)
        $hasSecondSig = count($flat['signatures'] ?? []) >= 2;
        $elements[] = [
            'id' => 'el_signature_2',
            'type' => 'signature',
            'name' => 'Firma 2 (Coordinador)',
            'authority_name' => $flat['signatures'][1]['name'] ?? 'M.Sc. Roberto Aguilar Paredes',
            'authority_title' => $flat['signatures'][1]['position'] ?? 'Coordinador Académico',
            'institution' => $flat['signatures'][1]['institution'] ?? 'Comisión de Grado',
            'asset_path' => $flat['signatures'][1]['asset_path'] ?? 'assets/institutional/firma_coordinador.svg',
            'x' => 38.0,
            'y' => 65.0,
            'width' => 24.0,
            'height' => 18.0,
            'z_index' => 10,
            'is_locked' => false,
            'is_visible' => $hasSecondSig,
            'is_mandatory' => false,
        ];

        // 15. Sello Seco Oficial (posicionado armónicamente según número de firmas)
        $stampX = $hasSecondSig ? 63.0 : 48.0;
        $elements[] = [
            'id' => 'el_stamp',
            'type' => 'stamp',
            'name' => 'Sello Seco Oficial',
            'asset_path' => 'assets/institutional/sello_oficial_vicedecanato.svg',
            'x' => $stampX,
            'y' => 64.0,
            'width' => 14.0,
            'height' => 18.0,
            'opacity' => 0.95,
            'z_index' => 10,
            'is_locked' => false,
            'is_visible' => isset($flat['show_stamp']) ? (bool)$flat['show_stamp'] : true,
            'is_mandatory' => false,
        ];

        // 16. Código QR
        $elements[] = [
            'id' => 'el_qr',
            'type' => 'qr',
            'name' => 'Código QR Oficial',
            'show_code' => true,
            'x' => 80.0,
            'y' => 64.0,
            'width' => 14.0,
            'height' => 18.0,
            'z_index' => 10,
            'is_locked' => false,
            'is_visible' => isset($flat['show_qr']) ? (bool)$flat['show_qr'] : true,
            'is_mandatory' => true,
        ];

        // Preservar valores planos para compatibilidad con código anterior
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
