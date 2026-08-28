<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'unique:students,email'],
            'age'        => ['required', 'integer', 'min:15'],
            'course'     => ['required', 'string', 'max:100'],
            'year_level' => ['required', 'integer', 'min:1', 'max:4'],
            'status'     => ['required', 'in:active,inactive'],
        ];
    }
}