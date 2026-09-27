<?php
// 게시판 글 목록(JSON)을 반환합니다.
header("Content-Type: application/json; charset=utf-8");

$dataFile = __DIR__ . "/../data/board.json";

if (!file_exists($dataFile)) {
    echo json_encode([]);
    exit;
}

$json = file_get_contents($dataFile);
$posts = json_decode($json, true);

if (!is_array($posts)) {
    $posts = [];
}

echo json_encode($posts, JSON_UNESCAPED_UNICODE);
