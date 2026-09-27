<?php
// PHOTO / VIDEO / MUSIC / MEMO / GPHOTO 폴더의 파일 목록을 JSON으로 반환합니다.
// 이 파일이 동작하려면 PHP를 지원하는 웹 호스팅(예: 닷홈, 카페24 등)에 올려야 합니다.

header("Content-Type: application/json; charset=utf-8");

$allowed = ["PHOTO", "VIDEO", "MUSIC", "MEMO", "GPHOTO"];
$dir = isset($_GET["dir"]) ? $_GET["dir"] : "";

if (!in_array($dir, $allowed, true)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid dir"]);
    exit;
}

$path = __DIR__ . "/../" . $dir;

if (!is_dir($path)) {
    echo json_encode([]);
    exit;
}

$files = scandir($path);
$result = [];

foreach ($files as $f) {
    if ($f === "." || $f === ".." ) continue;
    if (substr($f, 0, 1) === ".") continue;          // 숨김 파일 제외
    if (strtolower($f) === "index.html") continue;
    if (!is_file($path . "/" . $f)) continue;
    $result[$f] = filemtime($path . "/" . $f);
}

// 최신 업로드 순으로 정렬
arsort($result);

echo json_encode(array_keys($result), JSON_UNESCAPED_UNICODE);
