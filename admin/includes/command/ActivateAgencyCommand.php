<?php
// ActivateAgencyCommand.php

class ActivateAgencyCommand implements Command {
    private PDO $pdo;
    private int $agencyId;

    public function __construct(PDO $pdo, int $agencyId) {
        $this->pdo = $pdo;
        $this->agencyId = $agencyId;
    }

    public function execute(): void {
        $stmt = $this->pdo->prepare("UPDATE agencies SET status = 'verified' WHERE id = ?");
        $stmt->execute([$this->agencyId]);
    }
}
?>