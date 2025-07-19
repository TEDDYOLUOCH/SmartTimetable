<?php
require_once '../includes/auth.php';
require_login();
if (!in_array(current_user()['role'], ['admin', 'lecturer'])) {
    header('Location: dashboard.php');
    exit();
}
include '../templates/header.php';
require_once '../includes/db_connect.php';

// Fetch courses, lecturers, and rooms for select options
$courses = $conn->query("SELECT id, name FROM courses ORDER BY name");
$lecturers = $conn->query("SELECT id, name FROM users WHERE role='lecturer' ORDER BY name");
$rooms = $conn->query("SELECT id, name FROM rooms ORDER BY name");

// For edit modal, fetch all options as arrays
function fetch_options($result) {
    $arr = [];
    while ($row = $result->fetch_assoc()) $arr[] = $row;
    return $arr;
}
$course_opts = fetch_options($conn->query("SELECT id, name FROM courses ORDER BY name"));
$lecturer_opts = fetch_options($conn->query("SELECT id, name FROM users WHERE role='lecturer' ORDER BY name"));
$room_opts = fetch_options($conn->query("SELECT id, name FROM rooms ORDER BY name"));
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
                        <label>Course:</label>
                        <select class="form-control" name="course_id" required>
                            <option value="">Select</option>
                            <?php foreach($course_opts as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <label>Room:</label>
                        <select class="form-control" name="room_id" required>
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
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
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
function fetchTimetable() {
    fetch('../api/manage-timetable.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let html = '<table class="table table-bordered table-sm"><thead><tr>' +
                    '<th>Course</th><th>Lecturer</th><th>Room</th><th>Date</th><th>Start</th><th>End</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                data.entries.forEach(e => {
                    html += `<tr>
                        <td>${e.course||''}</td>
                        <td>${e.lecturer||''}</td>
                        <td>${e.room||''}</td>
                        <td>${e.date}</td>
                        <td>${e.start_time}</td>
                        <td>${e.end_time}</td>
                        <td>${e.status}</td>
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

document.getElementById('addTimetableForm').onsubmit = async function(e) {
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
        fetchTimetable();
    } else {
        msg.innerHTML = '<span style="color:red;">' + data.message + '</span>';
    }
};

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
document.getElementById('editTimetableForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new URLSearchParams(new FormData(form));
    const res = await fetch('../api/manage-timetable.php', {
        method: 'PUT',
        body: formData
    });
    const data = await res.json();
    const msg = document.getElementById('editMsg');
    if (data.success) {
        msg.innerHTML = '<span style="color:green;">' + data.message + '</span>';
        setTimeout(() => { closeEditModal(); fetchTimetable(); }, 1000);
    } else {
        msg.innerHTML = '<span style="color:red;">' + data.message + '</span>';
    }
};

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
</script>
<?php include '../templates/footer.php'; ?> 