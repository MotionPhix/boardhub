<?php

return [
  'name' => env('COMPANY_NAME', config('app.name')),
  'address' => env('COMPANY_ADDRESS', '123 Business St'),
  'phone' => env('COMPANY_PHONE', '+1234567890'),
  'email' => env('COMPANY_EMAIL', 'contact@company.com'),
];
