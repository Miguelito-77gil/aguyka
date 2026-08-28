<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/students-ui', function () {
    return view('students.index');
});

Route::get('/login', function () {
    return view('auth.login');
});