<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Constructor: aplica el middleware de autenticación a todos los métodos excepto login y register
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    // Método para registrar un nuevo usuario
    public function register(Request $request)
    {
        try {
            // Validación de los datos de entrada
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
            ]);

            // Creación del usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Asigna rol de admin al primer usuario, de lo contrario asigna rol de user
            if (User::count() === 1) {
                $user->assignRole('admin');
            } else {
                $user->assignRole('user');
            }

            // Genera token JWT para el usuario
            $token = JWTAuth::fromUser($user);

            // Retorna respuesta exitosa con datos del usuario y token
            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 201);
        } catch (ValidationException $e) {
            // Maneja errores de validación
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Maneja otros errores
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while registering the user',
            ], 500);
        }
    }

    // Método para iniciar sesión
    public function login(Request $request)
    {
        try {
            // Validación de los datos de entrada
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $credentials = $request->only('email', 'password');

            // Intenta autenticar al usuario
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Obtiene el usuario autenticado
            $user = JWTAuth::user();

            // Retorna respuesta exitosa con datos del usuario y token
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
        } catch (ValidationException $e) {
            // Maneja errores de validación
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Maneja otros errores
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while logging in',
            ], 500);
        }
    }

    // Método para cerrar sesión
    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    // Método para refrescar el token JWT
    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => auth('api')->user(),
            'authorization' => [
                'token' => auth('api')->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    // Método para obtener información del usuario autenticado
    public function me()
    {
        return response()->json(Auth::user());
    }
}