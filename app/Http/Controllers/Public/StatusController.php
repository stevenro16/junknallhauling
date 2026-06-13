<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class StatusController extends Controller
{
    public function index()
    {
        return view('public.status');
    }
}
