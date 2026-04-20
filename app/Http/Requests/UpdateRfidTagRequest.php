<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRfidTagRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('edit-rfid-tags');
    }

    public function rules()
    {
        $tag = $this->route('tag');
        
        return [
            'tag_code' => 'sometimes|string|unique:rfid_tags,tag_code,' . $tag->id,
            'status' => 'sometimes|in:active,inactive,lost,damaged',
            'frequency' => 'sometimes|string|max:50',
            'epc' => 'sometimes|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'tag_code.unique' => 'This tag code already exists',
            'status.in' => 'Invalid status value',
        ];
    }
}
