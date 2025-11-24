<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    // =================== LẤY DANH SÁCH ===================
    public function users()
    {
        $users = User::select('id', 'ten_dang_nhap', 'email', 'ho_ten', 'hinh_dai_dien', 'vai_tro', 'trang_thai')
            ->get()
            ->map(function($user) {
                $user->hinh_dai_dien_url = $user->hinh_dai_dien
                    ? "https://imagedelivery.net/" . env('CLOUDFLARE_ACCOUNT_HASH') . "/{$user->hinh_dai_dien}/public"
                    : null;
                return $user;
            });

        return response()->json($users);
    }

    // =================== LẤY USER HIỆN TẠI ===================
    public function me()
    {
        $user = auth()->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'ten_dang_nhap' => $user->ten_dang_nhap,
                'email' => $user->email,
                'ho_ten' => $user->ho_ten,
                'vai_tro' => $user->vai_tro,
                'trang_thai' => $user->trang_thai,
                'hinh_dai_dien' => $user->hinh_dai_dien,
                'hinh_dai_dien_url' => $user->hinh_dai_dien
                    ? "https://imagedelivery.net/" . env('CLOUDFLARE_ACCOUNT_HASH') . "/{$user->hinh_dai_dien}/public"
                    : null,
                'ngay_tao' => $user->created_at,
                'ngay_cap_nhat' => $user->updated_at,
            ]
        ]);
    }

    // =================== ĐĂNG KÝ ===================
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ten_dang_nhap' => 'required|string|max:50|unique:tai_khoan,ten_dang_nhap',
            'email' => 'required|email|unique:tai_khoan,email',
            'mat_khau' => 'required|string|min:6',
            'ho_ten' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'ten_dang_nhap' => $request->ten_dang_nhap,
            'email' => $request->email,
            'mat_khau' => Hash::make($request->mat_khau),
            'ho_ten' => $request->ho_ten,
            'vai_tro' => 'user',
            'trang_thai' => 'active',
        ]);

        return response()->json([
            'message' => 'Đăng ký thành công!',
            'user' => $user
        ], 201);
    }

    // =================== ĐĂNG NHẬP ===================
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ten_dang_nhap' => 'required|string',
            'mat_khau' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('ten_dang_nhap', $request->ten_dang_nhap)->first();

        if (!$user || !Hash::check($request->mat_khau, $user->mat_khau)) {
            return response()->json(['message' => 'Sai thông tin!'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'user' => [
                'id' => $user->id,
                'ten_dang_nhap' => $user->ten_dang_nhap,
                'email' => $user->email,
                'ho_ten' => $user->ho_ten,
                'vai_tro' => $user->vai_tro,
                'trang_thai' => $user->trang_thai,
                'hinh_dai_dien' => $user->hinh_dai_dien,
                'hinh_dai_dien_url' => $user->hinh_dai_dien
                    ? "https://imagedelivery.net/" . env('CLOUDFLARE_ACCOUNT_HASH') . "/{$user->hinh_dai_dien}/public"
                    : null,
                'ngay_tao' => $user->created_at,
                'ngay_cap_nhat' => $user->updated_at,
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    // =================== UPDATE THÔNG TIN ===================
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Không tìm thấy tài khoản!'], 404);

        $validator = Validator::make($request->all(), [
            'ho_ten' => 'nullable|string|max:100',
            'email'  => 'nullable|email|unique:tai_khoan,email,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->ho_ten = $request->ho_ten ?? $user->ho_ten;
        $user->email  = $request->email ?? $user->email;
        $user->save();

        return response()->json([
            'message' => 'Cập nhật tài khoản thành công!',
            'user' => [
                'id' => $user->id,
                'ten_dang_nhap' => $user->ten_dang_nhap,
                'email' => $user->email,
                'ho_ten' => $user->ho_ten,
                'vai_tro' => $user->vai_tro,
                'trang_thai' => $user->trang_thai,
                'hinh_dai_dien' => $user->hinh_dai_dien,
                'hinh_dai_dien_url' => $user->hinh_dai_dien
                    ? "https://imagedelivery.net/" . env('CLOUDFLARE_ACCOUNT_HASH') . "/{$user->hinh_dai_dien}/public"
                    : null,
                'ngay_tao' => $user->created_at,
                'ngay_cap_nhat' => $user->updated_at,
            ]
        ]);
    }

    // =================== UPLOAD AVATAR ===================
    public function uploadAvatar($id, Request $request)
    {
        $request->validate([
            'hinh_dai_dien_file' => 'required|image|max:2048'
        ]);

        $user = User::findOrFail($id);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('CLOUDFLARE_API_TOKEN')
        ])->attach(
            'file',
            file_get_contents($request->file('hinh_dai_dien_file')),
            $request->file('hinh_dai_dien_file')->getClientOriginalName()
        )->post("https://api.cloudflare.com/client/v4/accounts/".env('CLOUDFLARE_ACCOUNT_ID')."/images/v1");

        $result = $response->json();

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        // Lưu image ID vào database
        $user->hinh_dai_dien = $result['result']['id'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated',
            'user' => [
                'id' => $user->id,
                'ten_dang_nhap' => $user->ten_dang_nhap,
                'email' => $user->email,
                'ho_ten' => $user->ho_ten,
                'vai_tro' => $user->vai_tro,
                'trang_thai' => $user->trang_thai,
                'hinh_dai_dien' => $user->hinh_dai_dien,
                'hinh_dai_dien_url' => $user->hinh_dai_dien
                    ? "https://imagedelivery.net/" . env('CLOUDFLARE_ACCOUNT_HASH') . "/{$user->hinh_dai_dien}/public"
                    : null,
                'ngay_tao' => $user->created_at,
                'ngay_cap_nhat' => $user->updated_at,
            ]
        ]);
    }

    // =================== XOÁ ===================
    public function delete($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Không tìm thấy tài khoản!'], 404);

        $user->delete();

        return response()->json(['message' => 'Xoá tài khoản thành công!']);
    }

    // =================== ĐỔI PASSWORD ===================
    public function changePassword(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Không tìm thấy tài khoản!'], 404);

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->old_password, $user->mat_khau)) {
            return response()->json(['message' => 'Mật khẩu cũ không đúng!'], 400);
        }

        $user->mat_khau = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Đổi mật khẩu thành công!']);
    }
}
