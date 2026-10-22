<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\CertificateType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $adminRole = Role::firstOrCreate(
            ['name' => Role::ADMIN],
            [
                'display_name' => 'Administrador',
                'description' => 'Control total del sistema, plantillas, usuarios, revocaciones y configuración.',
            ]
        );

        $operatorRole = Role::firstOrCreate(
            ['name' => Role::OPERATOR],
            [
                'display_name' => 'Operador',
                'description' => 'Registro de beneficiarios, actividades y emisión/descarga de certificados.',
            ]
        );

        // 2. Usuarios Iniciales
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@universidad.edu'],
            [
                'name' => 'Vicedecanato Administrador',
                'role_id' => $adminRole->id,
                'password' => Hash::make('Admin*2026'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $operatorUser = User::updateOrCreate(
            ['email' => 'operador@universidad.edu'],
            [
                'name' => 'Personal Administrativo',
                'role_id' => $operatorRole->id,
                'password' => Hash::make('Operador*2026'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Tipos de Certificado
        $certificateTypes = [
            ['code' => 'aprobacion', 'name' => 'Certificado de Aprobación', 'description' => 'Para participantes que aprobaron las evaluaciones académicas.'],
            ['code' => 'asistencia', 'name' => 'Certificado de Asistencia', 'description' => 'Para participantes con cumplimiento de asistencia regular.'],
            ['code' => 'disertante', 'name' => 'Certificado de Disertante / Ponente', 'description' => 'Para expositores, docentes invitados o conferencistas.'],
            ['code' => 'organizador', 'name' => 'Certificado de Organizador', 'description' => 'Para personal de comisión organizadora o soporte técnico.'],
            ['code' => 'reconocimiento', 'name' => 'Certificado de Reconocimiento / Honorífico', 'description' => 'Por méritos destacados, trayectoria o colaboración especial.'],
        ];

        foreach ($certificateTypes as $type) {
            CertificateType::firstOrCreate(['code' => $type['code']], $type);
        }

        // 4. Recursos Institucionales Oficiales y Plantillas Profesionales
        $this->call([
            InstitutionalAssetsSeeder::class,
            CertificateTemplatesSeeder::class,
        ]);

        // 5. Configuraciones Institucionales del Sistema (Facultad de Tecnología - UMSA)
        $defaultSettings = [
            ['key' => 'institution_name', 'value' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Nombre de la Institución'],
            ['key' => 'faculty_name', 'value' => 'FACULTAD DE TECNOLOGÍA', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Facultad'],
            ['key' => 'department_name', 'value' => 'VICEDECANATO', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Unidad o Vicedecanato'],
            ['key' => 'authority_name', 'value' => 'Ing. Aniceto Velarde Forest', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Nombre del Vicedecano'],
            ['key' => 'authority_position', 'value' => 'Vicedecano', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Cargo de la Autoridad'],
            ['key' => 'dean_name', 'value' => 'Lic. Jorge Velasco Orellanos', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Nombre del Decano'],
            ['key' => 'dean_position', 'value' => 'Decano', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Cargo del Decano'],
            ['key' => 'faculty_motto', 'value' => 'Ciencia, cálculo y jerarquía Adelante Tecnología', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Lema Facultativo'],
            ['key' => 'institution_address', 'value' => 'Av. Arce No. 2295', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Dirección Facultativa'],
            ['key' => 'institution_city', 'value' => 'La Paz - Bolivia', 'group' => 'institutional', 'type' => 'string', 'display_name' => 'Ciudad y País'],
            ['key' => 'certificate_validity_years', 'value' => '3', 'group' => 'certificate', 'type' => 'integer', 'display_name' => 'Años de Vigencia Estándar'],
            ['key' => 'system_email', 'value' => 'vicedecanato.tecnologia@umsa.bo', 'group' => 'general', 'type' => 'string', 'display_name' => 'Correo de Contacto Institucional'],
        ];

        foreach ($defaultSettings as $stg) {
            Setting::updateOrCreate(['key' => $stg['key']], $stg);
        }

        // 6. Beneficiarios de Prueba
        $beneficiary1 = Beneficiary::firstOrCreate(
            ['document_number' => '8493021'],
            [
                'document_type' => 'CI',
                'first_name' => 'Rodrigo',
                'last_name' => 'Quispe Mamani',
                'academic_title' => 'Univ.',
                'email' => 'rodrigo.quispe@estudiante.edu',
                'phone' => '+591 71234567',
                'institution_or_department' => 'Carrera de Informática',
            ]
        );

        $beneficiary2 = Beneficiary::firstOrCreate(
            ['document_number' => '6192834'],
            [
                'document_type' => 'CI',
                'first_name' => 'María Elena',
                'last_name' => 'Flores Condori',
                'academic_title' => 'Lic.',
                'email' => 'maria.flores@profesional.edu',
                'phone' => '+591 79876543',
                'institution_or_department' => 'Carrera de Estadística',
            ]
        );

        // 7. Actividades de Prueba
        $activity1 = Activity::firstOrCreate(
            ['name' => 'Seminario Internacional de Inteligencia Artificial y Ciencia de Datos'],
            [
                'description' => 'Capacitación intensiva sobre arquitecturas de Machine Learning, Deep Learning y despliegue de modelos en la nube.',
                'activity_type' => 'Seminario',
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->subDays(7)->toDateString(),
                'academic_hours' => 40,
                'location' => 'Paraninfo Universitario y Modalidad Virtual',
                'organizer' => 'Vicedecanato en coordinación con el Instituto de Investigaciones',
                'responsible_authority' => 'Dr. Carlos Mendoza Fernández - Vicedecano',
                'status' => 'finalizada',
            ]
        );

        $activity2 = Activity::firstOrCreate(
            ['name' => 'Taller de Ciberseguridad y Protección de Datos Institucionales'],
            [
                'description' => 'Buenas prácticas de seguridad informática, mitigación de vulnerabilidades y normativas universitarias.',
                'activity_type' => 'Taller',
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->toDateString(),
                'academic_hours' => 20,
                'location' => 'Auditorio Central',
                'organizer' => 'Vicedecanato',
                'responsible_authority' => 'Dr. Carlos Mendoza Fernández - Vicedecano',
                'status' => 'activa',
            ]
        );
    }
}
