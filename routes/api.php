<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('/nfe')->group(function () {
    Route::post('envia', [App\Http\Controllers\NFeController::class, 'envia']);
    Route::post('consulta', [App\Http\Controllers\NFeController::class, 'consulta']);
    Route::post('cancela', [App\Http\Controllers\NFeController::class, 'cancela']);
    Route::post('corrige', [App\Http\Controllers\NFeController::class, 'corrige']);
    Route::post('inutiliza', [App\Http\Controllers\NFeController::class, 'inutiliza']);
    Route::post('pdf', [App\Http\Controllers\NFeController::class, 'pdf']);
    Route::post('cce', [App\Http\Controllers\NFeController::class, 'cce']);
    Route::post('zipNfe', [App\Http\Controllers\NFeController::class, 'zipNfe']);
    Route::GET('teste-adelcio', function() {
        //dd('test success!!!');
        echo('PHP version: ' . PHP_VERSION_ID);
    });
});
