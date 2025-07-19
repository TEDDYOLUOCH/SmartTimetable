<?php
require_once '../includes/auth.php';
require_login();
$user = current_user();
if (!in_array($user['role'], ['admin', 'lecturer'])) {
    header('Location: dashboard.php');
    exit();
}
include '../templates/header.php';
require_once '../includes/db_connect.php';

// Fetch stats
function get_count($conn, $table, $where = '') {
    $sql = "SELECT COUNT(*) as cnt FROM $table" . ($where ? " WHERE $where" : '');
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    return $row['cnt'];
}
$total_students = get_count($conn, 'users', "role='student'");
$total_lecturers = get_count($conn, 'users', "role='lecturer'");
$total_admins = get_count($conn, 'users', "role='admin'");
$total_courses = get_count($conn, 'courses');
$total_classes = get_count($conn, 'timetables');
$total_notifications = get_count($conn, 'notifications');

// For charts: user role distribution
$user_roles = [
    'Students' => $total_students,
    'Lecturers' => $total_lecturers,
    'Admins' => $total_admins
];
// For charts: notification type distribution
$notif_types = ['reminder' => 0, 'update' => 0, 'cancel' => 0];
$res = $conn->query("SELECT type, COUNT(*) as cnt FROM notifications GROUP BY type");
while ($row = $res->fetch_assoc()) {
    $notif_types[$row['type']] = $row['cnt'];
}
// Enrollments per course
$enroll_labels = [];
$enroll_counts = [];
$res = $conn->query("SELECT c.name, COUNT(e.id) as cnt FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id GROUP BY c.id, c.name ORDER BY c.name");
while ($row = $res->fetch_assoc()) {
    $enroll_labels[] = $row['name'];
    $enroll_counts[] = (int)$row['cnt'];
}
// Timetable entries per month (last 12 months)
$month_labels = [];
$month_counts = [];
$res = $conn->query("SELECT DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as cnt FROM timetables GROUP BY month ORDER BY month DESC LIMIT 12");
$month_data = [];
while ($row = $res->fetch_assoc()) {
    $month_data[$row['month']] = (int)$row['cnt'];
}
$month_data = array_reverse($month_data, true);
foreach ($month_data as $month => $cnt) {
    $month_labels[] = $month;
    $month_counts[] = $cnt;
}
// Top 5 Most Active Lecturers
$lecturer_names = [];
$lecturer_counts = [];
$res = $conn->query("SELECT u.name, COUNT(t.id) as cnt FROM users u LEFT JOIN timetables t ON u.id = t.lecturer_id WHERE u.role='lecturer' GROUP BY u.id, u.name ORDER BY cnt DESC, u.name LIMIT 5");
while ($row = $res->fetch_assoc()) {
    $lecturer_names[] = $row['name'];
    $lecturer_counts[] = (int)$row['cnt'];
}
// Course Popularity (top 5)
$pop_labels = [];
$pop_counts = [];
$res = $conn->query("SELECT c.name, COUNT(e.id) as cnt FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id GROUP BY c.id, c.name ORDER BY cnt DESC, c.name LIMIT 5");
while ($row = $res->fetch_assoc()) {
    $pop_labels[] = $row['name'];
    $pop_counts[] = (int)$row['cnt'];
}
// Notification Volume Over Time (last 12 months)
$notif_month_labels = [];
$notif_month_counts = [];
$res = $conn->query("SELECT DATE_FORMAT(sent_at, '%Y-%m') as month, COUNT(*) as cnt FROM notifications GROUP BY month ORDER BY month DESC LIMIT 12");
$notif_month_data = [];
while ($row = $res->fetch_assoc()) {
    $notif_month_data[$row['month']] = (int)$row['cnt'];
}
$notif_month_data = array_reverse($notif_month_data, true);
foreach ($notif_month_data as $month => $cnt) {
    $notif_month_labels[] = $month;
    $notif_month_counts[] = $cnt;
}
// Class Status Distribution
$status_types = ['scheduled' => 0, 'cancelled' => 0, 'moved' => 0];
$res = $conn->query("SELECT status, COUNT(*) as cnt FROM timetables GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $status_types[$row['status']] = $row['cnt'];
}
// Daily/Weekly Active Users
$active_day_labels = [];
$active_day_counts = [];
$res = $conn->query("SELECT DATE(login_time) as day, COUNT(DISTINCT user_id) as cnt FROM user_logins GROUP BY day ORDER BY day DESC LIMIT 14");
$day_data = [];
while ($row = $res->fetch_assoc()) {
    $day_data[$row['day']] = (int)$row['cnt'];
}
$day_data = array_reverse($day_data, true);
foreach ($day_data as $day => $cnt) {
    $active_day_labels[] = $day;
    $active_day_counts[] = $cnt;
}
// Weekly active users (last 8 weeks)
$active_week_labels = [];
$active_week_counts = [];
$res = $conn->query("SELECT YEARWEEK(login_time, 1) as week, COUNT(DISTINCT user_id) as cnt FROM user_logins GROUP BY week ORDER BY week DESC LIMIT 8");
$week_data = [];
while ($row = $res->fetch_assoc()) {
    $week = $row['week'];
    // Format as 'YYYY-WW'
    $year = substr($week, 0, 4);
    $w = substr($week, 4);
    $active_week_labels[] = $year.'-W'.$w;
    $active_week_counts[] = (int)$row['cnt'];
}
$active_week_labels = array_reverse($active_week_labels);
$active_week_counts = array_reverse($active_week_counts);

// Average Classes per Student
$avg_classes = 0;
$res = $conn->query("SELECT COUNT(*) as total_enrollments FROM enrollments");
$row = $res->fetch_assoc();
$total_enrollments = $row['total_enrollments'];
if ($total_students > 0) {
    $avg_classes = round($total_enrollments / $total_students, 2);
}
// Lecturer Workload Distribution (all lecturers)
$all_lecturer_names = [];
$all_lecturer_counts = [];
$res = $conn->query("SELECT u.name, COUNT(t.id) as cnt FROM users u LEFT JOIN timetables t ON u.id = t.lecturer_id WHERE u.role='lecturer' GROUP BY u.id, u.name ORDER BY u.name");
while ($row = $res->fetch_assoc()) {
    $all_lecturer_names[] = $row['name'];
    $all_lecturer_counts[] = (int)$row['cnt'];
}
// Upcoming Classes (next 7 days)
$upcoming_labels = [];
$upcoming_counts = [];
$upcoming_table = [];
$res = $conn->query("SELECT date, COUNT(*) as cnt FROM timetables WHERE date >= CURDATE() AND date < DATE_ADD(CURDATE(), INTERVAL 7 DAY) GROUP BY date ORDER BY date ASC");
while ($row = $res->fetch_assoc()) {
    $upcoming_labels[] = $row['date'];
    $upcoming_counts[] = (int)$row['cnt'];
    $upcoming_table[] = $row;
}
// Enrollment Trends Over Time (new enrollments per month)
$enroll_trend_labels = [];
$enroll_trend_counts = [];
$res = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM enrollments GROUP BY month ORDER BY month DESC LIMIT 12");
$enroll_trend_data = [];
while ($row = $res->fetch_assoc()) {
    $enroll_trend_data[$row['month']] = (int)$row['cnt'];
}
$enroll_trend_data = array_reverse($enroll_trend_data, true);
foreach ($enroll_trend_data as $month => $cnt) {
    $enroll_trend_labels[] = $month;
    $enroll_trend_counts[] = $cnt;
}
// Most Frequently Used Rooms
$room_labels = [];
$room_counts = [];
$res = $conn->query("SELECT r.name, COUNT(t.id) as cnt FROM rooms r LEFT JOIN timetables t ON r.id = t.room_id GROUP BY r.id, r.name ORDER BY cnt DESC, r.name LIMIT 10");
while ($row = $res->fetch_assoc()) {
    $room_labels[] = $row['name'];
    $room_counts[] = (int)$row['cnt'];
}
?>
<div class="container" style="max-width:1200px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Analytics Dashboard</h2>
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <h5 class="text-center">Daily Active Users (Last 14 Days)</h5>
                    <canvas id="dailyActiveUsersChart"></canvas>
                </div>
                <div class="col-md-6 mb-4">
                    <h5 class="text-center">Weekly Active Users (Last 8 Weeks)</h5>
                    <canvas id="weeklyActiveUsersChart"></canvas>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-12 mb-4">
                    <h5 class="text-center">Average Classes per Student</h5>
                    <div class="d-flex justify-content-center align-items-center" style="height:80px;">
                        <canvas id="avgClassesGauge" style="max-width:400px;"></canvas>
                        <span class="ml-4 display-4 text-primary"><?php echo $avg_classes; ?></span>
                    </div>
                </div>
            </div>
            <div class="row text-center mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card border-info h-100">
                        <div class="card-body">
                            <h5 class="card-title">Students</h5>
                            <p class="display-4 text-info"><?php echo $total_students; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">Lecturers</h5>
                            <p class="display-4 text-primary"><?php echo $total_lecturers; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-dark h-100">
                        <div class="card-body">
                            <h5 class="card-title">Admins</h5>
                            <p class="display-4 text-dark"><?php echo $total_admins; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row text-center mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card border-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Courses</h5>
                            <p class="display-4 text-success"><?php echo $total_courses; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-warning h-100">
                        <div class="card-body">
                            <h5 class="card-title">Timetable Entries</h5>
                            <p class="display-4 text-warning"><?php echo $total_classes; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-danger h-100">
                        <div class="card-body">
                            <h5 class="card-title">Notifications Sent</h5>
                            <p class="display-4 text-danger"><?php echo $total_notifications; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <h5 class="text-center">User Role Distribution</h5>
                    <canvas id="userRoleChart"></canvas>
                </div>
                <div class="col-md-6 mb-4">
                    <h5 class="text-center">Notification Type Distribution</h5>
                    <canvas id="notifTypeChart"></canvas>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-7 mb-4">
                    <h5 class="text-center">Enrollments per Course</h5>
                    <canvas id="enrollCourseChart"></canvas>
                </div>
                <div class="col-md-5 mb-4">
                    <h5 class="text-center">Timetable Entries per Month</h5>
                    <canvas id="timetableMonthChart"></canvas>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <h5 class="text-center">Top 5 Most Active Lecturers</h5>
                    <canvas id="topLecturerChart"></canvas>
                </div>
                <div class="col-md-6 mb-4">
                    <h5 class="text-center">Course Popularity (Top 5)</h5>
                    <canvas id="coursePopularityChart"></canvas>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <h5 class="text-center">Notification Volume Over Time</h5>
                    <canvas id="notifVolumeChart"></canvas>
                </div>
                <div class="col-md-6 mb-4">
                    <h5 class="text-center">Class Status Distribution</h5>
                    <canvas id="classStatusChart"></canvas>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-12 mb-4">
                    <h5 class="text-center">Lecturer Workload Distribution</h5>
                    <canvas id="lecturerWorkloadChart" style="max-height:400px;"></canvas>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-7 mb-4">
                    <h5 class="text-center">Upcoming Classes (Next 7 Days)</h5>
                    <canvas id="upcomingClassesChart"></canvas>
                </div>
                <div class="col-md-5 mb-4">
                    <h5 class="text-center">Upcoming Classes Table</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm bg-white">
                            <thead class="thead-light">
                                <tr><th>Date</th><th>Number of Classes</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcoming_table)): ?>
                                    <tr><td colspan="2" class="text-center text-muted">No classes scheduled in the next 7 days.</td></tr>
                                <?php else: foreach ($upcoming_table as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cnt']); ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-12 mb-4">
                    <h5 class="text-center">Enrollment Trends Over Time (New Enrollments per Month)</h5>
                    <canvas id="enrollTrendsChart"></canvas>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-12 mb-4">
                    <h5 class="text-center">Most Frequently Used Rooms</h5>
                    <canvas id="roomUsageChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// User Role Pie Chart
const userRoleCtx = document.getElementById('userRoleChart').getContext('2d');
new Chart(userRoleCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_keys($user_roles)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($user_roles)); ?>,
            backgroundColor: ['#17a2b8', '#007bff', '#343a40'],
        }]
    },
    options: {
        responsive: true,
        legend: { position: 'bottom' }
    }
});
// Notification Type Bar Chart
const notifTypeCtx = document.getElementById('notifTypeChart').getContext('2d');
new Chart(notifTypeCtx, {
    type: 'bar',
    data: {
        labels: ['Reminders', 'Updates', 'Cancellations'],
        datasets: [{
            label: 'Count',
            data: [<?php echo $notif_types['reminder']; ?>, <?php echo $notif_types['update']; ?>, <?php echo $notif_types['cancel']; ?>],
            backgroundColor: ['#17a2b8', '#ffc107', '#dc3545'],
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }]
        }
    }
});
// Enrollments per Course Bar Chart
const enrollCourseCtx = document.getElementById('enrollCourseChart').getContext('2d');
new Chart(enrollCourseCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($enroll_labels); ?>,
        datasets: [{
            label: 'Enrollments',
            data: <?php echo json_encode($enroll_counts); ?>,
            backgroundColor: '#28a745',
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }],
            xAxes: [{ ticks: { autoSkip: false } }]
        }
    }
});
// Timetable Entries per Month Line Chart
const timetableMonthCtx = document.getElementById('timetableMonthChart').getContext('2d');
new Chart(timetableMonthCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($month_labels); ?>,
        datasets: [{
            label: 'Entries',
            data: <?php echo json_encode($month_counts); ?>,
            backgroundColor: 'rgba(23,162,184,0.2)',
            borderColor: '#17a2b8',
            fill: true,
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }]
        }
    }
});
// Top 5 Most Active Lecturers Bar Chart
const topLecturerCtx = document.getElementById('topLecturerChart').getContext('2d');
new Chart(topLecturerCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($lecturer_names); ?>,
        datasets: [{
            label: 'Classes',
            data: <?php echo json_encode($lecturer_counts); ?>,
            backgroundColor: '#007bff',
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }],
            xAxes: [{ ticks: { autoSkip: false } }]
        }
    }
});
// Course Popularity Pie Chart
const coursePopularityCtx = document.getElementById('coursePopularityChart').getContext('2d');
new Chart(coursePopularityCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($pop_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($pop_counts); ?>,
            backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#dc3545', '#007bff'],
        }]
    },
    options: {
        responsive: true,
        legend: { position: 'bottom' }
    }
});
// Notification Volume Over Time Line Chart
const notifVolumeCtx = document.getElementById('notifVolumeChart').getContext('2d');
new Chart(notifVolumeCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($notif_month_labels); ?>,
        datasets: [{
            label: 'Notifications',
            data: <?php echo json_encode($notif_month_counts); ?>,
            backgroundColor: 'rgba(220,53,69,0.2)',
            borderColor: '#dc3545',
            fill: true,
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }]
        }
    }
});
// Class Status Distribution Pie Chart
const classStatusCtx = document.getElementById('classStatusChart').getContext('2d');
new Chart(classStatusCtx, {
    type: 'pie',
    data: {
        labels: ['Scheduled', 'Cancelled', 'Moved'],
        datasets: [{
            data: [<?php echo $status_types['scheduled']; ?>, <?php echo $status_types['cancelled']; ?>, <?php echo $status_types['moved']; ?>],
            backgroundColor: ['#28a745', '#dc3545', '#ffc107'],
        }]
    },
    options: {
        responsive: true,
        legend: { position: 'bottom' }
    }
});
// Lecturer Workload Distribution Bar Chart
const lecturerWorkloadCtx = document.getElementById('lecturerWorkloadChart').getContext('2d');
new Chart(lecturerWorkloadCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($all_lecturer_names); ?>,
        datasets: [{
            label: 'Classes',
            data: <?php echo json_encode($all_lecturer_counts); ?>,
            backgroundColor: '#007bff',
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }],
            xAxes: [{ ticks: { autoSkip: false } }]
        }
    }
});
// Upcoming Classes (next 7 days) Bar Chart
const upcomingClassesCtx = document.getElementById('upcomingClassesChart').getContext('2d');
new Chart(upcomingClassesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($upcoming_labels); ?>,
        datasets: [{
            label: 'Classes',
            data: <?php echo json_encode($upcoming_counts); ?>,
            backgroundColor: '#28a745',
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }],
            xAxes: [{ ticks: { autoSkip: false } }]
        }
    }
});
// Daily Active Users Line Chart
const dailyActiveUsersCtx = document.getElementById('dailyActiveUsersChart').getContext('2d');
new Chart(dailyActiveUsersCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($active_day_labels); ?>,
        datasets: [{
            label: 'Users',
            data: <?php echo json_encode($active_day_counts); ?>,
            backgroundColor: 'rgba(40,167,69,0.2)',
            borderColor: '#28a745',
            fill: true,
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }]
        }
    }
});
// Weekly Active Users Line Chart
const weeklyActiveUsersCtx = document.getElementById('weeklyActiveUsersChart').getContext('2d');
new Chart(weeklyActiveUsersCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($active_week_labels); ?>,
        datasets: [{
            label: 'Users',
            data: <?php echo json_encode($active_week_counts); ?>,
            backgroundColor: 'rgba(255,193,7,0.2)',
            borderColor: '#ffc107',
            fill: true,
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }]
        }
    }
});
// Average Classes per Student Gauge (horizontal bar)
const avgClassesGaugeCtx = document.getElementById('avgClassesGauge').getContext('2d');
new Chart(avgClassesGaugeCtx, {
    type: 'horizontalBar',
    data: {
        labels: [''],
        datasets: [{
            label: 'Average Classes',
            data: [<?php echo $avg_classes; ?>],
            backgroundColor: '#007bff',
            borderWidth: 1,
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            xAxes: [{
                ticks: {
                    beginAtZero: true,
                    min: 0,
                    max: Math.max(<?php echo $avg_classes; ?>, 8),
                    stepSize: 1
                },
                scaleLabel: {
                    display: true,
                    labelString: 'Classes'
                }
            }],
            yAxes: [{
                barPercentage: 0.5,
                categoryPercentage: 0.5
            }]
        },
        tooltips: { enabled: false },
        animation: {
            duration: 1000,
            easing: 'easeOutQuart'
        }
    }
});
// Enrollment Trends Over Time Line Chart
const enrollTrendsCtx = document.getElementById('enrollTrendsChart').getContext('2d');
new Chart(enrollTrendsCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($enroll_trend_labels); ?>,
        datasets: [{
            label: 'New Enrollments',
            data: <?php echo json_encode($enroll_trend_counts); ?>,
            backgroundColor: 'rgba(40,167,69,0.2)',
            borderColor: '#28a745',
            fill: true,
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }]
        }
    }
});
// Most Frequently Used Rooms Bar Chart
const roomUsageCtx = document.getElementById('roomUsageChart').getContext('2d');
new Chart(roomUsageCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($room_labels); ?>,
        datasets: [{
            label: 'Classes',
            data: <?php echo json_encode($room_counts); ?>,
            backgroundColor: '#007bff',
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }],
            xAxes: [{ ticks: { autoSkip: false } }]
        }
    }
});
</script>
<?php include '../templates/footer.php'; ?> 