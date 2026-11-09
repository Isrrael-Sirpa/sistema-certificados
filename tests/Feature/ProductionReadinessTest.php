<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\Role;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeds_only_institutional_data_and_preserves_edited_templates(): void
    {
        $environment = app()->environment();
        app()->instance('env', 'production');

        try {
            $this->artisan('db:seed', ['--force' => true, '--no-interaction' => true])->assertSuccessful();
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('beneficiaries', 0);
            $this->assertDatabaseCount('activities', 0);
            $this->assertDatabaseHas('certificate_types', ['code' => 'aprobacion', 'has_score' => true, 'min_passing_score' => 51]);

            $template = CertificateTemplate::firstOrFail();
            $template->update(['name' => 'Plantilla oficial editada', 'current_version' => 2]);
            $this->artisan('db:seed', ['--force' => true, '--no-interaction' => true])->assertSuccessful();
            $this->assertDatabaseHas('certificate_templates', ['id' => $template->id, 'name' => 'Plantilla oficial editada', 'current_version' => 2]);
        } finally {
            app()->instance('env', $environment);
        }
    }

    public function test_administrator_command_creates_a_hashed_password_and_rejects_an_existing_email(): void
    {
        Role::create(['name' => Role::ADMIN, 'display_name' => 'Administración central']);
        $arguments = ['email' => 'admin@example.test', '--name' => 'Administración de prueba'];
        $password = 'ContraseñaDePrueba491';

        $this->artisan('certificates:create-admin', $arguments)
            ->expectsQuestion('Contraseña nueva (mínimo 12 caracteres)', $password)
            ->expectsQuestion('Confirma la contraseña', $password)
            ->assertSuccessful();
        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check($password, $user->password));

        $this->artisan('certificates:create-admin', $arguments)->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_issuance_continues_above_the_highest_legacy_code_and_rejects_a_duplicate(): void
    {
        $this->seed();
        $administrator = User::firstOrFail();
        $participants = Beneficiary::orderBy('id')->get();
        $activity = Activity::firstOrFail();
        $type = CertificateType::where('code', 'asistencia')->firstOrFail();
        $data = [
            'activity_id' => $activity->id,
            'certificate_type_id' => $type->id,
            'certificate_template_id' => CertificateTemplate::firstOrFail()->id,
            'issue_date' => '2026-11-05',
        ];
        Certificate::create([...$data, 'beneficiary_id' => $participants[0]->id, 'generated_by_user_id' => $administrator->id, 'certificate_code' => 'CERT-2026-000800']);

        $service = app(CertificateService::class);
        $certificate = $service->issueCertificate([...$data, 'beneficiary_id' => $participants[1]->id], $administrator);
        $this->assertSame('CERT-2026-000801', $certificate->certificate_code);
        $this->assertDatabaseHas('certificate_sequences', ['year' => 2026, 'last_number' => 801]);
        $this->assertSame('CERT-2027-000001', Certificate::generateUniqueCode('2027-01-01'));

        $this->expectException(ValidationException::class);
        $service->issueCertificate([...$data, 'beneficiary_id' => $participants[1]->id, 'issue_date' => '2027-01-02'], $administrator);
    }
}
