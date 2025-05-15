<?php

use App\Models\Contract;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
  return view('welcome');
});

Route::get('contracts/{contract}/preview-pdf', function (Contract $contract) {
  return response($contract->generatePdf(), 200, [
    'Content-Type' => 'application/pdf',
  ]);
})->name('contracts.preview-pdf');
