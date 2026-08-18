<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RichnessAgency\RichWhatsApp\Http\Controllers\DashboardController;

Route::group([
    'prefix' => config('rich-whatsapp.dashboard_prefix', 'whatsapp'),
    'middleware' => config('rich-whatsapp.dashboard_middleware', ['web', 'auth']),
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('rich-whatsapp.dashboard');
    Route::get('/connect', [DashboardController::class, 'connect'])->name('rich-whatsapp.connect');
    Route::post('/reconnect', [DashboardController::class, 'reconnect'])->name('rich-whatsapp.reconnect');
    
    Route::get('/logout', [DashboardController::class, 'logout'])->name('rich-whatsapp.logout-view');
    Route::post('/logout', [DashboardController::class, 'logout'])->name('rich-whatsapp.logout');
    
    Route::get('/settings', [DashboardController::class, 'settings'])->name('rich-whatsapp.settings');

    Route::get('/chats', [DashboardController::class, 'chats'])->name('rich-whatsapp.chats');
    Route::get('/chats/{jid}', [DashboardController::class, 'chat'])->name('rich-whatsapp.chat');
    Route::get('/chats/{jid}/messages/{messageId}/media', [DashboardController::class, 'media'])->name('rich-whatsapp.media');
    Route::get('/chats/{jid}/picture', [DashboardController::class, 'picture'])->name('rich-whatsapp.picture');

    Route::get('/contacts', [DashboardController::class, 'contacts'])->name('rich-whatsapp.contacts');
    
    Route::post('/messages/send', [DashboardController::class, 'sendMessage'])->name('rich-whatsapp.messages.send');
    Route::post('/contacts/check', [DashboardController::class, 'checkContact'])->name('rich-whatsapp.contacts.check');
    
    Route::get('/status', [DashboardController::class, 'status'])->name('rich-whatsapp.status');
    Route::get('/qr', [DashboardController::class, 'qrCode'])->name('rich-whatsapp.qr');
});
