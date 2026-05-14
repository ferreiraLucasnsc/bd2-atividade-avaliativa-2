<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BibliotecasController;

Route::get('/', function () {
    return view('welcome');
});

Route::get("/bibliotecas", [BibliotecasController::class, 'index'])->name("bibliotecas.index");
Route::get("/bibliotecas/create", [BibliotecasController::class, 'store'])->name("bibliotecas.create");
Route::get("/bibliotecas/update/{id}", [BibliotecasController::class, 'update'])->name("bibliotecas.update");
Route::get("/bibliotecas/delete", [BibliotecasController::class, 'destroy'])->name("bibliotecas.destroy");

