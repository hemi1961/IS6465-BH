-- Part 1: Create tables
CREATE TABLE if not exists students (
    student_id INTEGER PRIMARY KEY,
    student_name TEXT NOT NULL,
    student_email TEXT NOT NULL UNIQUE
);

CREATE TABLE if not exists tutoring_requests (
    request_id INTEGER PRIMARY KEY,
    student_id INTEGER NOT NULL,
    course TEXT NOT NULL,
    topic TEXT NOT NULL,
    preferred_date TEXT NOT NULL,
    preferred_time TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'open',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);

-- Part 2: Insert students and tutoring requests
INSERT Or ignore INTO students (student_name, student_email) 
VALUES ('Maria Lopez', 'maria.lopez@example.com');
    

INSERT OR ignore INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time) 
VALUES (1, 'IS 6465', 'PHP arrays and forms', '2026-11-03', '14:00');

-- Part 3: Read the stored data
-- 1. Display all rows in students
SELECT * FROM students;

-- 2. Display all rows in tutoring_requests
SELECT * FROM tutoring_requests;

-- 3. Display only requests with a status of Open
SELECT * FROM tutoring_requests WHERE status = 'open';

-- 4. Display one request by its request_id using a WHERE clause
SELECT * FROM tutoring_requests WHERE request_id = 1;

-- 5. Display a request list using a JOIN
SELECT
  r.request_id,
  s.student_name,
  s.student_email,
  r.course, r.topic,
  r.preferred_date, r.preferred_time, r.status
FROM tutoring_requests AS r
JOIN students AS s ON r.student_id = s.student_id
ORDER BY r.preferred_date;

-- Part 4: Update one request
-- Change maria's request topic and time
UPDATE tutoring_requests
SET topic = 'PHP arrays and forms - updated',
    preferred_date = '2026-10-12',
    preferred_time = '15:00'
WHERE request_id = 1;

-- Delete one request
--DELETE from tutoring_requests
--WHERE request_id = 2;

-- Change Maria's request status to Cancelled
UPDATE tutoring_requests
SET status = 'Cancelled'
WHERE request_id = 1;

-- Part 5: Delete one test request
-- Insert test request
INSERT INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time) 
VALUES (1, 'Test Course', 'Delete Practice', '2026-12-01', '12:00 PM');

-- Delete the test request
DELETE FROM tutoring_requests WHERE request_id = 4;

-- Verify deletion
SELECT * FROM tutoring_requests;
