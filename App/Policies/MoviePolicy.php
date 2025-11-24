<?php

namespace App\Policies;

use App\Models\User;

class MoviePolicy
{
    /**
     * Chỉ admin mới có thể thêm/sửa/xóa phim
     */
    public function create(User $user)
    {
        return $user->vai_tro === 'admin';
    }

    public function update(User $user)
    {
        return $user->vai_tro === 'admin';
    }

    public function delete(User $user)
    {
        return $user->vai_tro === 'admin';
    }

    /**
     * Ai cũng được xem phim
     */
    public function viewAny(?User $user)
    {
        return true;
    }

    public function view(?User $user)
    {
        return true;
    }
}
