<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Psy\Sudo;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/api/procesar_suscripcion', [SubscriptionController::class, 'procesarPago'])
->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);


Route::get('/suscripciones', [SubscriptionController::class, 'mostrarSuscripciones']);
Route::get('/clientes', [SubscriptionController::class, 'clientes']);
Route::get('/ordenes', [SubscriptionController::class, 'ordenes']);
Route::get('/membresias', [SubscriptionController::class, 'membresias']);

Route::post('/createmembresia', action: [SubscriptionController::class, 'CrearMembresia'])
->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);


require __DIR__.'/auth.php';
