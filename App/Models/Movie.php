<?php

namespace App\Models;
use App\Models\TheLoai;
use App\Models\QuocGia;

use Illuminate\Database\Eloquent\Model;
class Movie extends Model
{
    protected $table = 'Phim';
    protected $primaryKey = 'MaPhim';
    public $timestamps = false; // bạn dùng NgayTao/NgayCapNhat thủ công

    protected $fillable = [
        'TenPhim','TieuDe','MoTa','NoiDung','ThoiLuong','NamPhatHanh',
        'DanhGia','LuotXem','TinhTrang','PhanLoai','HinhAnh','Link','MaQuocGia'
    ];

    // Quan hệ nhiều-nhiều với thể loại
    public function theloai()
    {
        return $this->belongsToMany(
            TheLoai::class,
            'Phim_TheLoai',
            'MaPhim',
            'MaTheLoai'
        );
    }

    public function quocgia()
    {
        return $this->belongsTo(QuocGia::class, 'MaQuocGia', 'MaQuocGia');
    }

    // them tap phim
    public function episodes()
    {
        return $this->hasMany(Episode::class, 'MaPhim', 'MaPhim');
    }

    public function videos()
    {
        return $this->hasMany(Video::class, 'MaPhim', 'MaPhim');
    }

    public function actors()
    {
        return $this->belongsToMany(Actor::class, 'Phim_DienVien', 'MaPhim', 'MaDienVien');
    }

    public function directors()
    {
        return $this->belongsToMany(Director::class, 'Phim_DaoDien', 'MaPhim', 'MaDaoDien');
    }

    // favorites, ratings, comments (optional)
    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'MaPhim', 'MaPhim');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'MaPhim', 'MaPhim');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'MaPhim', 'MaPhim');
    }
    public function getEpisodesAttribute($value)
{
    return $this->episodes()->get()->isEmpty() ? collect([]) : $this->episodes()->get();
}
}
