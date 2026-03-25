<?php

use App\Http\Controllers\Lms\CourseCatalogueController;
use Illuminate\Support\Facades\Route;

// Public course catalogue
Route::get('/online-courses', [CourseCatalogueController::class, 'index'])->name('lms.catalogue');
