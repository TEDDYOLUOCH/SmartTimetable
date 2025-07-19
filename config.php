<?php
// Database config is in includes/db_connect.php
// Email/SMTP configuration for notifications

define('SMTP_HOST', 'smtp.example.com'); // Change to your SMTP server
define('SMTP_PORT', 587); // 465 for SSL, 587 for TLS
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_email_password');
define('FROM_EMAIL', 'your_email@example.com');
define('FROM_NAME', 'Smart Timetable Notifier');

// Twilio SMS configuration (optional)
define('TWILIO_SID', ''); // Your Twilio Account SID
define('TWILIO_TOKEN', ''); // Your Twilio Auth Token
define('TWILIO_FROM', ''); // Your Twilio phone number (e.g., +1234567890) 