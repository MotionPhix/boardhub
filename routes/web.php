<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return view('welcome');
});

Route::get('contracts/{contract}/preview-pdf', function (Contract $contract) {
  return response($contract->generatePdf(), 200, [
    'Content-Type' => 'application/pdf',
  ]);
})->name('contracts.preview-pdf');
