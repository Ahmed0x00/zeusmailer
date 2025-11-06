<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;

Route::get('/', [CampaignController::class, 'index'])->name('campaign.index');
Route::get('/campaign/create', [CampaignController::class, 'create'])->name('campaign.create');
Route::post('/campaign', [CampaignController::class, 'store'])->name('campaign.store');
Route::get('/campaign/{id}', [CampaignController::class, 'show'])->name('campaign.show');
Route::delete('/campaign/{id}', [CampaignController::class, 'destroy'])->name('campaign.destroy');