<?php
// MEMO 폴더에 있는 메모 파일을 삭제합니다.
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "method not allowed"]);
    exit;
}

$dir = __DIR__ . "/../MEMO";

function safe_filename($name) {
    $name = basename($name);
    $name = preg_replace('/[\/\\\\:\*\?"<>\|\x00-\x1F]/u', '', $name);
    return trim($name);
}

$raw = isset($_POST["file"]) ? $_POST["file"] : "";
if (!is_string($raw) || trim($raw) === "") {
    http_response_code(400);
    echo json_encode(["error" => "삭제할 파일을 지정해주세요."]);
    exit;
}

$name = safe_filename($raw);
if ($name === "" || strtolower($name) === "readme.txt") {
    http_response_code(400);
    echo json_encode(["error" => "삭제할 수 없는 파일입니다."]);
    exit;
}

$realDir = realpath($dir);
$target  = $realDir !== false ? $realDir . "/" . $name : null;
$real    = $target !== null ? realpath($target) : false;

if ($realDir === false || $real === false || strpos($real, $realDir) !== 0 || !is_file($real)) {
    http_response_code(404);
    echo json_encode(["error" => "파일을 찾을 수 없습니다."]);
    exit;
}

if (!unlink($real)) {
    http_response_code(500);
    echo json_encode(["error" => "삭제하지 못했습니다."]);
    exit;
}

echo json_encode(["ok" => true, "deleted" => $name], JSON_UNESCAPED_UNICODE);
