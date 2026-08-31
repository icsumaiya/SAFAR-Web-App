<?php
// Extracted from api/admin/coupons.php so the POST (create/update/
// activate/deactivate/delete) and GET (search/list/pagination)
// orchestration logic can be unit tested with a mocked PDO, without
// needing header()/exit() or a real database. Reuses CouponService/
// CouponValidator/CouponSearchQueryBuilder — nothing duplicated.

class CouponApiHandler
{
    private PDO $pdo;
    private CouponService $service;

    public function __construct(PDO $pdo, CouponService $service)
    {
        $this->pdo = $pdo;
        $this->service = $service;
    }

    public function handlePost(array $input): array
    {
        $action = $input['action'] ?? 'create';

        if ($action === 'create' || $action === 'update') {
            $error = CouponValidator::validateCouponData($input);

            if ($error !== '') {
                return ['status' => 422, 'body' => ['success' => false, 'error' => $error]];
            }

            if ($action === 'create') {
                $this->service->create($input);
            } else {
                $this->service->update((int) ($input['id'] ?? 0), $input);
            }

            return ['status' => 200, 'body' => ['success' => true, 'message' => 'Coupon saved.']];
        }

        if ($action === 'activate' || $action === 'deactivate') {
            $this->service->setActive((int) ($input['id'] ?? 0), $action === 'activate');
            return ['status' => 200, 'body' => ['success' => true, 'message' => 'Coupon status updated.']];
        }

        if ($action === 'delete') {
            $deleted = $this->service->delete((int) ($input['id'] ?? 0));

            if (!$deleted) {
                return [
                    'status' => 422,
                    'body' => [
                        'success' => false,
                        'error' => 'This coupon has already been used and cannot be deleted. Deactivate it instead.',
                    ],
                ];
            }

            return ['status' => 200, 'body' => ['success' => true, 'message' => 'Coupon deleted.']];
        }

        return ['status' => 422, 'body' => ['success' => false, 'error' => 'Unknown action.']];
    }

    public function handleGet(array $query): array
    {
        $search = trim($query['search'] ?? '');
        $statusFilter = $query['status'] ?? 'all';
        $page = (int) ($query['page'] ?? 1);

        $built = CouponSearchQueryBuilder::build($search, $statusFilter, $page);

        $stmt = $this->pdo->prepare($built['query']);
        $stmt->execute($built['params']);
        $coupons = $stmt->fetchAll();

        $countStmt = $this->pdo->prepare($built['countQuery']);
        $countStmt->execute($built['params']);
        $total = (int) $countStmt->fetchColumn();

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'data' => $coupons,
                'pagination' => [
                    'page' => $built['page'],
                    'per_page' => $built['perPage'],
                    'total' => $total,
                    'total_pages' => CouponSearchQueryBuilder::totalPages($total),
                ],
            ],
        ];
    }
}