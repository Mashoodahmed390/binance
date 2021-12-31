<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use App\Jobs\SendEmailJob;
use App\Service\jwtservice as Token;
use App\Models\User;
use Twilio\Rest\Client;
use App\Http\Requests\ForgetPasswordRequest;
use Illuminate\Support\Str;
use App\Models\Password_reset;

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
            $signinUserData->phone_number = $validated["phone_number"];
            $signinUserData->email_verification_token = md5($validated["name"]);
            //$signinUserData->save();

            $account_sid = "ACa2e84a5417152b1b7cdbc26797002545";
            $auth_token = "2755809e2581e131152906fed5091afb";
            $twilio_number = "+14844410034";

            $receiverNumber = $validated["phone_number"];
            $otp = mt_rand(1000,9999);
            $signinUserData->otp = $otp;
            $message = "OTP code ".$otp;
            $client = new Client($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);
            $user = [
                'name' => $validated['name'],
                'info' => 'Press the Following Link to Verify Email',
                'Verification_link'=>url('user/verifyEmail/'.$validated['email'].'/'.$signinUserData->email_verification_token)
            ];
           dispatch(new \App\Jobs\SendEmailJob($validated['email'],$user));
           $signinUserData->save();
           return response()->success("SignUp Successfully",200);
        }
        catch(Exception $e)
        {
            return response()->error($e->getMessage(),403);
        }
    }
    public function verifyemail($email,$token)
            {
                if(User::where([["email",$email],["email_verification_token",$token]])->value('verifyemail') == 1)
                {
                    $m = ["You have already verified your account"];
                    return response()->error($m,404);
                }
                else
                {
                    $update=User::where("email",$email)->update(["verifyemail"=>1]);
                    $update=User::where("email",$email)->update(["email_verification_token"=>Null]);
                    if($update){
                        return response()->success("Account verified",200);
                    }else{
                        return response()->error("Failed",400);
                    }
                }
            }
    public function verifyphone(Request $request)
    {
        if($user = User::where("otp",$request->code)->first());
        {
        $user->phonenumberverified = 1;
        $user->save();
        return response()->success("Phone number Verified",200);
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
    public function forgetpassword(ForgetPasswordRequest $request)
    {
        // dd($request->email);
        // dd(User::where("email",$request->email)->exists());
        try{
            $resetPassword = new Password_reset();
            $resetPassword->email = $request->email;
            $resetPassword->token = Str::random(10);
            $resetPassword->save();
            $data = ['Verification_link'=>url('user/'.$resetPassword->email.'/'.$resetPassword->token)];
            \Mail::to($request->email)->send(new \App\Mail\ForgetPasswordEmail($data));
            return response()->success("Password reset mail has been sent",200);
           }
           catch(Exception $e)
           {
               return response()->error($e->getMessage(),400);
           }
    }
}
