<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $appends = ['hinh_dai_dien_url'];

    protected $table = 'tai_khoan';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'ten_dang_nhap',
        'mat_khau',
        'email',
        'ho_ten',
        'hinh_dai_dien',
        'vai_tro',
        'trang_thai',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }

    // Lấy full URL Avatar
  public function getHinhDaiDienUrlAttribute()
    {
        if (!$this->hinh_dai_dien) return null;

        return "https://imagedelivery.net/YOUR_ACCOUNT_ID/{$this->hinh_dai_dien}/public";
    }
}
