<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperator
{
    /**
     * Hanya role operator yang boleh lanjut (halaman admin + cetak QR).
     * Scanner hanya untuk halaman scan bilik.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isOperator()) {
            abort(403, 'Hanya operator yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
