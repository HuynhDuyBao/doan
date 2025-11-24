<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LichSu;
use App\Models\Movie; // THÊM DÒNG NÀY
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LichSuController extends Controller
{
    /**
     * GET /api/history
     * Lấy danh sách lịch sử xem phim của user hiện tại
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error('Unauthorized. Vui lòng đăng nhập!', 401);
        }

        $history = LichSu::where('TenDN', $user->ten_dang_nhap)
            ->with(['phim' => function ($query) {
                $query->select('MaPhim', 'TenPhim', 'HinhAnh', 'NamPhatHanh');
            }])
            ->orderBy('ThoiGian', 'desc')
            ->get();

        return $this->success('Lấy lịch sử thành công!', $history);
    }

    /**
     * POST /api/history
     * Lưu hoặc cập nhật lịch sử xem phim
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error('Unauthorized. Vui lòng đăng nhập!', 401);
        }

        // SỬA: exists:Phim,MaPhim → exists:movies,MaPhim (nếu bảng là movies)
        // HOẶC giữ nguyên nếu bảng là Phim
        $request->validate([
            'ma_phim' => 'required|integer|exists:Phim,MaPhim'
        ]);

        $lichSu = LichSu::updateOrCreate(
            [
                'TenDN' => $user->ten_dang_nhap,
                'MaPhim' => $request->ma_phim
            ],
            ['ThoiGian' => now()]
        );

        $lichSu->load(['phim' => function ($query) {
            $query->select('MaPhim', 'TenPhim', 'HinhAnh', 'NamPhatHanh');
        }]);

        return $this->success('Lưu lịch sử thành công!', $lichSu, 201);
    }

    /**
     * DELETE /api/history
     * Xóa toàn bộ lịch sử
     */
    public function destroyAll()
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error('Unauthorized. Vui lòng đăng nhập!', 401);
        }

        $deleted = LichSu::where('TenDN', $user->ten_dang_nhap)->delete();

        return $this->success("Xóa toàn bộ lịch sử thành công! Đã xóa $deleted bản ghi.");
    }

    /**
     * DELETE /api/history/{maPhim}
     * Xóa 1 phim khỏi lịch sử
     */
    public function destroy($maPhim)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error('Unauthorized. Vui lòng đăng nhập!', 401);
        }

        $deleted = LichSu::where('TenDN', $user->ten_dang_nhap)
            ->where('MaPhim', $maPhim)
            ->delete();

        if ($deleted === 0) {
            return $this->error('Không tìm thấy phim trong lịch sử!', 404);
        }

        return $this->success('Xóa thành công!');
    }

    // === HÀM HỖ TRỢ TRẢ VỀ JSON CHUẨN ===
    private function success($message, $data = null, $status = 200)
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    private function error($message, $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}