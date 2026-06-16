<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    public function store(StoreReceiptRequest $request): JsonResponse
    {
        $incoming = $request->has('data')
            ? $request->validated('data')
            : [$request->validated()];

        // 1. Single query to find all already-imported UUIDs
        $incomingIds = array_column($incoming, 'id');
        $existing    = DB::table('receipts')
            ->whereIn('id', $incomingIds)
            ->pluck('id')
            ->flip()
            ->all();

        $toInsert = array_filter($incoming, fn($r) => !isset($existing[$r['id']]));
        $skipped  = array_filter($incoming, fn($r) =>  isset($existing[$r['id']]));

        if (!empty($toInsert)) {
            $receiptRows  = [];
            $itemRows     = [];
            $paymentRows  = [];
            $discountRows = [];

            foreach ($toInsert as $data) {
                $receiptRows[] = [
                    'id'         => $data['id'],
                    'number'     => $data['number'],
                    'date_open'  => $data['dateOpen'],
                    'date_close' => $data['dateClose'],
                    'type'       => $data['type'],
                    'cashier'    => $data['cashier'],
                    'status'     => $data['status'],
                    'card'       => $data['card'] ?: null,
                    'pos'        => $data['pos'],
                    'total'      => $data['total'],
                    'shop'       => $data['shop'],
                    'shift'      => $data['shift'] ?? null,
                ];

                foreach ($data['items'] ?? [] as $item) {
                    $itemRows[] = [
                        'receipt_id'    => $data['id'],
                        'code'          => $item['code'],
                        'name'          => $item['name'],
                        'price'         => $item['price'],
                        'total'         => $item['total'],
                        'discountTotal' => $item['discountTotal'],
                        'qty'           => $item['qty']/1000,
                        'roundTotal'    => $item['roundTotal'],
                        'status'        => $item['status'] ? 1 : 0,
                        'no'            => $item['no'],
                    ];
                }

                foreach ($data['payments'] ?? [] as $p) {
                    $paymentRows[] = [
                        'receipt_id' => $data['id'],
                        'type'       => $p['type'],
                        'total'      => $p['total'],
                    ];
                }

                foreach ($data['discounts'] ?? [] as $d) {
                    $discountRows[] = [
                        'receipt_id' => $data['id'],
                        'receipt'    => $d['receipt'] ? 1 : 0,
                        'total'      => $d['total'],
                        'no'         => $d['no'] ?? null,
                    ];
                }
            }

            // 2. Single transaction, 4 bulk inserts
            DB::transaction(function () use ($receiptRows, $itemRows, $paymentRows, $discountRows) {
                foreach (array_chunk($receiptRows,  200) as $chunk) DB::table('receipts')->insert($chunk);
                foreach (array_chunk($itemRows,     500) as $chunk) DB::table('items')->insert($chunk);
                foreach (array_chunk($paymentRows,  500) as $chunk) DB::table('payments')->insert($chunk);
                foreach (array_chunk($discountRows, 500) as $chunk) DB::table('discounts')->insert($chunk);
            });
        }

        $results = array_merge(
            array_map(fn($r) => ['id' => $r['id'], 'status' => 'created'],  array_values($toInsert)),
            array_map(fn($r) => ['id' => $r['id'], 'status' => 'skipped'], array_values($skipped)),
        );

        return response()->json(
            ['results' => $results],
            !empty($toInsert) ? 201 : 200
        );
    }
}
