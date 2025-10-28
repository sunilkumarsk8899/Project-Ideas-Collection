-- In phpMyAdmin, run this SQL to create your tables.

--
-- Table structure for table users

CREATE TABLE users (
user_id int(11) NOT NULL AUTO_INCREMENT,
username varchar(50) NOT NULL,
password varchar(255) NOT NULL,
role enum('teacher','student','parent') NOT NULL,
full_name varchar(100) NOT NULL,
parent_id int(11) DEFAULT NULL, -- Link student to parent
PRIMARY KEY (user_id),
UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table homework

CREATE TABLE homework (
hw_id int(11) NOT NULL AUTO_INCREMENT,
teacher_id int(11) NOT NULL,
title varchar(255) NOT NULL,
description text NOT NULL,
due_date date NOT NULL,
created_at timestamp NOT NULL DEFAULT current_timestamp(),
PRIMARY KEY (hw_id),
KEY teacher_id (teacher_id),
FOREIGN KEY (teacher_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table submissions

CREATE TABLE submissions (
sub_id int(11) NOT NULL AUTO_INCREMENT,
hw_id int(11) NOT NULL,
student_id int(11) NOT NULL,
is_done tinyint(1) NOT NULL DEFAULT 0,
submitted_at timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
PRIMARY KEY (sub_id),
UNIQUE KEY hw_student_pair (hw_id,student_id),
KEY hw_id (hw_id),
KEY student_id (student_id),
FOREIGN KEY (hw_id) REFERENCES homework (hw_id) ON DELETE CASCADE,
FOREIGN KEY (student_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Example Data (Optional)

-- A teacher
INSERT INTO users (username, password, role, full_name) VALUES
('mr_jones', '$2y$10$K.o.W.Q.sYpogY4.o/P/zuBwR.g/1T1xW.8A6f.x/mJ/g.s/b.x/a', 'teacher', 'Mr. Jones'); -- Password is 'teacher123'

-- A parent
INSERT INTO users (username, password, role, full_name) VALUES
('mrs_smith', '$2y$10$o.W.q.e.yP.g/q.w/s.K.e.Y/x.G/f.w.Z.e/W.w/f.X/q.r.e', 'parent', 'Mrs. Smith'); -- Password is 'parent123'

-- A student, linked to the parent
INSERT INTO users (username, password, role, full_name, parent_id) VALUES
('john_smith', '$2y$10$s.W.q.r.e/Y.e/V.b/x.K.w/Z.e/T.a/x.W/g.s/b.w/f.e/q', 'student', 'John Smith', 2); -- Password is 'student123'