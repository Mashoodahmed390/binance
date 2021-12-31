<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ForgetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "email"=>"required|email|exists:users|string"
        ];
    }
    public function failedValidation(Validator $v)
        {
            throw new HttpResponseException(response()->json([
                'status'=> false,
                'message'=> 'Validation error',
                'data'=> $v->errors()
            ],400));
        }
}
