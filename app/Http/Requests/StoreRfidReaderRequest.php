<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRfidReaderRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create-rfid-readers');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ipv4|unique:rfid_readers,ip_address',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'location' => 'required|string|max:255',
            'status' => 'sometimes|in:active,inactive,maintenance|default:active',
            'read_range' => 'sometimes|integer|min:1|max:100|default:5',
            'frequency' => 'sometimes|string|max:50',
            'protocol' => 'sometimes|string|max:50|default:TCP',
            'port' => 'sometimes|integer|min:1|max:65535|default:9096',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Reader name is required',
            'ip_address.required' => 'IP address is required',
            'ip_address.ipv4' => 'Please enter a valid IP address',
            'ip_address.unique' => 'This IP address is already registered',
            'warehouse_id.required' => 'Warehouse selection is required',
            'warehouse_id.exists' => 'Selected warehouse does not exist',
            'location.required' => 'Reader location is required',
        ];
    }
}
