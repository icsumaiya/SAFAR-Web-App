<?php
// Extracted from api/admin/commissions.php so the POST (update commission
// percentage) and GET (sync + list + stats) orchestration logic can be
// unit tested with a mocked PDO, without needing header()/exit() or a
// real database. Reuses CommissionService/CommissionValidator/
// CommissionSearchQueryBuilder — nothing duplicated.

class CommissionApiHandler
{
    private PDO $pdo;
    private CommissionService $service;

    public function __construct(PDO $pdo, CommissionService $service)
    {
        $this->pdo = $pdo;
        $this->service = $service;
    }

    /**
     * @param array $input decoded JSON body (commission_percentage)
     * @return array{status:int, body:array}
     */
    public function handlePost(array $input): array
    {
        $error = CommissionValidator::validatePercentage($input['commission_percentage'] ?? null);

        if ($error !== '') {
            return ['status' => 422, 'body' => ['success' => false, 'error' => $error]];
        }

        $this->service->updatePercentage((float) $input['commission_percentage']);

        return ['status' => 200, 'body' => ['success' => true, 'message' => 'Commission percentage updated.']];
    }

    /**
     * @param array $query $_GET (search, agency_id, page)
     * @return array{status:int, body:array}
     */
    public function handleGet(array $query): array
    {
        $synced = $this->service->syncCommissions();

        $search = trim($query['search'] ?? '');
        $agencyId = (int) ($query['agency_id'] ?? 0);
        $page = (int) ($query['page'] ?? 1);

        $built = CommissionSearchQueryBuilder::build($search, $agencyId, $page);

        $stmt = $this->pdo->prepare($built['query']);
        $stmt->execute($built['params']);
        $commissions = $stmt->fetchAll();

        $countStmt = $this->pdo->prepare($built['countQuery']);
        $countStmt->execute($built['params']);
        $total = (int) $countStmt->fetchColumn();

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'synced' => $synced,
                'data' => $commissions,
                'pagination' => [
                    'page' => $built['page'],
                    'per_page' => $built['perPage'],
                    'total' => $total,
                    'total_pages' => CommissionSearchQueryBuilder::totalPages($total),
                ],
                'summary' => $this->service->getSummary(),
                'by_agency' => $this->service->getByAgency(),
                'commission_percentage' => $this->service->getPercentage(),
            ],
        ];
    }
}