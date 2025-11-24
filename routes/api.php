<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\YeuThichController;
use App\Http\Controllers\Api\LichSuController;
use App\Http\Controllers\Api\BinhLuanController;
use App\Http\Controllers\Api\RatingController;

// ================== PHIM ==================
Route::prefix('phim')->group(function () {
    Route::get('/', [MovieController::class, 'index']);
    Route::get('/{id}', [MovieController::class, 'show']);
    Route::get('/top/viewed', [MovieController::class, 'getTopViewedMovies']);

    // Giữ lại cho tương thích cũ (nếu còn dùng R2)
    Route::get('/{MaPhim}/tap/{MaTap}/status', [MovieController::class, 'episodeStatus']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [MovieController::class, 'store']);
        Route::put('/{id}', [MovieController::class, 'update']);
        Route::delete('/{id}', [MovieController::class, 'destroy']);

        // THÊM TẬP PHIM (Cloudflare Stream)
        Route::post('/{id}/tap', [MovieController::class, 'addEpisode']);

        Route::delete('/{id}/tap/{tapId}', [MovieController::class, 'deleteEpisode']);
    });
});

// ================== CLOUDFLARE STREAM UPLOAD (ADMIN ONLY) ==================
Route::middleware('auth:sanctum')->post('/admin/stream-upload-url', [MovieController::class, 'getCloudflareUploadUrl']);
Route::middleware('auth:sanctum')->match(['POST', 'OPTIONS'], '/admin/tus-upload-url', [MovieController::class, 'getTusUploadUrl']);

// ================== THỂ LOẠI ==================
Route::prefix('genres')->group(function () {
    Route::get('/', [GenreController::class, 'getGenres']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [GenreController::class, 'addGenre']);
        Route::put('/{id}', [GenreController::class, 'updateGenre']);
        Route::delete('/{id}', [GenreController::class, 'deleteGenre']);
    });
});

// ================== QUỐC GIA ==================
Route::prefix('countries')->group(function () {
    Route::get('/', [GenreController::class, 'getCountries']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [GenreController::class, 'addCountry']);
        Route::put('/{id}', [GenreController::class, 'updateCountry']);
        Route::delete('/{id}', [GenreController::class, 'deleteCountry']);
    });
});

// ================== TÀI KHOẢN ==================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users', [AuthController::class, 'users']); // Lấy danh sách tài khoản
        Route::put('/update/{id}', [AuthController::class, 'update']);
        
        Route::delete('/delete/{id}', [AuthController::class, 'delete']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/change-password/{id}', [AuthController::class, 'changePassword']);
    });
});

Route::middleware('auth:sanctum')->post('/auth/upload-avatar/{id}', [AuthController::class, 'uploadAvatar']);
// ================== YÊU THÍCH ==================
Route::prefix('favorites')->middleware('auth:sanctum')->group(function () {
    Route::get('/{id}', [YeuThichController::class, 'index']);
    Route::post('/', [YeuThichController::class, 'store']);
    Route::delete('/{id}/{maPhim}', [YeuThichController::class, 'destroy']);
});

// ================== LỊCH SỬ XEM PHIM ==================
Route::middleware('auth:sanctum')->prefix('history')->group(function () {
    Route::get('/', [LichSuController::class, 'index']);
    Route::post('/', [LichSuController::class, 'store']);
    Route::delete('/', [LichSuController::class, 'destroyAll']);
    Route::delete('/{maPhim}', [LichSuController::class, 'destroy']);
});

// ================== BÌNH LUẬN ==================
Route::prefix('binhluan')->group(function () {
    Route::get('/{maPhim}', [BinhLuanController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [BinhLuanController::class, 'store']);
        Route::put('/{id}', [BinhLuanController::class, 'update']);
        Route::delete('/{id}', [BinhLuanController::class, 'destroy']);
    });
});

// ================== ĐÁNH GIÁ PHIM ==================
Route::prefix('ratings')->group(function () {
    Route::get('/{maPhim}', [RatingController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [RatingController::class, 'store']);
        Route::put('/{maPhim}', [RatingController::class, 'update']);
        Route::delete('/{maPhim}', [RatingController::class, 'destroy']);
    });
});

