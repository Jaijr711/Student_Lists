-- Database Schema for Student Registration and Attendance System
-- Import this file into phpMyAdmin

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `registrars`
--

CREATE TABLE `registrars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `avatar` varchar(5) DEFAULT 'A',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `registrars`
--

INSERT INTO `registrars` (`id`, `username`, `password`, `name`, `avatar`) VALUES
(1, 'registrar01', 'reg2024', 'Angeline S. Alcantara', 'AA');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `dept` varchar(20) NOT NULL,
  `avatar` varchar(5) DEFAULT 'T',
  `created_at` date DEFAULT CURRENT_DATE,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `username`, `password`, `email`, `status`, `dept`, `avatar`, `created_at`) VALUES
('T001', 'Juan Dela Cruz', 'teacher01', 'tch2024', 'jdelacruz@iscc.edu.ph', 'Active', 'IT', 'JD', '2024-06-01'),
('T002', 'Maria Lopez', 'mlopez', 'mlop2024', 'mlopez@iscc.edu.ph', 'Active', 'IT', 'ML', '2024-06-01'),
('T003', 'Carmen Reyes', 'creyes', 'crey2024', 'creyes@iscc.edu.ph', 'Active', 'GE', 'CR', '2024-06-01'),
('T004', 'Pedro Santos', 'psantos', 'psan2024', 'psantos@iscc.edu.ph', 'Active', 'IT', 'PS', '2024-06-01'),
('T005', 'Rosa Lim', 'rlim', 'rlim2024', 'rlim@iscc.edu.ph', 'Active', 'GE', 'RL', '2024-06-02'),
('T006', 'Ana Valdez', 'avaldez', 'aval2024', 'avaldez@iscc.edu.ph', 'Inactive', 'GE', 'AV', '2024-06-02');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` varchar(20) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `given` varchar(50) NOT NULL,
  `middle` varchar(50) DEFAULT NULL,
  `course` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `section` varchar(10) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `civil` varchar(20) DEFAULT 'Single',
  `father` varchar(100) DEFAULT NULL,
  `mother` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Enrolled',
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT 'stu2024',
  `avatar` varchar(5) DEFAULT 'S',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `surname`, `given`, `middle`, `course`, `year`, `section`, `contact`, `email`, `address`, `dob`, `sex`, `civil`, `father`, `mother`, `status`, `username`, `password`, `avatar`) VALUES
('2022-0101', 'Dolores', 'Jay Jr.', 'Rabanal', 'BSIT', 3, 'S3A', '09162997494', 'jayjr1825302@gmail.com', 'Arnap, Cabugao, Ilocos Sur', '2005-09-23', 'Male', 'Single', 'Jay A. Dolores', 'Marianne P. Rabanal', 'Enrolled', 'student01', 'stu2024', 'JR'),
('2022-0102', 'Reyes', 'Ana', 'Santos', 'BSIT', 3, 'S3A', '09171234567', 'reyes@email.com', 'Bantay, Ilocos Sur', '2005-03-12', 'Female', 'Single', 'Roberto Reyes', 'Carla S. Reyes', 'Enrolled', '2022-0102', 'stu2024', 'AR'),
('2022-0103', 'Bautista', 'Carlo', 'Cruz', 'BSIT', 3, 'S3A', '09189876543', 'bautista@email.com', 'Vigan City, Ilocos Sur', '2004-11-05', 'Male', 'Single', 'Felix Bautista', 'Nora C. Bautista', 'Enrolled', '2022-0103', 'stu2024', 'CB'),
('2022-0104', 'Pascual', 'Diane', 'Lim', 'BSIT', 3, 'S3A', '09205556677', 'pascual@email.com', 'Narvacan, Ilocos Sur', '2005-07-19', 'Female', 'Single', 'Mario Pascual', 'Ines L. Pascual', 'Enrolled', '2022-0104', 'stu2024', 'DP'),
('2022-0105', 'Flores', 'Edgar', 'Morales', 'BSIT', 3, 'S3B', '09334445566', 'flores@email.com', 'Caoayan, Ilocos Sur', '2004-08-22', 'Male', 'Single', 'Ernesto Flores', 'Lita M. Flores', 'Enrolled', '2022-0105', 'stu2024', 'EF');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `units` int(11) NOT NULL,
  `course` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`code`, `name`, `units`, `course`, `year`) VALUES
('ALGO 201', 'Algorithms', 3, 'BSCS', 2),
('CC 201', 'Computer Architecture', 3, 'BSIT', 2),
('DB 101', 'Fundamentals of Database System (Lec/Lab)', 3, 'BSIT', 3),
('DS 201', 'Data Structures', 3, 'BSCS', 2),
('GS', 'Gender and Society', 3, 'BSIT', 3),
('GVC 101', 'Graphic and Visual Computing (Lec/Lab)', 3, 'BSIT', 3),
('MATH 201', 'Discrete Mathematics', 3, 'BSIT', 2),
('NET 101', 'Networking 1 (Lec/Lab)', 3, 'BSIT', 3),
('OS 201', 'Operating Systems', 3, 'BSIT', 2),
('PHOTO 101', 'Photography (Lec/Lab)', 3, 'BSIT', 3),
('PROG 201', 'Data Structures & Algorithms', 3, 'BSIT', 2),
('RM 101', 'Research Methodology', 3, 'BSIT', 3),
('RPH', 'Reading in Philippine History', 3, 'BSIT', 3),
('SE 101', 'Software Engineering', 3, 'BSIT', 3);

-- --------------------------------------------------------

--
-- Table structure for table `class_schedule`
--

CREATE TABLE `class_schedule` (
  `id` varchar(20) NOT NULL,
  `teacher_id` varchar(10) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `course` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `section` varchar(10) NOT NULL,
  `room` varchar(20) DEFAULT 'TBD',
  `day` varchar(20) DEFAULT NULL,
  `start_time` varchar(10) DEFAULT NULL,
  `end_time` varchar(10) DEFAULT NULL,
  `sem` varchar(10) DEFAULT '1st',
  `sy` varchar(20) DEFAULT '2025-2026',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `class_schedule`
--

INSERT INTO `class_schedule` (`id`, `teacher_id`, `subject_code`, `course`, `year`, `section`, `room`, `day`, `start_time`, `end_time`, `sem`, `sy`) VALUES
('L001', 'T001', 'NET 101', 'BSIT', 3, 'S3A', 'Lab 1', 'Wednesday', '07:00', '08:30', '1st', '2025-2026'),
('L002', 'T001', 'SE 101', 'BSIT', 3, 'S3A', 'Lab 4', 'Tuesday', '13:00', '14:30', '1st', '2025-2026'),
('L003', 'T002', 'DB 101', 'BSIT', 3, 'S3A', 'Lab 2', 'Tuesday', '08:30', '10:00', '1st', '2025-2026'),
('L004', 'T003', 'RPH', 'BSIT', 3, 'S3A', 'Room 301', 'Monday', '07:00', '08:30', '1st', '2025-2026'),
('L005', 'T004', 'RM 101', 'BSIT', 3, 'S3A', 'Room 303', 'Friday', '08:30', '10:00', '1st', '2025-2026'),
('L006', 'T005', 'GVC 101', 'BSIT', 3, 'S3A', 'Lab 3', 'Thursday', '10:00', '11:30', '1st', '2025-2026'),
('L007', 'T005', 'PHOTO 101', 'BSIT', 3, 'S3A', 'Studio 1', 'Wednesday', '13:00', '14:30', '1st', '2025-2026'),
('L008', 'T003', 'GS', 'BSIT', 3, 'S3A', 'Room 302', 'Monday', '10:00', '11:30', '1st', '2025-2026');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `load_id` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `time_in` varchar(10) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `load_id`, `date`, `time_in`, `status`) VALUES
('A001', '2022-0101', 'L001', '2025-01-15', '07:00', 'Present'),
('A002', '2022-0101', 'L001', '2025-01-22', '07:18', 'Late'),
('A003', '2022-0101', 'L001', '2025-01-29', NULL, 'Absent'),
('A004', '2022-0101', 'L001', '2025-02-05', '06:58', 'Present'),
('A005', '2022-0101', 'L003', '2025-01-14', '08:29', 'Present'),
('A006', '2022-0101', 'L003', '2025-01-21', '08:32', 'Present'),
('A007', '2022-0101', 'L003', '2025-01-28', NULL, 'Absent'),
('A008', '2022-0102', 'L001', '2025-01-15', '07:02', 'Present'),
('A009', '2022-0102', 'L001', '2025-01-22', NULL, 'Absent'),
('A010', '2022-0103', 'L001', '2025-01-15', '07:08', 'Present'),
('A011', '2022-0103', 'L001', '2025-01-22', '07:22', 'Late');

COMMIT;
