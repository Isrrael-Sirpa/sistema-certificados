<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class OrganizationalUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // 9 Carreras Oficiales de la Facultad de Tecnología - UMSA
            [
                'name' => 'Carrera de Aeronáutica',
                'code' => 'aeronautica',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de formación aeronáutica y tecnología de aviación de la Facultad de Tecnología.',
            ],
            [
                'name' => 'Carrera de Construcciones Civiles',
                'code' => 'construcciones_civiles',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de ingeniería aplicada a la construcción, infraestructura y obras civiles.',
            ],
            [
                'name' => 'Carrera de Electricidad Industrial',
                'code' => 'electricidad_industrial',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de sistemas eléctricos, generación, distribución y automatización eléctrica.',
            ],
            [
                'name' => 'Carrera de Electrónica y Telecomunicaciones',
                'code' => 'electronica_telecomunicaciones',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de sistemas electrónicos, microcontroladores, redes y telecomunicaciones avanzadas.',
            ],
            [
                'name' => 'Carrera de Electromecánica',
                'code' => 'electromecanica',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de sinergia electromecánica, máquinas rotativas e instalaciones industriales.',
            ],
            [
                'name' => 'Carrera de Geodesia, Topografía y Geomática',
                'code' => 'topografia_geodesia',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de ciencias espaciales, topografía territorial, geodesia y sistemas de información geográfica.',
            ],
            [
                'name' => 'Carrera de Mecánica Automotriz',
                'code' => 'mecanica_automotriz',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de tecnología automotriz, diagnóstico electrónico, termodinámica y motores térmicos.',
            ],
            [
                'name' => 'Carrera de Mecánica Industrial',
                'code' => 'mecanica_industrial',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de manufactura, metalmecánica, diseño de maquinaria industrial y siderurgia.',
            ],
            [
                'name' => 'Carrera de Química Industrial',
                'code' => 'quimica_industrial',
                'type' => OrganizationalUnit::TYPE_CARRERA,
                'responsible_title' => 'Director de Carrera',
                'responsible_name' => 'Ing. Docente Titular',
                'description' => 'Carrera de procesos químicos, control de calidad, análisis instrumental y transformación de materiales.',
            ],

            // Dependencias y Departamentos Administrativos
            [
                'name' => 'Vicedecanato',
                'code' => 'vicedecanato',
                'type' => OrganizationalUnit::TYPE_ADMINISTRATIVO,
                'responsible_title' => 'Vicedecano',
                'responsible_name' => 'Ing. Aniceto Velarde Forest',
                'description' => 'Unidad académica facultativa superior de gestión de certificaciones, docencia y acreditación institucional.',
            ],
            [
                'name' => 'Departamento de Cómputo y Sistemas',
                'code' => 'computo',
                'type' => OrganizationalUnit::TYPE_DEPARTAMENTO,
                'responsible_title' => 'Jefe de Departamento de Cómputo',
                'responsible_name' => 'Ing. Administrador de Sistemas',
                'description' => 'Departamento tecnológico administrador general de plataformas digitales, servidores y cuentas.',
            ],
            [
                'name' => 'Decanato',
                'code' => 'decanato',
                'type' => OrganizationalUnit::TYPE_ADMINISTRATIVO,
                'responsible_title' => 'Decano',
                'responsible_name' => 'Lic. Jorge Velasco Orellanos',
                'description' => 'Máxima autoridad ejecutiva facultativa de la Facultad de Tecnología.',
            ],
        ];

        foreach ($units as $u) {
            OrganizationalUnit::firstOrCreate(
                ['code' => $u['code']],
                $u
            );
        }
    }
}
