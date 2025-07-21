<?php include '../templates/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<div class="container" style="max-width:400px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Login</h2>
            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="input-group-append">
                            <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            <div id="loginMsg" style="margin-top:15px;"></div>
            <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        this.innerHTML = type === 'password'
            ? '<i class="fas fa-eye"></i>'
            : '<i class="fas fa-eye-slash"></i>';
    });
});
document.getElementById('loginForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const res = await fetch('../api/login.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    const msgDiv = document.getElementById('loginMsg');
    if (data.success) {
        msgDiv.innerHTML = '<span style="color:green;">' + data.message + '</span>';
        setTimeout(() => { window.location.href = 'dashboard.php'; }, 1000);
    } else {
        msgDiv.innerHTML = '<span style="color:red;">' + data.message + '</span>';
    }
};
</script>
<?php include '../templates/footer.php'; ?> 