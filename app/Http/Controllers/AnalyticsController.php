<?php

namespace App\Http\Controllers;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('analytics', [
            'keyMissing' => empty(config('services.gemini.key')),
        ]);
    }
}
