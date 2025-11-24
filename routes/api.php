<?php

use App\Http\Controllers\YodleeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('login', 'ApiController@login');
Route::prefix('yodlee')->group(function () {
    
    // Step 1: Get access token (Admin + User)
    Route::post('/get-access-token', [YodleeController::class, 'getAccessToken']);
    
    // Step 2: Get FastLink token
    Route::post('/get-fastlink-token', [YodleeController::class, 'getFastLinkToken']);
    
    // Step 3: Get transactions
    Route::post('/get-transactions', [YodleeController::class, 'getTransactions']);
    
    // Additional endpoints
    Route::post('/get-accounts', [YodleeController::class, 'getAccounts']);
    Route::post('/get-provider-accounts', [YodleeController::class, 'getProviderAccounts']);
});
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('logout', [ApiController::class, 'logout']);
    Route::get('get-projects', [ApiController::class, 'getProjects']);
    Route::post('add-tracker', [ApiController::class, 'addTracker']);
    Route::post('stop-tracker', [ApiController::class, 'stopTracker']);
    Route::post('upload-photos', [ApiController::class, 'uploadImage']);
});
