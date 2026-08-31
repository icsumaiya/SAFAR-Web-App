<?php
// Extracted from api/admin/agency-profile.php so the POST (profile update)
// and GET (full profile view) orchestration logic can be unit tested with
// a mocked PDO, without needing header()/http_response_code()/exit() or a
// real database. Reuses AgencyDetailsService, AgencyProfileValidator and
// ReviewService — nothing here duplicates their logic.

class AgencyProfileApiHandler
{
    private AgencyDetailsService $details;
    private ReviewService $reviews;

    public function __construct(PDO $pdo)
    {
        $this->details = new AgencyDetailsService($pdo);
        $this->reviews = new ReviewService($pdo);
    }

    /**
     * @param array $input decoded JSON body
     * @return array{status:int, body:array}
     */
    public function handlePost(array $input): array
    {
        $agencyId = (int) ($input['agency_id'] ?? 0);

        if ($agencyId === 0) {
            return [
                'status' => 422,
                'body' => ['success' => false, 'error' => 'agency_id is required.'],
            ];
        }

        if ($this->details->getAgency($agencyId) === null) {
            return [
                'status' => 404,
                'body' => ['success' => false, 'error' => 'Agency not found.'],
            ];
        }

        $error = AgencyProfileValidator::validate($input);
        if ($error !== '') {
            return [
                'status' => 422,
                'body' => ['success' => false, 'error' => $error],
            ];
        }

        $this->details->updateProfile($agencyId, $input);

        return [
            'status' => 200,
            'body' => ['success' => true, 'message' => 'Profile updated.'],
        ];
    }

    /**
     * @param array $query $_GET (agency_id)
     * @return array{status:int, body:array}
     */
    public function handleGet(array $query): array
    {
        $agencyId = (int) ($query['agency_id'] ?? 0);

        if ($agencyId === 0) {
            return [
                'status' => 422,
                'body' => ['success' => false, 'error' => 'agency_id is required.'],
            ];
        }

        $agency = $this->details->getAgency($agencyId);

        if ($agency === null) {
            return [
                'status' => 404,
                'body' => ['success' => false, 'error' => 'Agency not found.'],
            ];
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'agency' => $agency,
                'booking_stats' => $this->details->getBookingStats($agencyId),
                'package_count' => count($this->details->getPackages($agencyId)),
                'revenue' => $this->details->getRevenue($agencyId),
                'rating' => $this->reviews->getForAgency($agencyId),
            ],
        ];
    }
}