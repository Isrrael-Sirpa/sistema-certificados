<?php

namespace Database\Seeders;

use App\Models\InstitutionalAsset;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstitutionalAssetsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $assets = [
            // 1. Logos Oficiales
            [
                'name' => 'Escudo Oficial UMSA',
                'type' => InstitutionalAsset::TYPE_LOGO,
                'file_path' => 'assets/institutional/logo_umsa.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'institution' => 'Universidad Mayor de San Andrés',
                    'default_width' => '75px',
                    'position' => 'top_left',
                ],
                'is_mandatory' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Escudo Oficial Facultad de Tecnología (FT-UMSA)',
                'type' => InstitutionalAsset::TYPE_LOGO,
                'file_path' => 'assets/institutional/logo_ft_umsa.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'institution' => 'Facultad de Tecnología - UMSA',
                    'default_width' => '75px',
                    'position' => 'top_right',
                ],
                'is_mandatory' => true,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Emblema Heráldico FT-UMSA',
                'type' => InstitutionalAsset::TYPE_LOGO,
                'file_path' => 'assets/institutional/emblema_ft_oficial.png',
                'mime_type' => 'image/png',
                'metadata' => [
                    'institution' => 'Facultad de Tecnología',
                    'default_width' => '80px',
                    'position' => 'top_center',
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 3,
            ],

            // 2. Firmas Oficiales de Autoridades Vigentes
            [
                'name' => 'Firma Oficial - Vicedecano de Facultad',
                'type' => InstitutionalAsset::TYPE_SIGNATURE,
                'file_path' => 'assets/institutional/firma_vicedecano_ft.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'authority_name' => 'Ing. Aniceto Velarde Forest',
                    'authority_title' => 'Vicedecano',
                    'institution' => 'Facultad de Tecnología - UMSA',
                    'default_width' => '170px',
                ],
                'is_mandatory' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Firma Oficial - Decano de Facultad',
                'type' => InstitutionalAsset::TYPE_SIGNATURE,
                'file_path' => 'assets/institutional/firma_decano_ft.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'authority_name' => 'Lic. Jorge Velasco Orellanos',
                    'authority_title' => 'Decano',
                    'institution' => 'Facultad de Tecnología - UMSA',
                    'default_width' => '170px',
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Firma - Coordinador Académico / Director de Carrera',
                'type' => InstitutionalAsset::TYPE_SIGNATURE,
                'file_path' => 'assets/institutional/firma_coordinador.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'authority_name' => 'M.Sc. Coordinador Académico',
                    'authority_title' => 'Director de Carrera / Comisión Académica',
                    'institution' => 'Facultad de Tecnología - UMSA',
                    'default_width' => '160px',
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 3,
            ],

            // 3. Sellos Oficiales
            [
                'name' => 'Sello Oficial - Vicedecanato FT-UMSA',
                'type' => InstitutionalAsset::TYPE_STAMP,
                'file_path' => 'assets/institutional/sello_vicedecanato_ft.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'department' => 'Vicedecanato',
                    'default_width' => '115px',
                    'opacity' => 0.9,
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 1,
            ],

            // 4. Marca de Agua
            [
                'name' => 'Marca de Agua - Insignia FT-UMSA',
                'type' => InstitutionalAsset::TYPE_WATERMARK,
                'file_path' => 'assets/institutional/marca_agua_ft.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'default_width' => '320px',
                    'opacity' => 0.06,
                    'rotation' => 0,
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 1,
            ],

            // 5. Elementos Decorativos y Marcos
            [
                'name' => 'Esquinero Ornamental Académico',
                'type' => InstitutionalAsset::TYPE_DECORATIVE,
                'file_path' => 'assets/institutional/esquinero_ornamental.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'default_width' => '45px',
                    'position' => 'corners',
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 1,
            ],
        ];

        foreach ($assets as $assetData) {
            $assetData['created_by_user_id'] = $admin?->id;
            InstitutionalAsset::updateOrCreate(
                ['name' => $assetData['name'], 'type' => $assetData['type']],
                $assetData
            );
        }
    }
}
