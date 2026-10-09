<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionalAssetController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

// Redirección inicial
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('verification.index');
});

// ==========================================
// Rutas Públicas de Verificación (Sin Autenticación)
// ==========================================
Route::prefix('verificar')->group(function () {
    Route::get('/', [VerificationController::class, 'index'])->name('verification.index');
    Route::post('/buscar', [VerificationController::class, 'search'])->name('verification.search');
    Route::get('/{token}', [VerificationController::class, 'show'])->name('verification.show');
});

// ==========================================
// Rutas de Autenticación
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ==========================================
// Panel Administrativo y Operativo (Protegido)
// ==========================================
Route::middleware(['auth'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gestión de Certificados
    Route::prefix('certificados')->name('certificates.')->group(function () {
        Route::get('/', [CertificateController::class, 'index'])->name('index');
        Route::get('/crear', [CertificateController::class, 'create'])->name('create');
        Route::post('/', [CertificateController::class, 'store'])->name('store');
        Route::get('/{certificate}', [CertificateController::class, 'show'])->name('show');
        Route::get('/{certificate}/vista-previa', [CertificateController::class, 'previewPdf'])->name('preview');
        Route::get('/{certificate}/descargar', [CertificateController::class, 'downloadPdf'])->name('download');
        Route::post('/vista-previa-borrador', [CertificateController::class, 'previewDraft'])->name('preview-draft');
        Route::post('/{certificate}/anular', [CertificateController::class, 'revoke'])->name('revoke');
    });

    // Gestión de Beneficiarios
    Route::resource('beneficiarios', BeneficiaryController::class)
        ->names('beneficiaries')
        ->parameters(['beneficiarios' => 'beneficiary']);

    // Gestión de Actividades
    Route::resource('actividades', ActivityController::class)
        ->names('activities')
        ->parameters(['actividades' => 'activity']);
    Route::post('/actividades/{activity}/emision-masiva', [ActivityController::class, 'batchIssue'])->name('activities.batch-issue');
    Route::post('/actividades/{activity}/configuracion-certificado', [ActivityController::class, 'updateCertificateConfig'])->name('activities.certificate-config');

    // Módulo de Reportes
    Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');

    // ==========================================
    // Rutas Exclusivas para Administradores
    // ==========================================
    Route::middleware([EnsureUserIsAdmin::class])->group(function () {
        // Gestión y Diseñador de Plantillas Institucionales
        Route::resource('plantillas', CertificateTemplateController::class)
            ->names('templates')
            ->parameters(['plantillas' => 'template']);
        Route::get('/plantillas/{template}/disenador', [CertificateTemplateController::class, 'designer'])->name('templates.designer');
        Route::put('/plantillas/{template}/disenador', [CertificateTemplateController::class, 'updateDesigner'])->name('templates.designer.update');
        Route::post('/plantillas/{template}/duplicar', [CertificateTemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::post('/plantillas/{template}/toggle-estado', [CertificateTemplateController::class, 'toggleStatus'])->name('templates.toggle-status');
        Route::post('/plantillas/{template}/hacer-predeterminada', [CertificateTemplateController::class, 'setDefault'])->name('templates.set-default');
        Route::get('/plantillas/{template}/previsualizar-pdf', [CertificateTemplateController::class, 'previewPdf'])->name('templates.preview');
        Route::post('/plantillas/{template}/previsualizar-en-vivo', [CertificateTemplateController::class, 'livePreview'])->name('templates.live-preview');

        // Biblioteca de Recursos Institucionales (Logos, Firmas, Sellos, Marcas de Agua)
        Route::resource('recursos-institucionales', InstitutionalAssetController::class)
            ->names('assets')
            ->parameters(['recursos-institucionales' => 'asset'])
            ->only(['index', 'store', 'destroy']);
        Route::post('/recursos-institucionales/{asset}/toggle-estado', [InstitutionalAssetController::class, 'toggleStatus'])->name('assets.toggle-status');

        // Gestión de Usuarios
        Route::resource('usuarios', UserController::class)
            ->names('users')
            ->parameters(['usuarios' => 'user']);
        Route::post('/usuarios/{user}/toggle-estado', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Registro de Auditoría
        Route::get('/auditoria', [AuditLogController::class, 'index'])->name('audit.index');

        // Configuración Institucional
        Route::get('/configuracion', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/configuracion', [SettingController::class, 'update'])->name('settings.update');
    });
});
