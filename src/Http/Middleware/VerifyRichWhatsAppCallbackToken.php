<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyRichWhatsAppCallbackToken
{
    public function handle(Request $request, Closure $next)
    {
        $expectedToken = config('rich-whatsapp.callback_token');

        if (! $expectedToken || $expectedToken === '') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Callback token is not configured on the receiver.',
                ],
            ], 401);
        }

        $header = $request->header('Authorization', '');
        $token = '';
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            $token = trim($matches[1]);
        }

        if ($token === '' || ! hash_equals($expectedToken, $token)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Invalid callback token.',
                ],
            ], 401);
        }

        return $next($request);
    }
}
