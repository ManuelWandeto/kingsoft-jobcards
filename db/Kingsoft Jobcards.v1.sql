-- CREATE SCHEMA `kingsoft`;

CREATE TABLE `jc_jobcards` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `project` varchar(255) NOT NULL,
  `client_id` MEDIUMINT NOT NULL,
  -- mysql enum values are numbered from left to right starting with 1
  `priority` ENUM('LOW', 'MEDIUM', 'URGENT') NOT NULL,
  `description` TEXT NOT NULL,
  `assigned_to` MEDIUMINT,
  `supervised_by` MEDIUMINT,
  `location` varchar(255) NOT NULL,
  `status` ENUM('CANCELLED', 'SUSPENDED', 'REPORTED', 'SCHEDULED', 'ONGOING', 'COMPLETED', 'OVERDUE') NOT NULL,
  `completion_notes` TEXT,
  `issues_arrising` TEXT,
  -- in mysql 5.1, the first timestamp column automatically becomes -- DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  `updated_at` TIMESTAMP NOT NULL,
  -- in mysql 5.1, explicitly giving a timestamp column the value of 'null' is equivalent to giving it CURRENT_TIMESTAMP
  `created_at` TIMESTAMP NOT NULL,
  `start_date` TIMESTAMP NOT NULL,
  `end_date` TIMESTAMP NOT NULL
);

CREATE TABLE `jc_users` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255),
  `phone` varchar(255),
  `password` varchar(255) NOT NULL,
  `role` ENUM('USER', 'EDITOR', 'ADMIN') NOT NULL,
  `current_location` varchar(255),
  `current_task` TEXT,
  `updated_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL
);

CREATE TABLE `jc_clients` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255),
  `phone` varchar(255),
  `location` varchar(255) NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL
);

ALTER TABLE `jc_jobcards` ADD FOREIGN KEY (`client_id`) REFERENCES `jc_clients`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `jc_jobcards` ADD FOREIGN KEY (`assigned_to`) REFERENCES `jc_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `jc_jobcards` ADD FOREIGN KEY (`supervised_by`) REFERENCES `jc_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `jc_users` ADD CONSTRAINT unique_username_idx UNIQUE (username);

ALTER TABLE `jc_users` ADD CONSTRAINT unique_email_idx UNIQUE (email);

ALTER TABLE `jc_clients` ADD UNIQUE (`name`);

DELIMITER $$
CREATE TRIGGER jobcard_status_on_update 
BEFORE UPDATE ON jc_jobcards
FOR EACH ROW
BEGIN

  SET NEW.status = CASE
		WHEN NEW.status IN ('REPORTED', 'ONGOING', 'SCHEDULED') AND NEW.end_date < NOW() THEN 'OVERDUE'
		WHEN NEW.status = 'REPORTED' AND COALESCE(NEW.assigned_to, NEW.supervised_by) IS NOT NULL THEN 'SCHEDULED'
		WHEN NEW.status = 'SCHEDULED' AND NEW.start_date <= NOW() THEN 'ONGOING'
		WHEN NEW.status = 'OVERDUE' AND NEW.end_date > NOW() THEN 'ONGOING'
		ELSE NEW.status
	END;
  
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER jobcard_status_on_insert
BEFORE INSERT ON jc_jobcards
FOR EACH ROW
BEGIN

  SET NEW.status = CASE
		WHEN NEW.status IN ('REPORTED', 'ONGOING', 'SCHEDULED') AND NEW.end_date < NOW() THEN 'OVERDUE'
		WHEN NEW.status = 'REPORTED' AND COALESCE(NEW.assigned_to, NEW.supervised_by) IS NOT NULL THEN 'SCHEDULED'
		WHEN NEW.status = 'SCHEDULED' AND NEW.start_date <= NOW() THEN 'ONGOING'
		WHEN NEW.status = 'OVERDUE' AND NEW.end_date > NOW() THEN 'ONGOING'
		ELSE NEW.status
	END;
  
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE update_jobcard_statuses()
BEGIN
	UPDATE jc_jobcards 
	SET status = CASE
		WHEN status IN ('REPORTED', 'ONGOING', 'SCHEDULED') AND end_date < NOW() THEN 'OVERDUE'
		WHEN status = 'REPORTED' AND COALESCE(assigned_to, supervised_by) IS NOT NULL THEN 'SCHEDULED'
		WHEN status = 'SCHEDULED' AND start_date <= NOW() THEN 'ONGOING'
		WHEN status = 'OVERDUE' AND end_date > NOW() THEN 'ONGOING'
		ELSE status
	END
	WHERE status IS NOT NULL;

END$$

DELIMITER ;

CREATE TABLE `jc_attachments` (
	`id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
	`jobcard_id` MEDIUMINT NOT NULL,
	`file_path` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL
);

ALTER TABLE `jc_attachments` ADD FOREIGN KEY (`jobcard_id`) REFERENCES `jc_jobcards`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;