<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('.claude/docs/receipt.json');
        $rows = json_decode(file_get_contents($path), true)['data'];

        // Skip UUIDs already in the table
        $existing = DB::table('receipts')->pluck('id')->flip();

        $receipts  = [];
        $items     = [];
        $payments  = [];
        $discounts = [];

        foreach ($rows as $r) {
            if (isset($existing[$r['id']])) {
                continue;
            }

            $receipts[] = [
                'id'         => $r['id'],
                'number'     => $r['number'],
                'date_open'  => $r['dateOpen'],
                'date_close' => $r['dateClose'],
                'type'       => $r['type'],
                'cashier'    => $r['cashier'],
                'status'     => $r['status'],
                'card'       => $r['card'] ?: null,
                'pos'        => $r['pos'],
                'total'      => $r['total'],
                'shop'       => $r['shop'],
                'shift'      => $r['shift'] ?? null,
            ];

            foreach ($r['items'] as $item) {
                $items[] = [
                    'receipt_id'    => $r['id'],
                    'code'          => $item['code'],
                    'name'          => $item['name'],
                    'price'         => $item['price'],
                    'total'         => $item['total'],
                    'discountTotal' => $item['discountTotal'],
                    'qty'           => (float) str_replace(',', '', (string) $item['qty']) / 1000,
                    'roundTotal'    => $item['roundTotal'],
                    'status'        => $item['status'] ? 1 : 0,
                    'no'            => $item['no'],
                ];
            }

            foreach ($r['payments'] as $p) {
                $payments[] = [
                    'receipt_id' => $r['id'],
                    'type'       => $p['type'],
                    'total'      => $p['total'],
                ];
            }

            foreach ($r['discounts'] as $d) {
                $discounts[] = [
                    'receipt_id' => $r['id'],
                    'receipt'    => $d['receipt'] ? 1 : 0,
                    'total'      => $d['total'],
                    'no'         => $d['no'] ?? null,
                ];
            }
        }

        if (empty($receipts)) {
            $this->command->info('ReceiptSeeder: nothing new to insert.');
            return;
        }

        DB::transaction(function () use ($receipts, $items, $payments, $discounts) {
            foreach (array_chunk($receipts, 100) as $chunk) {
                DB::table('receipts')->insert($chunk);
            }
            foreach (array_chunk($items, 500) as $chunk) {
                DB::table('items')->insert($chunk);
            }
            foreach (array_chunk($payments, 500) as $chunk) {
                DB::table('payments')->insert($chunk);
            }
            if ($discounts) {
                foreach (array_chunk($discounts, 500) as $chunk) {
                    DB::table('discounts')->insert($chunk);
                }
            }
        });

        $this->command->info('ReceiptSeeder: inserted ' . count($receipts) . ' receipts.');
    }
}
