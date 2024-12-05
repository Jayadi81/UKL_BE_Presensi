<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Model User
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Fungsi untuk Register User
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:siswa,admin'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'), 201);
        
    }

    //Get User
    // Fungsi untuk mendapatkan semua user
public function getAllUsers()
{
    $users = User::all();

    return response()->json([
        'status' => true,
        'message' => 'Users retrieved successfully',
        'users' => $users
    ], 200);
}

// Fungsi untuk mendapatkan user berdasarkan ID
public function getUser($id)
{
    // Cari user berdasarkan ID
    $user = User::find($id);

    // Jika user tidak ditemukan
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found'
        ], 404);
    }

    // Jika user ditemukan
    return response()->json([
        'status' => true,
        'message' => 'User retrieved successfully',
        'user' => $user
    ], 200);
}

    //Update User
    public function updateUser(Request $req, $id)
    {
        // Validasi input
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6', // Opsional, hanya jika ingin mengubah password
            'role' => 'required|in:siswa,admin',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 400);
        }
    
        // Cari user berdasarkan ID
        $user = User::find($id);
    
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }
    
        // Update data user
        $user->name = $req->get('name');
        $user->email = $req->get('email');
        $user->role = $req->get('role');
    
        // Hash password jika ada
        if ($req->has('password')) {
            $user->password = Hash::make($req->get('password'));
        }
    
        // Simpan data
        try {
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'User updated successfully',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    //Delete User
    public function deleteUser($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found'
        ], 404);
    }

    // Hapus user
    try {
        $user->delete();
        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to delete user',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // Fungsi untuk Login User
    public function login(Request $request)
    {
        // Validasi input
        $credentials = $request->only('email', 'password');

        // Verifikasi kredensial dan buat token
        if ($token = JWTAuth::attempt($credentials)) {
            $user = JWTAuth::user(); // Ambil data user setelah login berhasil

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil login',
                'token' => $token,
                'user' => $user
            ]);
        } else {
            // Jika kredensial salah
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah'
            ], 401);
        }
    }

    // Fungsi untuk mendapatkan user yang sedang login
    public function getAuthenticatedUser()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }

        return response()->json(compact('user'));
    }
}