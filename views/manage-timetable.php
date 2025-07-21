<?php
require_once '../includes/auth.php';
require_login();
if (!in_array(current_user()['role'], ['admin', 'lecturer'])) {
    header('Location: dashboard.php');
    exit();
}
include '../templates/header.php';
require_once '../includes/db_connect.php';

// Define the main units/fields and their courses
$unit_courses = [
    'Information Technology & Computer Science' => [
        'Bachelor of Science in Information Technology (BSc. IT)',
        'Bachelor of Science in Computer Science',
        'Bachelor of Business Information Technology (BBIT)',
        'Bachelor of Science in Software Engineering',
        'Bachelor of Science in Computer Engineering',
        'Bachelor of Science in Data Science',
        'Bachelor of Science in Artificial Intelligence',
        'Bachelor of Science in Cybersecurity and Digital Forensics',
        'Bachelor of Science in Mobile Computing',
        'Bachelor of Science in Information Systems',
    ],
    'Business & Management' => [
        'Bachelor of Commerce (BCom)',
        'Bachelor of Business Administration (BBA)',
        'Bachelor of Procurement and Logistics',
        'Bachelor of Economics and Statistics',
        'Bachelor of Entrepreneurship and Innovation',
    ],
    'Science & Engineering' => [
        'Bachelor of Science in Electrical and Electronics Engineering',
        'Bachelor of Science in Mechanical Engineering',
        'Bachelor of Science in Civil Engineering',
        'Bachelor of Science in Actuarial Science',
        'Bachelor of Science in Applied Statistics',
    ],
    'Education & Arts' => [
        'Bachelor of Education in ICT',
        'Bachelor of Arts in Communication and Media Studies',
        'Bachelor of Arts in Sociology or Psychology',
        'Bachelor of Arts in Criminology and Security Studies',
    ],
    'Health & Life Sciences' => [
        'Bachelor of Science in Nursing',
        'Bachelor of Medicine and Surgery',
        'Bachelor of Pharmacy',
        'Bachelor of Science in Public Health',
        'Bachelor of Science in Medical Laboratory Science',
    ],
];
$unit_options = array_keys($unit_courses);

// Fetch courses for dropdown
$course_opts = [];
$result = $conn->query("SELECT id, name, unit FROM courses ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $course_opts[] = $row;
}

// Fetch lecturers for dropdown
$lecturer_opts = [];
$result = $conn->query("SELECT id, name FROM users WHERE role='lecturer' ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $lecturer_opts[] = $row;
}

// Fetch rooms for dropdown
$room_opts = [];
$result = $conn->query("SELECT id, name FROM rooms ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $room_opts[] = $row;
}
?>
<div class="container" style="max-width:1000px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4">Timetable Management</h2>
            <p class="lead">Add, edit, or remove class sessions. All changes trigger real-time notifications to enrolled students.</p>
            <hr>
            <h5 class="mb-3">Add New Timetable Entry</h5>
            <form id="addTimetableForm" class="mb-4">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label>Unit/Field:</label>
                        <select class="form-control" id="unit_filter" required>
                            <option value="">Select Unit/Field</option>
                            <?php foreach($unit_options as $unit): ?>
                                <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Course:</label>
                        <select class="form-control" name="course_id" id="course_id" required>
                            <option value="">Select</option>
                            <?php foreach($course_opts as $c): ?>
                                <option value="<?php echo $c['id']; ?>" data-unit="<?php echo htmlspecialchars($c['unit']); ?>">
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Unit/Field:</label>
                        <input type="text" class="form-control" id="course_unit_display" readonly>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Lecturer:</label>
                        <select class="form-control" name="lecturer_id" required>
                            <option value="">Select</option>
                            <?php foreach($lecturer_opts as $l): ?>
                                <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Mode:</label>
                        <select class="form-control" name="mode" id="mode" required>
                            <option value="physical">Physical</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Room:</label>
                        <select class="form-control" name="room_id" id="room_id">
                            <option value="">Select</option>
                            <?php foreach($room_opts as $r): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Date:</label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <div class="col-md-1 mb-2">
                        <label>Start:</label>
                        <input type="time" class="form-control" name="start_time" required>
                    </div>
                    <div class="col-md-1 mb-2">
                        <label>End:</label>
                        <input type="time" class="form-control" name="end_time" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Status:</label>
                        <select class="form-control" name="status" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="moved">Moved</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3 mb-2">
                        <label>Status:</label>
                        <select class="form-control" name="status" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="moved">Moved</option>
                        </select>
                    </div>
                    <div class="col-md-9 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Add Entry</button>
                        <span id="addMsg" style="margin-left:15px;"></span>
                    </div>
                </div>
            </form>
            <hr>
            <h5 class="mb-3">All Timetable Entries</h5>
            <div id="timetableTable"></div>
        </div>
    </div>
</div>
<!-- Edit Modal -->
<div class="modal" tabindex="-1" id="editModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editTimetableForm">
        <div class="modal-header">
          <h5 class="modal-title">Edit Timetable Entry</h5>
          <button type="button" class="close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <div class="form-group">
            <label>Course:</label>
            <select class="form-control" name="course_id" id="edit_course_id" required>
              <option value="">Select</option>
              <?php foreach($course_opts as $c): ?>
                <option value="<?php echo $c['id']; ?>">
                    <?php echo htmlspecialchars($c['name']) . ' (' . htmlspecialchars($c['unit']) . ')'; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Lecturer:</label>
            <select class="form-control" name="lecturer_id" id="edit_lecturer_id" required>
              <option value="">Select</option>
              <?php foreach($lecturer_opts as $l): ?>
                <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Room:</label>
            <select class="form-control" name="room_id" id="edit_room_id" required>
              <option value="">Select</option>
              <?php foreach($room_opts as $r): ?>
                <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Date:</label>
            <input type="date" class="form-control" name="date" id="edit_date" required>
          </div>
          <div class="form-group">
            <label>Start Time:</label>
            <input type="time" class="form-control" name="start_time" id="edit_start_time" required>
          </div>
          <div class="form-group">
            <label>End Time:</label>
            <input type="time" class="form-control" name="end_time" id="edit_end_time" required>
          </div>
          <div class="form-group">
            <label>Status:</label>
            <select class="form-control" name="status" id="edit_status" required>
              <option value="scheduled">Scheduled</option>
              <option value="cancelled">Cancelled</option>
              <option value="moved">Moved</option>
            </select>
          </div>
          <div class="form-group">
            <label>Mode:</label>
            <select class="form-control" name="mode" id="edit_mode" required>
                <option value="physical">Physical</option>
                <option value="online">Online</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
        </div>
        <span id="editMsg" style="margin-left:15px;"></span>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
function fetchTimetable() {
    fetch('../api/manage-timetable.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let html = '<table class="table table-bordered table-sm"><thead><tr>' +
                    '<th>Course</th><th>Unit/Field</th><th>Lecturer</th><th>Room</th><th>Date</th><th>Start</th><th>End</th><th>Status</th><th>Mode</th><th>Actions</th></tr></thead><tbody>';
                data.entries.forEach(e => {
                    html += `<tr>
                        <td>${e.course||''}</td>
                        <td>${e.unit||''}</td>
                        <td>${e.lecturer||''}</td>
                        <td>${e.room||''}</td>
                        <td>${e.date}</td>
                        <td>${e.start_time}</td>
                        <td>${e.end_time}</td>
                        <td>${e.status}</td>
                        <td>${e.mode||'physical'}</td>
                        <td>
                            <button class='btn btn-sm btn-info' onclick='openEditModal(${JSON.stringify(e)})'>Edit</button>
                            <button class='btn btn-sm btn-danger' onclick='deleteEntry(${JSON.stringify(e)})'>Delete</button>
                        </td>
                    </tr>`;
                });
                html += '</tbody></table>';
                document.getElementById('timetableTable').innerHTML = html;
            } else {
                document.getElementById('timetableTable').innerHTML = '<span style="color:red;">Failed to load timetable.</span>';
            }
        });
}
fetchTimetable();

function setRoomFieldState() {
    const mode = document.getElementById('mode').value;
    const roomField = document.getElementById('room_id');
    if (mode === 'physical') {
        roomField.disabled = false;
        roomField.required = true;
    } else {
        roomField.disabled = true;
        roomField.required = false;
        roomField.value = '';
    }
}
document.getElementById('mode').addEventListener('change', setRoomFieldState);
setRoomFieldState(); // Initial state

document.getElementById('addTimetableForm').addEventListener('submit', async function(e) {
    const mode = document.getElementById('mode').value;
    const room = document.getElementById('room_id').value;
    const dateInput = document.querySelector('input[name="date"]');
    const startTimeInput = document.querySelector('input[name="start_time"]');
    const dateVal = dateInput.value;
    const startTimeVal = startTimeInput.value;
    const now = new Date();
    const selectedDate = new Date(dateVal + 'T00:00:00');
    // Date restriction
    if (selectedDate < new Date(now.getFullYear(), now.getMonth(), now.getDate())) {
        alert('You cannot schedule a lesson in the past.');
        e.preventDefault();
        return;
    }
    // Time restriction for today
    if (selectedDate.getTime() === new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime()) {
        if (startTimeVal) {
            const [h, m] = startTimeVal.split(':');
            const selectedTime = new Date(selectedDate);
            selectedTime.setHours(parseInt(h), parseInt(m), 0, 0);
            if (selectedTime <= now) {
                alert('The selected time has already passed today.');
                e.preventDefault();
                return;
            }
        }
    }
    if (mode === 'physical' && !room) {
        alert('Please select a room for physical classes.');
        e.preventDefault();
        return;
    }
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const res = await fetch('../api/manage-timetable.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    const msg = document.getElementById('addMsg');
    if (data.success) {
        msg.innerHTML = '<span style="color:green;">' + data.message + '</span>';
        form.reset();
        setRoomFieldState();
        fetchTimetable();
    } else {
        msg.innerHTML = '<span style="color:red;">' + data.message + '</span>';
    }
});

// Edit Modal logic
function openEditModal(entry) {
    document.getElementById('edit_id').value = entry.id;
    document.getElementById('edit_course_id').value = getOptionId('course', entry.course);
    document.getElementById('edit_lecturer_id').value = getOptionId('lecturer', entry.lecturer);
    document.getElementById('edit_room_id').value = getOptionId('room', entry.room);
    document.getElementById('edit_date').value = entry.date;
    document.getElementById('edit_start_time').value = entry.start_time;
    document.getElementById('edit_end_time').value = entry.end_time;
    document.getElementById('edit_status').value = entry.status;
    document.getElementById('edit_mode').value = entry.mode || 'physical';
    setEditRoomFieldState();
    document.getElementById('editModal').style.display = 'block';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
function getOptionId(type, name) {
    let opts = {course: <?php echo json_encode($course_opts); ?>, lecturer: <?php echo json_encode($lecturer_opts); ?>, room: <?php echo json_encode($room_opts); ?>};
    let found = opts[type].find(o => o.name === name);
    return found ? found.id : '';
}
function setEditRoomFieldState() {
    const mode = document.getElementById('edit_mode').value;
    const roomField = document.getElementById('edit_room_id');
    if (mode === 'physical') {
        roomField.disabled = false;
        roomField.required = true;
    } else {
        roomField.disabled = true;
        roomField.required = false;
        roomField.value = '';
    }
}
document.getElementById('edit_mode').addEventListener('change', setEditRoomFieldState);
document.getElementById('editTimetableForm').addEventListener('submit', function(e) {
    const mode = document.getElementById('edit_mode').value;
    const room = document.getElementById('edit_room_id').value;
    if (mode === 'physical' && !room) {
        alert('Please select a room for physical classes.');
        e.preventDefault();
        return;
    }
});

// Delete logic
function deleteEntry(entry) {
    if (!confirm('Are you sure you want to delete this entry?')) return;
    const formData = new URLSearchParams();
    formData.append('id', entry.id);
    fetch('../api/manage-timetable.php', {
        method: 'DELETE',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchTimetable();
        } else {
            alert(data.message);
        }
    });
}
// Modal close on outside click
window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
document.getElementById('unit_filter').addEventListener('change', function() {
    var selectedUnit = this.value;
    var courseSelect = document.getElementById('course_id');
    for (var i = 0; i < courseSelect.options.length; i++) {
        var opt = courseSelect.options[i];
        if (!selectedUnit || opt.getAttribute('data-unit') === selectedUnit || opt.value === '') {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    }
    // Reset course selection if filtered out
    if (courseSelect.selectedIndex > 0 && courseSelect.options[courseSelect.selectedIndex].style.display === 'none') {
        courseSelect.selectedIndex = 0;
        document.getElementById('course_unit_display').value = '';
    }
});
document.getElementById('course_id').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var unit = selected.getAttribute('data-unit') || '';
    document.getElementById('course_unit_display').value = unit;
});
document.getElementById('unit_filter').dispatchEvent(new Event('change'));
window.openEditModal = function(entry) {
    document.getElementById('edit_id').value = entry.id;
    document.getElementById('edit_course_id').value = getOptionId('course', entry.course);
    document.getElementById('edit_lecturer_id').value = getOptionId('lecturer', entry.lecturer);
    document.getElementById('edit_room_id').value = getOptionId('room', entry.room);
    document.getElementById('edit_date').value = entry.date;
    document.getElementById('edit_start_time').value = entry.start_time;
    document.getElementById('edit_end_time').value = entry.end_time;
    document.getElementById('edit_status').value = entry.status;
    document.getElementById('edit_mode').value = entry.mode || 'physical';
    setEditRoomFieldState();
    document.getElementById('editModal').style.display = 'block';
};
window.deleteEntry = function(entry) {
    if (!confirm('Are you sure you want to delete this entry?')) return;
    const formData = new URLSearchParams();
    formData.append('id', entry.id);
    fetch('../api/manage-timetable.php', {
        method: 'DELETE',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchTimetable();
        } else {
            alert(data.message);
        }
    });
};
window.closeEditModal = function() {
    document.getElementById('editModal').style.display = 'none';
};
});
</script>
<?php include '../templates/footer.php'; ?> 