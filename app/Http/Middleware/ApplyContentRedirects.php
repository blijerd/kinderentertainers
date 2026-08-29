<?php

namespace App\Http\Middleware;

use App\Models\ContentRedirect;
use App\Support\Content\ContentRedirectPath;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyContentRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($request->is('up')) {
            return $next($request);
        }

        $fromPath = ContentRedirectPath::normalizeFrom($request->getPathInfo());

        if ($fromPath === '/' || ContentRedirectPath::isReservedFrom($fromPath)) {
            return $next($request);
        }

        try {
            $redirect = ContentRedirect::query()
                ->active()
                ->where('from_path', $fromPath)
                ->first();
        } catch (\Throwable) {
            return $next($request);
        }

        if ($redirect === null) {
            return $next($request);
        }

        $target = $redirect->destinationUrl($request->getQueryString());

        if (ContentRedirectPath::isAbsoluteUrl($target)) {
            return redirect()->away($target, $redirect->status_code);
        }

        return redirect()->to($target, $redirect->status_code);
    }
}
