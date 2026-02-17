-- Database Schema for Student Registration & Attendance System

CREATE DATABASE IF NOT EXISTS student_system;
USE student_system;

-- 1. Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    birthdate DATE,
    address TEXT,
    course VARCHAR(50) NOT NULL,
    year_level INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Teachers Table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL
);

-- 3. Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sub_code VARCHAR(20) UNIQUE NOT NULL, -- e.g., NET 101, GVC 101
    title VARCHAR(100) NOT NULL, -- e.g., Networking 1, Graphic Visual Computing
    units INT NOT NULL
);

-- 4. Class Schedule (Linking Subjects to Teachers & Sections)
-- This represents the "System Logic" Phase B
CREATE TABLE IF NOT EXISTS class_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    section VARCHAR(20) NOT NULL, -- e.g., "BSIT-3A"
    schedule_time VARCHAR(50) NOT NULL, -- e.g., "MW 8:00-10:00 AM"
    room VARCHAR(20) NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- 5. Enrollments (Linking Students to Classes)
-- This is the result of Phase A (Registrar Input)
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL, -- Refers to students.id
    class_schedule_id INT NOT NULL, -- Refers to class_schedule.id
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_schedule_id) REFERENCES class_schedule(id) ON DELETE CASCADE
);

-- 6. Attendance (The Teacher's Role - Phase C)
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late') DEFAULT 'Present',
    time_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
);

-- =============================================
-- SEED DATA (For Demonstration)
-- =============================================

-- Teachers
INSERT INTO teachers (name, department) VALUES
('Engr. John Doe', 'College of Computer Studies'),
('Prof. Jane Smith', 'General Education');

-- Subjects
INSERT INTO subjects (sub_code, title, units) VALUES
('NET 101', 'Networking 1', 3),
('GVC 101', 'Graphic and Visual Computing', 3),
('DB 101', 'Database Management Systems 1', 3),
('SE 101', 'Software Engineering 1', 3);

-- Class Schedule (Assigning Teachers to Subjects for BSIT-3A)
-- Assuming teacher 1 teaches NET 101 and GVC 101
INSERT INTO class_schedule (subject_id, teacher_id, section, schedule_time, room) VALUES
(1, 1, 'BSIT-3A', 'Mon/Wed 8:00 AM - 10:00 AM', 'Lab 1'),  -- NET 101
(2, 1, 'BSIT-3A', 'Tue/Thu 1:00 PM - 3:00 PM', 'Lab 2'),   -- GVC 101
(3, 2, 'BSIT-3A', 'Mon/Wed 10:00 AM - 12:00 PM', 'Room 304'); -- DB 101

-- Sample Student (Registrar Input)
INSERT INTO students (student_id, last_name, first_name, middle_name, birthdate, address, course, year_level) VALUES
('2023-0001', 'Dolores', 'Jay Jr.', 'R', '2003-05-15', 'Ilocos Sur', 'BS Information Technology', 3);

-- Enroll Student in Classes (System Logic)
-- Enrolling Jay Jr. in NET 101 (Schedule ID 1) and GVC 101 (Schedule ID 2)
INSERT INTO enrollments (student_id, class_schedule_id) VALUES
(1, 1),
(1, 2);
