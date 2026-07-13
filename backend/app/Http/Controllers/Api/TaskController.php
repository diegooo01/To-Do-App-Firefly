<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = $request->user()
            ->tasks()
            ->status($request->query('status'))
            ->priority($request->query('priority'))
            ->search($request->query('search'))
            ->latest()
            ->get();

        return response()->json($tasks, 200);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->validated());

        return response()->json($task, 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json($task, 200);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task->update($request->validated());

        return response()->json($task, 200);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $task->update(['status' => $request->validated('status')]);

        return response()->json($task, 200);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(null, 204);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $counts = $request->user()
            ->tasks()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'total' => $counts->sum(),
            'pending' => $counts->get(TaskStatus::Pending->value, 0),
            'in_progress' => $counts->get(TaskStatus::InProgress->value, 0),
            'done' => $counts->get(TaskStatus::Done->value, 0),
        ], 200);
    }
}
