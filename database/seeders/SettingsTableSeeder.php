<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'discount_rules'],
            [
                'value' => json_encode([
                    // Percentage-based discounts with multiple thresholds
                    'percentage_discounts' => [
                        ['threshold' => 500, 'discount_rate' => 0.05],  // 5% for orders over 500
                        ['threshold' => 1000, 'discount_rate' => 0.1], // 10% for orders over 1000
                        ['threshold' => 2000, 'discount_rate' => 0.15], // 15% for orders over 2000
                    ],

                    // "Buy X get Y free" rule
                    'buy_x_get_y_free' => [
                        [
                            'category' => [2],        // Categories eligible for "Buy X get Y free"
                            'required_units' => 6,    // Buy 6 units
                            'free_units' => 1,        // Get 1 free
                        ],
                    ],

                    // Percentage discount for minimum items
                    'min_items_to_discount' => [
                        [
                            'category' => [1, 3],     // Categories eligible for percentage discount
                            'discount_rate' => 0.2,   // 20% off
                            'min_items' => 2,         // Minimum 2 items required
                        ],
                    ],
                ]),
            ]
        );
    }
}
