<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWithdrawRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bank_account' => ['required', 'array'],
            'bank_account.bank' => ['required', 'string'],
            'bank_account.agency' => ['required', 'string'],
            'bank_account.account' => ['required', 'string'],
            'bank_account.account_type' => ['nullable', 'string', 'in:checking,savings'],
            'bank_account.account_holder_name' => ['required', 'string'],
            'bank_account.account_holder_document' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'bank_account.required' => 'Os dados da conta bancária são obrigatórios.',
            'bank_account.array' => 'Os dados da conta bancária devem ser um objeto.',
            'bank_account.bank.required' => 'O banco é obrigatório.',
            'bank_account.agency.required' => 'A agência é obrigatória.',
            'bank_account.account.required' => 'A conta é obrigatória.',
            'bank_account.account_holder_name.required' => 'O nome do titular é obrigatório.',
            'bank_account.account_holder_document.required' => 'O documento do titular é obrigatório.',
        ];
    }
}

