<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\YeuThich;

class YeuThichController extends Controller
{
    // Lấy danh sách phim yêu thích theo người dùng
    public function index($id)
    {
        $favorites = YeuThich::where('UserID', $id)->with('phim')->get();
        return response()->json($favorites);
    }

    // Thêm phim vào yêu thích (chỉ 1 lần / người dùng)
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:tai_khoan,id',
            'MaPhim' => 'required|exists:phim,MaPhim',
        ]);

        $exists = YeuThich::where('UserID', $request->id)
                          ->where('MaPhim', $request->MaPhim)
                          ->exists();

        if ($exists) {
            return response()->json(['message' => 'Phim này đã có trong danh sách yêu thích'], 409);
        }

        $favorite = YeuThich::create([
            'UserID' => $request->id,
            'MaPhim' => $request->MaPhim,
        ]);

        return response()->json([
            'message' => 'Đã thêm vào danh sách yêu thích',
            'data' => $favorite
        ], 201);
    }

    // Xóa phim khỏi danh sách yêu thích
    public function destroy($id, $maPhim)
    {
        $favorite = YeuThich::where('UserID', $id)
                            ->where('MaPhim', $maPhim)
                            ->first();

        if (!$favorite) {
            return response()->json(['message' => 'Không tìm thấy phim trong danh sách yêu thích'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Đã xóa khỏi danh sách yêu thích']);
    }
}
