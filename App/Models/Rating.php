<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'DanhGia';
    protected $primaryKey = null; // Không có khóa chính tự tăng (composite key)
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'TenDN', 'MaPhim', 'SoDiem', 'BinhLuan', 'ThoiGian'
    ];

    // Quan hệ với User
    public function user()
    {
        return $this->belongsTo(User::class, 'TenDN', 'ten_dang_nhap');
    }

    // Quan hệ với Movie
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'MaPhim', 'MaPhim');
    }
}