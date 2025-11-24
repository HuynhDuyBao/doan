<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Movie;
use App\Models\User;

class YeuThich extends Model
{
    use HasFactory;

    protected $table = 'phim_yeuthich';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'MaPhim',
        'UserID',
        'NgayThem',
    ];

    public function phim()
    {
       return $this->belongsTo(Movie::class, 'MaPhim', 'MaPhim');
    }

    public function taiKhoan()
    {
       return $this->belongsTo(User::class, 'UserID', 'id');
    }
}
