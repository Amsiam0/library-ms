<?php

namespace App\Services\Api\V1;

use App\Mail\UserCreated;
use App\Models\User;
use App\Repositories\Api\V1\AuthRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthService
{
    public function __construct(
        private readonly AuthRepository $authRepository
    ) {}

    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw new Exception('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
    }

    public function createUser(array $data): User
    {
        $password = Str::random(10);
        $data['password'] = Hash::make($password);

        $user = $this->authRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        try {
            Mail::to($user->email)->send(new UserCreated($user, $password));
        } catch (Exception $e) {
            throw new Exception('Failed to send user creation email', 500,);
        }

        return $user;
    }

    public function changePassword(array $data): void
    {
        $user = Auth::user();

        if (!Hash::check($data['old_password'], $user->password)) {
            throw new Exception('The provided password does not match your current password', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->authRepository->update($user, [
            'password' => Hash::make($data['new_password'])
        ]);
    }
}
