<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isProveedor = $this->input('role') === 'proveedor';

        return [
            'full_name'    => ['required', 'string', 'max:150'],
            'email'        => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'role'         => ['required', Rule::in(['cliente', 'proveedor'])],
            'phone'        => ['required', 'digits_between:7,15'],
            // Campos exclusivos de negocio
            'razon_social' => $isProveedor
                ? ['required', 'string', 'max:200']
                : ['nullable', 'string', 'max:200'],
            'ruc'          => $isProveedor
                ? ['required', 'digits:11']
                : ['nullable', 'digits:11'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'          => 'El teléfono es obligatorio.',
            'phone.digits_between'    => 'El teléfono debe tener entre 7 y 15 dígitos numéricos.',
            'razon_social.required'   => 'La razón social es obligatoria para registrar un negocio.',
            'ruc.required'            => 'El RUC es obligatorio para registrar un negocio.',
            'ruc.digits'              => 'El RUC debe tener exactamente 11 dígitos numéricos.',
        ];
    }
}
