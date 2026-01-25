<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:inventory_category,id',
            'code' => 'nullable|string|max:100|unique:inventory_item,code',
            'custom_code' => 'nullable|string|max:20', // Kode khusus seperti BKBA
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100|unique:inventory_item,serial_number',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'status' => 'nullable|in:Tersedia,Dipinjam,Rusak,Hilang,Dijual',
            'quantity' => 'required|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'room_id' => 'nullable|exists:room,id',
            'building_id' => 'nullable|exists:building,id',
            'location_note' => 'nullable|string',
            'warranty_expiry' => 'nullable|date',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'reference_number' => 'nullable|string|max:255', // Untuk transaksi masuk
        ];
    }
}
