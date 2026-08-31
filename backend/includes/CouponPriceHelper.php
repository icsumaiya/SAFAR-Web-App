<?php
// Shared helper so every traveler-facing page shows the same "X% OFF"
// badge + discounted price whenever an admin has an active site-wide
// coupon — without the traveler needing to type a code just to see it.
// The code is still required at actual checkout to lock in the discount.

/**
 * Finds the single best currently-active coupon (highest discount_value),
 * or null if none are active today.
 */
function getActiveSiteWideCoupon(PDO $pdo): ?array
{
    $stmt = $pdo->prepare(
        "SELECT * FROM coupons
         WHERE is_active = 1 AND start_date <= CURDATE() AND expiry_date >= CURDATE()
         ORDER BY discount_value DESC LIMIT 1"
    );
    $stmt->execute();
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    return $coupon !== false ? $coupon : null;
}

/**
 * Applies a coupon (from getActiveSiteWideCoupon) to a single price.
 *
 * @return array{has_discount:bool, original:float, discounted:float, percent_label:float, code:?string}
 */
function applyCouponToPrice(float $price, ?array $coupon): array
{
    $result = [
        'has_discount' => false,
        'original' => $price,
        'discounted' => $price,
        'percent_label' => 0,
        'code' => null,
    ];

    if (!$coupon || $price < (float) $coupon['min_booking_amount']) {
        return $result;
    }

    if ($coupon['discount_type'] === 'percentage') {
        $discountAmount = $price * ((float) $coupon['discount_value'] / 100);
    if ($coupon['max_discount_amount'] !== null && $coupon['max_discount_amount'] !== '') {
    $discountAmount = min($discountAmount, (float) $coupon['max_discount_amount']);
    }
        $percentLabel = (float) $coupon['discount_value'];
    } else {
        $discountAmount = (float) $coupon['discount_value'];
        $percentLabel = $price > 0 ? round(($discountAmount / $price) * 100) : 0;
    }

    $result['has_discount'] = true;
    $result['discounted'] = max(0, $price - $discountAmount);
    $result['percent_label'] = $percentLabel;
    $result['code'] = $coupon['code'];

    return $result;
}