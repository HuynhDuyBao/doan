<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TapPhim', function (Blueprint $table) {
            // Giữ lại cột Link cũ để tương thích dữ liệu cũ (nếu cần)
            // $table->string('Link')->nullable()->change();

            // Thêm các cột mới
            $table->string('original_file')->nullable()->after('Link'); // file MKV gốc trên R2
            $table->string('hls_url')->nullable()->after('original_file');     // link master.m3u8
            $table->string('r2_folder')->nullable()->after('hls_url');         // thư mục: hls/123/45
            $table->string('status')->default('pending')->after('r2_folder');  // pending | processing | ready | failed
            $table->integer('duration')->nullable(); // giây, lấy từ FFmpeg
        });
    }

    public function down(): void
    {
        Schema::table('TapPhim', function (Blueprint $table) {
            $table->dropColumn(['original_file', 'hls_url', 'r2_folder', 'status', 'duration']);
        });
    }
};