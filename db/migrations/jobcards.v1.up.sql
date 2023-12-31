CREATE TABLE `jc_jobcards` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `project` varchar(255) NOT NULL,
  `client_id` MEDIUMINT NOT NULL,
  `priority` ENUM('LOW', 'MEDIUM', 'URGENT') NOT NULL,
  `reported_by` VARCHAR(255),
  `reporter_contacts` VARCHAR(255),
  `description` TEXT NOT NULL,
  `assigned_to` MEDIUMINT,
  `supervised_by` MEDIUMINT,
  `location` varchar(255) NOT NULL,
  `status` ENUM('CANCELLED', 'SUSPENDED', 'REPORTED', 'SCHEDULED', 'ONGOING', 'COMPLETED', 'OVERDUE') NOT NULL,
  `completion_notes` TEXT,
  `issues_arrising` TEXT,
  `start_date` TIMESTAMP NOT NULL,
  `end_date` TIMESTAMP NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE `jc_users` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) NOT NULL UNIQUE,
  `email` varchar(255) UNIQUE,
  `phone` varchar(255),
  `password` varchar(255) NOT NULL,
  `role` ENUM('USER', 'EDITOR', 'ADMIN') NOT NULL,
  `current_location` varchar(255),
  `current_task` TEXT,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `jc_clients` (
  `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255),
  `contact_person` VARCHAR(255),
  `phone` VARCHAR(255),
  `location` VARCHAR(255) NOT NULL,
  `logo` VARCHAR(255) UNIQUE,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `jc_jobcards` ADD FOREIGN KEY (`client_id`) REFERENCES `jc_clients`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `jc_jobcards` ADD FOREIGN KEY (`assigned_to`) REFERENCES `jc_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `jc_jobcards` ADD FOREIGN KEY (`supervised_by`) REFERENCES `jc_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;


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

CREATE TABLE `jc_attachments` (
	`id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
	`jobcard_id` MEDIUMINT NOT NULL,
	`file_name` VARCHAR(255) NOT NULL,
  `uploaded_by` MEDIUMINT NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `uc_jobcard_id_file_name_uploaded_by` UNIQUE (`jobcard_id`, `file_name`, `uploaded_by`)
);

ALTER TABLE jc_attachments ADD FOREIGN KEY (`uploaded_by`) REFERENCES jc_users(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE jc_tags (
	`id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
	`label` varchar(255) UNIQUE NOT NULL,
	`colorcode` VARCHAR(7) UNIQUE,
	`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jc_jobcard_tags (
	`id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
	`jobcard_id` MEDIUMINT NOT NULL,
	`tag_id` MEDIUMINT NOT NULL,
	`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `uc_jobcard_id_tag_id` UNIQUE (`jobcard_id`, `tag_id`)
);

ALTER TABLE `jc_jobcard_tags` ADD FOREIGN KEY (`jobcard_id`) REFERENCES `jc_jobcards`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `jc_jobcard_tags` ADD FOREIGN KEY (`tag_id`) REFERENCES `jc_tags`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

-- CREATE TABLE `jc_logs` (
--   `id` MEDIUMINT PRIMARY KEY AUTO_INCREMENT,
--   `severity` ENUM('TRACE', 'DEBUG', 'INFO', 'WARN', 'ERROR', 'FATAL') NOT NULL,
--   `action_type` VARCHAR(255) NOT NULL,
--   `user_id` MEDIUMINT,
--   `description` TEXT NOT NULL,
--   `stack_trace` TEXT,
--   `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
-- );

-- ALTER TABLE `jc_logs` ADD FOREIGN KEY (`user_id`) REFERENCES `jc_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
