<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BeneficiaryRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public static function scopedRoles(): array
    {
        return [
            'jefe de carrera' => [Role::JEFE_CARRERA, 'carrera'],
            'secretaria de carrera' => [Role::SECRETARIA, 'carrera'],
            'jefe de departamento' => [Role::JEFE_CARRERA, 'departamento'],
            'secretaria de departamento' => [Role::SECRETARIA, 'departamento'],
            'operador con unidad' => [Role::OPERATOR, 'carrera'],
        ];
    }

    #[DataProvider('scopedRoles')]
    public function test_unit_registration_has_fixed_unit_and_rejects_foreign_students(string $role, string $unitType): void
    {
        [$ownUnit, $otherUnit] = $this->makeUnits($unitType);
        $user = $this->makeUser($role, $ownUnit);
        $response = $this->actingAs($user)->get(route('beneficiaries.create'))->assertOk();
        $this->assertSame([$ownUnit->id], $response->viewData('units')->modelKeys());
        $xpath = $this->formDocument($response->getContent());
        $this->assertSame(0, $xpath->query('//select[@name="primary_organizational_unit_id"]')->length);
        $this->assertSame($ownUnit->name, $xpath->query('//input[@id="primary_organizational_unit_id" and @readonly]')->item(0)?->getAttribute('value'));
        $this->assertSame($ownUnit->name, $this->formState($xpath)['unitName']);

        $this->post(route('beneficiaries.store'), $this->registrationData())->assertSessionHas('success');
        $this->assertDatabaseHas('beneficiaries', ['document_number' => '000921-A LP', 'primary_organizational_unit_id' => $ownUnit->id]);
        $this->post(route('beneficiaries.store'), [...$this->registrationData('000922 LP'), 'primary_organizational_unit_id' => $otherUnit->id])->assertForbidden();
        $this->assertDatabaseMissing('beneficiaries', ['document_number' => '000922 LP']);
    }

    public function test_administrator_can_register_students_in_any_career_or_department(): void
    {
        [$career, $otherCareer] = $this->makeUnits();
        $department = OrganizationalUnit::create(['name' => 'Departamento de Cómputo', 'code' => 'DCS', 'type' => 'departamento', 'is_active' => true]);
        $response = $this->actingAs($this->makeUser(Role::ADMIN))->get(route('beneficiaries.create'))->assertOk();
        $this->assertEqualsCanonicalizing([$career->id, $otherCareer->id, $department->id], $response->viewData('units')->modelKeys());
        $xpath = $this->formDocument($response->getContent());
        $this->assertSame(1, $xpath->query('//select[@name="primary_organizational_unit_id" and @required]')->length);
        foreach ([$otherCareer, $department] as $unit) {
            $document = '90000'.$unit->id;
            $this->post(route('beneficiaries.store'), [...$this->registrationData($document), 'primary_organizational_unit_id' => $unit->id])->assertSessionHas('success');
            $this->assertDatabaseHas('beneficiaries', ['document_number' => $document, 'primary_organizational_unit_id' => $unit->id]);
        }
    }

    public function test_identity_and_official_unit_are_required_for_manual_registration(): void
    {
        $this->actingAs($this->makeUser(Role::ADMIN))->post(route('beneficiaries.store'), ['document_type' => 'CI'])
            ->assertSessionHasErrors(['document_number', 'first_name', 'last_name', 'primary_organizational_unit_id' => 'Seleccione la carrera o departamento al que pertenece el beneficiario.']);
        $this->assertDatabaseCount('beneficiaries', 0);
    }

    public function test_editing_cannot_transfer_or_remove_assigned_unit(): void
    {
        [$ownUnit, $otherUnit] = $this->makeUnits();
        $student = Beneficiary::create([...$this->registrationData(), 'primary_organizational_unit_id' => $ownUnit->id]);
        $response = $this->actingAs($this->makeUser(Role::SECRETARIA, $ownUnit))->get(route('beneficiaries.edit', $student))->assertOk();
        $this->assertSame([$ownUnit->id], $response->viewData('units')->modelKeys());
        $xpath = $this->formDocument($response->getContent());
        $this->assertSame(0, $xpath->query('//select[@name="primary_organizational_unit_id"]')->length);
        $this->assertTrue($this->formState($xpath)['detailsOpen']);
        $this->put(route('beneficiaries.update', $student), [...$this->registrationData(), 'primary_organizational_unit_id' => $otherUnit->id])->assertForbidden();
        $this->put(route('beneficiaries.update', $student), [...$this->registrationData(), 'first_name' => 'Nombre corregido', 'primary_organizational_unit_id' => null])->assertSessionHas('success');
        $this->assertDatabaseHas('beneficiaries', ['id' => $student->id, 'first_name' => 'Nombre corregido', 'primary_organizational_unit_id' => $ownUnit->id]);
    }

    public function test_document_duplicates_are_rejected_across_careers_and_own_document_can_be_kept(): void
    {
        [$ownUnit, $otherUnit] = $this->makeUnits();
        $student = Beneficiary::create([...$this->registrationData(), 'primary_organizational_unit_id' => $ownUnit->id]);
        $this->actingAs($this->makeUser(Role::ADMIN))->post(route('beneficiaries.store'), [...$this->registrationData(), 'primary_organizational_unit_id' => $otherUnit->id])
            ->assertSessionHasErrors(['document_number' => 'Este documento ya está registrado en el padrón institucional.']);
        $this->put(route('beneficiaries.update', $student), [...$this->registrationData(), 'primary_organizational_unit_id' => $ownUnit->id])->assertSessionHas('success');
        $this->assertDatabaseCount('beneficiaries', 1);
    }

    public function test_editing_preserves_an_existing_inactive_unit(): void
    {
        [$unit] = $this->makeUnits();
        $student = Beneficiary::create([...$this->registrationData(), 'primary_organizational_unit_id' => $unit->id]);
        $unit->update(['is_active' => false]);
        $response = $this->actingAs($this->makeUser(Role::ADMIN))->get(route('beneficiaries.edit', $student))->assertOk();
        $this->assertTrue($response->viewData('units')->contains('id', $unit->id));
        $this->assertSame($unit->name, $this->formState($this->formDocument($response->getContent()))['unitName']);
        $this->put(route('beneficiaries.update', $student), [...$this->registrationData(), 'primary_organizational_unit_id' => $unit->id])->assertSessionHas('success');
        $this->assertSame($unit->id, $student->fresh()->primary_organizational_unit_id);
    }

    public function test_validation_restores_identity_unit_and_opens_optional_fields_with_errors(): void
    {
        [$unit] = $this->makeUnits();
        $name = "Ana D'Ávila";
        $this->actingAs($this->makeUser(Role::ADMIN))->from(route('beneficiaries.create'))
            ->post(route('beneficiaries.store'), [...$this->registrationData(), 'first_name' => $name, 'primary_organizational_unit_id' => $unit->id, 'email' => 'correo-invalido'])->assertSessionHasErrors('email');
        $response = $this->withCookie(config('session.cookie'), session()->getId())->get(route('beneficiaries.create'))->assertOk();
        $state = $this->formState($this->formDocument($response->getContent()));
        $this->assertSame($name.' Quispe Mamani', $state['fullName']);
        $this->assertSame('000921-A LP', $state['documentNumber']);
        $this->assertSame($unit->name, $state['unitName']);
        $this->assertTrue($state['detailsOpen']);
        $this->assertDatabaseCount('beneficiaries', 0);
    }

    public function test_accounts_without_assigned_unit_cannot_register_and_guests_must_sign_in(): void
    {
        $this->get(route('beneficiaries.create'))->assertRedirect(route('login'));
        $this->actingAs($this->makeUser(Role::JEFE_CARRERA))->get(route('beneficiaries.create'))->assertForbidden();
        $this->post(route('beneficiaries.store'), $this->registrationData())->assertForbidden();
        $this->assertDatabaseCount('beneficiaries', 0);
    }

    /** @return array{OrganizationalUnit, OrganizationalUnit} */
    private function makeUnits(string $type = 'carrera'): array
    {
        return [
            OrganizationalUnit::create(['name' => $type === 'carrera' ? 'Química Industrial' : 'Departamento de Ciencias Básicas', 'code' => $type === 'carrera' ? 'QUI' : 'DCB', 'type' => $type, 'is_active' => true]),
            OrganizationalUnit::create(['name' => 'Mecánica Industrial', 'code' => 'MEC', 'type' => 'carrera', 'is_active' => true]),
        ];
    }

    private function makeUser(string $name, ?OrganizationalUnit $unit = null): User
    {
        $role = Role::firstOrCreate(['name' => $name], ['display_name' => $name]);

        return User::factory()->create(['role_id' => $role->id, 'organizational_unit_id' => $unit?->id, 'is_active' => true]);
    }

    /** @return array{document_type: string, document_number: string, first_name: string, last_name: string} */
    private function registrationData(string $document = '000921-A LP'): array
    {
        return ['document_type' => 'CI', 'document_number' => $document, 'first_name' => 'Ana', 'last_name' => 'Quispe Mamani'];
    }

    private function formDocument(string $html): \DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($document);
    }

    /** @return array{fullName: string, documentNumber: string, unitName: string, detailsOpen: bool} */
    private function formState(\DOMXPath $xpath): array
    {
        $root = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ft-form-page ")]')->item(0);
        $this->assertNotNull($root);
        $script = <<<'JS'
let input = '';
process.stdin.on('data', chunk => input += chunk);
process.stdin.on('end', () => {
    const state = new Function('return (' + input + ')')();
    process.stdout.write(JSON.stringify({fullName: state.fullName, documentNumber: state.documentNumber, unitName: state.unitName, detailsOpen: state.detailsOpen}));
});
JS;
        $process = new Process(['node', '-e', $script], base_path(), null, $root->getAttribute('x-data'));
        $process->mustRun();

        return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    }
}
