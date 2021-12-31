<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
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
            'name' => 'required|string|between:3,32',
            'email' => 'required|email|unique:users',
            'password'=> 'required|confirmed|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!.%*#?&]/',
            'mobile'=>'required|numeric|digits:11',
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
