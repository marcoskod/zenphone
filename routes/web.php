<?php

use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

// TODO(action_09): replace with a real PricingController-backed page.
Route::view('/tarifs', 'pages.coming-soon', [
    'title' => 'Nos tarifs',
    'message' => 'La grille de tarifs complète par service et par pays arrive très prochainement.',
    'icon' => 'fa-tags',
])->name('pricing');

// TODO(action_13): replace with the full FAQ page, organized by category.
Route::view('/faq', 'pages.coming-soon', [
    'title' => 'Foire aux questions',
    'message' => 'La FAQ complète, organisée par catégorie, arrive très prochainement.',
    'icon' => 'fa-circle-question',
])->name('faq');

// TODO(action_13): replace with the real CGV page (incl. refund policy for undelivered SMS).
Route::view('/cgv', 'pages.coming-soon', [
    'title' => 'Conditions générales de vente',
    'icon' => 'fa-file-contract',
])->name('cgv');

// TODO(action_13): replace with the real mentions légales page.
Route::view('/mentions-legales', 'pages.coming-soon', [
    'title' => 'Mentions légales',
    'icon' => 'fa-scale-balanced',
])->name('mentions-legales');

// TODO(action_13): replace with the real politique de confidentialité page.
Route::view('/confidentialite', 'pages.coming-soon', [
    'title' => 'Politique de confidentialité',
    'icon' => 'fa-user-shield',
])->name('confidentialite');

// TODO(action_13): replace with the real contact form (ContactController, support_tickets).
Route::view('/contact', 'pages.coming-soon', [
    'title' => 'Contact',
    'message' => "Le formulaire de contact arrive très prochainement. En attendant, écrivez-nous à contact@zensms.example.",
    'icon' => 'fa-envelope',
])->name('contact');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // TODO(action_07): replace with the real Mobile Money top-up page once the
    // aggregator (CinetPay/Kkiapay/PayDunya) is chosen.
    Route::view('/recharger', 'pages.coming-soon', [
        'title' => 'Recharger mon solde',
        'message' => 'Le paiement Mobile Money (Orange Money, Wave, MTN MoMo, Moov Money) arrive très prochainement.',
        'icon' => 'fa-wallet',
    ])->name('topup');

    Route::get('/acheter', [PurchaseController::class, 'index'])->name('purchase');
    Route::post('/acheter', [PurchaseController::class, 'store'])->name('purchase.store');

    Route::get('/commande/{order}/attente', [PurchaseController::class, 'waiting'])
        ->name('purchase.waiting');

    Route::post('/commande/{order}/annuler', [PurchaseController::class, 'cancel'])
        ->name('purchase.cancel');

    Route::get('/historique/commandes', [HistoryController::class, 'orders'])->name('history');

    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/services', [CatalogController::class, 'services'])->name('services');
        Route::get('/countries', [CatalogController::class, 'countries'])->name('countries');
        Route::get('/price', [CatalogController::class, 'price'])->name('price');
        Route::get('/orders/{order}/status', [PurchaseController::class, 'status'])->name('orders.status');
    });
});

require __DIR__.'/auth.php';
