<?php

namespace App\Http\Requests\Community;

use App\Models\TacActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTacActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessTacAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(TacActivity::TYPE_LABELS))],
            'tac_track_id' => ['nullable', 'integer', 'exists:tac_tracks,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:4096'],

            'location_type' => ['required', Rule::in(['physical', 'virtual', 'hybrid'])],
            'location' => ['nullable', 'string', 'max:255', 'required_unless:location_type,virtual'],
            'meeting_url' => ['nullable', 'url', 'max:500', 'required_if:location_type,virtual'],

            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'timezone' => ['nullable', 'string', 'max:64'],

            'is_recurring' => ['boolean'],
            'recurrence' => ['nullable', 'array'],
            'recurrence.frequency' => ['nullable', Rule::in(['weekly', 'biweekly', 'monthly'])],
            'recurrence.count' => ['nullable', 'integer', 'min:1', 'max:52'],

            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_required' => ['boolean'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],

            'is_paid' => ['boolean'],
            'price' => ['nullable', 'integer', 'min:0', 'required_if:is_paid,true'],
            'currency' => ['nullable', 'string', 'max:8'],

            'organizer_leader_id' => ['nullable', 'integer', 'exists:tac_leaders,id'],
            'status' => ['required', Rule::in([
                TacActivity::STATUS_DRAFT,
                TacActivity::STATUS_PUBLISHED,
                TacActivity::STATUS_CANCELLED,
                TacActivity::STATUS_COMPLETED,
            ])],
            'is_featured' => ['boolean'],

            'outcome_summary' => ['nullable', 'string', 'max:5000'],

            // Competition rubric, only meaningful when type=competition.
            'criteria' => ['array'],
            'criteria.*.id' => ['nullable', 'integer'],
            'criteria.*.label' => ['required_with:criteria', 'string', 'max:255'],
            'criteria.*.description' => ['nullable', 'string', 'max:500'],
            'criteria.*.max_score' => ['required_with:criteria', 'integer', 'min:1', 'max:100'],
            'criteria.*.weight' => ['required_with:criteria', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'location.required_unless' => 'Tell people where this is happening.',
            'meeting_url.required_if' => 'A virtual activity needs a join link.',
            'price.required_if' => 'Set a price for a paid activity.',
        ];
    }
}
