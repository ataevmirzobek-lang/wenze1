<?php
header('Content-Type: application/json');

$maxSize = 20 * 1024 * 1024; // 20MB
$allowedMime = [
  'image/png',
  'image/jpeg',
  'application/pdf'
];

if (!isset($_FILES['file'])) {
  http_response_code(400);
  echo json_encode(['error' => 'No file uploaded']);
  exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['error' => 'Upload error']);
  exit;
}

if ($file['size'] > $maxSize) {
  http_response_code(400);
  echo json_encode(['error' => 'File exceeds 20MB']);
  exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedMime, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid file format']);
  exit;
}

$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
  mkdir($uploadsDir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$safeName = bin2hex(random_bytes(12)) . '.' . strtolower($ext);
$destination = $uploadsDir . '/' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save file']);
  exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fileUrl = $scheme . '://' . $host . '/uploads/' . $safeName;

echo json_encode([
  'success' => true,
  'fileUrl' => $fileUrl,
  'fileName' => $safeName
]);
