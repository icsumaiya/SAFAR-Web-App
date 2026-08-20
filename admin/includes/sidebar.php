<?php
// $active must be set in the parent page before including this file.
// Example: $active = 'dashboard';
$active = $active ?? '';

function navClass($key, $active) {
    return $key === $active
        ? 'background: var(--bg-light); color: var(--primary); font-weight: 600;'
        : 'color: var(--text-main);';
}
?>
<aside style="width: 250px; background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--glass-border); position: sticky; top: 100px; align-self: flex-start;">
    <h3 style="margin-bottom: 20px; color: var(--primary);">Admin Menu</h3>
    <ul style="list-style: none; padding: 0; margin: 0;">
        <li style="margin-bottom: 8px;">
            <a href="index.php" style="display: block; padding: 10px; border-radius: 8px; transition: var(--transition); <?php echo navClass('dashboard', $active); ?>">
                <i class="fas fa-chart-line" style="width: 20px;"></i> Dashboard
            </a>
        </li>
        <li style="margin-bottom: 8px;">
            <a href="packages.php" style="display: block; padding: 10px; border-radius: 8px; transition: var(--transition); <?php echo navClass('packages', $active); ?>">
                <i class="fas fa-suitcase" style="width: 20px;"></i> Manage Packages
            </a>
        </li>
        <li style="margin-bottom: 8px;">
            <a href="agencies.php" style="display: block; padding: 10px; border-radius: 8px; transition: var(--transition); <?php echo navClass('agencies', $active); ?>">
                <i class="fas fa-building" style="width: 20px;"></i> Manage Agencies
            </a>
        </li>
        <li style="margin-bottom: 8px;">
            <a href="bookings.php" style="display: block; padding: 10px; border-radius: 8px; transition: var(--transition); <?php echo navClass('bookings', $active); ?>">
                <i class="fas fa-calendar-check" style="width: 20px;"></i> Manage Bookings
            </a>
        </li>
        <li style="margin-bottom: 8px;">
            <a href="users.php" style="display: block; padding: 10px; border-radius: 8px; transition: var(--transition); <?php echo navClass('users', $active); ?>">
                <i class="fas fa-users" style="width: 20px;"></i> Manage Users
            </a>
        </li>
        <li style="margin-bottom: 8px; border-top: 1px solid var(--glass-border); margin-top: 10px; padding-top: 10px;">
            <a href="packages.php?type=tour&new=1" style="display: block; padding: 10px; border-radius: 8px; transition: var(--transition); color: var(--text-main);">
                <i class="fas fa-plus" style="width: 20px;"></i> Create Tour
            </a>
        </li>
        <li style="margin-bottom: 8px;">
            <a href="packages.php?type=hotel&new=1" style="display: block; padding: 10px; border-radius: 8px; transition: var(--transition); color: var(--text-main);">
                <i class="fas fa-hotel" style="width: 20px;"></i> Create Hotel
            </a>
        </li>
        <li>
            <a href="../profile.php" style="display: block; padding: 10px; border-radius: 8px; transition: var(--transition); color: var(--text-main);">
                <i class="fas fa-user-shield" style="width: 20px;"></i> Admin Profile
            </a>
        </li>
    </ul>
</aside>