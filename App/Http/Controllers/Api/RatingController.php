<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    // Hàm hỗ trợ trả về JSON chuẩn (tương tự LichSuController)
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
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    // GET /api/ratings/{maPhim} - Lấy danh sách đánh giá cho phim
    public function index($maPhim)
    {
     $ratings = Rating::where('MaPhim', $maPhim)
            ->with(['user' => function ($query) {
                $query->select('ten_dang_nhap', 'ho_ten', 'hinh_dai_dien');
            }])
            ->orderBy('ThoiGian', 'desc')
            ->get();

      
        return $this->success('Lấy danh sách đánh giá thành công!', $ratings);
    }

    // POST /api/ratings - Thêm đánh giá (auth required)
    public function store(Request $request)
{
    $user = Auth::user();
    if (!$user) {
        return $this->error('Unauthorized. Vui lòng đăng nhập!', 401);
    }

    $validator = Validator::make($request->all(), [
        'MaPhim'    => 'required|integer|exists:phim,MaPhim',
        'so_diem'   => 'required|integer|min:1|max:10',
        'binh_luan' => 'nullable|string|max:1000',
    ]);

    if ($validator->fails()) {
        return $this->error('Dữ liệu không hợp lệ: ' . implode(', ', $validator->errors()->all()), 422);
    }

    $rating = Rating::updateOrCreate(
        ['TenDN' => $user->ten_dang_nhap, 'MaPhim' => $request->MaPhim],
        [
            'SoDiem'    => $request->so_diem,
            'BinhLuan'  => $request->binh_luan ?? '',
            'ThoiGian'  => now()
        ]
    );

    $this->updateMovieAverageRating($request->MaPhim);

    return $this->success('Đánh giá thành công!', $rating->load('user'), 201);
}

    // PUT /api/ratings/{maPhim} - Cập nhật đánh giá (auth required)
    public function update(Request $request, $maPhim)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthorized. Vui lòng đăng nhập!', 401);
        }

        $rating = Rating::where('TenDN', $user->ten_dang_nhap)->where('MaPhim', $maPhim)->first();
        if (!$rating) {
            return $this->error('Bạn chưa đánh giá phim này', 404);
        }

        $validator = Validator::make($request->all(), [
            'so_diem' => 'required|integer|min:1|max:10',
            'binh_luan' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('Dữ liệu không hợp lệ', 422);
        }

        $rating->update([
            'SoDiem' => $request->so_diem,
            'BinhLuan' => $request->binh_luan,
            'ThoiGian' => now()
        ]);

        // Cập nhật điểm trung bình cho phim
        $this->updateMovieAverageRating($maPhim);

        return $this->success('Cập nhật đánh giá thành công!', $rating);
    }

    // DELETE /api/ratings/{maPhim} - Xóa đánh giá (auth required)
    public function destroy($maPhim)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthorized. Vui lòng đăng nhập!', 401);
        }

        $deleted = Rating::where('TenDN', $user->ten_dang_nhap)->where('MaPhim', $maPhim)->delete();

        if ($deleted === 0) {
            return $this->error('Không tìm thấy đánh giá của bạn', 404);
        }

        // Cập nhật điểm trung bình cho phim
        $this->updateMovieAverageRating($maPhim);

        return $this->success('Xóa đánh giá thành công!');
    }

    // Hàm hỗ trợ cập nhật điểm trung bình cho phim
    private function updateMovieAverageRating($maPhim)
    {
        $average = Rating::where('MaPhim', $maPhim)->avg('SoDiem');
        Movie::where('MaPhim', $maPhim)->update(['DanhGia' => round($average, 1)]);
    }
}