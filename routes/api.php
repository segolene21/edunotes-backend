<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\StatistiqueController;


Route::get('/bulletin/{matricule}/{niveau}/{idperiode}',
    [BulletinController::class, 'getBulletin']);
Route::get('/statistiques', [StatistiqueController::class, 'getStats']);