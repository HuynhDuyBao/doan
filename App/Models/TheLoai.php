<?php  
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TheLoai extends Model
{
    protected $table = 'TheLoai';
    protected $primaryKey = 'MaTheLoai';
    public $timestamps = false;
    protected $fillable = ['TenTheLoai'];
}