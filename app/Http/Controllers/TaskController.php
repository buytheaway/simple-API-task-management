<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * @var list<string>
     */
    private array $allowedStatuses = ['new', 'in_progress', 'done'];

    public function index(): JsonResponse
    {
        $tasks = Task::query()
            ->orderByDesc('id')
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in($this->allowedStatuses)],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->messages());
        }

        $data = $validator->validated();
        if (!array_key_exists('status', $data) || $data['status'] === null) {
            $data['status'] = 'new';
        }

        $task = Task::create($data);

        return response()->json($task, 201);
    }

    public function show(int $id): JsonResponse
    {
        $task = Task::find($id);
        if ($task === null) {
            return $this->notFound();
        }

        return response()->json($task);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::find($id);
        if ($task === null) {
            return $this->notFound();
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in($this->allowedStatuses)],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->messages());
        }

        $data = $validator->validated();
        if ($data === []) {
            return response()->json([
                'error' => 'Validation failed',
                'fields' => [
                    'request' => 'No fields to update.',
                ],
            ], 422);
        }

        $task->fill($data);
        $task->save();

        return response()->json($task);
    }

    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);
        if ($task === null) {
            return $this->notFound();
        }

        $task->delete();

        return response()->json(null, 204);
    }

    /**
     * @param array<string, list<string>> $messages
     */
    private function validationError(array $messages): JsonResponse
    {
        $fields = [];
        foreach ($messages as $field => $items) {
            $fields[$field] = $items[0] ?? 'Invalid value.';
        }

        return response()->json([
            'error' => 'Validation failed',
            'fields' => $fields,
        ], 422);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Task not found'], 404);
    }
}
