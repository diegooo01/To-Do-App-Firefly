<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'Diego',
            'email' => 'diego@test.com',
            'password' => 'password123',
        ]);

        $demo->tasks()->createMany([
            [
                'title' => 'Configurar autenticación JWT',
                'description' => 'Instalar tymon/jwt-auth y definir el guard de la API.',
                'status' => TaskStatus::Done,
                'priority' => TaskPriority::High,
                'due_date' => now()->subDays(3),
            ],
            [
                'title' => 'Implementar el CRUD de tareas',
                'description' => 'Endpoints de listado, creación, edición y eliminación.',
                'status' => TaskStatus::Done,
                'priority' => TaskPriority::High,
                'due_date' => now()->subDay(),
            ],
            [
                'title' => 'Construir el dashboard de métricas',
                'description' => 'Agregar los conteos por estado en una sola consulta.',
                'status' => TaskStatus::InProgress,
                'priority' => TaskPriority::Medium,
                'due_date' => now()->addDays(2),
            ],
            [
                'title' => 'Maquetar el listado con Tailwind',
                'description' => null,
                'status' => TaskStatus::InProgress,
                'priority' => TaskPriority::Medium,
                'due_date' => now()->addDays(4),
            ],
            [
                'title' => 'Escribir el README del proyecto',
                'description' => 'Incluir requisitos, instalación y decisiones técnicas.',
                'status' => TaskStatus::Pending,
                'priority' => TaskPriority::Medium,
                'due_date' => now()->addWeek(),
            ],
            [
                'title' => 'Grabar el video de demostración',
                'description' => 'Menos de cinco minutos explicando el sistema.',
                'status' => TaskStatus::Pending,
                'priority' => TaskPriority::High,
                'due_date' => now()->addWeek(),
            ],
            [
                'title' => 'Revisar los mensajes de validación',
                'description' => null,
                'status' => TaskStatus::Pending,
                'priority' => TaskPriority::Low,
                'due_date' => null,
            ],
            [
                'title' => 'Investigar refresh tokens',
                'description' => 'Evaluar si conviene renovar el token antes de que expire.',
                'status' => TaskStatus::Pending,
                'priority' => TaskPriority::Low,
                'due_date' => null,
            ],
        ]);

        $otro = User::factory()->create([
            'name' => 'Usuario Secundario',
            'email' => 'otro@test.com',
            'password' => 'password123',
        ]);

        Task::factory()
            ->count(5)
            ->for($otro)
            ->create();
    }
}
