<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuocGia extends Model
{
    protected $table = 'QuocGia';
    protected $primaryKey = 'MaQuocGia';
    public $timestamps = false;

    protected $fillable = ['TenQuocGia'];
}