<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ChangePasswordRequest;
use App\Http\Requests\Api\v1\LoginRequest;
use App\Http\Requests\Api\v1\CreateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\UserCreated;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validationData();

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user)
        ]);
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()?->revoke();
        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function store(CreateUserRequest $request)
    {
        $validatedData = $request->validated();

        //random password generation

        $validatedData['password'] = Str::random(10);
        $hashedPassword = Hash::make($validatedData['password']);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => $hashedPassword,
            'role' => $validatedData['role'],
        ]);

        //send email with password
        Mail::to($user->email)->send(new UserCreated($user, $validatedData['password']));

        return response()->json([
            'message' => 'User created successfully',
            'user' => new UserResource($user),
        ], 201);
    }

    public function index(Request $request)
    {
        //admin only

        if (!(Auth::check() && Auth::user()->role === 'admin')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $perPage = $request->get('per_page', 10);
        $perPage = is_numeric($perPage) ? (int)$perPage : 10;
        $perPage = $perPage > 100 ? 100 : $perPage;
        $perPage = $perPage < 1 ? 1 : $perPage;


        $users = User::latest()
            ->when($request->search, function ($query) use ($request) {
                return $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            })
            ->when($request->role, function ($query) use ($request) {
                return $query->where('role', $request->role);
            })

            ->paginate(15);

        return UserResource::collection($users);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'Invalid old password',
                'errors' => [
                    'old_password' => ['The provided password does not match your current password.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }
}
