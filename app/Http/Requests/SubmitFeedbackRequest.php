<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFeedbackRequest extends FormRequest
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
            'is_anonymous' => ['boolean'],
            'respondent_name' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => ! $this->boolean('is_anonymous') && ! auth()->check()),
                'nullable',
                'string',
                'max:255',
            ],
            'respondent_email' => ['nullable', 'email', 'max:255'],
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
