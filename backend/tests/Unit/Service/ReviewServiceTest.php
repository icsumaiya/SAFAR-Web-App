<?php

use PHPUnit\Framework\TestCase;

final class ReviewServiceTest extends TestCase
{
    // ---- guardSubmission ----

    public function testGuardSubmissionFailsWhenBookingNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new ReviewService($pdo);
        $result = $service->guardSubmission(1, 99);

        $this->assertSame('Booking not found.', $result['error']);
        $this->assertNull($result['package_id']);
    }

    public function testGuardSubmissionFailsWhenBookingNotApproved(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(['status' => 'pending', 'package_id' => 3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new ReviewService($pdo);
        $result = $service->guardSubmission(1, 5);

        $this->assertSame('You can only review a completed booking.', $result['error']);
    }

    public function testGuardSubmissionFailsWhenAlreadyReviewed(): void
    {
        $bookingStmt = $this->createMock(PDOStatement::class);
        $bookingStmt->method('fetch')->willReturn(['status' => 'approved', 'package_id' => 3]);

        $reviewStmt = $this->createMock(PDOStatement::class);
        $reviewStmt->method('fetch')->willReturn(['id' => 9]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($bookingStmt, $reviewStmt);

        $service = new ReviewService($pdo);
        $result = $service->guardSubmission(1, 5);

        $this->assertSame('You have already reviewed this booking.', $result['error']);
    }

    public function testGuardSubmissionPassesWhenEligible(): void
    {
        $bookingStmt = $this->createMock(PDOStatement::class);
        $bookingStmt->method('fetch')->willReturn(['status' => 'approved', 'package_id' => 3]);

        $reviewStmt = $this->createMock(PDOStatement::class);
        $reviewStmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($bookingStmt, $reviewStmt);

        $service = new ReviewService($pdo);
        $result = $service->guardSubmission(1, 5);

        $this->assertSame('', $result['error']);
        $this->assertSame(3, $result['package_id']);
    }

    // ---- submitReview ----

    public function testSubmitReviewInsertsWithNullCommentWhenEmpty(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([1, 5, 3, 4, null]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO reviews'))
            ->willReturn($stmt);

        (new ReviewService($pdo))->submitReview(1, 5, 3, 4, '');
    }

    public function testSubmitReviewInsertsWithComment(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([1, 5, 3, 4, 'Loved it!']);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new ReviewService($pdo))->submitReview(1, 5, 3, 4, 'Loved it!');
    }

    // ---- setStatus ----

    public function testSetStatusUpdatesReview(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['hidden', 9]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE reviews SET status'))
            ->willReturn($stmt);

        (new ReviewService($pdo))->setStatus(9, 'hidden');
    }

    // ---- getForPackage ----

    public function testGetForPackageComputesAverageCountAndDistribution(): void
    {
        $summaryStmt = $this->createMock(PDOStatement::class);
        $summaryStmt->expects($this->once())->method('execute')->with([3]);
        $summaryStmt->method('fetch')->willReturn(['avg_rating' => '4.3333', 'review_count' => '3']);

        $distStmt = $this->createMock(PDOStatement::class);
        $distStmt->expects($this->once())->method('execute')->with([3]);
        $distStmt->method('fetchAll')->willReturn([
            ['rating' => 5, 'count' => 2],
            ['rating' => 4, 'count' => 1],
        ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($summaryStmt, $distStmt);

        $service = new ReviewService($pdo);
        $result = $service->getForPackage(3);

        $this->assertSame(4.33, $result['average_rating']);
        $this->assertSame(3, $result['review_count']);
        $this->assertSame(
            [1 => 0, 2 => 0, 3 => 0, 4 => 1, 5 => 2],
            $result['distribution']
        );
    }

    public function testGetForPackageHandlesNoReviewsYet(): void
    {
        $summaryStmt = $this->createMock(PDOStatement::class);
        $summaryStmt->method('fetch')->willReturn(['avg_rating' => '0', 'review_count' => '0']);

        $distStmt = $this->createMock(PDOStatement::class);
        $distStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($summaryStmt, $distStmt);

        $service = new ReviewService($pdo);
        $result = $service->getForPackage(3);

        $this->assertSame(0.0, $result['average_rating']);
        $this->assertSame(0, $result['review_count']);
        $this->assertSame([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], $result['distribution']);
    }

    // ---- getForAgency ----

    public function testGetForAgencyComputesAverageAndCount(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([7]);
        $stmt->method('fetch')->willReturn(['avg_rating' => '4.6667', 'review_count' => '9']);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('p.agency_id = ?'))
            ->willReturn($stmt);

        $result = (new ReviewService($pdo))->getForAgency(7);

        $this->assertSame(4.67, $result['average_rating']);
        $this->assertSame(9, $result['review_count']);
    }

    public function testGetForAgencyHandlesNoReviewsYet(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(['avg_rating' => '0', 'review_count' => '0']);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $result = (new ReviewService($pdo))->getForAgency(7);

        $this->assertSame(0.0, $result['average_rating']);
        $this->assertSame(0, $result['review_count']);
    }

    // ---- getStats ----
    public function testGetStatsComputesRealCounts(): void
    {
        $countsStmt = $this->createMock(PDOStatement::class);
        $countsStmt->method('fetchAll')->willReturn(['visible' => 8, 'hidden' => 2]);

        $avgStmt = $this->createMock(PDOStatement::class);
        $avgStmt->method('fetchColumn')->willReturn('4.1');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls($countsStmt, $avgStmt);

        $service = new ReviewService($pdo);
        $stats = $service->getStats();

        $this->assertSame(10, $stats['total_count']);
        $this->assertSame(8, $stats['visible_count']);
        $this->assertSame(2, $stats['hidden_count']);
        $this->assertSame(4.1, $stats['average_rating']);
    }

    public function testGetStatsHandlesNoReviewsYet(): void
    {
        $countsStmt = $this->createMock(PDOStatement::class);
        $countsStmt->method('fetchAll')->willReturn([]);

        $avgStmt = $this->createMock(PDOStatement::class);
        $avgStmt->method('fetchColumn')->willReturn('0');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls($countsStmt, $avgStmt);

        $service = new ReviewService($pdo);
        $stats = $service->getStats();

        $this->assertSame(0, $stats['total_count']);
        $this->assertSame(0.0, $stats['average_rating']);
    }
}