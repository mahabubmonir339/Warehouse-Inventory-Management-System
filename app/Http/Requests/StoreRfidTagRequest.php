<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRfidTagRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create-rfid-tags');
    }

    public function rules()
    {
        return [
            'tag_code' => 'required|string|unique:rfid_tags,tag_code',
            'tag_type' => 'sometimes|string|in:UHF,HF,LF,NFC|default:UHF',
            'status' => 'sometimes|in:active,inactive|default:active',
            'frequency' => 'sometimes|string|max:50',
            'epc' => 'sometimes|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'tag_code.required' => 'Tag code is required',
            'tag_code.unique' => 'This tag code already exists',
            'tag_type.in' => 'Invalid tag type',
        ];
    }
}
