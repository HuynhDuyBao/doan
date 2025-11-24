<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    protected $table = 'TapPhim';
    protected $primaryKey = 'MaTap';
    public $timestamps = false;

    protected $fillable = [
        'MaPhim','TenTap','Link',
        'original_file','hls_url','r2_folder','status','duration',
        'cloudflare_uid','video_uid'
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'MaPhim', 'MaPhim');
    }

    // Helper: kiểm tra có thể phát chưa
    public function isReady()
    {
        return $this->status === 'ready' && $this->hls_url;
    }
}