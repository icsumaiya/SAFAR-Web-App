<?php
// DB operations for Admin Commission & Revenue management: syncing new
// successful payments into commission records, summary/by-agency stats,
// and the configurable platform commission percentage. Extracted as a
// service so it can be unit tested with a mocked PDO instead of a real
// database connection.

class CommissionService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Finds successful payments that don't have a commission record yet,
     * computes gross/commission/agency-earning at the *current* platform
     * rate, and inserts one commission row per payment. Never overwrites
     * existing commission records (so past records keep the rate that was
     * active when they were created).
     *
     * @return int number of new commission records created
     */
    public function syncCommissions(): int
    {
        $percentage = $this->getPercentage();

        $stmt = $this->pdo->query(
            "SELECT pay.id AS payment_id, pay.booking_id, pay.amount, p.agency_id
             FROM payments pay
             JOIN bookings b ON pay.booking_id = b.id
             JOIN packages p ON b.package_id = p.id
             LEFT JOIN commissions c ON c.payment_id = pay.id
             WHERE pay.status = 'successful' AND c.id IS NULL"
        );
        $pending = $stmt->fetchAll();

        if (count($pending) === 0) {
            return 0;
        }

        $insert = $this->pdo->prepare(
            "INSERT INTO commissions
                (booking_id, payment_id, agency_id, gross_amount, commission_percentage, commission_amount, agency_earning)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($pending as $row) {
            $gross = (float) $row['amount'];
            $commissionAmount = round($gross * ($percentage / 100), 2);
            $agencyEarning = round($gross - $commissionAmount, 2);

            $insert->execute([
                $row['booking_id'],
                $row['payment_id'],
                $row['agency_id'],
                $gross,
                $percentage,
                $commissionAmount,
                $agencyEarning,
            ]);
        }

        return count($pending);
    }

    /**
     * @return array{total_sales:float, total_commission:float, total_agency_earnings:float}
     */
    public function getSummary(): array
    {
        $row = $this->pdo->query(
            "SELECT
                COALESCE(SUM(gross_amount), 0) AS total_sales,
                COALESCE(SUM(commission_amount), 0) AS total_commission,
                COALESCE(SUM(agency_earning), 0) AS total_agency_earnings
             FROM commissions"
        )->fetch();

        return [
            'total_sales' => (float) $row['total_sales'],
            'total_commission' => (float) $row['total_commission'],
            'total_agency_earnings' => (float) $row['total_agency_earnings'],
        ];
    }

    /**
     * @return array<int, array{company_name:string, gross:float, commission:float, earning:float}>
     */
    public function getByAgency(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                a.company_name,
                SUM(c.gross_amount) AS gross,
                SUM(c.commission_amount) AS commission,
                SUM(c.agency_earning) AS earning
             FROM commissions c
             JOIN agencies a ON c.agency_id = a.id
             GROUP BY a.id, a.company_name
             ORDER BY gross DESC"
        );

        return $stmt->fetchAll();
    }

    public function getPercentage(): float
    {
        $stmt = $this->pdo->query("SELECT commission_percentage FROM platform_settings WHERE id = 1");
        $value = $stmt->fetchColumn();

        // Falls back to a sane default if the settings row hasn't been created yet.
        return $value !== false ? (float) $value : 10.0;
    }

    public function updatePercentage(float $percentage): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO platform_settings (id, commission_percentage)
             VALUES (1, ?)
             ON DUPLICATE KEY UPDATE commission_percentage = VALUES(commission_percentage), updated_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$percentage]);
    }
}