<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_code' => ['required', 'string', 'max:50', 'unique:courses,code'],
            'course_name' => ['required', 'string', 'max:255'],
            'course_description' => ['nullable', 'string', 'max:1000'],
            'topic_title' => ['required', 'string', 'max:255'],
            'topic_description' => ['nullable', 'string', 'max:1000'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'starts_at' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before:20:00'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at', 'before_or_equal:20:00'],
            'notes' => ['nullable', 'string', 'max:500'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }
}

