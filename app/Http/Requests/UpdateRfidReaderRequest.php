<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRfidReaderRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('edit-rfid-readers');
    }

    public function rules()
    {
        $reader = $this->route('reader');
        
        return [
            'name' => 'sometimes|string|max:255',
            'ip_address' => 'sometimes|ipv4|unique:rfid_readers,ip_address,' . $reader->id,
            'location' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive,maintenance',
            'read_range' => 'sometimes|integer|min:1|max:100',
            'frequency' => 'sometimes|string|max:50',
            'protocol' => 'sometimes|string|max:50',
            'port' => 'sometimes|integer|min:1|max:65535',
        ];
    }

    public function messages()
    {
        return [
            'ip_address.ipv4' => 'Please enter a valid IP address',
            'ip_address.unique' => 'This IP address is already registered',
        ];
    }
}
