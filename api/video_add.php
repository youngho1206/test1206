<?php
// 유튜브 링크를 받아 영상 ID를 추출하고 목록에 저장합니다.
// 업로드(추가)는 비밀번호(3553)가 맞아야만 처리됩니다.
header("Content-Type: application/json; charset=utf-8");

$dataFile = __DIR__ . "/../data/videos.json";
$PASSWORD = "3553";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "method not allowed"]);
    exit;
}

$password = isset($_POST["password"]) ? $_POST["password"] : "";
if ($password !== $PASSWORD) {
    http_response_code(403);
    echo json_encode(["error" => "비밀번호가 올바르지 않습니다."]);
    exit;
}

$link = isset($_POST["link"]) ? trim($_POST["link"]) : "";

function extract_youtube_id($url) {
    $patterns = [
        '/youtu\.be\/([A-Za-z0-9_-]{11})/',
        '/[?&]v=([A-Za-z0-9_-]{11})/',
        '/youtube\.com\/embed\/([A-Za-z0-9_-]{11})/',
        '/youtube\.com\/shorts\/([A-Za-z0-9_-]{11})/',
        '/youtube\.com\/live\/([A-Za-z0-9_-]{11})/',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return null;
}

$id = extract_youtube_id($link);

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "올바른 유튜브 링크가 아니에요."]);
    exit;
}

$videos = [];
if (file_exists($dataFile)) {
    $decoded = json_decode(file_get_contents($dataFile), true);
    if (is_array($decoded)) $videos = $decoded;
}

// 이미 등록된 영상이면 중복 추가하지 않음
foreach ($videos as $v) {
    if (isset($v["id"]) && $v["id"] === $id) {
        echo json_encode(["ok" => true, "id" => $id, "duplicate" => true]);
        exit;
    }
}

$videos[] = [
    "id"      => $id,
    "link"    => $link,
    "addedAt" => date("Y-m-d H:i:s"),
];

if (!is_dir(dirname($dataFile))) {
    mkdir(dirname($dataFile), 0755, true);
}
file_put_contents($dataFile, json_encode($videos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode(["ok" => true, "id" => $id]);
