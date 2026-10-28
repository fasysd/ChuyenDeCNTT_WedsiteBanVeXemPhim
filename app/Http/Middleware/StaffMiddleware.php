<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
	public function handle(Request $request, Closure $next): Response
	{
		if (! Auth::check()) {
			return redirect()->route('login');
		}

		if (! in_array(Auth::user()->role, ['STAFF', 'ADMIN'], true)) {
			abort(403);
		}

		return $next($request);
	}
}