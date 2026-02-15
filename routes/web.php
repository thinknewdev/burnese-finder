<?php

use App\Http\Controllers\DogController;
use App\Http\Controllers\BreederController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Home / Search
Route::get('/', [SearchController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/find-best', [SearchController::class, 'findBestDog'])->name('find-best');
Route::get('/active-breeding', [SearchController::class, 'activeBreeding'])->name('active-breeding');

// Dogs
Route::get('/dogs', [DogController::class, 'index'])->name('dogs.index');
Route::get('/dogs/top', [DogController::class, 'topRated'])->name('dogs.top');
Route::get('/dogs/{dog}', [DogController::class, 'show'])->name('dogs.show');

// Breeders
Route::get('/breeders', [BreederController::class, 'index'])->name('breeders.index');
Route::get('/breeders/top', [BreederController::class, 'topRated'])->name('breeders.top');
Route::get('/breeders/{breeder}', [BreederController::class, 'show'])->name('breeders.show');
