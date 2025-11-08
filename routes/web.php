<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\SmtpTestController;
use App\Http\Controllers\SmtpCheckerController;
use App\Http\Controllers\EmailVerifierController;

Route::get('/', [CampaignController::class, 'index'])->name('campaign.index');
Route::get('/campaign/create', [CampaignController::class, 'create'])->name('campaign.create');
Route::post('/campaign', [CampaignController::class, 'store'])->name('campaign.store');
Route::get('/campaign/{id}', [CampaignController::class, 'show'])
    ->name('campaign.show');
Route::delete('/campaign/{id}', [CampaignController::class, 'destroy'])->name('campaign.destroy');
Route::post('/campaign/{id}/toggle', [CampaignController::class, 'toggleStatus'])->name('campaign.toggle');
Route::post('/campaign/{id}/update-smtps', [CampaignController::class, 'updateSmtps'])->name('campaign.update-smtps');
Route::get('/campaign/{id}/export-failed', [CampaignController::class, 'exportFailedEmails'])
    ->name('campaign.export-failed');
Route::post('/campaigns/{id}/update', [CampaignController::class, 'updateCampaign'])
    ->name('campaign.update');

Route::get('/smtp/test', [SmtpTestController::class, 'index'])->name('smtp.test');
Route::post('/smtp/start', [SmtpTestController::class, 'start']);
Route::get('/smtp/poll/{batchId}', [SmtpTestController::class, 'poll']);
Route::post('/smtp/run-next', [SmtpTestController::class, 'runNext']);

Route::get('/html-preview', function () {
    return view('html_preview');
})->name('html.preview');


Route::prefix('verifier')->group(function () {
    Route::get('/', [EmailVerifierController::class, 'create'])->name('verifier.create');
    Route::post('/start', [EmailVerifierController::class, 'start'])->name('verifier.start');
    Route::get('/batch/{batch}', [EmailVerifierController::class, 'show'])->name('verifier.show');
    Route::get('/batch/{batch}/status', [EmailVerifierController::class, 'status'])->name('verifier.status');
});


Route::prefix('smtp')->group(function () {
    // main checker view (where user uploads combos)
    Route::get('/', [SmtpCheckerController::class, 'index'])->name('smtp.create');

    // start a new batch check
    Route::post('/start', [SmtpCheckerController::class, 'start'])->name('smtp.start');

    // list all batches
    Route::get('/batches', [SmtpCheckerController::class, 'batches'])->name('smtp.batches');

    // show status/progress for a specific batch
    Route::get('/batch/{batch}', [SmtpCheckerController::class, 'status'])->name('smtp.batch.status');

        Route::get('/batch/{batch}/live', [SmtpCheckerController::class, 'liveStatus'])->name('smtp.batch.live'); // 👈 new JSON route
    // pause or resume batch processing
    Route::post('/batch/{batch}/pause', [SmtpCheckerController::class, 'pause'])->name('smtp.batch.pause');
    Route::post('/batch/{batch}/resume', [SmtpCheckerController::class, 'resume'])->name('smtp.batch.resume');

    // list all results for a specific batch
    Route::get('/batch/{batch}/results', [SmtpCheckerController::class, 'results'])->name('smtp.batch.results');

    // export results for a batch
    Route::get('/batch/{batch}/export', [SmtpCheckerController::class, 'export'])->name('smtp.batch.export');

    // (optional) show all stored results globally
    Route::get('/results', [SmtpCheckerController::class, 'allResults'])->name('smtp.results');
});
