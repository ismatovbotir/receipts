<?php

namespace App\Http\Controllers;

use App\Models\DayNote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();

        // Sales per day this month
        $sales = DB::table('receipts')
            ->whereBetween('date_close', [$startOfMonth, $endOfMonth])
            ->selectRaw("DATE(date_close) as date, SUM(total) as revenue, COUNT(*) as transactions")
            ->groupByRaw("DATE(date_close)")
            ->get()
            ->keyBy('date');

        // Notes for this month
        $notes = DayNote::whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn($n) => $n->date->toDateString());

        // Build calendar grid (Monday-first; null = padding cell before month start)
        $days            = [];
        $firstDayOfWeek  = ($startOfMonth->dayOfWeek + 6) % 7; // Mon=0, Sun=6

        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $days[] = null;
        }

        for ($d = 1; $d <= $endOfMonth->day; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $days[] = [
                'day'          => $d,
                'date'         => $dateStr,
                'revenue'      => isset($sales[$dateStr]) ? (float) $sales[$dateStr]->revenue : null,
                'transactions' => isset($sales[$dateStr]) ? (int)   $sales[$dateStr]->transactions : null,
                'note'         => isset($notes[$dateStr])
                    ? $notes[$dateStr]->only(['id', 'date', 'type', 'title', 'notes'])
                    : null,
            ];
        }

        $prevMonth = $startOfMonth->copy()->subMonth();
        $nextMonth = $startOfMonth->copy()->addMonth();

        $types = DayNote::$types;

        return view('calendar.index', compact('year', 'month', 'startOfMonth', 'days', 'prevMonth', 'nextMonth', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'  => ['required', 'date'],
            'type'  => ['required', 'in:holiday,weather,sport,promo,other'],
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $note = DayNote::updateOrCreate(
            ['date' => $validated['date']],
            ['type' => $validated['type'], 'title' => $validated['title'], 'notes' => $validated['notes'] ?? null]
        );

        return response()->json(['success' => true, 'id' => $note->id]);
    }

    public function destroy(int $id)
    {
        DayNote::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
