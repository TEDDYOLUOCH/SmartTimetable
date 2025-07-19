<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
function nav_active($path) {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
}
// Set base path for links
$base = '';
if (strpos($_SERVER['REQUEST_URI'], 'SCHOOL PROJECT') !== false) $base = '/SCHOOL%20PROJECT/';
?>
<style>
    .navbar-custom {
        background: linear-gradient(90deg, #004080 0%, #1976d2 100%);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .navbar-custom .navbar-brand, .navbar-custom .nav-link, .navbar-custom .navbar-text {
        color: #fff !important;
        font-weight: 500;
    }
    .navbar-custom .nav-link.active, .navbar-custom .nav-link:hover {
        color: #ffc107 !important;
        background: rgba(255,255,255,0.10);
        border-radius: 4px;
        transition: background 0.2s, color 0.2s;
    }
    .navbar-custom .navbar-brand i {
        color: #ffc107;
    }
    .navbar-custom .navbar-toggler {
        border-color: #fff;
    }
    .navbar-custom .navbar-toggler-icon {
        background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba%28255,255,255,0.7%29' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
    }
    .navbar-custom .navbar-text {
        margin-right: 1rem;
    }
    .navbar-custom {
        position: sticky;
        top: 0;
        z-index: 1030;
    }
</style>
<nav class="navbar navbar-expand-lg navbar-custom border-bottom mb-3">
  <a class="navbar-brand d-flex align-items-center" href="<?php echo $base; ?>views/dashboard.php">
    <i class="fas fa-calendar-alt mr-2"></i> <span style="font-weight:bold;letter-spacing:1px;">Smart Timetable</span>
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav mr-auto">
      <?php if ($user): ?>
        <li class="nav-item <?php echo nav_active('views/dashboard.php'); ?>"><a class="nav-link" href="<?php echo $base; ?>views/dashboard.php">Dashboard</a></li>
        <?php if ($user['role'] === 'student'): ?>
          <li class="nav-item <?php echo nav_active('student/view-schedule.php'); ?>"><a class="nav-link" href="<?php echo $base; ?>student/view-schedule.php">My Timetable</a></li>
          <li class="nav-item <?php echo nav_active('student/enroll.php'); ?>"><a class="nav-link" href="<?php echo $base; ?>student/enroll.php">Enroll in Courses</a></li>
        <?php endif; ?>
        <?php if (in_array($user['role'], ['admin', 'lecturer'])): ?>
          <li class="nav-item <?php echo nav_active('views/manage-timetable.php'); ?>"><a class="nav-link" href="<?php echo $base; ?>views/manage-timetable.php">Manage Timetable</a></li>
          <li class="nav-item <?php echo nav_active('admin/user-management.php'); ?>"><a class="nav-link" href="<?php echo $base; ?>admin/user-management.php">User/Enrollment Management</a></li>
          <li class="nav-item <?php echo nav_active('views/analytics.php'); ?>"><a class="nav-link" href="<?php echo $base; ?>views/analytics.php">Analytics</a></li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
    <ul class="navbar-nav align-items-center">
      <?php if ($user): ?>
        <li class="nav-item"><span class="navbar-text"><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['role']); ?>)</span></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>views/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      <?php else: ?>
        <li class="nav-item <?php echo nav_active('views/login.php'); ?>"><a class="nav-link" href="<?php echo $base; ?>views/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
        <li class="nav-item <?php echo nav_active('views/register.php'); ?>"><a class="nav-link" href="<?php echo $base; ?>views/register.php"><i class="fas fa-user-plus"></i> Register</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>