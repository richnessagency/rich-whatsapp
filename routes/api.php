<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RichnessAgency\RichWhatsApp\Http\Controllers\CallbackController;
use RichnessAgency\RichWhatsApp\Http\Middleware\VerifyRichWhatsAppCallbackToken;

Route::post('/rich-whatsapp/api/callback', [CallbackController::class, 'handle'])
    ->middleware([VerifyRichWhatsAppCallbackToken::class])
    ->name('rich-whatsapp.callback');
