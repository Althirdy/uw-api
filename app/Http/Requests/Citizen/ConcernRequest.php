<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class ConcernRequest extends FormRequest
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
            'type' => 'required|in:manual,voice',
            'title' => 'required_if:type,manual|nullable|string|max:100',
            'description' => 'required_if:type,manual|nullable|string',
            'category' => 'required|string|in:safety,security,infrastructure,environment,noise,other',
            'severity' => 'nullable|string|in:low,medium,high',
            'transcript_text' => 'nullable|string',
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'files' => 'nullable|array|min:1|max:3', // Maximum 3 files
            'files.*' => 'file|mimes:jpeg,jpg,png,gif,mp3,wav,m4a,aac|max:10240', // Each file max 10MB
        ];
    }

    /**
     * Customize the error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Concern type is required.',
            'type.in' => 'Type must be either manual or voice.',
            'title.required_if' => 'Title is required for manual concerns.',
            'title.string' => 'Title must be a string.',
            'title.max' => 'Title cannot exceed 100 characters.',
            'description.required_if' => 'Description is required for manual concerns.',
            'description.string' => 'Description must be a string.',
            'category.required' => 'Category is required.',
            'category.string' => 'Category must be a string.',
            'category.in' => 'Category must be one of: safety, security, infrastructure, environment, noise, other.',
            'severity.string' => 'Severity must be a string.',
            'severity.in' => 'Severity must be one of: low, medium, high.',
            'transcript_text.string' => 'Transcript text must be a string.',
            'longitude.numeric' => 'Longitude must be a numeric value.',
            'latitude.numeric' => 'Latitude must be a numeric value.',
            'files.array' => 'Files must be an array.',
            'files.min' => 'At least one file must be uploaded when files are provided.',
            'files.max' => 'You can upload a maximum of 3 files.',
            'files.*.file' => 'Each uploaded item must be a valid file.',
            'files.*.mimes' => 'Each file must be of type: jpeg, jpg, png, gif, mp3, wav, m4a, aac.',
            'files.*.max' => 'Each file size cannot exceed 10MB.',
        ];
    }
}
