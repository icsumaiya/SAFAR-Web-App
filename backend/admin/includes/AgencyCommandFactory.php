<?php
// Picks the correct Command (Command pattern) based on the requested action
// string. Extracted from admin/agencies.php's match block so the "which
// command for which action" decision logic can be unit tested (with a
// mocked PDO) without needing header()/exit()/a real database.

class AgencyCommandFactory
{
    public static function build(string $action, PDO $pdo, int $agencyId): ?Command
    {
        return match ($action) {
            'verify' => new ApproveAgencyCommand($pdo, $agencyId),
            'reject' => new RejectAgencyCommand($pdo, $agencyId),
            'unverify' => new UnverifyAgencyCommand($pdo, $agencyId),
            'suspend' => new SuspendAgencyCommand($pdo, $agencyId),
            'activate' => new ActivateAgencyCommand($pdo, $agencyId),
            default => null,
        };
    }
}