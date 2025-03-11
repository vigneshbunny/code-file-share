<?php
$room_id = $_POST['room_id'] ?? 'unknown';
$upload_dir = "uploads/" . $room_id . "/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_FILES['file']['error'] == 0) {
    $file_name = basename($_FILES['file']['name']);
    $target_file = $upload_dir . $file_name;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        echo json_encode(["url" => $target_file, "file" => $file_name]);
    } else {
        echo json_encode(["error" => "Upload failed"]);
    }
}
?>
