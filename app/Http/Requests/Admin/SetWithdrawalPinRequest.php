<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SetWithdrawalPinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isExecutive();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'pin' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
            'pin_confirmation' => ['required', 'string', 'same:pin'],
        ];

        if ($this->user()->hasWithdrawalPin()) {
            $rules['current_pin'] = ['required', 'string', 'size:4'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pin.required' => 'Please enter a 4-digit PIN.',
            'pin.size' => 'PIN must be exactly 4 digits.',
            'pin.regex' => 'PIN must contain only numbers.',
            'pin_confirmation.same' => 'PIN confirmation does not match.',
            'current_pin.required' => 'Please enter your current PIN.',
        ];
    }
}
