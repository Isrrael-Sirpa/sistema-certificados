<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BeneficiaryBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('is_active', true)->first();
    }

    public function test_can_download_official_template_file(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('beneficiaries.template.download'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="plantilla_beneficiarios_ft_umsa.csv"', $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('CI;NOMBRES;APELLIDOS;TITULO_GRADO;CARRERA_UNIDAD', $content);
        $this->assertStringContainsString('Licenciatura en Informática', $content);
    }

    public function test_can_preview_and_import_valid_csv_file(): void
    {
        $csvContent = implode("\n", [
            'CI,NOMBRES,APELLIDOS,TITULO_GRADO,CARRERA_UNIDAD,CORREO,TELEFONO',
            '1000001,Alvaro Mateo,Mamani Flores,Univ.,Licenciatura en Informática,alvaro@umsa.bo,70000001',
            '1000002,Beatriz Jimena,Condori Quispe,Univ.,Ingeniería Electrónica,beatriz@umsa.bo,70000002',
        ]);

        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csvContent);

        // 1. Preview
        $response = $this->actingAs($this->user)
            ->post(route('beneficiaries.import.preview'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('beneficiaries.import'));
        $this->assertTrue(session()->has('beneficiary_bulk_import_data'));

        $preview = session('beneficiary_bulk_import_data');
        $this->assertEquals(2, $preview['summary']['ready']);
        $this->assertEquals(0, $preview['summary']['duplicate_skip']);
        $this->assertEquals(0, $preview['summary']['error']);

        // 2. Execute import
        $storeResponse = $this->actingAs($this->user)
            ->post(route('beneficiaries.import.store'));

        $storeResponse->assertRedirect(route('beneficiaries.index'));
        $storeResponse->assertSessionHas('success');

        $this->assertDatabaseHas('beneficiaries', [
            'document_number' => '1000001',
            'first_name' => 'Alvaro Mateo',
            'last_name' => 'Mamani Flores',
        ]);

        $this->assertDatabaseHas('beneficiaries', [
            'document_number' => '1000002',
            'first_name' => 'Beatriz Jimena',
            'last_name' => 'Condori Quispe',
        ]);

        // Verificar registro en AuditLog
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'beneficiaries_bulk_imported',
        ]);
    }

    public function test_duplicate_beneficiary_by_ci_is_automatically_skipped(): void
    {
        // Beneficiario ya existente
        Beneficiary::create([
            'document_type' => 'CI',
            'document_number' => '8492019',
            'first_name' => 'Juan Original',
            'last_name' => 'Perez Original',
            'institution_or_department' => 'Carrera Antigua',
        ]);

        // Archivo que contiene el mismo CI pero con otro nombre y un alumno nuevo
        $csvContent = implode("\n", [
            'CI;NOMBRES;APELLIDOS;CARRERA',
            '8492019;Juan Modificado;Perez Modificado;Química Industrial',
            '9999001;Nuevo Estudiante;Gomez;Mecánica Automotriz',
        ]);

        $file = UploadedFile::fake()->createWithContent('nomina.csv', $csvContent);

        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.preview'), ['file' => $file]);

        $preview = session('beneficiary_bulk_import_data');
        $this->assertEquals(1, $preview['summary']['ready']);
        $this->assertEquals(1, $preview['summary']['duplicate_skip']);

        // Confirmar importación
        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.store'));

        // El alumno original NO debe haber sido sobreescrito
        $original = Beneficiary::where('document_number', '8492019')->first();
        $this->assertNotNull($original);
        $this->assertEquals('Juan Original', $original->first_name);

        // El nuevo alumno sí se debe haber creado
        $this->assertDatabaseHas('beneficiaries', [
            'document_number' => '9999001',
            'first_name' => 'Nuevo Estudiante',
        ]);
    }

    public function test_intelligent_header_mapping_recognizes_synonyms(): void
    {
        $csvContent = implode("\n", [
            'CARNET;NOMBRES_ESTUDIANTE;APELLIDOS;CARRERA;E-MAIL',
            '7778889;Carlos David;Rojas Salazar;Licenciatura en Informática;carlos@umsa.bo',
        ]);

        $file = UploadedFile::fake()->createWithContent('variacion_columnas.csv', $csvContent);

        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.preview'), ['file' => $file]);

        $preview = session('beneficiary_bulk_import_data');
        $this->assertEquals(1, $preview['summary']['ready']);
        $this->assertEquals('7778889', $preview['rows'][0]['data']['document_number']);
        $this->assertEquals('Carlos David', $preview['rows'][0]['data']['first_name']);
        $this->assertEquals('Rojas Salazar', $preview['rows'][0]['data']['last_name']);
        $this->assertEquals('Licenciatura en Informática', $preview['rows'][0]['data']['institution_or_department']);
    }

    public function test_rows_with_missing_mandatory_fields_are_marked_as_error(): void
    {
        $csvContent = implode("\n", [
            'CI;NOMBRES;APELLIDOS',
            ';Pedro;Sin Carnet',       // Sin CI
            '5555555;;Sin Nombre',     // Sin Nombre
            '6666666;Laura;Valida',    // Válido
        ]);

        $file = UploadedFile::fake()->createWithContent('con_errores.csv', $csvContent);

        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.preview'), ['file' => $file]);

        $preview = session('beneficiary_bulk_import_data');
        $this->assertEquals(1, $preview['summary']['ready']);
        $this->assertEquals(2, $preview['summary']['error']);

        // Al ejecutar, solo se importa el 1 válido
        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.store'));

        $this->assertDatabaseHas('beneficiaries', ['document_number' => '6666666']);
        $this->assertDatabaseMissing('beneficiaries', ['first_name' => 'Sin Carnet']);
    }

    public function test_cancel_preview_clears_session(): void
    {
        session(['beneficiary_bulk_import_data' => ['rows' => []]]);

        $response = $this->actingAs($this->user)
            ->post(route('beneficiaries.import.cancel'));

        $response->assertRedirect(route('beneficiaries.import'));
        $this->assertFalse(session()->has('beneficiary_bulk_import_data'));
    }

    public function test_can_import_beneficiaries_from_xlsx_file(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_').'.xlsx';
        $zip = new \ZipArchive;
        $zip->open($tempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // xl/sharedStrings.xml
        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="6" uniqueCount="6">'
            .'<si><t>CI</t></si>'
            .'<si><t>NOMBRES</t></si>'
            .'<si><t>APELLIDOS</t></si>'
            .'<si><t>8899112</t></si>'
            .'<si><t>Gabriel</t></si>'
            .'<si><t>Mendoza</t></si>'
            .'</sst>';
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);

        // xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'
            .'<row r="1">'
            .'<c r="A1" t="s"><v>0</v></c>'
            .'<c r="B1" t="s"><v>1</v></c>'
            .'<c r="C1" t="s"><v>2</v></c>'
            .'</row>'
            .'<row r="2">'
            .'<c r="A2" t="s"><v>3</v></c>'
            .'<c r="B2" t="s"><v>4</v></c>'
            .'<c r="C2" t="s"><v>5</v></c>'
            .'</row>'
            .'</sheetData>'
            .'</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $file = new UploadedFile($tempPath, 'alumnos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.preview'), ['file' => $file]);

        $preview = session('beneficiary_bulk_import_data');
        $this->assertNotNull($preview);
        $this->assertEquals(1, $preview['summary']['ready']);
        $this->assertEquals('8899112', $preview['rows'][0]['data']['document_number']);
        $this->assertEquals('Gabriel', $preview['rows'][0]['data']['first_name']);
        $this->assertEquals('Mendoza', $preview['rows'][0]['data']['last_name']);

        @unlink($tempPath);
    }

    public function test_can_import_single_column_full_names_with_bolivian_departments(): void
    {
        $csvContent = implode("\n", [
            "Nombre\tCI\tCorreo Electrónico\tCarrera o Facultad",
            "Ana Lucía Quispe Mamani\t7845123 LP\tana.quispe@universidad.edu.bo\tMedicina",
            "Carlos Eduardo Rojas Fernández\t6547891 CB\tcarlos.rojas@universidad.edu.bo\tIngeniería de Sistemas",
            "María Fernanda Choque Apaza\t8912345 LP\tmaria.choque@universidad.edu.bo\tDerecho",
            "José Luis Villarroel Paredes\t5678123 SC\tjose.villarroel@universidad.edu.bo\tIngeniería Civil",
            "Valeria Nicole Flores Ticona\t9123456 LP\tvaleria.flores@universidad.edu.bo\tPsicología",
        ]);

        $file = UploadedFile::fake()->createWithContent('alumnos_reales.csv', $csvContent);

        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.preview'), ['file' => $file]);

        $preview = session('beneficiary_bulk_import_data');
        $this->assertNotNull($preview);
        $this->assertEquals(5, $preview['summary']['ready']);
        $this->assertEquals(0, $preview['summary']['error']);

        // Verificar separación del primer estudiante
        $row1 = $preview['rows'][0]['data'];
        $this->assertEquals('7845123 LP', $row1['document_number']);
        $this->assertEquals('Ana Lucía', $row1['first_name']);
        $this->assertEquals('Quispe Mamani', $row1['last_name']);
        $this->assertEquals('Medicina', $row1['institution_or_department']);
        $this->assertEquals('ana.quispe@universidad.edu.bo', $row1['email']);

        // Verificar segundo estudiante
        $row2 = $preview['rows'][1]['data'];
        $this->assertEquals('6547891 CB', $row2['document_number']);
        $this->assertEquals('Carlos Eduardo', $row2['first_name']);
        $this->assertEquals('Rojas Fernández', $row2['last_name']);
        $this->assertEquals('Ingeniería de Sistemas', $row2['institution_or_department']);

        // Confirmar guardado definitivo
        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.store'));

        $this->assertDatabaseHas('beneficiaries', [
            'document_number' => '7845123 LP',
            'first_name' => 'Ana Lucía',
            'last_name' => 'Quispe Mamani',
            'institution_or_department' => 'Medicina',
        ]);
    }

    public function test_can_import_with_arbitrary_column_order_separate_paterno_materno_and_extra_columns(): void
    {
        // Orden arbitrario con columnas extra: Correo, Observación, CI, Semestre, Materno, Nombres, Paterno
        $csvContent = implode("\n", [
            'Correo;Observacion;CI;Semestre;Apellido Materno;Nombres;Apellido Paterno',
            'lucas@umsa.bo;Aprobado;1234567 CB;8vo;Mamani;Lucas Daniel;Flores',
        ]);

        $file = UploadedFile::fake()->createWithContent('desordenado.csv', $csvContent);

        $this->actingAs($this->user)
            ->post(route('beneficiaries.import.preview'), ['file' => $file]);

        $preview = session('beneficiary_bulk_import_data');
        $this->assertNotNull($preview);
        $this->assertEquals(1, $preview['summary']['ready']);
        $this->assertEquals(0, $preview['summary']['error']);

        $row = $preview['rows'][0]['data'];
        $this->assertEquals('1234567 CB', $row['document_number']);
        $this->assertEquals('Lucas Daniel', $row['first_name']);
        $this->assertEquals('Flores Mamani', $row['last_name']);
        $this->assertEquals('lucas@umsa.bo', $row['email']);
        $this->assertEquals('Univ.', $row['academic_title']);
        $this->assertEquals('Facultad de Tecnología - UMSA', $row['institution_or_department']);
    }
}
