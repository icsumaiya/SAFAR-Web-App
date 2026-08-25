<?php
// DB operations for coupon management: CRUD, lookups, and usage tracking.
// Discount math itself lives in CouponValidator (pure, no DB). Extracted
// as a service so it can be unit tested with a mocked PDO.

class CouponService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO coupons
                (code, discount_type, discount_value, min_booking_amount, max_discount_amount, start_date, expiry_date, usage_limit, per_user_limit, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([
            strtoupper(trim($data['code'])),
            $data['discount_type'],
            $data['discount_value'],
            $data['min_booking_amount'] !== '' ? $data['min_booking_amount'] : 0,
            ($data['max_discount_amount'] ?? '') !== '' ? $data['max_discount_amount'] : null,
            $data['start_date'],
            $data['expiry_date'],
            ($data['usage_limit'] ?? '') !== '' ? $data['usage_limit'] : null,
            ($data['per_user_limit'] ?? '') !== '' ? $data['per_user_limit'] : 1,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE coupons SET
                code = ?, discount_type = ?, discount_value = ?, min_booking_amount = ?,
                max_discount_amount = ?, start_date = ?, expiry_date = ?, usage_limit = ?, per_user_limit = ?
             WHERE id = ?"
        );
        $stmt->execute([
            strtoupper(trim($data['code'])),
            $data['discount_type'],
            $data['discount_value'],
            $data['min_booking_amount'] !== '' ? $data['min_booking_amount'] : 0,
            ($data['max_discount_amount'] ?? '') !== '' ? $data['max_discount_amount'] : null,
            $data['start_date'],
            $data['expiry_date'],
            ($data['usage_limit'] ?? '') !== '' ? $data['usage_limit'] : null,
            ($data['per_user_limit'] ?? '') !== '' ? $data['per_user_limit'] : 1,
            $id,
        ]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->pdo->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    /**
     * Deletes a coupon only if it has never been used. Returns false
     * (without deleting) if the coupon has usage history, to preserve
     * the audit trail on past payments.
     */
    public function delete(int $id): bool
    {
        if ($this->countUsageForCoupon($id) > 0) {
            return false;
        }

        $stmt = $this->pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);

        return true;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([strtoupper(trim($code))]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function countUsageForCoupon(int $couponId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = ?");
        $stmt->execute([$couponId]);

        return (int) $stmt->fetchColumn();
    }

    public function countUsageForUser(int $couponId, int $travelerId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = ? AND traveler_id = ?");
        $stmt->execute([$couponId, $travelerId]);

        return (int) $stmt->fetchColumn();
    }

    public function recordUsage(int $couponId, int $bookingId, int $travelerId, float $discountAmount): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO coupon_usages (coupon_id, booking_id, traveler_id, discount_amount) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$couponId, $bookingId, $travelerId, $discountAmount]);
    }
}