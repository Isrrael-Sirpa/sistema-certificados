<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class FormExperienceTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationalUnit $unit;

    private OrganizationalUnit $otherUnit;

    private CertificateType $type;

    private CertificateTemplate $globalTemplate;

    private CertificateTemplate $ownTemplate;

    private CertificateTemplate $foreignTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->unit = OrganizationalUnit::create(['name' => 'Química Industrial', 'code' => 'QUI', 'type' => 'carrera']);
        $this->otherUnit = OrganizationalUnit::create(['name' => 'Mecánica Industrial', 'code' => 'MEC', 'type' => 'carrera']);
        $this->type = CertificateType::create(['name' => 'Aprobación', 'code' => 'aprobacion', 'has_score' => true, 'min_passing_score' => 51, 'is_active' => true, 'validity_mode' => 'years', 'default_validity_years' => 3]);
        $this->globalTemplate = $this->makeTemplate('Compartida', null);
        $this->ownTemplate = $this->makeTemplate('Química Privada', $this->unit->id);
        $this->foreignTemplate = $this->makeTemplate('Mecánica Privada', $this->otherUnit->id);
        Role::firstOrCreate(['name' => Role::SECRETARIA], ['display_name' => 'Secretaria']);
    }

    public static function rolePages(): array
    {
        $cases = [];
        foreach ([Role::ADMIN, Role::JEFE_CARRERA, Role::SECRETARIA, Role::OPERATOR] as $role) {
            foreach (['users.create', 'settings.index', 'activities.create', 'templates.create'] as $route) {
                $allowed = $role === Role::ADMIN || $route === 'activities.create' || ($route === 'templates.create' && $role !== Role::OPERATOR);
                $cases[$role.' '.$route] = [$role, $route, $allowed ? 200 : 403];
            }
        }

        return $cases;
    }

    #[DataProvider('rolePages')]
    public function test_forms_render_and_respect_roles(string $role, string $route, int $expected): void
    {
        $response = $this->actingAs($this->makeUser($role))->get(route($route))->assertStatus($expected);
        if ($expected === 200) {
            $state = $this->checkClientExpressions($response->getContent(), $route);
            if ($route === 'activities.create') {
                $this->assertSame('temporal', $state['validityMode']);
                $response->assertSee('Presentación del certificado');
            }
        }
    }

    public function test_unit_template_sources_are_filtered_and_foreign_duplication_is_rejected(): void
    {
        $user = $this->makeUser(Role::SECRETARIA);
        $this->actingAs($user)->get(route('templates.create'))->assertOk()->assertSee('Química Privada')->assertDontSee('Mecánica Privada');
        $this->post(route('templates.store'), ['name' => 'Intento ajeno', 'category' => 'institucional', 'orientation' => 'landscape', 'creation_mode' => 'duplicate', 'source_template_id' => $this->foreignTemplate->id])->assertForbidden();
        $this->assertDatabaseMissing('certificate_templates', ['name' => 'Intento ajeno']);
    }

    public function test_template_modes_create_scoped_designs_and_validate_source(): void
    {
        $this->actingAs($this->makeUser(Role::JEFE_CARRERA));
        $this->post(route('templates.store'), ['name' => 'Sin origen', 'category' => 'institucional', 'orientation' => 'landscape', 'creation_mode' => 'duplicate'])->assertSessionHasErrors('source_template_id');
        $this->post(route('templates.store'), ['name' => 'Nueva de Química', 'category' => 'institucional', 'orientation' => 'portrait', 'creation_mode' => 'blank', 'organizational_unit_id' => $this->otherUnit->id])->assertSessionHas('success');
        $this->assertDatabaseHas('certificate_templates', ['name' => 'Nueva de Química', 'orientation' => 'portrait', 'organizational_unit_id' => $this->unit->id]);
    }

    public function test_administrator_can_create_global_template_and_inactive_user(): void
    {
        $this->actingAs($this->makeUser(Role::ADMIN));
        $this->post(route('templates.store'), ['name' => 'Diseño global nuevo', 'category' => 'institucional', 'orientation' => 'landscape', 'creation_mode' => 'blank', 'organizational_unit_id' => null])->assertSessionHas('success');
        $this->assertDatabaseHas('certificate_templates', ['name' => 'Diseño global nuevo', 'organizational_unit_id' => null]);
        $role = Role::firstOrCreate(['name' => Role::SECRETARIA], ['display_name' => 'Secretaria']);
        $this->post(route('users.store'), ['name' => 'Usuario de prueba', 'email' => 'formularios@example.test', 'role_id' => $role->id, 'organizational_unit_id' => $this->unit->id, 'password' => 'PruebaSegura123', 'password_confirmation' => 'PruebaSegura123', 'is_active' => '0'])->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['email' => 'formularios@example.test', 'is_active' => false]);
    }

    public function test_activity_saves_unit_template_and_disabled_score_visibility(): void
    {
        $this->actingAs($this->makeUser(Role::JEFE_CARRERA))->get(route('activities.create'))->assertOk()->assertSee('Química Privada')->assertDontSee('Mecánica Privada');
        $this->post(route('activities.store'), ['name' => 'Actividad de prueba', 'activity_type' => 'Taller', 'start_date' => '2026-11-03', 'end_date' => '2026-11-04', 'academic_hours' => 20, 'status' => 'activa', 'certificate_type_id' => $this->type->id, 'certificate_template_id' => $this->ownTemplate->id, 'validity_mode' => 'temporal', 'validity_years' => 3, 'show_score_on_pdf' => '0', 'show_score_on_portal' => '0', 'organizational_unit_id' => $this->otherUnit->id])->assertSessionHas('success');
        $activity = Activity::where('name', 'Actividad de prueba')->firstOrFail();
        $this->assertSame($this->unit->id, $activity->organizational_unit_id);
        $this->assertDatabaseHas('activity_certificate_configs', ['activity_id' => $activity->id, 'certificate_template_id' => $this->ownTemplate->id, 'show_score_on_pdf' => false, 'show_score_on_portal' => false]);
    }

    public function test_settings_validate_values_and_do_not_persist_form_controls(): void
    {
        $this->actingAs($this->makeUser(Role::ADMIN))->post(route('settings.update'), ['_settings_tab' => 'institutional', 'system_email' => 'correo-invalido'])->assertSessionHasErrors('system_email');
        $this->post(route('settings.update'), ['_settings_tab' => 'institutional', 'institution_name' => 'Universidad de prueba', 'system_email' => 'contacto@example.test'])->assertSessionHas('success');
        $this->assertDatabaseHas('settings', ['key' => 'system_email', 'value' => 'contacto@example.test']);
        $this->assertDatabaseMissing('settings', ['key' => '_settings_tab']);
        $this->post(route('settings.update'), ['certificate_validity_years' => 0])->assertSessionHasErrors('certificate_validity_years');
    }

    public function test_settings_restore_section_and_type_modal_after_validation_errors(): void
    {
        $this->actingAs($this->makeUser(Role::ADMIN));
        $this->from(route('settings.index'))->post(route('settings.certificate-types.store'), ['name' => '', '_settings_tab' => 'certificates', '_type_form' => 'create', 'validity_mode' => 'years'])->assertSessionHasErrors('name');
        $response = $this->withCookie(config('session.cookie'), session()->getId())->get(route('settings.index'))->assertOk()->assertSee('Revisa los siguientes datos:');
        $state = $this->checkClientExpressions($response->getContent(), 'settings.index');
        $this->assertSame('certificates', $state['activeTab']);
        $this->assertTrue($state['newTypeModal']);
    }

    public function test_certificate_type_checkboxes_save_explicit_false_values(): void
    {
        $this->actingAs($this->makeUser(Role::ADMIN));
        $this->post(route('settings.certificate-types.store'), ['name' => 'Participación sin notas', 'code' => 'participacion_sin_notas', 'validity_mode' => 'permanent', 'has_score' => '0'])->assertSessionHas('success');
        $this->assertDatabaseHas('certificate_types', ['code' => 'participacion_sin_notas', 'has_score' => false, 'is_active' => true, 'default_validity_years' => null]);
        $this->put(route('settings.certificate-types.update', $this->type), ['name' => $this->type->name, 'validity_mode' => 'years', 'default_validity_years' => 5, 'has_score' => '0', 'is_active' => '0'])->assertSessionHas('success');
        $this->assertDatabaseHas('certificate_types', ['id' => $this->type->id, 'has_score' => false, 'is_active' => false, 'default_validity_years' => 5]);
    }

    public function test_edit_type_modal_restores_values_and_displays_validation_errors(): void
    {
        $this->actingAs($this->makeUser(Role::ADMIN))->from(route('settings.index', ['tab' => 'certificates']))
            ->put(route('settings.certificate-types.update', $this->type), ['name' => '', '_settings_tab' => 'certificates', '_type_form' => 'edit', '_editing_type_id' => $this->type->id, 'validity_mode' => 'years', 'default_validity_years' => 9, 'has_score' => '0', 'is_active' => '0'])->assertSessionHasErrors('name');
        $this->assertSame('edit', session()->getOldInput('_type_form'));
        $this->assertTrue(session('errors')->any());
        $this->withCookie(config('session.cookie'), session()->getId());
        $response = $this->get(route('settings.index'))->assertOk();
        $this->assertTrue($response->viewData('errors')->any());
        $state = $this->checkClientExpressions($response->getContent(), 'settings.index');
        $this->assertTrue($state['editTypeModal']);
        $this->assertSame(9, $state['editingType']['default_validity_years']);
        $this->assertFalse($state['editingType']['has_score']);
        $this->assertFalse($state['editingType']['is_active']);
        $response->assertSee('Revisa los siguientes datos:');
    }

    public function test_optional_template_uses_available_design_and_global_validity_when_no_duration_is_defined(): void
    {
        $user = $this->makeUser(Role::SECRETARIA);
        $this->foreignTemplate->update(['is_default' => true]);
        $this->type->update(['default_validity_years' => null]);
        $this->actingAs($this->makeUser(Role::ADMIN))->post(route('settings.update'), ['certificate_validity_years' => 7])->assertSessionHas('success');
        $activity = Activity::create(['name' => 'Evento de Química', 'activity_type' => 'Taller', 'start_date' => '2026-11-03', 'end_date' => '2026-11-04', 'academic_hours' => 20, 'status' => 'activa', 'organizational_unit_id' => $this->unit->id, 'target_audience_mode' => 'own_unit']);
        $student = Beneficiary::create(['first_name' => 'Ana', 'last_name' => 'Quispe', 'document_number' => '900010', 'document_type' => 'CI', 'primary_organizational_unit_id' => $this->unit->id]);
        $certificate = app(CertificateService::class)->issueCertificate(['beneficiary_id' => $student->id, 'activity_id' => $activity->id, 'certificate_type_id' => $this->type->id, 'score' => 85, 'issue_date' => '2026-11-03'], $user);
        $this->assertSame($this->globalTemplate->id, $certificate->certificate_template_id);
        $this->assertSame('2033-11-03', $certificate->expiration_date->toDateString());
    }

    /** @return array<string, mixed> */
    private function checkClientExpressions(string $html, string $route): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);
        $expressions = [];
        foreach ($xpath->query('//*[@x-data]') as $element) {
            $expressions[] = $element->getAttribute('x-data');
        }
        $root = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ft-form-page ")]')->item(0);
        $this->assertNotNull($root);
        $script = <<<'JS'
let input = '';
process.stdin.on('data', chunk => input += chunk);
process.stdin.on('end', () => {
    const payload = JSON.parse(input);
    for (const expression of payload.expressions) new Function('return (' + expression + ')');
    const state = new Function('return (' + payload.root + ')')();
    const result = {activeTab: state.activeTab, newTypeModal: state.newTypeModal, editTypeModal: state.editTypeModal, editingType: state.editingType, validityMode: state.validityMode};
    if (payload.route === 'users.create') {
        const unitRole = Object.entries(state.roles).find(([, value]) => value.name === 'secretaria' || value.name === 'jefe_carrera');
        if (unitRole) { state.roleId = unitRole[0]; if (!state.requiresUnit) throw new Error('La unidad debe ser obligatoria'); }
    }
    if (payload.route === 'activities.create') {
        state.onTypeSelect(Object.keys(state.typesData)[0]);
        if (state.validityMode !== 'temporal') throw new Error('La vigencia temporal no coincide con el formulario');
        for (const [id, template] of Object.entries(state.templateData)) {
            const available = !template.organizational_unit_id || String(template.organizational_unit_id) === String(state.organizerUnitId);
            if (Boolean(state.templateVisible(id)) !== available) throw new Error('La galería no respeta la unidad organizadora');
            if (!available) {
                state.selectedTemplateId = id;
                state.onOrganizerChange();
                if (state.selectedTemplateId !== '') throw new Error('Se conservó una selección de otra unidad');
            }
        }
        state.templateSearch = 'sin coincidencias para esta búsqueda';
        if (state.matchingTemplateCount !== 0) throw new Error('El filtro de nombres no se aplica');
    }
    process.stdout.write(JSON.stringify(result));
});
JS;
        $process = new Process(['node', '-e', $script], base_path(), null, json_encode(['expressions' => $expressions, 'root' => $root->getAttribute('x-data'), 'route' => $route], JSON_THROW_ON_ERROR));
        $process->mustRun();

        return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function makeUser(string $name): User
    {
        $role = Role::firstOrCreate(['name' => $name], ['display_name' => $name, 'description' => 'Descripción del rol']);

        return User::factory()->create(['role_id' => $role->id, 'organizational_unit_id' => $this->unit->id, 'is_active' => true]);
    }

    private function makeTemplate(string $name, ?int $unitId): CertificateTemplate
    {
        return CertificateTemplate::create(['name' => $name, 'slug' => Str::slug($name), 'category' => 'institucional', 'orientation' => 'landscape', 'organizational_unit_id' => $unitId, 'is_active' => true, 'current_version' => 1, 'configuration' => ['elements' => []]]);

    }
}
