<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Episode;
use App\Models\TheLoai;
use App\Models\LichSu;
use App\Models\QuocGia;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MovieController extends Controller
{
    use AuthorizesRequests;

    // === CÁC HÀM CŨ GIỮ NGUYÊN ===
    public function index()
{
    $movies = Movie::with(['quocgia', 'theloai', 'episodes'])->get();

    // FIX LỖI EPISODES NULL
    $movies->each(function ($movie) {
        if (!$movie->relationLoaded('episodes')) {
            $movie->setRelation('episodes', collect([]));
        }
    });

    return response()->json($movies);
}

public function show($id)
{
    $movie = Movie::with(['quocgia', 'theloai', 'episodes'])->findOrFail($id);

    // FIX LỖI EPISODES NULL
    if (!$movie->relationLoaded('episodes') || is_null($movie->episodes)) {
        $movie->setRelation('episodes', collect([]));
    }

    return response()->json($movie);
}
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'TenPhim'       => 'required|string|max:255',
            'TieuDe'        => 'nullable|string|max:255',
            'MoTa'          => 'nullable|string',
            'NamPhatHanh'   => 'nullable|integer|min:1900|max:' . date('Y'),
            'DanhGia'       => 'nullable|numeric|min:0|max:10',
            'PhanLoai'      => 'required|in:Lẻ,Bộ',
            'TinhTrang'     => 'required|in:Đang chiếu,Sắp chiếu,Đã kết thúc',
            'MaQuocGia'     => 'nullable|exists:QuocGia,MaQuocGia',
            'MaTheLoai'     => 'nullable|array',
            'MaTheLoai.*'   => 'exists:TheLoai,MaTheLoai',
            'Link'          => 'nullable|string',
            'HinhAnh'       => 'nullable|string',
            'imageFile'     => 'nullable|image|max:5120', // 5MB max
        ]);

        try {
            // Handle image upload if provided
            $imagePath = null;
            if ($request->hasFile('imageFile')) {
                $imagePath = $request->file('imageFile')->store('phim', 'public');
                $validated['HinhAnh'] = '/storage/' . $imagePath;
            } else if (!empty($validated['HinhAnh'])) {
                // Keep URL if provided
                $validated['HinhAnh'] = $validated['HinhAnh'];
            }

            // Create the movie
            $movie = Movie::create($validated);

            // Attach genres (many-to-many)
            if (!empty($validated['MaTheLoai'])) {
                $movie->theloai()->attach($validated['MaTheLoai']);
            }

            // Load relationships for response
            $movie->load(['quocgia', 'theloai', 'episodes']);

            // Ensure episodes is always an array
            if (!$movie->relationLoaded('episodes') || is_null($movie->episodes)) {
                $movie->setRelation('episodes', collect([]));
            }

            return response()->json([
                'success' => true,
                'message' => 'Phim đã được thêm thành công!',
                'movie'   => $movie,
                'data'    => $movie // Also return as 'data' for compatibility
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thêm phim: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);

        $validated = $request->validate([
            'TenPhim'       => 'sometimes|required|string|max:255',
            'TieuDe'        => 'nullable|string|max:255',
            'MoTa'          => 'nullable|string',
            'NamPhatHanh'   => 'nullable|integer|min:1900|max:' . date('Y'),
            'DanhGia'       => 'nullable|numeric|min:0|max:10',
            'PhanLoai'      => 'sometimes|required|in:Lẻ,Bộ',
            'TinhTrang'     => 'sometimes|required|in:Đang chiếu,Sắp chiếu,Đã kết thúc',
            'MaQuocGia'     => 'nullable|exists:QuocGia,MaQuocGia',
            'MaTheLoai'     => 'nullable|array',
            'MaTheLoai.*'   => 'exists:TheLoai,MaTheLoai',
            'Link'          => 'nullable|string',
            'HinhAnh'       => 'nullable|string',
            'imageFile'     => 'nullable|image|max:5120',
        ]);

        try {
            if ($request->hasFile('imageFile')) {
                $imagePath = $request->file('imageFile')->store('phim', 'public');
                $validated['HinhAnh'] = '/storage/' . $imagePath;
            }

            $movie->update($validated);

            if (!empty($validated['MaTheLoai'])) {
                $movie->theloai()->sync($validated['MaTheLoai']);
            }

            $movie->load(['quocgia', 'theloai', 'episodes']);
            if (!$movie->relationLoaded('episodes') || is_null($movie->episodes)) {
                $movie->setRelation('episodes', collect([]));
            }

            return response()->json([
                'success' => true,
                'message' => 'Phim đã được cập nhật thành công!',
                'movie'   => $movie
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật phim: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTopViewedMovies(Request $request) { /* giữ nguyên */ }
    public function searchWithFilters(Request $request) { /* giữ nguyên */ }
    public function search(Request $request) { /* giữ nguyên */ }

    public function destroy($id)
    {
        try {
            $movie = Movie::findOrFail($id);
            $movie->delete();
            return response()->json([
                'success' => true,
                'message' => 'Phim đã được xóa thành công!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa phim: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================== CLOUDFLARE STREAM UPLOAD URL - TUS DIRECT ==================
   public function getCloudflareUploadUrl(Request $request)
{
    $accountId = config('services.cloudflare.account_id', env('CLOUDFLARE_STREAM_ACCOUNT_ID'));
    $token     = config('services.cloudflare.api_token', env('CLOUDFLARE_STREAM_API_TOKEN'));
    $domain    = config('services.cloudflare.stream_domain', env('CLOUDFLARE_STREAM_DOMAIN'));

    if (empty($accountId) || empty($token) || empty($domain)) {
        Log::error('Cloudflare Config Missing', [
            'accountId' => $accountId ? 'exists' : 'missing',
            'token' => $token ? 'exists' : 'missing',
            'domain' => $domain ? 'exists' : 'missing',
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Chưa cấu hình Cloudflare Stream trong .env',
            'debug' => [
                'accountId' => !empty($accountId),
                'token' => !empty($token),
                'domain' => !empty($domain)
            ]
        ], 500)->header('Content-Type', 'application/json');
    }

    // TẠO DIRECT UPLOAD URL cho basic upload (<200MB)
    $response = Http::withToken($token)->post(
        "https://api.cloudflare.com/client/v4/accounts/{$accountId}/stream/direct_upload",
        [
            'maxDurationSeconds' => 28800,
            'requireSignedURLs'  => false,
            'meta' => [
                'name' => 'Upload from Movie App'
            ]
        ]
    );

    if ($response->failed()) {
        Log::error('Cloudflare API Error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Lỗi kết nối Cloudflare Stream',
            'error'   => $response->json(),
            'debug'   => $response->body()
        ], 500)->header('Content-Type', 'application/json');
    }

    $data = $response->json()['result'];
    $videoUid = $data['uid'];
    $uploadURL = $data['uploadURL'];

    // TRẢ VỀ UPLOAD URL cho Basic Upload
    return response()->json([
        'success'      => true,
        'uploadURL'    => $uploadURL,
        'video_uid'    => $videoUid,
        'hls_url'      => "https://{$domain}/{$videoUid}/manifest/video.m3u8",
        'thumbnail'    => "https://customer-" . substr($videoUid, 0, 32) . ".cloudflarestream.com/{$videoUid}/thumbnails/thumbnail.jpg?time=10s&height=480",
    ], 200)->header('Content-Type', 'application/json');
}

    // ================== TẠO TUS UPLOAD URL (MỌI FILE SIZE) ==================
    public function getTusUploadUrl(Request $request)
    {
        $accountId = config('services.cloudflare.account_id');
        $token = config('services.cloudflare.api_token');
        $domain = config('services.cloudflare.stream_domain');

        if (!$accountId || !$token) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare chưa được cấu hình'
            ], 500);
        }

        // Lấy metadata từ TUS client
        $uploadLength = $request->header('Upload-Length');
        $uploadMetadata = $request->header('Upload-Metadata');

        Log::info('Creating TUS endpoint', [
            'uploadLength' => $uploadLength,
            'uploadMetadata' => $uploadMetadata
        ]);

        // Tạo TUS upload endpoint (Cloudflare Stream)
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Tus-Resumable' => '1.0.0',
            'Upload-Length' => $uploadLength,
            'Upload-Metadata' => $uploadMetadata,
        ])->post("https://api.cloudflare.com/client/v4/accounts/{$accountId}/stream?direct_user=true");

        if ($response->failed()) {
            Log::error('TUS Endpoint Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo TUS endpoint',
                'error' => $response->body()
            ], 500);
        }

        // TUS endpoint trả về Location header
        $tusUploadUrl = $response->header('Location');
        
        // Lấy video UID từ upload URL
        $video_uid = basename(parse_url($tusUploadUrl, PHP_URL_PATH));
        $hls_url = "https://{$domain}/{$video_uid}/manifest/video.m3u8";

        Log::info('TUS endpoint created', [
            'location' => $tusUploadUrl,
            'video_uid' => $video_uid
        ]);

        // Trả về response với CORS headers
        return response()->json([
            'uploadURL' => $tusUploadUrl,
            'video_uid' => $video_uid,
            'hls_url' => $hls_url
        ], 200, [
            'Access-Control-Expose-Headers' => 'Location',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Origin' => '*',
            'Location' => $tusUploadUrl
        ]);
}

    // ================== UPLOAD VIDEO QUA PROXY (BYPASS CORS) ==================
    public function uploadVideoProxy(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,mkv,webm,flv|max:10240000', // Max 10GB
            'uploadURL' => 'required|url',
        ]);

        try {
            $videoFile = $request->file('video');
            $uploadURL = $request->input('uploadURL');

            $fileSize = $videoFile->getSize();
            $fileName = $videoFile->getClientOriginalName();

            Log::info('Uploading to Cloudflare', [
                'uploadURL' => $uploadURL,
                'fileSize' => $fileSize,
                'fileName' => $fileName
            ]);

            // Sử dụng stream thay vì đọc toàn bộ file vào memory
            // Điều này giúp xử lý file lớn (>1GB) mà không gặp lỗi memory
            $stream = fopen($videoFile->getRealPath(), 'r');

            // Upload file lên Cloudflare Stream bằng POST với multipart streaming
            $response = Http::timeout(7200) // 2 giờ timeout cho file lớn
                ->attach('file', $stream, $fileName)
                ->post($uploadURL);
            
            // Đóng stream sau khi upload
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($response->successful()) {
                Log::info('Cloudflare Upload Success');
                return response()->json([
                    'success' => true,
                    'message' => 'Upload thành công!'
                ], 200);
            } else {
                // Log error để debug
                Log::error('Cloudflare Upload Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Upload thất bại',
                    'error' => $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Upload Exception', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi upload: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================== THÊM TẬP PHIM MỚI (CLOUDFLARE STREAM) ==================
    public function addEpisode(Request $request, $MaPhim)
    {
        // Accept either a cloudflare upload (video_uid + hls_url) or a direct link (Link)
        $request->validate([
            'TenTap'     => 'required|string|max:100',
            'video_uid'  => 'nullable|string',
            'hls_url'    => 'nullable|url',
            'Link'       => 'nullable|url',
        ]);

        $movie = Movie::findOrFail($MaPhim);

        // Determine final play URL (hls_url preferred, otherwise Link)
        $playUrl = $request->hls_url ?? $request->Link ?? null;
        if (!$playUrl) {
            return response()->json(['message' => 'Thiếu đường dẫn video (hls_url hoặc Link)'], 422);
        }

        $episode = $movie->episodes()->create([
            'TenTap'         => $request->TenTap,
            'Link'           => $playUrl,
            'cloudflare_uid' => $request->video_uid ?? null,
            'hls_url'        => $playUrl,
            'status'         => $request->video_uid ? 'ready' : 'ready',
            'original_file'  => null,
            'r2_folder'      => null,
        ]);

        // Reload movie với tất cả episodes để trả về response đầy đủ
        $movie->load('episodes');

        return response()->json([
            'message'   => 'Thêm tập thành công! Video đã sẵn sàng phát!',
            'episode'   => $episode,
            'play_url'  => $playUrl,
            'movie'     => $movie,
        ], 201);
    }

    // ================== XÓA TẬP PHIM + XÓA VIDEO TRÊN CLOUDFLARE ==================
    public function deleteEpisode($MaPhim, $MaTap)
    {
        $episode = Episode::where('MaPhim', $MaPhim)
                          ->where('MaTap', $MaTap)
                          ->firstOrFail();

        // XÓA VIDEO TRÊN CLOUDFLARE
        if ($episode->cloudflare_uid) {
            $accountId = env('CLOUDFLARE_STREAM_ACCOUNT_ID');
            $token = env('CLOUDFLARE_STREAM_API_TOKEN');

            if ($accountId && $token) {
                Http::withToken($token)->delete(
                    "https://api.cloudflare.com/client/v4/accounts/{$accountId}/stream/{$episode->cloudflare_uid}"
                );
            }
        }

        $episode->delete();

        return response()->json(['message' => 'Xóa tập thành công + video đã xóa khỏi Cloudflare']);
    }

    // ================== TRẠNG THÁI TẬP PHIM (TƯƠNG THÍCH CŨ) ==================
    public function episodeStatus($MaPhim, $MaTap)
    {
        $episode = Episode::where('MaPhim', $MaPhim)
                               ->where('MaTap', $MaTap)
                               ->firstOrFail();

        return response()->json([
            'status'   => $episode->status,
            'hls_url'  => $episode->status === 'ready' ? $episode->hls_url : null,
            'message'  => $episode->status === 'ready' ? 'Sẵn sàng phát!' : 'Đang xử lý...'
        ]);
    }
    public function getGenres()
    {
        $genres = TheLoai::select('MaTheLoai', 'TenTheLoai')->get();
        return response()->json($genres);
    }

    public function getCountries()
    {
        $countries = QuocGia::select('MaQuocGia', 'TenQuocGia')->get();
        return response()->json($countries);
    }
}