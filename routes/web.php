<?php

use App\Http\Controllers\Admin\ArenaController;
use App\Http\Controllers\Admin\AthleteController;
use App\Http\Controllers\Admin\BracketController;
use App\Http\Controllers\Admin\CallingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContingentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\EquipmentLoanController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\MatchupController;
use App\Http\Controllers\Admin\PerformanceController;
use App\Http\Controllers\Admin\ReadinessController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ScoreController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('display.index'));

Route::get('/welcome', fn() => view('welcome'));

/*
|--------------------------------------------------------------------------
| Alias dashboard (dipakai redirect Breeze setelah login)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', fn() => redirect()->route('admin.dashboard'))
    ->middleware(['auth', 'active'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Public Display (tanpa login)
|--------------------------------------------------------------------------
*/
Route::get('/display', [DisplayController::class, 'index'])->name('display.index');
Route::get('/display/data', [DisplayController::class, 'data'])->name('display.data');
Route::get('/display/arena/{arena}', [DisplayController::class, 'arena'])->name('display.arena');
Route::get('/display/arena/{arena}/data', [DisplayController::class, 'arenaData'])->name('display.arena.data');

/*
|--------------------------------------------------------------------------
| Back office
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    /* Data Atlet */
    Route::get('athletes', [AthleteController::class, 'index'])->middleware('permission:athletes.view')->name('athletes.index');
    Route::post('athletes', [AthleteController::class, 'store'])->middleware('permission:athletes.create')->name('athletes.store');
    Route::get('athletes/{athlete}', [AthleteController::class, 'show'])->middleware('permission:athletes.view')->name('athletes.show');
    Route::put('athletes/{athlete}', [AthleteController::class, 'update'])->middleware('permission:athletes.update')->name('athletes.update');
    Route::delete('athletes/{athlete}', [AthleteController::class, 'destroy'])->middleware('permission:athletes.delete')->name('athletes.destroy');
    Route::get('athletes-labels', [AthleteController::class, 'labels'])->middleware('permission:athletes.view')->name('athletes.labels');

    Route::get('contingents', [ContingentController::class, 'index'])->middleware('permission:contingents.view')->name('contingents.index');
    Route::post('contingents', [ContingentController::class, 'store'])->middleware('permission:contingents.create')->name('contingents.store');
    Route::put('contingents/{contingent}', [ContingentController::class, 'update'])->middleware('permission:contingents.update')->name('contingents.update');
    Route::delete('contingents/{contingent}', [ContingentController::class, 'destroy'])->middleware('permission:contingents.delete')->name('contingents.delete');

    Route::get('categories', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update')->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.delete');

    /* Jadwal */
    Route::get('schedules', [ScheduleController::class, 'index'])->middleware('permission:schedules.view')->name('schedules.index');
    Route::post('schedules', [ScheduleController::class, 'store'])->middleware('permission:schedules.create')->name('schedules.store');
    Route::get('schedules/{schedule}', [ScheduleController::class, 'show'])->middleware('permission:schedules.view')->name('schedules.show');
    Route::put('schedules/{schedule}', [ScheduleController::class, 'update'])->middleware('permission:schedules.update')->name('schedules.update');
    Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy'])->middleware('permission:schedules.delete')->name('schedules.delete');
    Route::get('schedules-export', [ScheduleController::class, 'export'])->middleware('permission:schedules.export')->name('schedules.export');
    Route::get('schedules-template', [ScheduleController::class, 'template'])->middleware('permission:schedules.import')->name('schedules.template');
    Route::post('schedules-import', [ScheduleController::class, 'import'])->middleware('permission:schedules.import')->name('schedules.import');

    /* Arena */
    Route::get('arenas', [ArenaController::class, 'index'])->middleware('permission:arenas.view')->name('arenas.index');
    Route::post('arenas', [ArenaController::class, 'store'])->middleware('permission:arenas.create')->name('arenas.store');
    Route::put('arenas/{arena}', [ArenaController::class, 'update'])->middleware('permission:arenas.update')->name('arenas.update');
    Route::delete('arenas/{arena}', [ArenaController::class, 'destroy'])->middleware('permission:arenas.delete')->name('arenas.delete');

    /* Calling */
    Route::get('callings', [CallingController::class, 'index'])->middleware('permission:callings.view')->name('callings.index');
    Route::get('callings-data', [CallingController::class, 'data'])->middleware('permission:callings.view')->name('callings.data');
    Route::post('callings/{calling}/send', [CallingController::class, 'send'])->middleware('permission:callings.send')->name('callings.send');
    Route::post('callings-schedule/{schedule}/send-all', [CallingController::class, 'sendAll'])->middleware('permission:callings.send')->name('callings.send-all');

    /* Scan / Verifikasi */
    Route::get('verifications', [VerificationController::class, 'index'])->middleware('permission:verifications.view')->name('verifications.index');
    Route::post('verifications', [VerificationController::class, 'store'])->middleware('permission:verifications.create')->name('verifications.store');

    /* Pemeriksaan kesiapan */
    Route::get('readiness', [ReadinessController::class, 'index'])->middleware('permission:readiness.view')->name('readiness.index');
    Route::post('readiness/{readinessCheck}', [ReadinessController::class, 'update'])->middleware('permission:readiness.update')->name('readiness.update');
    Route::post('readiness-schedule/{schedule}/ready', [ReadinessController::class, 'markReady'])->middleware('permission:readiness.update')->name('readiness.mark-ready');

    /* Perlengkapan */
    Route::get('equipments', [EquipmentController::class, 'index'])->middleware('permission:equipments.view')->name('equipments.index');
    Route::post('equipments', [EquipmentController::class, 'store'])->middleware('permission:equipments.create')->name('equipments.store');
    Route::put('equipments/{equipment}', [EquipmentController::class, 'update'])->middleware('permission:equipments.update')->name('equipments.update');
    Route::delete('equipments/{equipment}', [EquipmentController::class, 'destroy'])->middleware('permission:equipments.delete')->name('equipments.delete');

    Route::get('equipment-loans', [EquipmentLoanController::class, 'index'])->middleware('permission:equipment-loans.view')->name('equipment-loans.index');
    Route::post('equipment-loans', [EquipmentLoanController::class, 'store'])->middleware('permission:equipment-loans.borrow')->name('equipment-loans.store');
    Route::post('equipment-loans/{equipmentLoan}/return', [EquipmentLoanController::class, 'return'])->middleware('permission:equipment-loans.return')->name('equipment-loans.return');

    /* Daeryun */
    Route::get('matches', [MatchupController::class, 'index'])->middleware('permission:matches.view')->name('matches.index');
    Route::get('matches/{matchup}', [MatchupController::class, 'show'])->middleware('permission:matches.view')->name('matches.show');
    Route::post('matches/{matchup}/start', [MatchupController::class, 'start'])->middleware('permission:matches.start')->name('matches.start');
    Route::post('matches/{matchup}/finish', [MatchupController::class, 'finish'])->middleware('permission:matches.result')->name('matches.finish');

    /* Seni + Nilai Juri */
    Route::get('performances', [PerformanceController::class, 'index'])->middleware('permission:performances.view')->name('performances.index');
    Route::post('performances', [PerformanceController::class, 'store'])->middleware('permission:performances.create')->name('performances.store');
    Route::post('performances/{performance}/status', [PerformanceController::class, 'updateStatus'])->middleware('permission:performances.update')->name('performances.status');
    Route::get('scores/{performance}', [ScoreController::class, 'edit'])->middleware('permission:scores.view')->name('scores.edit');
    Route::post('scores/{performance}', [ScoreController::class, 'store'])->middleware('permission:scores.create')->name('scores.store');

    /* Bracket */
    Route::get('brackets', [BracketController::class, 'index'])->middleware('permission:brackets.view')->name('brackets.index');
    Route::post('brackets/generate', [BracketController::class, 'generate'])->middleware('permission:brackets.generate')->name('brackets.generate');

    /* Hasil & Ranking */
    Route::get('results', [ResultController::class, 'index'])->middleware('permission:results.view')->name('results.index');

    /* Riwayat */
    Route::get('history', [HistoryController::class, 'index'])->middleware('permission:history.view')->name('history.index');

    /* Manajemen user & role */
    Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.view')->name('users.show');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->middleware('permission:roles.permissions')->name('roles.permissions');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:roles.permissions')->name('roles.permissions.update');

    /* Identitas & tampilan aplikasi (judul, deskripsi, logo, favicon) */
    Route::get('settings', [SettingController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->middleware('permission:settings.update')->name('settings.update');
    Route::delete('settings', [SettingController::class, 'reset'])->middleware('permission:settings.update')->name('settings.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
