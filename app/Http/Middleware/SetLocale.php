<?php

namespace App\Http\Middleware;

use App\Services\LanguageService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private readonly LanguageService $language) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->language->initialize();

        return $next($request);
    }
}
