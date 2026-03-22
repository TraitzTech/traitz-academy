<?php

namespace App\Http\Requests\Admin;

use App\Models\Withdrawal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawFundsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isExecutive() && $this->user()->hasWithdrawalPin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:100'],
            'service' => ['required', 'string', Rule::in(Withdrawal::availableServices())],
            'receiver' => ['required', 'string', 'regex:/^6\d{8}$/'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the withdrawal amount.',
            'amount.min' => 'Minimum withdrawal amount is 100 XAF.',
            'service.required' => 'Please select a mobile money provider.',
            'service.in' => 'Invalid mobile money provider selected.',
            'receiver.required' => 'Please enter the receiver phone number.',
            'receiver.regex' => 'Phone number must be a valid Cameroon number (e.g. 6XXXXXXXX).',
            'receiver_name.required' => 'Please confirm the receiver\'s MoMo name.',
            'pin.required' => 'Please enter your 4-digit PIN.',
            'pin.size' => 'PIN must be exactly 4 digits.',
            'pin.regex' => 'PIN must contain only numbers.',
        ];
    }
}
