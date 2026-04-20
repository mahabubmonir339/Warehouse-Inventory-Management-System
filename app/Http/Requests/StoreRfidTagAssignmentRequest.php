<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRfidTagAssignmentRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('assign-rfid-tags');
    }

    public function rules()
    {
        return [
            'rfid_tag_id' => 'required|integer|exists:rfid_tags,id',
            'item_id' => 'required|integer|exists:items,id',
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'notes' => 'sometimes|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'rfid_tag_id.required' => 'Please select an RFID tag',
            'rfid_tag_id.exists' => 'Selected tag does not exist',
            'item_id.required' => 'Please select an item',
            'item_id.exists' => 'Selected item does not exist',
        ];
    }
}
