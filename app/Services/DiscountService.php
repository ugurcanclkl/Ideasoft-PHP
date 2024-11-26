<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;

class DiscountService
{
    protected $rules;

    public function __construct()
    {
        $this->rules = $this->getDiscountRules();
    }

    public function calculateDiscounts(Order $order)
    {
        $discounts = [];
        $totalDiscount = 0;

        // Apply "Buy X Get Y Free" rule
        $buyXGetYDiscounts = $this->applyBuyXGetYFreeDiscount($order);
        $discounts = array_merge($discounts, $buyXGetYDiscounts);
        $totalDiscount += array_sum(array_column($buyXGetYDiscounts, 'discountAmount'));

        // Apply "Minimum Items to Discount" rule
        $minItemsDiscounts = $this->applyMinItemsToDiscount($order);
        $discounts = array_merge($discounts, $minItemsDiscounts);
        $totalDiscount += array_sum(array_column($minItemsDiscounts, 'discountAmount'));

        // Apply the highest percentage discount threshold
        $thresholdDiscount = $this->applyHighestThresholdDiscount($order->total - $totalDiscount);
        if ($thresholdDiscount) {
            $discounts[] = $thresholdDiscount;
            $totalDiscount += $thresholdDiscount['discountAmount'];
        }

        $discountedTotal = $order->total - $totalDiscount;

        return [
            'orderId' => $order->id,
            'discounts' => $this->formatDiscounts($discounts),
            'totalDiscount' => number_format($totalDiscount, 2),
            'discountedTotal' => number_format($discountedTotal, 2),
        ];
    }

    private function formatDiscounts(array $discounts)
    {
        return array_map(function ($discount) {
            $discount['discountAmount'] = number_format($discount['discountAmount'], 2);
            return $discount;
        }, $discounts);
    }

    private function applyBuyXGetYFreeDiscount(Order $order)
    {
        $discounts = [];
        $rules = $this->rules['buy_x_get_y_free'] ?? [];

        foreach ($rules as $rule) {
            if (isset($rule['required_units'], $rule['free_units'], $rule['category'])) {
                foreach ($rule['category'] as $categoryId) {

                    $categoryItems = $order->orderItems->filter(function ($item) use ($categoryId) {
                        return $item->product->category == $categoryId;
                    });

                    foreach ($categoryItems as $item) {
                        $freeUnits = floor($item->quantity / $rule['required_units']) * $rule['free_units'];
                        if ($freeUnits > 0) {
                            $discountAmount = $freeUnits * $item->unit_price;
                            $discounts[] = [
                                'discountReason' => 'BUY_X_GET_Y_FREE',
                                'discountAmount' => $discountAmount,
                                'productId' => $item->product_id,
                            ];
                        }
                    }
                }
            }
        }

        return $discounts;
    }

    private function applyMinItemsToDiscount(Order $order)
    {
        $discounts = [];
        $rules = $this->rules['min_items_to_discount'] ?? [];

        foreach ($rules as $rule) {
            if (isset($rule['discount_rate'], $rule['min_items'], $rule['category'])) {
                foreach ($rule['category'] as $categoryId) {
                    $categoryItems = $order->orderItems->filter(function ($item) use ($categoryId) {
                        return $item->product->category == $categoryId;
                    });

                    if ($categoryItems->count() >= $rule['min_items']) {
                        $cheapestItem = $categoryItems->sortBy('unit_price')->first();
                        $discountAmount = $cheapestItem->unit_price * $rule['discount_rate'];
                        $discounts[] = [
                            'discountReason' => 'CATEGORY_PERCENTAGE_DISCOUNT',
                            'discountAmount' => $discountAmount,
                            'productId' => $cheapestItem->product_id,
                        ];
                    }
                }
            }
        }

        return $discounts;
    }

    private function applyHighestThresholdDiscount($totalAfterOtherDiscounts)
    {
        $thresholds = $this->rules['percentage_discounts'] ?? [];
        $highestApplicableThreshold = null;

        foreach ($thresholds as $threshold) {
            if ($totalAfterOtherDiscounts >= $threshold['threshold']) {
                $highestApplicableThreshold = $threshold;
            }
        }

        if ($highestApplicableThreshold) {
            $discountAmount = $totalAfterOtherDiscounts * $highestApplicableThreshold['discount_rate'];
            return [
                'discountReason' => 'HIGHEST_THRESHOLD_DISCOUNT',
                'discountAmount' => $discountAmount,
                'threshold' => $highestApplicableThreshold['threshold'],
            ];
        }

        return null;
    }

    private function getDiscountRules()
    {
        $setting = Setting::where('key', 'discount_rules')->first();
        return $setting ? json_decode($setting->value, true) : [];
    }
}
