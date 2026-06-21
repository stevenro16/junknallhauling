<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EodReportController extends Controller
{
    /** End-of-day report: all visits scheduled for a given day (default today). */
    public function index(Request $request)
    {
        $date = $this->parseDate((string) $request->query('date', ''));
        $key = $date->format('Y-m-d');

        // confirmed_date_time is a 'Y-m-d\TH:i(:s)' string — match on the date prefix.
        $visits = Inquiry::where('confirmed_date_time', 'like', $key.'%')
            ->where('status', '!=', 'cancelled')
            ->with('assignedEmployee:id,username')
            ->orderBy('confirmed_date_time')
            ->get();

        return view('admin.eod-report', [
            'date' => $date,
            'visits' => $visits,
            'prevDate' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $date->copy()->addDay()->format('Y-m-d'),
            'today' => now()->format('Y-m-d'),
        ]);
    }

    private function parseDate(string $raw): Carbon
    {
        try {
            return $raw !== '' ? Carbon::createFromFormat('Y-m-d', $raw)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }
}
