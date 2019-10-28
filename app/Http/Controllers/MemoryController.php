<?php

namespace App\Http\Controllers;

use App\Memory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Auth::user()->memories()->with('emotions')->with('reasons')->get());
    }
}
