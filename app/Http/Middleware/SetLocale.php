<?php

namespace App\Http\Middleware;

use App\Core\Helpers\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = LocaleHelper::apply($request);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
