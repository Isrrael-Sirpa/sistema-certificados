<?php

namespace App\Services;

class CertificateLayoutService
{
    public const VERSION = 2;

    /** @return array<string, array<int, float|int>> */
    public function presets(string $orientation): array
    {
        if ($orientation === 'portrait') {
            return [
                'header' => [22, 6, 56, 5, 15, 12], 'faculty' => [20, 11.5, 60, 3, 11, 10],
                'department' => [20, 15, 60, 3, 10, 9], 'divider' => [20, 19, 60, .3, 1, 1], 'preamble' => [12, 20.5, 76, 2.5, 9.5, 9],
                'award' => [10, 24, 80, 11, 30, 21], 'intro' => [10, 36, 80, 3, 11, 10],
                'recipient_intro' => [10, 39, 80, 2, 10, 9], 'beneficiary' => [10, 42, 80, 8.5, 26, 20], 'document' => [15, 51, 70, 2.5, 10, 9],
                'resolution' => [12, 54, 76, 2, 8.5, 8], 'body' => [12, 57, 76, 13, 13, 11.5], 'date' => [12, 71, 76, 5, 10, 9],
                'qualification' => [12, 77, 64, 3, 12, 10], 'mention' => [12, 80, 64, 2.5, 10.5, 9],
                'validity' => [10, 95, 80, 2, 8.5, 7.5], 'expiration' => [10, 93, 80, 2, 8.5, 7.5],
                'metadata' => [10, 97, 80, 1.5, 7.5, 7], 'signature_divider' => [12, 82.5, 64, .2, 1, 1],
            ];
        }

        return [
            'header' => [19, 6, 62, 4.5, 17, 14], 'faculty' => [19, 11, 62, 3.5, 13, 11],
            'department' => [19, 15, 62, 3, 11, 10], 'divider' => [20, 19.5, 60, .3, 1, 1], 'preamble' => [15, 19, 70, 2, 9, 8],
            'award' => [9, 23, 82, 8.5, 32, 23], 'intro' => [10, 33, 80, 3.5, 11.5, 10],
            'recipient_intro' => [10, 35.5, 80, 2, 9.5, 9], 'beneficiary' => [9, 38, 82, 7, 28, 21], 'document' => [15, 46, 70, 3, 10.5, 9],
            'body' => [9, 51, 82, 15, 14, 12], 'date' => [9, 67, 82, 5, 10.5, 9.5],
            'qualification' => [10, 74, 54, 3.5, 12, 10], 'mention' => [10, 78, 54, 3, 10.5, 9],
            'validity' => [10, 94, 80, 2, 8.5, 7.5], 'expiration' => [10, 93, 80, 2, 8.5, 7.5], 'resolution' => [12, 49, 76, 2, 8.5, 8],
            'metadata' => [10, 97, 80, 1.5, 7.5, 7], 'signature_divider' => [8, 80, 55, .2, 1, 1],
        ];
    }

    public function role(array $element): ?string
    {
        if (! empty($element['layout_role'])) {
            return $element['layout_role'];
        }
        $field = $element['data_field'] ?? null;
        if (in_array($field, ['qualification', 'mention', 'validity', 'expiration'], true)) {
            return $field;
        }
        $id = $element['id'] ?? '';
        $ids = [
            'el_header_title' => 'header', 'el_header_subtitle' => 'faculty', 'el_header_department' => 'department',
            'el_award_title' => 'award', 'el_award_intro' => 'intro', 'el_beneficiary_intro' => 'recipient_intro', 'el_header_preamble' => 'preamble',
            'el_preamble' => 'preamble', 'el_resolution' => 'resolution',
            'el_divider_signatures' => 'signature_divider',
            'el_beneficiary_name' => 'beneficiary', 'el_beneficiary_doc' => 'document',
            'el_body_text' => 'body', 'el_date_place' => 'date', 'el_divider' => 'divider',
        ];
        if (isset($ids[$id])) {
            return $ids[$id];
        }
        $types = ['header_title' => 'header', 'header_subtitle' => 'faculty', 'certificate_type' => 'award', 'beneficiary_name' => 'beneficiary', 'beneficiary_document' => 'document', 'body_text' => 'body', 'activity_dates' => 'date', 'metadata_code' => 'metadata', 'divider' => 'divider', 'decorative_line' => 'divider', 'line' => 'divider'];
        if (isset($types[$element['type'] ?? ''])) {
            return $types[$element['type']];
        }
        $content = $element['content'] ?? '';
        foreach ([
            'beneficiario.nombre' => 'beneficiary', 'beneficiario.documento' => 'document',
            'certificado.puntaje' => 'qualification', 'certificado.calificacion' => 'qualification',
            'certificado.mencion' => 'mention', 'certificado.vigencia' => 'validity',
            'certificado.fecha_vencimiento' => 'expiration', 'actividad.nombre' => 'body',
            'certificado.tipo' => 'award', 'certificado.lugar_fecha_emision' => 'date',
            'certificado.fecha_emision_larga' => 'date', 'institucion.nombre' => 'header',
            'facultad.nombre' => 'faculty', 'vicedecanato.nombre' => 'department',
        ] as $token => $role) {
            if (str_contains($content, $token)) {
                return $role;
            }
        }

        return null;
    }

    public function improve(array $schema, string $orientation): array
    {
        $schema['layout_version'] = self::VERSION;
        $schema['page'] = array_merge($schema['page'] ?? [], ['orientation' => $orientation, 'width_mm' => $orientation === 'portrait' ? 210 : 297, 'height_mm' => $orientation === 'portrait' ? 297 : 210]);
        if (empty($schema['elements'])) {
            return $schema;
        }
        $presets = $this->presets($orientation);
        $elements = $schema['elements'];
        foreach (['qualification' => ['Calificación', 'Calificación: {{certificado.puntaje}}', 'with_score'], 'mention' => ['Mención académica', '{{certificado.mencion}}', 'with_score'], 'validity' => ['Vigencia', '{{certificado.vigencia}}', 'always']] as $role => [$name, $content, $rule]) {
            $exists = array_filter($elements, fn (array $element): bool => $this->role($element) === $role);
            if (! $exists) {
                $elements[] = ['id' => 'el_academic_'.$role, 'type' => 'text', 'name' => $name, 'content' => $content, 'data_field' => $role, 'layout_role' => $role, 'visibility_rule' => $rule, 'is_visible' => true, 'is_locked' => false, 'opacity' => 1, 'z_index' => 20, 'styles' => []];
            }
        }
        foreach ($elements as &$element) {
            $role = $this->role($element);
            if ($role) {
                $element['layout_role'] = $role;
            }
            if (! empty($element['is_locked'])) {
                continue;
            }
            $styles = $element['styles'] ?? [];
            foreach (['font_family', 'font_size', 'font_weight', 'font_style', 'text_align', 'text_color', 'line_height', 'letter_spacing', 'object_fit'] as $property) {
                if (! isset($styles[$property]) && isset($element[$property])) {
                    $styles[$property] = $element[$property];
                }
                unset($element[$property]);
            }
            if ($role && isset($presets[$role])) {
                [$element['x'], $element['y'], $element['width'], $element['height'], $fontSize, $minimum] = $presets[$role];
                $styles = array_merge($styles, ['font_size' => $fontSize, 'min_font_size' => $minimum, 'fit_text' => true, 'line_height' => $role === 'body' ? 1.45 : 1.2, 'text_align' => 'center']);
                if (in_array($role, ['header', 'award', 'beneficiary', 'qualification'], true)) {
                    $styles['font_weight'] = 'bold';
                }
                if ($role === 'beneficiary') {
                    $styles['border_bottom'] = false;
                    $element['border_bottom'] = false;
                }
                if ($role === 'body') {
                    $styles['letter_spacing'] = 0;
                }
                if ($role === 'preamble' && str_contains($element['content'] ?? '', 'CIENCIAS PURAS')) {
                    $element['content'] = '{{vicedecanato.nombre}} · {{facultad.nombre}}';
                }
                if (in_array($role, ['divider', 'signature_divider'], true)) {
                    $styles['height'] = 1;
                    $styles['background'] = $schema['secondary_color'] ?? '#d4af37';
                }
            } elseif (($element['type'] ?? '') === 'watermark') {
                [$element['x'], $element['y'], $element['width'], $element['height']] = [27, 25, 46, 48];
                $element['opacity'] = .035;
            } elseif (($element['type'] ?? '') === 'stamp') {
                [$element['x'], $element['y'], $element['width'], $element['height']] = $orientation === 'portrait' ? [64, 84, 12, 9] : [67, 80, 11, 15.6];
            }
            $element['styles'] = $styles;
        }
        unset($element);
        $this->arrange($elements, ['logo'], $orientation, 'logos');
        $this->arrange($elements, ['signature', 'signature_block'], $orientation, 'signatures');
        $this->arrange($elements, ['qr', 'qr_code'], $orientation, 'qr');
        $schema['elements'] = $elements;

        return $schema;
    }

    private function arrange(array &$elements, array $types, string $orientation, string $group): void
    {
        $indexes = array_keys(array_filter($elements, fn (array $element): bool => in_array($element['type'] ?? '', $types, true) && empty($element['is_locked'])));
        $count = count($indexes);
        foreach ($indexes as $position => $index) {
            $portrait = $orientation === 'portrait';
            if ($group === 'logos') {
                $width = $portrait ? 14 : 10;
                $height = $portrait ? 11 : 16;
                $x = $count === 1 ? 6 : 6 + $position * (88 - $width) / max(1, $count - 1);
                $y = 5;
            } elseif ($group === 'qr') {
                [$x, $y, $width, $height] = $portrait ? [80, 82, 13, 12] : [83, 76, 11, 18];
                $x = max(3, $x - $position * ($width + 1));
            } else {
                $columns = min(3, $count);
                $rows = (int) ceil($count / max(1, $columns));
                $width = (56 - 4 * ($columns - 1)) / max(1, $columns);
                $height = min($portrait ? 10.5 : 13, 18 / $rows);
                $x = 8 + ($position % $columns) * ($width + 4);
                $y = ($portrait ? 82.5 : 81) - ($rows - 1) * $height + intdiv($position, $columns) * $height;
                $elements[$index]['styles']['font_size'] = $columns > 2 ? 10 : 11.5;
            }
            $elements[$index] = array_merge($elements[$index], compact('x', 'y', 'width', 'height'));
        }
    }
}
