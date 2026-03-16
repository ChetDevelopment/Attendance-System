<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(
            Role::query()
                ->select(['id', 'name', 'description'])
                ->orderBy('name')
                ->get()
        );
    }
}
