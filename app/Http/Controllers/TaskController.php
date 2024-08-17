<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    // Constructor: aplica el middleware de autenticación a todas las rutas de este controlador
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // Método para listar todas las tareas
    public function index()
    {
        $user = Auth::user();
        // Si el usuario es admin, obtiene todas las tareas con sus usuarios, sino, solo sus propias tareas
        $tasks = $user->hasRole('admin') ? Task::with('user')->get() : $user->tasks;
        return response()->json([
            'status' => 'success',
            'data' => $tasks
        ]);
    }

    // Método para crear una nueva tarea
    public function store(Request $request)
    {
        try {
            // Valida los datos de entrada
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'completed' => 'boolean',
            ]);

            // Crea la tarea asociada al usuario autenticado
            $task = Auth::user()->tasks()->create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Task created successfully',
                'data' => $task
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
                'message' => 'An error occurred while creating the task',
            ], 500);
        }
    }

    // Método para mostrar una tarea específica
    public function show(Task $task)
    {
        try {
            // Verifica si el usuario está autorizado para ver esta tarea
            $this->authorize('view', $task);
            return response()->json([
                'status' => 'success',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            // Maneja errores de autorización
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view this task',
            ], 403);
        }
    }

    // Método para actualizar una tarea
    public function update(Request $request, Task $task)
    {
        try {
            // Verifica si el usuario está autorizado para actualizar esta tarea
            $this->authorize('update', $task);

            // Valida los datos de entrada
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'completed' => 'boolean',
            ]);

            // Actualiza la tarea
            $task->update($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => $task
            ]);
        } catch (ValidationException $e) {
            // Maneja errores de validación
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Maneja otros errores (incluyendo errores de autorización)
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this task',
            ], 403);
        }
    }

    // Método para eliminar una tarea
    public function destroy(Task $task)
    {
        try {
            // Verifica si el usuario está autorizado para eliminar esta tarea
            $this->authorize('delete', $task);
            // Elimina la tarea
            $task->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            // Maneja errores (incluyendo errores de autorización)
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this task',
            ], 403);
        }
    }
}