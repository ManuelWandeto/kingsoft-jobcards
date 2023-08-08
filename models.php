<?php 
enum Priority {
    case URGENT;
    case MEDIUM;
    case LOW;
}

enum Status {
    case COMPLETED;
    case ONGOING;
    case REPORTED;
    case SCHEDULED;
    case OVERDUE;
    case SUSPENDED;
}
class Job {
    public int $created_on;
    public string $project;
    public string $client;
    public string $description;
    public Priority $priority;
    public string $assignee;
    public string $supervisor;
    public string $site;
    public Status $status;
    public string $completion_notes;
    public string $issues_arrising;
}

?>