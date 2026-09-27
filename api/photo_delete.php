<?php
// PHOTO 폴더에 있는 사진 파일을 삭제합니다.
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "method not allowed"]);
    exit;
}

$dir = __DIR__ . "/../PHOTO";

function safe_filename($name) {
    $name = basename($name);
    $name = preg_replace('/[\/\\\\:\*\?"<>\|\x00-\x1F]/u', '', $name);
    return trim($name);
}

$raw = isset($_POST["file"]) ? $_POST["file"] : "";
if (!is_string($raw) || trim($raw) === "") {
    http_response_code(400);
    echo json_encode(["error" => "삭제할 사진을 지정해주세요."]);
    exit;
}

$name = safe_filename($raw);
if ($name === "") {
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

// 사진에 등록된 정보(photo_info)도 있으면 함께 정리합니다.
$infoFile = __DIR__ . "/../data/photo_info.json";
if (is_file($infoFile)) {
    $json = json_decode(file_get_contents($infoFile), true);
    if (is_array($json) && isset($json[$name])) {
        unset($json[$name]);
        file_put_contents($infoFile, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

echo json_encode(["ok" => true, "deleted" => $name], JSON_UNESCAPED_UNICODE);
