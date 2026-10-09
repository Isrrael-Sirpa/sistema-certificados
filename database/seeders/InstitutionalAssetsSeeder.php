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
            // 1. Logos
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
                'name' => 'Sello Institucional FCPN',
                'type' => InstitutionalAsset::TYPE_LOGO,
                'file_path' => 'assets/institutional/logo_fcpn.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'institution' => 'Facultad de Ciencias Puras y Naturales',
                    'default_width' => '70px',
                    'position' => 'top_right',
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Emblema Vicedecanato',
                'type' => InstitutionalAsset::TYPE_LOGO,
                'file_path' => 'assets/institutional/logo_vicedecanato.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'institution' => 'Vicedecanato FCPN',
                    'default_width' => '65px',
                    'position' => 'top_center',
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 3,
            ],

            // 2. Firmas
            [
                'name' => 'Firma Oficial - Vicedecano de Facultad',
                'type' => InstitutionalAsset::TYPE_SIGNATURE,
                'file_path' => 'assets/institutional/firma_vicedecano.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'authority_name' => 'Dr. Carlos Mendoza Fernández',
                    'authority_title' => 'Vicedecano',
                    'institution' => 'Facultad de Ciencias Puras y Naturales - UMSA',
                    'default_width' => '170px',
                ],
                'is_mandatory' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Firma - Coordinador General de Evento',
                'type' => InstitutionalAsset::TYPE_SIGNATURE,
                'file_path' => 'assets/institutional/firma_coordinador.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'authority_name' => 'M.Sc. Roberto Aguilar Paredes',
                    'authority_title' => 'Coordinador General',
                    'institution' => 'Comisión Académica del Vicedecanato',
                    'default_width' => '160px',
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 2,
            ],

            // 3. Sellos
            [
                'name' => 'Sello Seco Oficial - Vicedecanato',
                'type' => InstitutionalAsset::TYPE_STAMP,
                'file_path' => 'assets/institutional/sello_oficial_vicedecanato.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'department' => 'Vicedecanato',
                    'default_width' => '110px',
                    'opacity' => 0.9,
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 1,
            ],

            // 4. Marca de Agua
            [
                'name' => 'Marca de Agua - Escudo Académico',
                'type' => InstitutionalAsset::TYPE_WATERMARK,
                'file_path' => 'assets/institutional/marca_agua_escudo.svg',
                'mime_type' => 'image/svg+xml',
                'metadata' => [
                    'default_width' => '320px',
                    'opacity' => 0.05,
                    'rotation' => 0,
                ],
                'is_mandatory' => false,
                'is_active' => true,
                'display_order' => 1,
            ],
        ];

        foreach ($assets as $assetData) {
            $assetData['created_by_user_id'] = $admin?->id;
            InstitutionalAsset::firstOrCreate(
                ['name' => $assetData['name'], 'type' => $assetData['type']],
                $assetData
            );
        }
    }
}
