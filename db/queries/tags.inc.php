<?php
require_once(__DIR__ . '/../functions.inc.php');

function addTag(mysqli $conn, array $tag) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    $label = $tag['label'];
    $color = $tag['colorcode'];
    if(queryRow($conn, 'tag exists', 
        "SELECT * FROM `jc_tags` WHERE `label` = ? OR `colorcode` = ?;", 'ss', $label, $color)
    ) {
        throw new Exception("A tag with that label or color already exists", 400);
    }
    $sql = 'INSERT INTO jc_tags 
                (
                    `label`, 
                    colorcode
                ) 
            VALUES (?, ?);';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    $boundOk = mysqli_stmt_bind_param(
        $stmt, 
        'ss', 
        $label,
        $color
    );
    if (!$boundOk) {
        throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing insert tag query: ".mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $lastInsertId = mysqli_insert_id($conn);
    $newTag = IdExists($conn, $lastInsertId, 'jc_tags');
    return $newTag;
}
function getTagInfo($conn, int $id) {
    return queryRow(
        $conn, 
        'get tag by Id', 
        "SELECT * FROM jc_tags WHERE id = ?;",
        'i',
        $id
    );
}
function getTags(mysqli $conn) {
    $results = mysqli_query($conn, "SELECT * FROM jc_tags;");
    if (!$results) {
        throw new Exception("Error getting tags");
    }
    $tags = array();
    while ($row = mysqli_fetch_assoc($results)) {
        $tags[] = $row;
    }
    return $tags;
}
function updateTag(mysqli $conn, array $tag) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    $id = $tag['id'];
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_tags')) {
        throw new Exception("tag with id: $id not found", 404);
    }
    // if id exists, perform update
    $sql = 'UPDATE jc_tags SET `label` = ?, `colorcode` = ? WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'ssi', $tag['label'], $tag['colorcode'], $id)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing update tag query: ". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $updatedTag = IdExists($conn, $id, 'jc_tags');
    return $updatedTag;
}
function deleteTag(mysqli $conn, int $id) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_tags')) {
        throw new Exception("tag record with id: $id not found", 404);
    }
    // if id exists, perform deletion
    $sql = 'DELETE FROM jc_tags WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $id)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing delete tag query:". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    return true;
}