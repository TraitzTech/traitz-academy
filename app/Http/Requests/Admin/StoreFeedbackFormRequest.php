<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackFormRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['boolean'],
            'allow_anonymous' => ['boolean'],
            'send_thank_you_email' => ['boolean'],
            'closes_at' => ['nullable', 'date', 'after:now'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string', 'max:1000'],
            'questions.*.type' => ['required', 'in:text,multiple_choice'],
            'questions.*.options' => ['nullable', 'array', 'min:2'],
            'questions.*.options.*' => ['string', 'max:500'],
            'questions.*.required' => ['boolean'],
        ];
    }
}
