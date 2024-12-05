<?php

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
| //untuk menampilkan get le pakek get users

*/

use App\Http\Controllers\AuthController;
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Hanya bisa diakses jika user memiliki token yang valid
Route::middleware('auth:api')->get('/user', [AuthController::class, 'getAuthenticatedUser']);

use App\Http\Controllers\UserController;
// Route untuk menambahkan user (hanya admin)
Route::middleware('auth:api', 'role:admin')->post('/user/create', [UserController::class, 'createUser']);
// Route untuk mengubah data user (hanya admin atau user yang bersangkutan)
Route::middleware('auth:api')->put('/user/{id}', [UserController::class, 'updateUser']);
// Route untuk mengambil data user berdasarkan ID
Route::middleware('auth:api')->get('/user/{id}', [UserController::class, 'getUserById']);

use App\Http\Controllers\PresenceController;
// Route untuk kehadiran izn dll
Route::middleware('auth:api')->post('/presensi', [PresenceController::class, 'store']); 
Route::get('/presensi/riwayat', [PresenceController::class, 'riwayat'])
    ->middleware(['auth:api', 'role:admin,siswa']);
Route::get('/presensi/riwayat/{user_id}', [PresenceController::class, 'riwayatByUserId'])
    ->middleware(['auth:api', 'role:admin']);

// Route untuk menghapus data user (hanya admin)
Route::middleware('auth:api', 'role:admin')->delete('/user/{id}', [UserController::class, 'deleteUser']); //delete/user

//Rekap Kehadiran Bulanan
Route::get('/presence/summary/{user_id}', [presenceController::class, 'monthlySummary']); // Melihat rekap kehadiran bulanan
    // Analisis Kehadiran
    Route::post('/presence/analysis', [presenceController::class, 'analyzePresence']); // Analisis tingkat kehadiran


