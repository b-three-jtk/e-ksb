<?php

namespace Database\Seeders;

use App\Models\AmdkStockIncoming;
use App\Models\AmdkStockIncomingItem;
use App\Models\AmdkProduct;
use App\Models\User;
use Illuminate\Database\Seeder;

class AmdkStockIncomingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 stock incoming records with items
        for ($i = 1; $i <= 10; $i++) {
            $stockIncoming = AmdkStockIncoming::create([
                'receipt_number' => 'IN' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'notes' => 'Barang masuk dari supplier - batch ' . $i,
                'incoming_date' => now()->subDays(random_int(1, 30))->format('Y-m-d'),
                'receive_receipt' => 'REC-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'updated_by' => User::inRandomOrder()->first()->id,
            ]);

            // Add 2-5 items per stock incoming
            $itemCount = random_int(2, 5);
            $products = AmdkProduct::inRandomOrder()->limit($itemCount)->get();

            foreach ($products as $product) {
                AmdkStockIncomingItem::create([
                    'amdk_stock_incoming_id' => $stockIncoming->id,
                    'amdk_product_id' => $product->id,
                    'quantity' => random_int(10, 100),
                    'unit_measure' => collect(['PCS', 'BOX', 'DUS', 'KG', 'LITER'])->random(),
                ]);
            }
        }
    }
}
