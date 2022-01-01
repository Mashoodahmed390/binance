<?php

namespace App\Http\Middleware;
// use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Auth;
class JwtMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $key = config('constant.secret');
        try {
            $decoded = JWT::decode($request->bearerToken(), new Key($key, 'HS256'));
            $user=User::where('email',$decoded->data->email)->first();
            if($user->verifyemail==1)
            {
                if(!isset($user))
                {
                    return response()->json(['status' => 'Not a valid user token']);
                }
                else
                {
                    if (!Hash::check($decoded->data->password, $user->password)) {
                        return response()->json(['status' => 'Not a valid user token']);
                    }
                }
            }
            else
            {
                return response()->json(['error' => 'Please verify the link first'], 401);
            }

        } catch (Exception $e) {
            if ($e instanceof \Firebase\JWT\SignatureInvalidException){
                return response()->error(['status' => 'Token is Invalid'],400);
            }else if ($e instanceof \Firebase\JWT\ExpiredException){
                return response()->error(['status' => 'Token is Expired'],401);
            }else{
                return response()->error(['status' => 'Token Not Found'],400);
            }
        }
        $request = $request->merge(array("decoded"=>$decoded));
        return $next($request);
    }
}
