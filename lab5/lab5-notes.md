# Lab 5 Notes

## Part 1: Create the database tables
**1. Screenshot or pasted output showing table names and schema:**

![Output after running `.tables` and `.schema` in Codespaces SQLite](./Part1.JPG)

**2. Which column is the primary key in each table?**
* In the `students` table, it is `student_id`.
* In the `tutoring_requests` table, it is `request_id`.

**3. Which column is the foreign key, and what relationship does it represent?**
The `student_id` column in the `tutoring_requests` table is the foreign key. It represents a one-to-many relationship, linking a specific tutoring request back to the unique student who created it. 

**4. Why is student email marked UNIQUE?**
It prevents duplicate accounts from being created. This ensures that every email address in the database belongs to one and only one student row, maintaining data integrity.

## Part 2: Insert students and tutoring requests
**1. INSERT statements used:**
```sql
INSERT INTO students (student_name, student_email) 
VALUES 
    ('Jordan Lee', 'jordan.lee@example.edu'),
    ('Avery Patel', 'avery.patel@example.edu');

INSERT INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time) 
VALUES 
    (1, 'IS 6465', 'Forms', '2026-11-20', '10:00 AM'),
    (1, 'IS 6465', 'SQL SELECT', '2026-11-22', '2:00 PM'),
    (2, 'IS 6465', 'SQLite CRUD', '2026-11-23', '1:00 PM');