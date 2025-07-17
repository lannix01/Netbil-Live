<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class COntrolPanelController extends Controller
{
    public function index()
    {
        return view('control.panel');
    }
}
