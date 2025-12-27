<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['active_user_count'] = User::where('status', UserStatus::ACTIVE->value)->count();
        return inertia('Admin/Dashboard', $data);
    }
}
