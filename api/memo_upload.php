<?php
// 실제 기기에서 선택한 파일(txt, doc, db 등)을 MEMO 폴더로 업로드합니다.
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "method not allowed"]);
    exit;
}

$dir = __DIR__ . "/../MEMO";
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// 웹 서버에서 실행될 수 있는 스크립트 확장자만 안전을 위해 막습니다.
$blocked = ["php", "php3", "php4", "php5", "phtml", "phar", "exe", "sh", "cgi"];

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
        if ($orig === "") { $errors[] = $names[$i]; continue; }

        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (in_array($ext, $blocked, true)) { $errors[] = $names[$i]; continue; }

        $base    = pathinfo($orig, PATHINFO_FILENAME);
        $extPart = $ext !== "" ? ("." . $ext) : "";
        $target  = $dir . "/" . $orig;

        $n = 2;
        while (file_exists($target)) {
            $target = $dir . "/" . $base . "_" . $n . $extPart;
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
    echo json_encode(["error" => "불러올 파일을 선택해주세요."]);
    exit;
}

echo json_encode(["ok" => true, "saved" => $saved, "errors" => $errors], JSON_UNESCAPED_UNICODE);
