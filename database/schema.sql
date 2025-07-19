-- Smart Class Timetable Notifier Database Schema

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'lecturer', 'admin') NOT NULL,
    phone VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Courses table (optional, for normalization)
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL
);

-- Rooms table (optional, for normalization)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Timetables table
CREATE TABLE IF NOT EXISTS timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    lecturer_id INT,
    room_id INT,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('scheduled', 'cancelled', 'moved') DEFAULT 'scheduled',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    type ENUM('reminder', 'update', 'cancel') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Enrollments table: links students to courses
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
); 

CREATE TABLE IF NOT EXISTS user_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
); 

-- Sample students
INSERT INTO users (name, email, password, role) VALUES
('Student One', 'student1@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Two', 'student2@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Three', 'student3@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Four', 'student4@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Five', 'student5@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Six', 'student6@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Seven', 'student7@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Eight', 'student8@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Nine', 'student9@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Ten', 'student10@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Eleven', 'student11@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Twelve', 'student12@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Thirteen', 'student13@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Fourteen', 'student14@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Fifteen', 'student15@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Sixteen', 'student16@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Seventeen', 'student17@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Eighteen', 'student18@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Nineteen', 'student19@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student'),
('Student Twenty', 'student20@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'student');

-- Sample lecturers
INSERT INTO users (name, email, password, role) VALUES
('Lecturer One', 'lecturer1@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Two', 'lecturer2@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Three', 'lecturer3@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Four', 'lecturer4@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Five', 'lecturer5@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Six', 'lecturer6@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Seven', 'lecturer7@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Eight', 'lecturer8@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Nine', 'lecturer9@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer'),
('Lecturer Ten', 'lecturer10@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'lecturer');
-- All passwords are 'password' (hash: $2y$10$abcdefghijklmnopqrstuv) 

-- Sample courses
INSERT INTO courses (code, name) VALUES
('CSC101', 'Introduction to Computer Science'),
('CSC102', 'Programming Fundamentals'),
('CSC201', 'Data Structures'),
('CSC202', 'Database Systems'),
('CSC203', 'Web Development'),
('CSC204', 'Operating Systems'),
('CSC205', 'Computer Networks'),
('CSC206', 'Software Engineering'),
('CSC207', 'Discrete Mathematics'),
('CSC208', 'Artificial Intelligence'); 