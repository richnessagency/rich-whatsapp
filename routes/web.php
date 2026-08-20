<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RichnessAgency\RichWhatsApp\Http\Controllers\DashboardController;

Route::group([
    'prefix' => config('rich-whatsapp.dashboard_prefix', 'whatsapp'),
    'middleware' => config('rich-whatsapp.dashboard_middleware', ['web', 'auth']),
    'as' => 'admin.whatsapp.',
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/connect', [DashboardController::class, 'connect'])->name('connect');
    Route::post('/reconnect', [DashboardController::class, 'reconnect'])->name('reconnect');
    
    Route::get('/logout', [DashboardController::class, 'logout'])->name('logout-view');
    Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');
    
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');

    Route::get('/chats', [DashboardController::class, 'chats'])->name('chats');
    Route::get('/chats/{jid}', [DashboardController::class, 'chat'])->name('chat');
    Route::get('/chats/{jid}/messages/{messageId}/media', [DashboardController::class, 'media'])->name('media');
    Route::get('/chats/{jid}/picture', [DashboardController::class, 'picture'])->name('picture');

    Route::get('/contacts', [DashboardController::class, 'contacts'])->name('contacts');
    
    Route::post('/messages/send', [DashboardController::class, 'sendMessage'])->name('messages.send');
    Route::post('/contacts/check', [DashboardController::class, 'checkContact'])->name('contacts.check');
    
    Route::get('/status', [DashboardController::class, 'status'])->name('status');
    Route::get('/qr', [DashboardController::class, 'qrCode'])->name('qr');
});
