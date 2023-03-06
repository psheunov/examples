<?php

use Axxon\EditionFeatures\Http\Controllers\EditionFeaturesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::group(['prefix' => '/features/'], function () {
    Route::get('/tree/{locale}', [EditionFeaturesController::class, 'getTree']);
    Route::post('/update/{locale}', [EditionFeaturesController::class, 'update']);
    Route::post('/create-root/{locale}', [EditionFeaturesController::class, 'createRootElement']);
    Route::post('/create-child/{locale}/{parent}', [EditionFeaturesController::class, 'createChildElement']);
    Route::delete('/delete/{id}', [EditionFeaturesController::class, 'delete']);
});

Route::get('/locales', function () {
    return response()->json(config('multilingual.locales'));
}
);
