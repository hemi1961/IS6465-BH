<?php
/**
 * Initializes database/tutoring.db from setup-database.sql and inserts
 * fictional classroom accounts (Student, Tutor, Administrator) using
 * PHP password_hash(PASSWORD_DEFAULT).
 *
 * Run from the lab7 directory:
 *   php database/seed-database.php
 */
require_once dirname(__DIR__) . '/config/db.php';

$dbPath = __DIR__ . '/tutoring.db';
$schemaPath = __DIR__ . '/setup-database.sql';

$pdo = openDatabase($dbPath);
$pdo->exec(file_get_contents($schemaPath));

$seedUsers = array(
    array(
        'full_name' => 'Maria Lopez',
        'email' => 'maria.lopez@example.edu',
        'password' => 'StudentPass123!',
        'role' => 'Student',
        'status' => 'Active'
    ),
    array(
        'full_name' => 'Jordan Lee',
        'email' => 'jordan.lee@example.edu',
        'password' => 'StudentPass123!',
        'role' => 'Student',
        'status' => 'Active'
    ),
    array(
        'full_name' => 'Taylor Kim',
        'email' => 'taylor.kim@example.edu',
        'password' => 'TutorPass123!',
        'role' => 'Tutor',
        'status' => 'Active'
    ),
    array(
        'full_name' => 'Alex Morgan',
        'email' => 'admin@example.edu',
        'password' => 'AdminPass123!',
        'role' => 'Administrator',
        'status' => 'Active'
    )
);

$insertUser = $pdo->prepare(
    'INSERT OR IGNORE INTO users (full_name, email, password_hash, role, status)
     VALUES (:full_name, :email, :password_hash, :role, :status)'
);

foreach ($seedUsers as $u) {
    $insertUser->execute(array(
        'full_name' => $u['full_name'],
        'email' => strtolower(trim($u['email'])),
        'password_hash' => password_hash($u['password'], PASSWORD_DEFAULT),
        'role' => $u['role'],
        'status' => $u['status']
    ));
}

// Link seeded students and insert sample tutoring requests if none exist yet
$findUser = $pdo->prepare('SELECT user_id, full_name, email FROM users WHERE email = ?');
$insertStudent = $pdo->prepare(
    'INSERT OR IGNORE INTO students (student_name, student_email, user_id) VALUES (?, ?, ?)'
);
$findStudent = $pdo->prepare('SELECT student_id FROM students WHERE user_id = ?');

$findUser->execute(array('maria.lopez@example.edu'));
$maria = $findUser->fetch();
if ($maria) {
    $insertStudent->execute(array($maria['full_name'], $maria['email'], (int)$maria['user_id']));
    $findStudent->execute(array((int)$maria['user_id']));
    $mariaStudent = $findStudent->fetch();

    if ($mariaStudent) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM tutoring_requests WHERE student_id = ?');
        $countStmt->execute(array((int)$mariaStudent['student_id']));
        if ((int)$countStmt->fetchColumn() === 0) {
            $insertReq = $pdo->prepare(
                'INSERT INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insertReq->execute(array(
                (int)$mariaStudent['student_id'],
                'IS 6465',
                json_encode(array(
                    'reason' => 'Need help understanding PHP sessions and PDO prepared statements.',
                    'topics' => array('Exam review', 'Practice problems')
                )),
                '2026-12-10',
                'Afternoon',
                'Open'
            ));
            $insertReq->execute(array(
                (int)$mariaStudent['student_id'],
                'IS 6495',
                json_encode(array(
                    'reason' => 'Reviewing normalization exercises before the quiz.',
                    'topics' => array('Study plan')
                )),
                '2026-12-12',
                'Morning',
                'Cancelled'
            ));
        }
    }
}

$findUser->execute(array('jordan.lee@example.edu'));
$jordan = $findUser->fetch();
if ($jordan) {
    $insertStudent->execute(array($jordan['full_name'], $jordan['email'], (int)$jordan['user_id']));
    $findStudent->execute(array((int)$jordan['user_id']));
    $jordanStudent = $findStudent->fetch();

    if ($jordanStudent) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM tutoring_requests WHERE student_id = ?');
        $countStmt->execute(array((int)$jordanStudent['student_id']));
        if ((int)$countStmt->fetchColumn() === 0) {
            $insertReq = $pdo->prepare(
                'INSERT INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insertReq->execute(array(
                (int)$jordanStudent['student_id'],
                'IS 6570',
                json_encode(array(
                    'reason' => 'Looking for practice problems on ER diagrams and SQL joins.',
                    'topics' => array('Practice problems')
                )),
                '2026-12-15',
                'Evening',
                'Open'
            ));
        }
    }
}

echo "Database seeded successfully at: " . $dbPath . PHP_EOL;
