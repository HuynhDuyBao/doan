<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Movie;
use App\Models\User;

class BinhLuan extends Model
{
    use HasFactory;

    protected $table = 'binhluan';
    protected $primaryKey = 'MaBinhLuan';
    public $timestamps = false; // vì bạn dùng cột ThoiGian riêng, không có created_at/update_at

    protected $fillable = [
        'TenDN',
        'MaPhim',
        'NoiDung',
        'ThoiGian',
        'parent_id',
    ];

    // Mối quan hệ với phim
 public function phim()
{
    return $this->belongsTo(Movie::class, 'MaPhim', 'MaPhim');
}

    // Mối quan hệ với tài khoản
    public function nguoiDung()
    {
        return $this->belongsTo(User::class, 'TenDN', 'ten_dang_nhap');
    }
public function replies()
{
    return $this->hasMany(BinhLuan::class, 'parent_id', 'MaBinhLuan')
                ->with('nguoiDung')
                ->orderByDesc('ThoiGian');
}

public function parent()
{
    return $this->belongsTo(BinhLuan::class, 'parent_id', 'MaBinhLuan');
}
}
