<?php

namespace App\Http\Controllers;

use App\Services\SpendingForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ForecastController extends Controller
{
    /**
     * Display the upcoming-month deterministic spending forecast.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $forecast = SpendingForecastService::generateForecast($user);

        return view('forecast', compact('forecast', 'user'));
    }
}
