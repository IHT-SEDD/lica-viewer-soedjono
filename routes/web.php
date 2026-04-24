<?php

use Illuminate\Support\Facades\Route;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'ViewerController@index')->name('viewer');

// VIEWER ::begin
Route::prefix('viewer')->group(function () {
    Route::post('access', 'ViewerController@access')->name('viewer.access');
    Route::get('result/{medrec}', 'ViewerController@indexResult')->name('viewer.result');
    Route::get('datatable/{startDate?}/{endDate?}/{groupId?}', 'ViewerController@datatable');
    Route::get('datatable-test/{transactionId}', 'ViewerController@datatableTest');
    Route::get('datatable-process-time/{transactionId}', 'ViewerController@datatableProcessTime');
    Route::get('getFirstPrint/{id}', 'ViewerController@getFirstPrint');
    Route::get('select-options/{masterData}/{searchKey}/{roomType?}', 'ViewerController@selectOptions');
    Route::put('print-package-name/{transactionId}/{packageId}', 'ViewerController@printPackageName');
    Route::get('get-verificator-name/{id}', 'ViewerController@getVerificatorName');
    Route::get('printHasilTest/{id}', 'ViewerController@hasilTest');
    Route::get('exportPdfHasil/{id}', 'ViewerController@exportPdfHasil');
    Route::put('update-is-print/{testtransactionId}', 'PostAnalyticController@updatePrintTest');
    Route::put('update-is-print-memo/{testtransactionId}', 'PostAnalyticController@updatePrintMemo');
});
// VIEWER ::end

Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('view:clear');
    $exitCode = Artisan::call('route:clear');
    // return what you want
});
