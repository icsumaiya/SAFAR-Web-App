<?php
// PHPUnit bootstrap for SAFAR.
// Only loads pure class/interface definitions. Does NOT include any file
// that connects to the database, starts a session, redirects, or exit()s.

define('SAFAR_ROOT', dirname(__DIR__));

require_once SAFAR_ROOT . '/admin/includes/PackageFactory.php';
require_once SAFAR_ROOT . '/admin/includes/adapter/HotelDataAdapter.php';

require_once SAFAR_ROOT . '/admin/includes/strategy/FilterStrategy.php';
require_once SAFAR_ROOT . '/admin/includes/strategy/FilterContext.php';
require_once SAFAR_ROOT . '/admin/includes/strategy/LocationFilter.php';
require_once SAFAR_ROOT . '/admin/includes/strategy/PriceMaxFilter.php';
require_once SAFAR_ROOT . '/admin/includes/strategy/TypeFilter.php';

require_once SAFAR_ROOT . '/admin/includes/observer/BookingObserver.php';
require_once SAFAR_ROOT . '/admin/includes/observer/BookingSubject.php';
require_once SAFAR_ROOT . '/admin/includes/observer/AgencyStatsObserver.php';
require_once SAFAR_ROOT . '/admin/includes/observer/AdminStatsObserver.php';

require_once SAFAR_ROOT . '/admin/includes/command/Command.php';
require_once SAFAR_ROOT . '/admin/includes/command/ApproveAgencyCommand.php';
require_once SAFAR_ROOT . '/admin/includes/command/RejectAgencyCommand.php';
require_once SAFAR_ROOT . '/admin/includes/command/UnverifyAgencyCommand.php';
require_once SAFAR_ROOT . '/admin/includes/command/SuspendAgencyCommand.php';
require_once SAFAR_ROOT . '/admin/includes/command/ActivateAgencyCommand.php';

require_once SAFAR_ROOT . '/admin/includes/AgencyCommandFactory.php';
require_once SAFAR_ROOT . '/admin/includes/AgencySearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/AgencyDetailsService.php';

require_once SAFAR_ROOT . '/admin/includes/UserManagementValidator.php';
require_once SAFAR_ROOT . '/admin/includes/UserSearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/BookingManagementHelper.php';
require_once SAFAR_ROOT . '/admin/includes/BookingSearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/BookingDetailsService.php';

require_once SAFAR_ROOT . '/admin/includes/PaymentValidator.php';
require_once SAFAR_ROOT . '/admin/includes/PaymentSearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/PaymentService.php';
require_once SAFAR_ROOT . '/admin/includes/CancellationValidator.php';
require_once SAFAR_ROOT . '/admin/includes/CancellationSearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/CancellationService.php';
require_once SAFAR_ROOT . '/admin/includes/CommissionValidator.php';
require_once SAFAR_ROOT . '/admin/includes/CommissionSearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/CommissionService.php';
require_once SAFAR_ROOT . '/admin/includes/CouponValidator.php';
require_once SAFAR_ROOT . '/admin/includes/CouponSearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/CouponService.php';

require_once SAFAR_ROOT . '/admin/includes/facade/AdminFacade.php';

require_once SAFAR_ROOT . '/admin/includes/PackageValidator.php';
require_once SAFAR_ROOT . '/admin/includes/PackageSearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/BookingRequestValidator.php';
require_once SAFAR_ROOT . '/admin/includes/ListingFilterFactory.php';
require_once SAFAR_ROOT . '/admin/includes/TravelerBookingSearchQueryBuilder.php';
require_once SAFAR_ROOT . '/admin/includes/AdminDashboardService.php';

require_once SAFAR_ROOT . '/admin/includes/Database.php';
require_once SAFAR_ROOT . '/includes/JwtHelper.php';