<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
