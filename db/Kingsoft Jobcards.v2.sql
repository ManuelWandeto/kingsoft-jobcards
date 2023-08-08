CREATE SCHEMA `kingsoft`;

CREATE TABLE `kingsoft`.`jobcards` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `project` varchar(255) NOT NULL,
  `client_id` MEDIUMINT NOT NULL,
  `priority` ENUM('URGENT', 'MEDIUM', 'LOW') NOT NULL,
  `description` TEXT NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` ENUM('REPORTED', 'SCHEDULED', 'ONGOING', 'OVERDUE', 'CANCELLED', 'COMPLETED', 'SUSPENDED') NOT NULL,
  `durationFrom` TIMESTAMP NOT NULL,
  `durationTo` TIMESTAMP NOT NULL,
  `completionNotes` TEXT,
  `issues_arrising` TEXT,
  `created_at` timestamp DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` timestamp DEFAULT (CURRENT_TIMESTAMP) ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `kingsoft`.`users` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255),
  `password` varchar(255) NOT NULL,
  `current_location` varchar(255),
  `current_task` TEXT,
  `created_at` timestamp DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` timestamp DEFAULT (CURRENT_TIMESTAMP) ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `kingsoft`.`clients` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` timestamp DEFAULT (CURRENT_TIMESTAMP) ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `kingsoft`.`jobWorkers` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `job_id` MEDIUMINT NOT NULL,
  `worker_id` MEDIUMINT NOT NULL,
  `created_at` timestamp DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` timestamp DEFAULT (CURRENT_TIMESTAMP) ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `kingsoft`.`jobSupervisors` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `job_id` MEDIUMINT NOT NULL,
  `supervisor_id` MEDIUMINT NOT NULL,
  `created_at` timestamp DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` timestamp DEFAULT (CURRENT_TIMESTAMP) ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE `kingsoft`.`jobSupervisors` ADD FOREIGN KEY (`supervisor_id`) REFERENCES `kingsoft`.`users` (`id`);

ALTER TABLE `kingsoft`.`jobWorkers` ADD FOREIGN KEY (`job_id`) REFERENCES `kingsoft`.`jobcards` (`id`);

ALTER TABLE `kingsoft`.`jobSupervisors` ADD FOREIGN KEY (`job_id`) REFERENCES `kingsoft`.`jobcards` (`id`);

ALTER TABLE `kingsoft`.`jobWorkers` ADD FOREIGN KEY (`worker_id`) REFERENCES `kingsoft`.`users` (`id`);

ALTER TABLE `kingsoft`.`jobcards` ADD FOREIGN KEY (`client_id`) REFERENCES `kingsoft`.`clients` (`id`);
