<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect('/login'));

Route::get('/login', fn () => Inertia::render('Login'))->name('login');
Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
Route::get('/tasks', fn () => Inertia::render('Tasks'))->name('tasks');
