<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ChangePasswordRequest;
use App\Http\Requests\Api\v1\CreateUserRequest;
use App\Http\Requests\Api\v1\LoginRequest;
use App\Http\Resources\UserResource;
use App\Repositories\Api\V1\AuthRepository;
use App\Repositories\UserRepositoryInterface;
use App\Services\Api\V1\AuthService as V1AuthService;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly V1AuthService $authService,
        private readonly AuthRepository $userRepository
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $tokenData = $this->authService->login($request->validated());
        return response()->json([
            'message' => 'Login successful',
            'access_token' => $tokenData['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($tokenData['user'])
        ], Response::HTTP_OK);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return response()->json([
            'message' => 'Logged out successfully'
        ], Response::HTTP_OK);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->createUser($request->validated());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
        return response()->json([
            'message' => 'User created successfully',
            'user' => new UserResource($user)
        ], Response::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $users = $this->userRepository->getFilteredUsers($request);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
        return UserResource::collection($users)->response();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->changePassword($request->validated());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
        return response()->json([
            'message' => 'Password changed successfully'
        ], Response::HTTP_OK);
    }
}
