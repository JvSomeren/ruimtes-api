<?php

namespace App\Http\Middleware;

use Closure;
use App\Admin;
use App\Session;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    private function convert_epoch_to_datetime($epoch)
    {
        return date('Y-m-d H:i:s', substr($epoch, 0, 10));
    }

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $role = null)
    {
        if($role == "user" || $role == "admin")
        {
            if($request->header('Authorization')) {
                $header = explode(':', $request->header('Authorization'));
                $time = $this->convert_epoch_to_datetime(time());
    
                $session = Session::where('uid', $header[0])
                    ->where('hash', $header[1])
                    ->whereDate('expires', '>', $time)
                    ->first();
    
                if(!empty($session)) {
                    if($role == "admin") {
                        $admin = Admin::where('uid', $header[0])->first();

                        if(empty($admin))
                            return response()->json(['msg' => 'Invalide rechten'], 401);
                    }

                    return $next($request);
                }
            }
    
            return response()->json(['msg' => 'Invalide sessie'], 401);
        }

        return $next($request);
    }
}
