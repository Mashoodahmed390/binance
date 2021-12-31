<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Exception;

class LoginUserMiddlerware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try
        {
            if($user = User::where("email",$request["email"])->first())
            {
                if(Hash::check($request["password"], $user->password))
            {
                if(!($user->verifyemail))
                {
                    return response()->error('Account not verified',400);
                }
                return $next($request);
            }
            else
            {
                return response()->error('Password is Wrong',400);
            }
            }
            else
            {
                return response()->error('email is invalid',400);
            }
        }
        catch(Exception $e)
        {
            return response()->error($e->getMessage(),404);
        }
    }

}
