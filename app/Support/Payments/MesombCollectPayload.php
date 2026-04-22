<?php

namespace App\Support\Payments;

use App\Models\Course;

/**
 * Builds the array passed to {@see \App\Support\Payments\Contracts\PaymentGateway::collect()}.
 * Matches program checkout ({@see \App\Http\Controllers\PaymentController::store}) so MeSomb sees the same shape.
 */
final class MesombCollectPayload
{
    /**
     * Single-line product collect — same keys as program payments.
     *
     * @return array<string, mixed>
     */
    public static function singleProduct(
        string $payerPhone,
        int $amount,
        string $provider,
        string $currency,
        string $customerEmail,
        string $customerFirstName,
        string $customerLastName,
        string $productId,
        string $productName,
        string $productCategory,
        float $productLineAmount,
    ): array {
        $country = (string) config('services.mesomb.country', 'CM');

        return [
            'payer' => $payerPhone,
            'amount' => $amount,
            'service' => $provider,
            'country' => $country,
            'currency' => $currency,
            'customer' => [
                'email' => $customerEmail,
                'first_name' => $customerFirstName,
                'last_name' => $customerLastName,
                'country' => $country,
            ],
            'products' => [[
                'id' => $productId,
                'name' => $productName,
                'category' => $productCategory,
                'quantity' => 1,
                'amount' => $productLineAmount,
            ]],
        ];
    }

    /**
     * Product category for course payments: optional env (match a working program category on MeSomb),
     * else the course category slug (kebab-case, same style as {@see \App\Models\Program::$category}),
     * else the same default as typical program records.
     */
    public static function courseProductCategory(Course $course): string
    {
        $configured = config('services.mesomb.course_product_category');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $course->loadMissing('category');

        return (string) ($course->category?->slug ?? 'professional-training');
    }
}
