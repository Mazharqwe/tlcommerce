<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSubscriberSeparation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $user_type)
    {
        if ($user_type != Auth::user()->user_type) {
            if ($user_type == config('saas.user_type.subscriber')) {
                return redirect('/user/'.getSaasPrefix().'/login');
            } else {
                return redirect('/admin/login');
            }
        }
        return $next($request);
    }
}