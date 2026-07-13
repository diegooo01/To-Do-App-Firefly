<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->route('task')->status === TaskStatus::Done) {
                $validator->errors()->add(
                    'status',
                    'Una tarea completada no puede ser editada.'
                );
            }
        });
    }
}
