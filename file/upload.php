<?php
$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) {
  mkdir($dir, 0755, true); // tạo thư mục nếu chưa có
}

$tmp = $_FILES['upload']['tmp_name'];
$name = basename($_FILES['upload']['name']);
$destination = $dir . '/' . $name;

if (move_uploaded_file($tmp, $destination)) {
  echo json_encode([
    'url' => '/datn/file/uploads/' . $name
  ]);
} else {
  http_response_code(500);
  echo json_encode([ 'error' => 'Upload failed' ]);
}

?>