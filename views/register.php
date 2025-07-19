<?php include '../templates/header.php'; ?>
<div class="container" style="max-width:500px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Register</h2>
            <form id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="student">Student</option>
                        <option value="lecturer">Lecturer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="phone">Phone (optional):</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            <div id="registerMsg" style="margin-top:15px;"></div>
            <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>
<script>
document.getElementById('registerForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const res = await fetch('../api/register.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    const msgDiv = document.getElementById('registerMsg');
    if (data.success) {
        msgDiv.innerHTML = '<span style="color:green;">' + data.message + '</span>';
        setTimeout(() => { window.location.href = 'login.php'; }, 1000);
    } else {
        msgDiv.innerHTML = '<span style="color:red;">' + data.message + '</span>';
    }
};
</script>
<?php include '../templates/footer.php'; ?> 