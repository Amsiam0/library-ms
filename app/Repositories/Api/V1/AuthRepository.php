<?php

namespace App\Repositories\Api\V1;

use App\Exceptions\UnauthorizedException;
use App\Models\User;
use App\Traits\Api\V1\PaginationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthRepository
{
    use PaginationTrait;
    public function getFilteredUsers(Request $request): LengthAwarePaginator
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            throw new Exception('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $perPage = $this->sanitizePerPage($request->get('per_page', 10));

        return User::latest()
            ->when($request->search, fn($query) => $query
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role, fn($query) => $query
                ->where('role', $request->role))
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }
}
