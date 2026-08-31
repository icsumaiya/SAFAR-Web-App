<?php
// Extracted from api/admin/featured-packages.php so the POST (feature/
// recommend/offer toggles) and GET (all four homepage-section lists)
// orchestration logic can be unit tested with a mocked PDO, without
// needing header()/exit() or a real database. Reuses FeaturedPackageService
// and FeaturedPackageValidator — nothing duplicated.

class FeaturedPackageApiHandler
{
    private PDO $pdo;
    private FeaturedPackageService $service;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->service = new FeaturedPackageService($pdo);
    }

    public function handlePost(array $input): array
    {
        $packageId = (int) ($input['package_id'] ?? 0);
        $action = $input['action'] ?? '';

        if ($packageId === 0) {
            return ['status' => 422, 'body' => ['success' => false, 'error' => 'package_id is required.']];
        }

        switch ($action) {
            case 'set_featured':
                $this->service->setFeatured($packageId, true);
                break;

            case 'unset_featured':
                $this->service->setFeatured($packageId, false);
                break;

            case 'set_recommended':
                $this->service->setRecommended($packageId, true);
                break;

            case 'unset_recommended':
                $this->service->setRecommended($packageId, false);
                break;

            case 'set_offer':
                $error = FeaturedPackageValidator::validateOffer($input);

                if ($error !== '') {
                    return ['status' => 422, 'body' => ['success' => false, 'error' => $error]];
                }

                $this->service->setSpecialOffer(
                    $packageId,
                    (float) $input['offer_discount_percentage'],
                    $input['offer_expiry']
                );
                break;

            case 'clear_offer':
                $this->service->clearSpecialOffer($packageId);
                break;

            default:
                return ['status' => 422, 'body' => ['success' => false, 'error' => 'Unknown action.']];
        }

        return ['status' => 200, 'body' => ['success' => true, 'message' => 'Updated.']];
    }

    public function handleGet(): array
    {
        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'featured' => $this->service->getFeatured(),
                'recommended' => $this->service->getRecommended(),
                'special_offers' => $this->service->getSpecialOffers(),
                'popular' => $this->service->getPopular(),
            ],
        ];
    }
}