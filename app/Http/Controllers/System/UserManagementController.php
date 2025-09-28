<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index()
    {
        return Inertia::render('system/users/Index', [
            'users' => [],
            'stats' => [
                'total_users' => 0,
                'active_users' => 0,
                'super_admins' => 0,
            ]
        ]);
    }
}
