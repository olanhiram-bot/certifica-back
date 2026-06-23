<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseCallController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingContentController;
use App\Http\Controllers\LandingContentTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\StampActivityCommentController;
use App\Http\Controllers\StampActivityController;
use App\Http\Controllers\StampController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login/username', [AuthController::class, 'loginByUsername'])->name('auth.loginByUsername');
Route::post('/login/efirma', [AuthController::class, 'loginByEfirma'])->name('auth.loginByEfirma');
Route::post('/login/sso', [AuthController::class, 'loginBySSO'])->name('auth.loginBySSO');

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('dashboard/resume', [DashboardController::class, 'resume'])->name('dashboard.resume');
    Route::get('dashboard/status-with-totals-activities', [DashboardController::class, 'getStatusWithTotalsActivities'])->name('dashboard.statusWithTotalsActivities');
    Route::get('dashboard/stamp-activities', [DashboardController::class, 'getStampActivities'])->name('dashboard.stampActivities');

    Route::get('stamps/current-by-provider', [StampController::class, 'lastByProvider'])->name('stamps.lastByProvider');
    Route::resource('stamps', StampController::class)->only(['index', 'store', 'show', 'destroy'])->names('stamp');
    Route::put('stamps/{stamp}/approve', [StampController::class, 'approve'])->name('stamps.approve');
    Route::put('stamps/{stamp}/reject', [StampController::class, 'reject'])->name('stamps.reject');
    Route::post('stamp-activities/{stamp_activity}/add-file', [StampActivityController::class, 'addFile'])->name('stampActivities.addFile');
    Route::put('stamp-activities/{stamp_activity}/material-was-viwed', [StampActivityController::class, 'viewMaterial'])->name('StampsActivity.MaterialWasViwed');
    Route::put('stamp-activities/{stamp_activity}/approved', [StampActivityController::class, 'approvedStampActivity'])->name('StampsActivity.approved');
    Route::put('stamp-activities/{stamp_activity}/rejected', [StampActivityController::class, 'rejectedStampActivity'])->name('StampsActivity.rejected');
    Route::put('stamp-activities/{stamp_activity}/deleted-file', [StampActivityController::class, 'destroyFile'])->name('StampsActivity.destroyFile');
    Route::resource('stamps.stamp-activities', StampActivityController::class)->shallow()->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::resource('stamp-activities.comment', StampActivityCommentController::class)->shallow()->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::resource('sections.activities', ActivityController::class)->shallow()->only(['index', 'show']);
    Route::resource('landing-content-types', LandingContentTypeController::class)->shallow()->only(['index']);

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_as_read');
    Route::put('notification-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all_as_read');
    Route::put('notifications-all-viewed', [NotificationController::class, 'markAllAsViewed'])->name('notifications.mark_all_as_viewed');
    Route::get('sections-types/{sectionsType}/sections', [SectionController::class, 'index'])->name('sections-types.sections.index');
    /**
     * -----------------------------------------------------------------------------------------------------------
     * | Rutas de administración (Validador + Admin)                                                             |
     * -----------------------------------------------------------------------------------------------------------
     */
    Route::middleware('auth.method.sso')->prefix('admin')->group(function () {
        Route::get('dashboard/resume', [AdminDashboardController::class, 'resume'])->name('admin.dashboard.resume');
        Route::get('dashboard/stamp-statuses', [AdminDashboardController::class, 'stampStatusesWithTotals'])->name('admin.dashboard.stampStatusesWithTotals');
        Route::get('dashboard/stale-alert', [AdminDashboardController::class, 'staleAlert'])->name('admin.dashboard.staleAlert');
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard.index');
        Route::get('dashboard/export', [AdminDashboardController::class, 'export'])->name('admin.dashboard.export');
        Route::resource('course-call', CourseCallController::class)->only(['index', 'store', 'show', 'destroy'])->names('courseCall');
        Route::put('stamps/{stamp}/take-reviewer', [StampController::class, 'takeReviewerStamp'])->name('admin.stamps.takeReviewerStamp');
        Route::put('stamps/{stamp}/release-stamp', [StampController::class, 'releaseStamp'])->name('admin.stamps.releaseStamp');

        Route::resource('content-types.landing-contents', LandingContentController::class)->shallow()->only(['store', 'update', 'destroy']);
        Route::resource('sections-types.sections', SectionController::class)->shallow()->only(['store', 'update', 'destroy']);
        Route::resource('sections.activities', ActivityController::class)->shallow()->only(['store', 'update', 'destroy']);
    });
});

Route::resource('content-types.landing-contents', LandingContentController::class)->shallow()->only(['index', 'show']);

// Relación: SectionType -> Section
Route::resource('sections-types.sections', SectionController::class)->shallow()->only(['show']);

// Stream de contenido de la actividad (PDF) — público para permitir visualización sin token
Route::get('activities/{activity}/content', [ActivityController::class, 'content'])->name('activities.content');

// Servir archivo de evidencia — acceso público mediante URL firmada temporalmente (sin Sanctum)
Route::get('stamp-activities/{stamp_activity}/file', [StampActivityController::class, 'serveFile'])
    ->name('stampActivities.serveFile')
    ->middleware('signed');
