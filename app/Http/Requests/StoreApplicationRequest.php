<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programCategory = Program::query()
            ->whereKey($this->integer('program_id'))
            ->value('category');

        $requiresCv = in_array($programCategory, ['professional-internship', 'job-opportunity'], true);

        return [
            'program_id' => ['required', 'exists:programs,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'academic_duration' => ['nullable', 'string', 'max:255'],
            'motivation' => ['required', 'string', 'min:20', 'max:2000'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'internship_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'cv' => [Rule::requiredIf($requiresCv), 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivation.required' => 'Please tell us why you want to join this program.',
            'motivation.min' => 'Your motivation should be at least 20 characters.',
            'cv.required' => 'A CV is required for professional internships and job applications.',
        ];
    }
}
