<?php
// 저장된 유튜브 영상 목록(JSON)을 반환합니다.
header("Content-Type: application/json; charset=utf-8");

$dataFile = __DIR__ . "/../data/videos.json";

if (!file_exists($dataFile)) {
    echo json_encode([]);
    exit;
}

$json = file_get_contents($dataFile);
$videos = json_decode($json, true);

if (!is_array($videos)) {
    $videos = [];
}

// 최신 추가 순으로 정렬
usort($videos, function($a, $b) {
    return strcmp($b["addedAt"] ?? "", $a["addedAt"] ?? "");
});

echo json_encode($videos, JSON_UNESCAPED_UNICODE);
