<?php
include 'db_config.php';

if (!isset($_GET['id'])) {
    die("Invalid link.");
}

$unique_id = $_GET['id'];
$sql = "SELECT * FROM shared_content WHERE unique_id='$unique_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['content_type'] == 'text') {
        echo "<h1>Shared Text</h1><pre>{$row['content']}</pre>";
    } else {
        echo "<h1>Download File</h1>";
        echo "<a href='{$row['file_path']}' download>{$row['file_name']}</a>";
    }
} else {
    echo "Content not found.";
}
?>
