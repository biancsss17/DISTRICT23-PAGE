<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePageDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $rules = [
            'revision' => 'present|nullable|string',
            'settings' => 'required|array',
            'sections' => 'required|array',
            'sections.*.id' => [
                'required',
                'distinct',
                Rule::exists('page_sections', 'id')->where(fn ($query) => $query->where('key', '!=', 'activities')),
            ],
            'sections.*.title' => 'required|string|max:180',
            'sections.*.description' => 'nullable|string|max:1000',
            'sections.*.sort_order' => 'required|integer|min:0|max:1000',
            'sections.*.visible' => 'required|boolean',
            'slides' => 'present|array|max:20',
            'slides.*' => 'integer|distinct|exists:media,id',
            'styles' => 'present|array',
        ];
        foreach (config('cms') as $fields) {
            foreach ($fields as $key => $field) {
                $rules['settings.'.$key] = match ($field[1]) {
                    'color' => ['required', 'regex:/^#[a-f0-9]{6}$/i'],
                    'number' => 'required|integer|min:0|max:9999999',
                    'media' => ['nullable', Rule::exists('media', 'id')],
                    'email' => 'nullable|email|max:255',
                    'url' => 'nullable|url:http,https|max:255',
                    'path' => ['required', 'regex:~^/(?!/)[a-zA-Z0-9/_#?=&%.-]*$~', 'max:255'],
                    default => 'present|nullable|string|max:5000',
                };
            }
        }
        foreach (['hero_heading', 'hero_kicker', 'header_title', 'primary_text', 'secondary_text'] as $key) {
            $rules['settings.'.$key] = 'required|string|max:5000';
        }
        foreach (config('page_editor.targets') as $key => $target) {
            $rules['styles.'.$key] = 'sometimes|array:font,size,weight,align,color';
            $rules['styles.'.$key.'.font'] = ['nullable', Rule::in(array_keys(config('page_editor.fonts')))];
            $rules['styles.'.$key.'.size'] = 'nullable|integer|min:10|max:120';
            $rules['styles.'.$key.'.weight'] = ['nullable', Rule::in(['300', '400', '500', '600', '700', '800'])];
            $rules['styles.'.$key.'.align'] = ['nullable', Rule::in(['left', 'center', 'right'])];
            $rules['styles.'.$key.'.color'] = ['nullable', 'regex:/^#[a-f0-9]{6}$/i'];
        }
        $rules['styles.background'] = 'sometimes|array:mode,start,end,angle';
        $rules['styles.background.mode'] = ['nullable', Rule::in(['original', 'solid', 'gradient'])];
        foreach (['start', 'end'] as $key) {
            $rules['styles.background.'.$key] = ['nullable', 'regex:/^#[a-f0-9]{6}$/i'];
        }
        $rules['styles.background.angle'] = 'nullable|integer|min:0|max:360';
        $rules['styles.spacing'] = 'nullable|integer|min:16|max:160';

        return $rules;
    }
}
