<?php
// 새 메모를 MEMO 폴더에 텍스트 파일(.txt)로 저장합니다.
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

$title   = isset($_POST["title"]) ? trim($_POST["title"]) : "";
$content = isset($_POST["content"]) ? $_POST["content"] : "";

if ($title === "" && trim($content) === "") {
    http_response_code(400);
    echo json_encode(["error" => "내용을 입력해주세요."]);
    exit;
}

function safe_filename($name) {
    $name = preg_replace('/[\/\\\\:\*\?"<>\|\x00-\x1F]/u', '', $name);
    return trim($name);
}

$base = $title !== "" ? safe_filename($title) : "";
if ($base === "") {
    $base = "메모_" . date("Ymd_His");
}

$filename = $base . ".txt";
$path = $dir . "/" . $filename;

// 같은 이름 파일이 있으면 번호를 붙여 중복 저장
$i = 2;
while (file_exists($path)) {
    $filename = $base . "_" . $i . ".txt";
    $path = $dir . "/" . $filename;
    $i++;
}

$body = $title !== "" ? ($title . "\n\n" . $content) : $content;

if (file_put_contents($path, $body) === false) {
    http_response_code(500);
    echo json_encode(["error" => "파일을 저장하지 못했어요."]);
    exit;
}

echo json_encode(["ok" => true, "file" => $filename]);
