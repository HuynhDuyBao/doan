<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TheLoai;  
use App\Models\QuocGia;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    // ================== THỂ LOẠI ==================

    public function getGenres()
    {
        $genres = TheLoai::orderBy('MaTheLoai', 'asc')->get();  // SỬA: TheLoai + MaTheLoai
        return response()->json([
            'success' => true,
            'data' => $genres
        ]);
    }

    public function addGenre(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50'
        ]);

        $exists = TheLoai::where('TenTheLoai', $request->name)->exists(); 
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Thể loại đã tồn tại!'
            ], 400);
        }

        $genre = TheLoai::create([  
            'TenTheLoai' => $request->name  
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm thể loại thành công!',
            'data' => $genre
        ], 201);
    }

    public function updateGenre(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:50'
        ]);

        $genre = TheLoai::findOrFail($id);  // SỬA: TheLoai
        $genre->TenTheLoai = $request->name;  // SỬA: TenTheLoai
        $genre->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công!',
            'data' => $genre
        ]);
    }

    public function deleteGenre($id)
    {
        try {
            $genre = TheLoai::findOrFail($id);  // SỬA: TheLoai
            $genre->delete();
            return response()->json([
                'success' => true,
                'message' => 'Xóa thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa: có phim đang dùng thể loại này!'
            ], 400);
        }
    }

    // ================== QUỐC GIA ================== (OK, không thay đổi)
    public function getCountries()
    {
        $countries = QuocGia::orderBy('MaQuocGia', 'asc')->get();
        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    public function addCountry(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:QuocGia,TenQuocGia'
        ]);

        $country = QuocGia::create([
            'TenQuocGia' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm quốc gia thành công!',
            'data' => $country
        ], 201);
    }

    public function updateCountry(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100'
        ]);

        $country = QuocGia::findOrFail($id);
        $country->TenQuocGia = $request->name;
        $country->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công!',
            'data' => $country
        ]);
    }

    public function deleteCountry($id)
    {
        try {
            $country = QuocGia::findOrFail($id);
            $country->delete();
            return response()->json([
                'success' => true,
                'message' => 'Xóa thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa: có phim hoặc diễn viên đang dùng quốc gia này!'
            ], 400);
        }
    }
}