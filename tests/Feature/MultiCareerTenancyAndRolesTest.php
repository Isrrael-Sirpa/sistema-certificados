<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\BeneficiaryImportService;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCareerTenancyAndRolesTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $jefeRole;

    protected Role $secretariaRole;

    protected OrganizationalUnit $unitQuimica;

    protected OrganizationalUnit $unitMecanica;

    protected OrganizationalUnit $unitVicedecanato;

    protected User $superAdmin;

    protected User $jefeQuimica;

    protected User $secretariaQuimica;

    protected User $jefeMecanica;

    protected CertificateType $typeAprobacion;

    protected CertificateType $typeParticipacion;

    protected CertificateTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $this->adminRole = Role::firstOrCreate(['name' => Role::ADMIN], ['display_name' => 'Super Administrador']);
        $this->jefeRole = Role::firstOrCreate(['name' => Role::JEFE_CARRERA], ['display_name' => 'Jefe de Carrera / Departamento']);
        $this->secretariaRole = Role::firstOrCreate(['name' => Role::SECRETARIA], ['display_name' => 'Secretaria']);

        // 2. Unidades
        $this->unitQuimica = OrganizationalUnit::create([
            'name' => 'Carrera de Química Industrial',
            'code' => 'QUI',
            'type' => OrganizationalUnit::TYPE_CARRERA,
            'is_active' => true,
        ]);

        $this->unitMecanica = OrganizationalUnit::create([
            'name' => 'Carrera de Mecánica Industrial',
            'code' => 'MEC',
            'type' => OrganizationalUnit::TYPE_CARRERA,
            'is_active' => true,
        ]);

        $this->unitVicedecanato = OrganizationalUnit::create([
            'name' => 'Vicedecanato y Facultad de Tecnología',
            'code' => 'VICEDECANATO',
            'type' => OrganizationalUnit::TYPE_DEPARTAMENTO,
            'is_active' => true,
        ]);

        // 3. Usuarios
        $this->superAdmin = User::create([
            'name' => 'Administrador Cómputo',
            'email' => 'admin.computo@umsa.bo',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        $this->jefeQuimica = User::create([
            'name' => 'Jefe Química Industrial',
            'email' => 'jefe.quimica@umsa.bo',
            'password' => bcrypt('password'),
            'role_id' => $this->jefeRole->id,
            'organizational_unit_id' => $this->unitQuimica->id,
            'is_active' => true,
        ]);

        $this->secretariaQuimica = User::create([
            'name' => 'Secretaria Química Industrial',
            'email' => 'secretaria.quimica@umsa.bo',
            'password' => bcrypt('password'),
            'role_id' => $this->secretariaRole->id,
            'organizational_unit_id' => $this->unitQuimica->id,
            'is_active' => true,
        ]);

        $this->jefeMecanica = User::create([
            'name' => 'Jefe Mecánica Industrial',
            'email' => 'jefe.mecanica@umsa.bo',
            'password' => bcrypt('password'),
            'role_id' => $this->jefeRole->id,
            'organizational_unit_id' => $this->unitMecanica->id,
            'is_active' => true,
        ]);

        // 4. Tipos de Certificado y Plantilla
        $this->typeAprobacion = CertificateType::create([
            'code' => 'APROBACION',
            'name' => 'Certificado de Aprobación',
            'slug' => 'aprobacion',
            'has_score' => true,
            'min_passing_score' => 51.0,
            'validity_mode' => 'permanent',
            'is_active' => true,
        ]);

        $this->typeParticipacion = CertificateType::create([
            'code' => 'PARTICIPACION',
            'name' => 'Certificado de Participación',
            'slug' => 'participacion',
            'has_score' => false,
            'validity_mode' => 'permanent',
            'is_active' => true,
        ]);

        $this->template = CertificateTemplate::create([
            'name' => 'Plantilla Base Facultad',
            'slug' => 'plantilla-base-facultad',
            'category' => 'institucional',
            'orientation' => 'landscape',
            'is_active' => true,
            'is_default' => true,
            'current_version' => 1,
        ]);
    }

    public function test_super_admin_can_access_carreras_departamentos_and_crud(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('carreras-departamentos.index'));
        $response->assertOk();
        $response->assertSee('Carrera de Química Industrial');

        // Crear una nueva carrera dinámicamente
        $createResponse = $this->actingAs($this->superAdmin)->post(route('carreras-departamentos.store'), [
            'name' => 'Carrera de Electromecánica Industrial',
            'code' => 'ELM',
            'type' => 'carrera',
            'responsible_name' => 'Ing. Carlos Mendoza',
        ]);
        $createResponse->assertRedirect(route('carreras-departamentos.index'));
        $this->assertDatabaseHas('organizational_units', ['code' => 'ELM', 'name' => 'Carrera de Electromecánica Industrial']);
    }

    public function test_jefe_and_secretaria_cannot_access_carreras_departamentos(): void
    {
        $this->actingAs($this->jefeQuimica)->get(route('carreras-departamentos.index'))->assertForbidden();
        $this->actingAs($this->secretariaQuimica)->get(route('carreras-departamentos.index'))->assertForbidden();
    }

    public function test_role_permissions_for_reports_and_templates(): void
    {
        // 1. Reportes: Super Admin y Jefe pueden acceder; Secretaria tiene 403
        $this->actingAs($this->superAdmin)->get(route('reports.index'))->assertOk();
        $this->actingAs($this->jefeQuimica)->get(route('reports.index'))->assertOk();
        $this->actingAs($this->secretariaQuimica)->get(route('reports.index'))->assertForbidden();

        // 2. Plantillas: Super Admin, Jefe y Secretaria pueden acceder
        $this->actingAs($this->superAdmin)->get(route('templates.index'))->assertOk();
        $this->actingAs($this->jefeQuimica)->get(route('templates.index'))->assertOk();
        $this->actingAs($this->secretariaQuimica)->get(route('templates.index'))->assertOk();
    }

    public function test_activity_isolation_between_careers(): void
    {
        // Actividad creada en Química
        $activityQuimica = Activity::create([
            'name' => 'Seminario de Termodinámica Química',
            'activity_type' => 'Seminario',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'academic_hours' => 20,
            'organizational_unit_id' => $this->unitQuimica->id,
            'target_audience_mode' => Activity::AUDIENCE_OWN_UNIT,
            'status' => 'activa',
        ]);

        // Jefe de Química puede ver su actividad
        $this->actingAs($this->jefeQuimica)->get(route('activities.show', $activityQuimica))->assertOk();

        // Jefe de Mecánica NO puede ver ni editar la actividad de Química (403)
        $this->actingAs($this->jefeMecanica)->get(route('activities.show', $activityQuimica))->assertForbidden();
        $this->actingAs($this->jefeMecanica)->get(route('activities.edit', $activityQuimica))->assertForbidden();

        // Super Admin puede ver la actividad de cualquier carrera
        $this->actingAs($this->superAdmin)->get(route('activities.show', $activityQuimica))->assertOk();
    }

    public function test_resolution_restricts_cross_career_enrolment_and_certificate_issuance(): void
    {
        // Actividad de Química exclusiva para su propia unidad
        $activity = Activity::create([
            'name' => 'Taller de Laboratorio Químico Avanzado',
            'activity_type' => 'Taller',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'academic_hours' => 30,
            'resolution_number' => 'RF-045/2026',
            'organizational_unit_id' => $this->unitQuimica->id,
            'target_audience_mode' => Activity::AUDIENCE_OWN_UNIT,
            'certificate_type_id' => $this->typeParticipacion->id,
            'status' => 'activa',
        ]);

        // Estudiante de Química (autorizado)
        $studentQuimica = Beneficiary::create([
            'document_type' => 'CI',
            'document_number' => '1001001',
            'first_name' => 'Juan',
            'last_name' => 'Pérez Química',
            'primary_organizational_unit_id' => $this->unitQuimica->id,
        ]);

        // Estudiante de Mecánica (no autorizado por resolución)
        $studentMecanica = Beneficiary::create([
            'document_type' => 'CI',
            'document_number' => '2002002',
            'first_name' => 'Pedro',
            'last_name' => 'Gómez Mecánica',
            'primary_organizational_unit_id' => $this->unitMecanica->id,
        ]);

        // Agregar manualmente al estudiante de Química -> Habilitado
        $this->actingAs($this->jefeQuimica)->post(route('activities.participants.add-manual', $activity), [
            'beneficiary_id' => $studentQuimica->id,
            'role' => 'Participante',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $activity->id,
            'beneficiary_id' => $studentQuimica->id,
            'career_eligible' => true,
            'status' => 'inscrito',
        ]);

        // Agregar manualmente al estudiante de Mecánica -> Observado por Resolución
        $this->actingAs($this->jefeQuimica)->post(route('activities.participants.add-manual', $activity), [
            'beneficiary_id' => $studentMecanica->id,
            'role' => 'Participante',
        ])->assertSessionHas('warning');

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $activity->id,
            'beneficiary_id' => $studentMecanica->id,
            'career_eligible' => false,
            'status' => 'observado',
        ]);

        // Al emitir certificados a la nómina, solo el estudiante habilitado recibe certificado
        $importService = app(BeneficiaryImportService::class);
        $certificateService = app(CertificateService::class);

        $result = $importService->issueCertificatesToActivityParticipants(
            $activity,
            $this->typeParticipacion->id,
            $this->template->id,
            now()->toDateString(),
            $this->jefeQuimica,
            $certificateService
        );

        $this->assertEquals(1, $result['issued_count']);
        $this->assertEquals(1, $result['ineligible_count']);

        // Verificar que Juan Química tiene certificado y Pedro Mecánica no
        $this->assertDatabaseHas('certificates', [
            'activity_id' => $activity->id,
            'beneficiary_id' => $studentQuimica->id,
            'organizational_unit_id' => $this->unitQuimica->id,
        ]);

        $this->assertDatabaseMissing('certificates', [
            'activity_id' => $activity->id,
            'beneficiary_id' => $studentMecanica->id,
        ]);
    }

    public function test_inter_career_resolution_allows_multiple_selected_careers(): void
    {
        // Evento conjunto Química y Mecánica autorizado por Resolución Facultativa RF-100/2026
        $activity = Activity::create([
            'name' => 'Simposio Conjunto Química y Mecánica Industrial',
            'activity_type' => 'Seminario',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'academic_hours' => 40,
            'resolution_number' => 'RF-100/2026',
            'organizational_unit_id' => $this->unitQuimica->id,
            'target_audience_mode' => Activity::AUDIENCE_SPECIFIC_UNITS,
            'certificate_type_id' => $this->typeParticipacion->id,
            'status' => 'activa',
        ]);
        $activity->allowedUnits()->attach($this->unitMecanica->id);

        $studentQuimica = Beneficiary::create([
            'document_type' => 'CI',
            'document_number' => '3003003',
            'first_name' => 'Ana',
            'last_name' => 'Química',
            'primary_organizational_unit_id' => $this->unitQuimica->id,
        ]);

        $studentMecanica = Beneficiary::create([
            'document_type' => 'CI',
            'document_number' => '4004004',
            'first_name' => 'Carlos',
            'last_name' => 'Mecánica',
            'primary_organizational_unit_id' => $this->unitMecanica->id,
        ]);

        // Ambos son válidos bajo la resolución
        $this->assertTrue($activity->isCareerAllowed($studentQuimica->primary_organizational_unit_id));
        $this->assertTrue($activity->isCareerAllowed($studentMecanica->primary_organizational_unit_id));

        // Pero otra unidad no autorizada no es válida
        $otherUnit = OrganizationalUnit::create(['name' => 'Topografía', 'code' => 'TOP', 'type' => 'carrera']);
        $this->assertFalse($activity->isCareerAllowed($otherUnit->id));
    }

    public function test_unit_deactivation_protects_historical_certificates(): void
    {
        // Emitir un certificado para Química
        $activity = Activity::create([
            'name' => 'Congreso Histórico',
            'activity_type' => 'Congreso',
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subYear()->toDateString(),
            'academic_hours' => 50,
            'organizational_unit_id' => $this->unitQuimica->id,
            'target_audience_mode' => Activity::AUDIENCE_OWN_UNIT,
            'certificate_type_id' => $this->typeParticipacion->id,
            'status' => 'finalizada',
        ]);

        $student = Beneficiary::create([
            'document_type' => 'CI',
            'document_number' => '5005005',
            'first_name' => 'María',
            'last_name' => 'Histórica',
            'primary_organizational_unit_id' => $this->unitQuimica->id,
        ]);

        $certificateService = app(CertificateService::class);
        $cert = $certificateService->issueCertificate([
            'beneficiary_id' => $student->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $this->typeParticipacion->id,
            'issue_date' => now()->subYear()->toDateString(),
        ], $this->superAdmin);

        // Desactivar la unidad de Química
        $toggleResponse = $this->actingAs($this->superAdmin)->patch(route('carreras-departamentos.toggle', $this->unitQuimica));
        $toggleResponse->assertRedirect(route('carreras-departamentos.index'));
        $this->assertFalse($this->unitQuimica->fresh()->is_active);

        // El certificado histórico sigue estando VIGENTE y con su hash intacto
        $certFresh = $cert->fresh();
        $this->assertEquals(Certificate::STATUS_VIGENTE, $certFresh->status);
        $this->assertTrue($certFresh->isValid());

        // Verificación pública vía token QR sigue funcionando al 100%
        $verifyResponse = $this->get(route('verification.show', $certFresh->verification_token));
        $verifyResponse->assertOk();
        $verifyResponse->assertSee($certFresh->certificate_code);
        $verifyResponse->assertSee('María Histórica');
    }

    public function test_beneficiary_360_academic_portfolio_view(): void
    {
        $student = Beneficiary::create([
            'document_type' => 'CI',
            'document_number' => '6006006',
            'first_name' => 'Elena',
            'last_name' => 'Portfolio',
            'primary_organizational_unit_id' => $this->unitQuimica->id,
        ]);

        // Ver portafolio del estudiante
        $response = $this->actingAs($this->jefeQuimica)->get(route('beneficiaries.show', $student));
        $response->assertOk();
        $response->assertSee('Portafolio Académico 360°');
        $response->assertSee('Carrera: Carrera de Química Industrial');
    }
}
