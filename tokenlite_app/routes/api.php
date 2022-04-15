<?php

use Illuminate\Http\Request;

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

Route::get('/stage', 'APIController@stage')->name('stage');
Route::get('/stage/full', 'APIController@stage_full')->name('stage.full');

Route::get('/bonus', 'APIController@bonuses')->name('bonus');
Route::get('/price', 'APIController@prices')->name('price');

Route::get('/transactions', 'APIController@transactions')->name('transactions');
Route::get('/recent', 'APIController@recent_transactions')->name('recent_transactions');
Route::get('/price_chart', 'APIController@price_chart')->name('price_chart');
Route::get('/price_data', 'APIController@price_data')->name('price_data');

Route::any('/{any?}', function() {
    throw new App\Exceptions\APIException("Enter a valid endpoint", 400);
})->where('any', '.*');