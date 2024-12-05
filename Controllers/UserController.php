<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Admin dapat menambahkan user baru.
     */
    public function createUser(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:siswa,admin' // Role harus siswa atau admin
        ]);
 
        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Membuat user baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hashing password sebelum disimpan
            'role' => $request->role
        ]);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data' => $user
        ], 201);
    }

    /**
     * Mengubah data user berdasarkan ID
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);

        // Jika user tidak ditemukan
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:siswa,admin'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update user data
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password); // Hash password
        }
        if ($request->has('role') && $user->role !== 'admin') {
            $user->role = $request->role;
        }

        $user->save();

        return response()->json([
            'message' => 'User berhasil diperbarui.',
            'data' => $user
        ]);
    }

    /**
     * Mengambil data user berdasarkan ID
     */
    public function getUserById($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }

        return response()->json([
            'message' => 'Data user ditemukan.',
            'data' => $user
        ]);
    }
        public function deleteUser($id)
        {
            $user = User::find($id);
    
            if ($user) {
                $user->delete();
                return response()->json(['status' => true, 'message' => 'User berhasil dihapus']);
            } else {
                return response()->json(['status' => false, 'message' => 'User tidak ditemukan']);
            }
        }
    
        
    }