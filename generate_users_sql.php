<?php

echo "<pre>"; // Pre-formatted text for easy copying

// New Dummy data for users
$users_data = [
    [
        'username' => 'Hamza Ali',
        'email' => 'hamza.ali@newdomain.com',
        'password' => 'newuser123',
        'url' => 'http://hamza.new',
        'tel' => '03009876543',
        'dob' => '1991-08-20',
        'volume' => 80,
        'age' => 33,
        'gender' => 'male',
        'skills' => 'Python, Django, AWS',
        'department' => 'Backend',
        'color' => '#1A2B3C',
        'feedback' => 'Fresh talent, eager to learn.',
        'role' => 'user'
    ],
    [
        'username' => 'Erum Naz',
        'email' => 'erum.naz@newdomain.com',
        'password' => 'erumsecure',
        'url' => 'http://erumnaz.dev',
        'tel' => '03211234567',
        'dob' => '1993-02-14',
        'volume' => 90,
        'age' => 31,
        'gender' => 'female',
        'skills' => 'PHP, Laravel, API Development',
        'department' => 'Backend',
        'color' => '#8B0000',
        'feedback' => 'Experienced and highly efficient.',
        'role' => 'admin' // New Admin User 1
    ],
    [
        'username' => 'Daniyal Khan',
        'email' => 'daniyal.khan@newdomain.com',
        'password' => 'daniyal_pass',
        'url' => 'http://daniyal.info',
        'tel' => '03332211445',
        'dob' => '1989-06-05',
        'volume' => 68,
        'age' => 35,
        'gender' => 'male',
        'skills' => 'UI/UX Design, Figma',
        'department' => 'Design',
        'color' => '#FF4500',
        'feedback' => 'Excellent design aesthetic.',
        'role' => 'user'
    ],
    [
        'username' => 'Hira Javed',
        'email' => 'hira.javed@newdomain.com',
        'password' => 'hirasafepass',
        'url' => '',
        'tel' => '03456677889',
        'dob' => '1996-01-25',
        'volume' => 85,
        'age' => 28,
        'gender' => 'female',
        'skills' => 'Digital Marketing, SEO, SEM',
        'department' => 'Marketing',
        'color' => '#DAA520',
        'feedback' => 'Very strong in online campaigns.',
        'role' => 'user'
    ],
    [
        'username' => 'Zain Abbas',
        'email' => 'zain.abbas@newdomain.com',
        'password' => 'zain@work',
        'url' => 'http://zainabbas.co',
        'tel' => '03100099887',
        'dob' => '1990-10-10',
        'volume' => 72,
        'age' => 34,
        'gender' => 'male',
        'skills' => 'Database Administration, SQL',
        'department' => 'Operations',
        'color' => '#4682B4',
        'feedback' => 'Detail-oriented and reliable.',
        'role' => 'user'
    ],
    [
        'username' => 'Rabia Sultan',
        'email' => 'rabia.sultan@newdomain.com',
        'password' => 'rabia_strong',
        'url' => 'http://rabia.online',
        'tel' => '03004433221',
        'dob' => '1994-04-18',
        'volume' => 95,
        'age' => 30,
        'gender' => 'female',
        'skills' => 'Human Resources, Recruitment',
        'department' => 'HR',
        'color' => '#FF6347',
        'feedback' => 'Excellent interpersonal skills.',
        'role' => 'user'
    ],
    [
        'username' => 'Kamran Raza',
        'email' => 'kamran.raza@newdomain.com',
        'password' => 'kamran_admin',
        'url' => '',
        'tel' => '03357788990',
        'dob' => '1986-12-01',
        'volume' => 70,
        'age' => 38,
        'gender' => 'male',
        'skills' => 'System Administration, Cloud',
        'department' => 'IT',
        'color' => '#5F9EA0',
        'feedback' => 'Proactive and technically sound.',
        'role' => 'admin' // New Admin User 2
    ],
    [
        'username' => 'Asma Pervez',
        'email' => 'asma.pervez@newdomain.com',
        'password' => 'asma_pass',
        'url' => 'http://asmap.net',
        'tel' => '03201122334',
        'dob' => '1997-09-03',
        'volume' => 88,
        'age' => 27,
        'gender' => 'female',
        'skills' => 'Frontend Development, Vue.js',
        'department' => 'Frontend',
        'color' => '#CD5C5C',
        'feedback' => 'Quick learner with modern skills.',
        'role' => 'user'
    ],
    [
        'username' => 'Junaid Bashir',
        'email' => 'junaid.bashir@newdomain.com',
        'password' => 'junaid_secret',
        'url' => 'http://junaidb.com',
        'tel' => '03005566778',
        'dob' => '1992-07-07',
        'volume' => 78,
        'age' => 32,
        'gender' => 'male',
        'skills' => 'Quality Assurance, Testing',
        'department' => 'QA',
        'color' => '#8FBC8F',
        'feedback' => 'Thorough and detail-oriented tester.',
        'role' => 'user'
    ],
    [
        'username' => 'Sidra Iqbal',
        'email' => 'sidra.iqbal@newdomain.com',
        'password' => 'sidra_safe',
        'url' => '',
        'tel' => '03409988776',
        'dob' => '1995-03-12',
        'volume' => 82,
        'age' => 29,
        'gender' => 'female',
        'skills' => 'Technical Writing, Documentation',
        'department' => 'Content',
        'color' => '#6A5ACD',
        'feedback' => 'Clear and concise communication.',
        'role' => 'user'
    ],
];

foreach ($users_data as $user) {
    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);

    // Escape special characters in strings for SQL INSERT
    $username = addslashes($user['username']);
    $email = addslashes($user['email']);
    $hashed_password_escaped = addslashes($hashed_password); // Hashed password bhi escape karein
    $url = addslashes($user['url']);
    $tel = addslashes($user['tel']);
    $dob = addslashes($user['dob']);
    $gender = addslashes($user['gender']);
    $skills = addslashes($user['skills']);
    $department = addslashes($user['department']);
    $color = addslashes($user['color']);
    $feedback = addslashes($user['feedback']);
    $role = addslashes($user['role']);

    $sql = "INSERT INTO info (username, email, password, url, tel, dob, volume, age, gender, skills, department, profile_picture, file_upload, color, feedback, role) VALUES (
        '$username',
        '$email',
        '$hashed_password_escaped',
        '$url',
        '$tel',
        '$dob',
        {$user['volume']},
        {$user['age']},
        '$gender',
        '$skills',
        '$department',
        NULL, -- Profile Picture (initially NULL)
        NULL, -- File Upload (initially NULL)
        '$color',
        '$feedback',
        '$role'
    );";
    echo $sql . "\n\n";
}

echo "</pre>";

?>