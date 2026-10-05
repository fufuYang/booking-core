<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * 路由已經套上 auth:sanctum，能走到這裡就代表已登入。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'available_slot_id' => ['required', 'integer', 'exists:available_slots,id'],
            // 通用表單資料，欄位由各租戶自訂，這裡只確保它是物件而非純量。
            'meta_data' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_id.exists' => '選擇的服務項目不存在。',
            'available_slot_id.exists' => '選擇的時段不存在。',
        ];
    }
}
