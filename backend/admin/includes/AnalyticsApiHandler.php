<?php
// Extracted from api/admin/analytics.php so the response-assembly logic
// (which AnalyticsService calls feed which JSON keys) can be unit tested
// with a mocked PDO, without needing header()/exit() or a real database.

class AnalyticsApiHandler
{
    private AnalyticsService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new AnalyticsService($pdo);
    }

    /**
     * @return array{success:bool, summary:array, charts:array}
     */
    public function handle(): array
    {
        return [
            'success' => true,
            'summary' => $this->service->getSummary(),
            'charts' => [
                'monthly_bookings' => $this->service->getMonthlyBookings(),
                'monthly_revenue' => $this->service->getMonthlyRevenue(),
                'booking_status_distribution' => $this->service->getBookingStatusDistribution(),
                'user_growth' => $this->service->getUserGrowth(),
                'popular_packages' => $this->service->getPopularPackages(),
                'popular_destinations' => $this->service->getPopularDestinations(),
                'top_agencies' => $this->service->getTopAgencies(),
                'revenue_trend' => $this->service->getRevenueTrend(),
            ],
        ];
    }
}