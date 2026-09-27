<?php
// 카메라로 촬영한 사진을 PHOTO 폴더에 저장합니다.
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "method not allowed"]);
    exit;
}

$dir = __DIR__ . "/../PHOTO";
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// 사진(이미지) 확장자만 허용합니다.
$allowed = ["jpg", "jpeg", "png", "gif", "webp", "bmp", "heic"];

function safe_filename($name) {
    $name = basename($name);
    $name = preg_replace('/[\/\\\\:\*\?"<>\|\x00-\x1F]/u', '', $name);
    return trim($name);
}

$saved  = [];
$errors = [];

if (!empty($_FILES["files"]) && is_array($_FILES["files"]["name"])) {
    $names = $_FILES["files"]["name"];
    $tmp   = $_FILES["files"]["tmp_name"];
    $err   = $_FILES["files"]["error"];
    $count = count($names);

    for ($i = 0; $i < $count; $i++) {
        if ($err[$i] !== UPLOAD_ERR_OK) { $errors[] = $names[$i]; continue; }

        $orig = safe_filename($names[$i]);
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if ($ext === "" || !in_array($ext, $allowed, true)) { $errors[] = $names[$i]; continue; }

        // 카메라 촬영본은 파일명이 비어있거나 겹칠 수 있어 타임스탬프로 새 이름을 만듭니다.
        $base   = "camera_" . date("Ymd_His") . "_" . $i;
        $target = $dir . "/" . $base . "." . $ext;

        $n = 2;
        while (file_exists($target)) {
            $target = $dir . "/" . $base . "_" . $n . "." . $ext;
            $n++;
        }

        if (move_uploaded_file($tmp[$i], $target)) {
            $saved[] = basename($target);
        } else {
            $errors[] = $names[$i];
        }
    }
}

if (empty($saved) && empty($errors)) {
    http_response_code(400);
    echo json_encode(["error" => "저장할 사진이 없어요."]);
    exit;
}

echo json_encode(["ok" => true, "saved" => $saved, "errors" => $errors], JSON_UNESCAPED_UNICODE);
