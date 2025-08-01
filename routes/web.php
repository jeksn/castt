<?php

use App\Http\Controllers\PodcastController;
use App\Http\Controllers\EpisodeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [PodcastController::class, 'index'])->name('dashboard');
    
    // Podcast routes
    Route::post('podcasts', [PodcastController::class, 'store'])->name('podcasts.store');
    Route::post('podcasts/{podcast}/refresh', [PodcastController::class, 'refresh'])->name('podcasts.refresh');
    Route::delete('podcasts/{podcast}', [PodcastController::class, 'destroy'])->name('podcasts.destroy');
    
    // Episode routes
    Route::get('episodes', [EpisodeController::class, 'index'])->name('episodes.index');
    Route::post('episodes/{episode}/toggle-completed', [EpisodeController::class, 'toggleCompleted'])->name('episodes.toggle-completed');
    Route::post('episodes/mark-all-completed', [EpisodeController::class, 'markAllCompleted'])->name('episodes.mark-all-completed');
    Route::post('episodes/mark-all-incomplete', [EpisodeController::class, 'markAllIncomplete'])->name('episodes.mark-all-incomplete');
    
    // Podcast completion statistics
    Route::get('podcasts/{podcast}/completion-stats', [EpisodeController::class, 'getCompletionStats'])->name('podcasts.completion-stats');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
