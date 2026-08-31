<?php
// Extracted from api/admin/agencies.php so the POST (status-change) and
// GET (search/list) orchestration logic can be unit tested with a mocked
// PDO, without needing header()/http_response_code()/exit() or a real
// database. The page file just calls these and echoes the result.

class AgencyApiHandler
{
    /**
     * @param array $input decoded JSON body (agency_id, action)
     * @return array{status:int, body:array}
     */
    public static function handlePost(PDO $pdo, array $input): array
    {
        $agencyId = (int) ($input['agency_id'] ?? 0);
        $action = $input['action'] ?? '';

        if ($agencyId === 0 || $action === '') {
            return [
                'status' => 422,
                'body' => ['success' => false, 'error' => 'agency_id and action are required.'],
            ];
        }

        $command = AgencyCommandFactory::build($action, $pdo, $agencyId);

        if ($command === null) {
            return [
                'status' => 422,
                'body' => ['success' => false, 'error' => 'Unknown action.'],
            ];
        }

        $command->execute();

        return [
            'status' => 200,
            'body' => ['success' => true, 'message' => 'Agency status updated.'],
        ];
    }

    /**
     * @param array $query $_GET (search, filter_status)
     * @return array{status:int, body:array}
     */
    public static function handleGet(PDO $pdo, array $query): array
    {
        $search = trim($query['search'] ?? '');
        $filterStatus = $query['filter_status'] ?? 'all';

        $built = AgencySearchQueryBuilder::build($search, $filterStatus);
        $stmt = $pdo->prepare($built['query']);
        $stmt->execute($built['params']);
        $agencies = $stmt->fetchAll();

        return [
            'status' => 200,
            'body' => ['success' => true, 'data' => $agencies],
        ];
    }
}