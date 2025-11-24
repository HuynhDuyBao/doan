<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BinhLuan;
use App\Models\Movie;
use Illuminate\Support\Facades\Auth;

class BinhLuanController extends Controller
{
  

    // 📌 Lấy danh sách bình luận theo mã phim
// app/Http/Controllers/Api/BinhLuanController.php
public function index($maPhim)
{
    try {
        $comments = BinhLuan::where('MaPhim', $maPhim)
            ->whereNull('parent_id')
            ->with([
                'nguoiDung' => function ($query) {
                    $query->select('id', 'ten_dang_nhap', 'ho_ten', 'hinh_dai_dien');
                },
                'replies' => function ($query) { // SỬA: 'replies' (không phải 'replies.nguoiDung')
                    $query->with(['nguoiDung' => function ($q) {
                        $q->select('id', 'ten_dang_nhap', 'ho_ten', 'hinh_dai_dien');
                    }]);
                }
            ])
            ->orderByDesc('ThoiGian')
            ->get();

        return response()->json($comments);
    } catch (\Exception $e) {
        \Log::error("Lỗi lấy bình luận phim {$maPhim}: " . $e->getMessage());
        return response()->json(['error' => 'Lỗi server'], 500);
    }
}

    // ➕ Thêm bình luận
    public function store(Request $request)
    {
        $request->validate([
            'MaPhim' => 'required|exists:phim,MaPhim',
            'NoiDung' => 'required|string|max:500',
            'parent_id' => 'nullable|exists:binhluan,MaBinhLuan',
        ]);

        $user = Auth::user(); // lấy từ token
if (!$user) return response()->json(['message' => 'Unauthorized'], 401);
        $comment = BinhLuan::create([
            'TenDN' => $user->ten_dang_nhap,
            'MaPhim' => $request->MaPhim,
            'NoiDung' => $request->NoiDung,
            'ThoiGian' => now(),
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'message' => 'Bình luận thành công!',
            'data' => $comment->load('nguoiDung')
        ], 201);
    }

    // ✏️ Cập nhật bình luận (chỉ chủ bình luận mới được sửa)
    public function update(Request $request, $id)
    {
        $comment = BinhLuan::find($id);
        $user = Auth::user();

        if (!$comment) {
            return response()->json(['message' => 'Không tìm thấy bình luận!'], 404);
        }

        if ($comment->TenDN !== $user->ten_dang_nhap) {
            return response()->json(['message' => 'Bạn không có quyền sửa bình luận này!'], 403);
        }

        $request->validate([
            'NoiDung' => 'required|string|max:500',
        ]);

        $comment->NoiDung = $request->NoiDung;
        $comment->save();

        return response()->json(['message' => 'Cập nhật bình luận thành công!', 'data' => $comment]);
    }

    // ❌ Xoá bình luận
    public function destroy($id)
    {
        $comment = BinhLuan::find($id);
        $user = Auth::user();

        if (!$comment) {
            return response()->json(['message' => 'Không tìm thấy bình luận!'], 404);
        }

        if ($comment->TenDN !== $user->ten_dang_nhap) {
            return response()->json(['message' => 'Bạn không có quyền xoá bình luận này!'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Đã xoá bình luận thành công!']);
    }
}
