<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;

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

