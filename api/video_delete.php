<?php
// 저장된 유튜브 영상을 목록(videos.json)에서 삭제합니다.
header("Content-Type: application/json; charset=utf-8");

$dataFile = __DIR__ . "/../data/videos.json";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "method not allowed"]);
    exit;
}

$id = isset($_POST["id"]) ? trim($_POST["id"]) : "";
if ($id === "") {
    http_response_code(400);
    echo json_encode(["error" => "삭제할 영상을 지정해주세요."]);
    exit;
}

$videos = [];
if (file_exists($dataFile)) {
    $decoded = json_decode(file_get_contents($dataFile), true);
    if (is_array($decoded)) $videos = $decoded;
}

$found = false;
$videos = array_values(array_filter($videos, function($v) use ($id, &$found) {
    if (isset($v["id"]) && $v["id"] === $id) { $found = true; return false; }
    return true;
}));

if (!$found) {
    http_response_code(404);
    echo json_encode(["error" => "영상을 찾을 수 없습니다."]);
    exit;
}

file_put_contents($dataFile, json_encode($videos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// 영상에 등록된 정보(video_info)도 있으면 함께 정리합니다.
$infoFile = __DIR__ . "/../data/video_info.json";
if (is_file($infoFile)) {
    $json = json_decode(file_get_contents($infoFile), true);
    if (is_array($json) && isset($json[$id])) {
        unset($json[$id]);
        file_put_contents($infoFile, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

echo json_encode(["ok" => true, "deleted" => $id], JSON_UNESCAPED_UNICODE);
