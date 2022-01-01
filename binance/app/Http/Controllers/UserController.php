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
use App\Models\Authentication;
use Illuminate\Support\Str;
use App\Models\Password_reset;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\Kyc;
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

            $auth = new Authentication();
            $auth->email_verification_token = Str::random(16);
            //$signinUserData->save();

            // $account_sid = "ACa2e84a5417152b1b7cdbc26797002545";
            // $auth_token = "2755809e2581e131152906fed5091afb";
            // $twilio_number = "+14844410034";

            //$receiverNumber = $validated["phone_number"];
            $otp = mt_rand(1000,9999);
            $auth->otp = $otp;
            //$message = "OTP code ".$otp;

            // $client = new Client($account_sid, $auth_token);
            // $client->messages->create($receiverNumber, [
            //     'from' => $twilio_number,
            //     'body' => $message]);

            $user = [
                'name' => $validated['name'],
                'info' => 'Press the Following Link to Verify Email',
                'Verification_link'=>url('user/verifyEmail/'.$validated['email'].'/'.$auth->email_verification_token)
            ];
           dispatch(new \App\Jobs\SendEmailJob($validated['email'],$user));
           $signinUserData->save();
           $signinUserData->authentication()->save($auth);
           $auth->save();
           return response()->success("SignUp Successfully",200);
        }
        catch(Exception $e)
        {
            return response()->error($e->getMessage(),403);
        }
    }
    public function verifyemail($email,$token)
            {
                try
                {
                if(User::where("email",$email)->value('verifyemail') == 1)
                {
                    $m = ["You have already verified your account"];
                    return response()->error($m,404);
                }
                else
                {
                    //$update=User::where("email",$email)->update(["verifyemail"=>1]);
                    $user = User::where("email",$email)->first();
                    //dd(Authentication::where([['email_verification_token',$token],['user_id',$user->id]])->exists());
                    if(Authentication::where([['email_verification_token',$token],['user_id',$user->id]])->exists())
                    {
                        User::where("email",$email)->update(["verifyemail"=>1]);
                        Authentication::where("email_verification_token",$token)->update(["email_verification_token"=>Null]);
                    }
                        return response()->success("Account verified",200);
                }
                }
                catch(Exception $e)
                {
                    return response()->error($e->getMessage(),400);
                }
            }
    public function verifyphone(Request $request)
    {
        try
        {

        if(Authentication::where("otp",$request->code)->exists());
        {
        $auth = Authentication::where("otp",$request->code)->first();
        $user = User::find($auth->user_id);
        $user->phone_number_verified = 1;
        $user->save();
        Authentication::where("otp",$request->code)->update(['otp'=>NULL]);
        return response()->success("Phone number Verified",200);
        }
        return response()->error('OTP code Expired',400);
        }
        catch(Exception $e)
        {
            return response()->error($e->getMessage(),400);
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
        try{
            $validated = $request->validated();
            $resetPassword = new Password_reset();
            $resetPassword->email = $request->email;
            $resetPassword->token = Str::random(10);
            $resetPassword->save();
            $data = ['Verification_link'=>url('user/updatepassword/'.$resetPassword->email.'/'.$resetPassword->token)];
            dispatch(new \App\Jobs\Forgetpasswordjob($validated['email'],$data));
            return response()->success("Password reset mail has been sent",200);
           }
           catch(Exception $e)
           {
               return response()->error($e->getMessage(),400);
           }
    }
    public function updatepassword(UpdatePasswordRequest $request,$email,$token)
    {
        try{
        if(Password_reset::where('token',$token)->exists())
        {
            $deleteToken = Password_reset::where('token',$token)->first();
            $deleteToken->delete();
            $validated = $request->validated();
            $user = User::where('email',$email)->first();
            $validated['password'] = bcrypt($validated['password']);
            $user->password =$validated['password'];
            $user->save();
            return response()->success("Password Updated",200);
        }
        else
        {
            return response()->error("Unauthorized",404);
        }
           }
           catch(Exception $e)
           {
               return response()->error($e->getMessage(),404);
           }
    }
    public function logout(Request $request){
        try {
            $decoded = $request->decoded;
            User::where("user_id",$decoded->data->id)->update(['rememberToken'=>NULL]);
            $response_data['message']='Logout Successfully!!!';
            return response()->success($response_data,200);
        }
        catch (Exception $e) {
            return response()->error($e->getMessage(),400);
        }
    }
    public function profilephoto(Request $request)
    {
        try{
            $base64encode = $request->profilePicture;
            $decoded = $request->decoded;
            $imageName = Str::random(10) .".png";
            $path = public_path().'//storage//'.$imageName;
            file_put_contents($path,base64_decode($base64encode));
            $kyc = new Kyc();
            $kyc->profile_image = $path;
            $kyc->user()->associate($decoded->data->id);
            $kyc->save();
            $m=["message"=>"picture uploaded successfully","image_detail"=>$kyc->profile_image];
            return response()->success($m,201);
           }
           catch(Exception $e)
           {
               return response()->error($e->getMessage(),400);
           }
    }
    public function documentphoto(Request $request)
    {
        try{
            $base64encode = $request->profilePicture;
            $decoded = $request->decoded;
            $imageName = Str::random(10) .".png";
            $path = public_path().'//storage//'.$imageName;
            file_put_contents($path,base64_decode($base64encode));
            $kyc = Kyc::where("user_id",$decoded->data->id)->first();
            $kyc->document_image = $path;
            $kyc->save();
            $m=["message"=>"picture uploaded successfully","image_detail"=>$kyc->document_image];
            return response()->success($m,201);
           }
           catch(Exception $e)
           {
               return response()->error($e->getMessage(),400);
           }
        }
           public function idphoto(Request $request)
    {
        try{
            $base64encode = $request->profilePicture;
            $decoded = $request->decoded;
            $imageName = Str::random(10) .".png";
            $path = public_path().'//storage//'.$imageName;
            file_put_contents($path,base64_decode($base64encode));
            $kyc = Kyc::where("user_id",$decoded->data->id)->first();
            $kyc->id_image = $path;
            $kyc->save();
            $m=["message"=>"picture uploaded successfully","image_detail"=>$kyc->id_image];
            return response()->success($m,201);

           }
           catch(Exception $e)
           {
               return response()->error($e->getMessage(),400);
           }
    }
}
