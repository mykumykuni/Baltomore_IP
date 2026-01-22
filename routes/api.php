<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return ['status' => 'Success', 'message' => 'Your API is working!'];
});
