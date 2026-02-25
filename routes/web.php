<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

//Clear Cache facade value:
Route::get('/key', function () {
    $exitCode = Artisan::call('key:generate');
    return '<h1>Key generated with success !</h1>';
});

//Clear Cache facade value:
Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('cache:clear');
    return '<h1>Cache facade value cleared</h1>';
});

//Reoptimized class loader:
Route::get('/optimize', function () {
    $exitCode = Artisan::call('optimize:clear');
    return '<h1>Reoptimized class loader</h1>';
});

//Route cache:
Route::get('/route-cache', function () {
    $exitCode = Artisan::call('route:cache');
    return '<h1>Routes cached</h1>';
});

//Clear Route cache:
Route::get('/route-clear', function () {
    $exitCode = Artisan::call('route:clear');
    return '<h1>Route cache cleared</h1>';
});

//Clear View cache:
Route::get('/view-clear', function () {
    $exitCode = Artisan::call('view:clear');
    return '<h1>View cache cleared</h1>';
});

//Clear Config cache:
Route::get('/config-cache', function () {
    $exitCode = Artisan::call('config:cache');
    return '<h1>Clear Config cache cleared</h1>';
});

//Storage link:
Route::get('/link-storage', function () {
    Artisan::call('storage:link');
    return response('<h1>Storage link created successfully</h1>');
});

//worker
Route::get('/workers', function () {
    Artisan::call('schedule:work');
    Artisan::call('queue:work');
    return response('<h1>Storage link created successfully</h1>');
});

//Clear Config cache:
Route::get('/proc-open-error', function () {
    $exitCode = Artisan::call('vendor:publish', ['--tag' => 'flare-config']);
    return '<h1>Proc open error resolved -> Think to change parameters in config/flare.php !!!</h1>';
});

//Storage route link
Route::get('/any-route', function () {
    $exitCode = Artisan::call('storage:link');
    echo $exitCode; // 0 exit code for no errors.
});