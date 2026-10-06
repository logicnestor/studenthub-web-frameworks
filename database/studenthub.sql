-- ==========================================
-- StudentHub Database
-- Practical 8
-- ==========================================

CREATE DATABASE IF NOT EXISTS studenthub_db;

USE studenthub_db;


-- ==========================================
-- Students Table
-- ==========================================

CREATE TABLE IF NOT EXISTS students (

    student_id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,

    course VARCHAR(100) NOT NULL,

    year VARCHAR(30) NOT NULL

);


-- ==========================================
-- Events Table
-- ==========================================

CREATE TABLE IF NOT EXISTS events (

    event_id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(150) NOT NULL,

    category VARCHAR(50) NOT NULL,

    event_date DATE NOT NULL,

    location VARCHAR(150) NOT NULL,

    description TEXT

);


-- ==========================================
-- Registrations Table
-- ==========================================

CREATE TABLE IF NOT EXISTS registrations (

    registration_id INT AUTO_INCREMENT PRIMARY KEY,

    student_id INT NOT NULL,

    event_id INT NOT NULL,

    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_registration_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_registration_event
        FOREIGN KEY (event_id)
        REFERENCES events(event_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT unique_student_event
        UNIQUE (student_id, event_id)

);

-- ==========================================
-- Student Seed Data
-- ==========================================

INSERT INTO students
(name, email, course, year)
VALUES

(
    'Aarav Patel',
    'aarav.patel@studenthub.com',
    'Computer Science Engineering',
    'Third Year'
),

(
    'Burhanuddin Kapadia',
    'burhanuddin.kapadia@studenthub.com',
    'Computer Science Engineering',
    'Third Year'
),

(
    'Rahul Shah',
    'rahul.shah@studenthub.com',
    'Information Technology',
    'Second Year'
),

(
    'Ayesha Khan',
    'ayesha.khan@studenthub.com',
    'Computer Engineering',
    'Fourth Year'
),

(
    'Dev Mehta',
    'dev.mehta@studenthub.com',
    'Mechanical Engineering',
    'First Year'
);

-- ==========================================
-- Event Seed Data
-- ==========================================

INSERT INTO events
(title, category, event_date, location, description)
VALUES

(
    'TechFest 2026',
    'Technical',
    '2026-10-05',
    'CHARUSAT Auditorium',
    'Annual technical festival featuring coding competitions, workshops, and technology exhibitions.'
),

(
    'Hackathon 2026',
    'Technical',
    '2026-10-12',
    'Innovation Lab',
    'A 24-hour coding event where students develop innovative software solutions.'
),

(
    'Web Development Workshop',
    'Workshop',
    '2026-10-18',
    'Computer Lab 1',
    'Hands-on workshop covering modern HTML, CSS, JavaScript, and web development.'
),

(
    'AI and Machine Learning Seminar',
    'Seminar',
    '2026-10-22',
    'Seminar Hall',
    'Introduction to artificial intelligence and machine learning applications.'
),

(
    'Sports Meet',
    'Sports',
    '2026-11-02',
    'University Sports Ground',
    'Annual sports event featuring athletics and team sports.'
);

-- ==========================================
-- Registration Seed Data
-- ==========================================

INSERT INTO registrations
(student_id, event_id)
VALUES

(1, 1),

(2, 1),

(2, 2),

(3, 3),

(4, 4),

(5, 5);