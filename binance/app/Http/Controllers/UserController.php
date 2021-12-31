<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use App\Jobs\SendEmailJob;
use App\Service\jwtservice as Token;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserController extends Controller
{
    public function signup(UserRequest $request)
    {
        try
        {
            $validated = $request->validated();
            $validated['password'] = bcrypt($validated['password']);
            $signinUserData = New User();
            $signinUserData->name = $validated["name"];
            $signinUserData->email = $validated["email"];
            $signinUserData->password = $validated["password"];
            $signinUserData->mobile = $validated["mobile"];
            $signinUserData->email_verification_token = md5($validated["name"]);
            $signinUserData->save();
            $user = [
                'name' => $validated['name'],
                'info' => 'Press the Following Link to Verify Email',
                'Verification_link'=>url('user/verifyEmail/'.$validated['email'].'/'.$signinUserData->email_verification_token)
            ];
           dispatch(new \App\Jobs\SendEmailJob($validated['email'],$user));
            return response()->success("SignUp Successfully",200);
        }
        catch(Exception $e)
        {
            return response()->error($e->getMessage(),403);
        }
    }
    public function verify($email,$token)
            {
                if(User::where([["email",$email],["email_verification_token",$token]])->value('verify') == 1)
                {
                    $m = ["You have already verified your account"];
                    return response()->error($m,404);
                }
                else
                {
                    $update=User::where("email",$email)->update(["verify"=>1],["email_verified_at"=>now()]);
                    if($update){
                        return response()->success("Account verified",200);
                    }else{
                        return response()->error("Failed",400);
                    }
                }
            }
            public function login(LoginRequest $request)
    {
        try
        {
            $validated = $request->validated();
            $user = User::where("email",$validated["email"])->first();
            $data = [
                "id"=>$user->id,
                "email"=>$validated["email"],
                "password"=>$validated["password"],
                "mobile"=>$user->mobile
            ];
            $jwt = (new Token)->jwt_encode($data);
            $user->remembertoken = $jwt;
            $user->save();
            $user = array_merge($user->toArray(),array("password"=>$validated["password"]));
            return response()->success($user,200);
        }
        catch(Exception $e)
        {
            return response()->error($e->getMessage(),404);
        }
    }
}
