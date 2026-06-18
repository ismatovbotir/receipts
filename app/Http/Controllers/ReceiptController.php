<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = Receipt::query()->orderByDesc('date_close');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('number',  'like', '%' . $search . '%')
                  ->orWhere('cashier', 'like', '%' . $search . '%')
                  ->orWhere('shop',    'like', '%' . $search . '%');
            });
        }

        if ($request->filled('shop')) {
            $query->where('shop', $request->input('shop'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_close', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_close', '<=', $request->input('date_to'));
        }

        $receipts = $query->withCount('payments')->paginate(20);

        $shops_list = Receipt::select('shop')->distinct()->orderBy('shop')->pluck('shop');

        return view('receipts.index', compact('receipts', 'shops_list'));
    }

    public function show($id)
    {
        $receipt = Receipt::with(['items', 'payments', 'discounts'])->findOrFail($id);

        return view('receipts.show', compact('receipt'));
    }

    public function exportAnalytics(Request $request): Response
    {
        $dateFrom = $request->input('dateFrom', '');
        $dateTo   = $request->input('dateTo', '');
        $shop     = $request->input('shop', '');

        $driver   = DB::getDriverName();
        $timeExpr = $driver === 'mysql'
            ? 'TIMESTAMPDIFF(SECOND, date_open, date_close)'
            : "(CAST(strftime('%s', date_close) AS INTEGER) - CAST(strftime('%s', date_open) AS INTEGER))";

        $base = Receipt::query()->where('status', 'Закрыт')->where('type', 'Продажа');
        if ($dateFrom) $base->where('date_close', '>=', $dateFrom);
        if ($dateTo)   $base->where('date_close', '<=', $dateTo);
        if ($shop)     $base->where('shop', $shop);

        $shopStats = (clone $base)
            ->selectRaw("shop, COUNT(*) as cnt,
                ROUND(SUM(total),0) as sum_total,
                ROUND(MAX(total),0) as max_total,
                ROUND(MIN(total),0) as min_total,
                ROUND(AVG(total),0) as avg_total,
                ROUND(AVG(CASE WHEN date_open IS NOT NULL AND date_close > date_open
                               THEN {$timeExpr} END), 0) as avg_sec")
            ->groupBy('shop')
            ->orderByDesc('sum_total')
            ->get();

        $cashierStats = (clone $base)
            ->selectRaw("cashier, shop, COUNT(*) as cnt,
                ROUND(SUM(total),0) as sum_total,
                ROUND(MAX(total),0) as max_total,
                ROUND(MIN(total),0) as min_total,
                ROUND(AVG(total),0) as avg_total,
                ROUND(AVG(CASE WHEN date_open IS NOT NULL AND date_close > date_open
                               THEN {$timeExpr} END), 0) as avg_sec")
            ->groupBy('cashier', 'shop')
            ->orderByDesc('sum_total')
            ->get();

        $fmtTime = function (int|float|null $sec): string {
            $sec = (int) ($sec ?? 0);
            if ($sec <= 0) return '—';
            if ($sec < 60) return $sec . ' son';
            $m = intdiv($sec, 60);
            $s = $sec % 60;
            if ($m < 60) return $m . ' daq' . ($s > 0 ? ' ' . $s . ' son' : '');
            $h  = intdiv($m, 60);
            $m2 = $m % 60;
            return $h . ' soat' . ($m2 > 0 ? ' ' . $m2 . ' daq' : '');
        };

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
              . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // Bold header style
        $xml .= '<Styles>'
              . '<Style ss:ID="H"><Font ss:Bold="1" ss:Size="11"/><Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/></Style>'
              . '<Style ss:ID="N"><NumberFormat ss:Format="#,##0"/></Style>'
              . '</Styles>' . "\n";

        // Sheet builder closure
        $cell = fn(string $type, mixed $val) =>
            '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars((string) $val, ENT_XML1) . '</Data></Cell>';
        $hcell = fn(string $val) =>
            '<Cell ss:StyleID="H"><Data ss:Type="String">' . htmlspecialchars($val, ENT_XML1) . '</Data></Cell>';
        $ncell = fn(mixed $val) =>
            '<Cell ss:StyleID="N"><Data ss:Type="Number">' . (int) $val . '</Data></Cell>';

        // ── Sheet 1: Do'konlar ─────────────────────────────────────────────
        $xml .= '<Worksheet ss:Name="Dokonlar"><Table>' . "\n";
        $xml .= '<Row>'
              . $hcell("Do'kon") . $hcell('Cheklar') . $hcell('Jami (UZS)')
              . $hcell('Eng katta') . $hcell('Eng kichik') . $hcell("O'rtacha")
              . $hcell("O'rt. vaqt")
              . '</Row>' . "\n";

        foreach ($shopStats as $r) {
            $xml .= '<Row>'
                  . $cell('String', $r->shop)
                  . $ncell($r->cnt)
                  . $ncell($r->sum_total)
                  . $ncell($r->max_total)
                  . $ncell($r->min_total)
                  . $ncell($r->avg_total)
                  . $cell('String', $fmtTime($r->avg_sec))
                  . '</Row>' . "\n";
        }
        $xml .= '</Table></Worksheet>' . "\n";

        // ── Sheet 2: Kassirlar ─────────────────────────────────────────────
        $xml .= '<Worksheet ss:Name="Kassirlar"><Table>' . "\n";
        $xml .= '<Row>'
              . $hcell('Kassir') . $hcell("Do'kon") . $hcell('Cheklar')
              . $hcell('Jami (UZS)') . $hcell('Eng katta') . $hcell('Eng kichik')
              . $hcell("O'rtacha") . $hcell("O'rt. vaqt")
              . '</Row>' . "\n";

        foreach ($cashierStats as $r) {
            $xml .= '<Row>'
                  . $cell('String', $r->cashier)
                  . $cell('String', $r->shop)
                  . $ncell($r->cnt)
                  . $ncell($r->sum_total)
                  . $ncell($r->max_total)
                  . $ncell($r->min_total)
                  . $ncell($r->avg_total)
                  . $cell('String', $fmtTime($r->avg_sec))
                  . '</Row>' . "\n";
        }
        $xml .= '</Table></Worksheet>' . "\n";

        $xml .= '</Workbook>';

        $filename = 'receipts-analytics-' . date('Y-m-d') . '.xls';

        return response($xml)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }
}
