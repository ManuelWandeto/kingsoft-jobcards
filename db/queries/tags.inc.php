<?php
use Monolog\Logger;
require_once(__DIR__ . '/../functions.inc.php');

function addTag(PDO $conn, array $tag, Logger $logger) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    try {
        $label = $tag['label'];
        $color = $tag['colorcode'];
        $tagRecord = queryRowPdo($conn, 'tag exists', "SELECT * FROM `jc_tags` WHERE `label` = ? OR `colorcode` = ?;", $label, $color);
    
        if($tagRecord) {
            throw new Exception("A tag with that label or color already exists", 400);
        }
        $sql = 'INSERT INTO jc_tags 
                    (
                        `label`, 
                        colorcode
                    ) 
                VALUES (?, ?);';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $label,
            $color
        ]);
        $lastInsertId = $conn->lastInsertId();
        $newTag = IdExistsPdo($conn, $lastInsertId, 'jc_tags');
        return $newTag;
    } catch (PDOException $e) {
        $logger->error('Error adding tag', ['message'=>$e->getMessage()]);
        throw new Exception('Error adding tag: '.$e->getMessage(), 500);
    }
}

function getTags(PDO $conn, Logger $logger) {
    try {
        $results = $conn->query("SELECT * FROM jc_tags;")->fetchAll(PDO::FETCH_ASSOC);
        if (!$results) {
            throw new Exception("Error getting tags");
        }
        return $results;
    } catch (PDOException $e) {
        $logger->critical('Error getting tags', ['message'=>$e->getMessage()]);
        throw new Error('Error getting tags: '.$e->getMessage(), 500);
    }
}
function updateTag(PDO $conn, array $tag, Logger $logger) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    try {
        $id = $tag['id'];
        // check if row with given id exists
        if (!IdExistsPdo($conn, $id, 'jc_tags')) {
            throw new Exception("tag with id: $id not found", 404);
        }
        // if id exists, perform update
        $sql = 'UPDATE jc_tags SET `label` = ?, `colorcode` = ? WHERE id = ?;';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$tag['label'], $tag['colorcode'], $id]);
        $updatedTag = queryRowPdo(
            $conn, 
            'get updated tag', 
            "SELECT * FROM jc_tags WHERE id = ?;",
            $id
        );
        return $updatedTag;
    } catch (PDOException $e) {
        $logger->error('Error updating tag', ['message'=>$e->getMessage()]);
        throw new Error('Error updating tag: '.$e->getMessage());
    }
}
function deleteTag(PDO $conn, int $id, Logger $logger) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    try {
        // check if row with given id exists
        if (!IdExistsPdo($conn, $id, 'jc_tags')) {
            throw new Exception("tag record with id: $id not found", 404);
        }
        // if id exists, perform deletion
        $sql = 'DELETE FROM jc_tags WHERE id = ?;';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        return true;
    } catch (PDOException $e) {
        $logger->error('Error deleting tag', ['message'=>$e->getMessage()]);
        throw new Error('Error deleting tag: '.$e->getMessage(), 500);
    }
}