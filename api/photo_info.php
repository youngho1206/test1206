<?php
// PHOTO 폴더 사진별 "정보" 텍스트를 저장/조회합니다.
// 저장할 때는 비밀번호(3553)가 맞아야만 반영됩니다.

header("Content-Type: application/json; charset=utf-8");

$dataFile = __DIR__ . "/../data/photo_info.json";
$PASSWORD = "3553";

function load_info($dataFile){
    if (!file_exists($dataFile)) return [];
    $decoded = json_decode(file_get_contents($dataFile), true);
    return is_array($decoded) ? $decoded : [];
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $file = isset($_GET["file"]) ? $_GET["file"] : "";
    $all  = load_info($dataFile);
    echo json_encode(["info" => isset($all[$file]) ? $all[$file] : ""], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === "POST") {
    $file     = isset($_POST["file"]) ? trim($_POST["file"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    $info     = isset($_POST["info"]) ? $_POST["info"] : "";

    if ($file === "") {
        http_response_code(400);
        echo json_encode(["error" => "invalid file"]);
        exit;
    }
    if ($password !== $PASSWORD) {
        http_response_code(403);
        echo json_encode(["error" => "wrong password"]);
        exit;
    }

    $all = load_info($dataFile);
    $all[$file] = $info;

    if (!is_dir(dirname($dataFile))) {
        mkdir(dirname($dataFile), 0755, true);
    }
    file_put_contents($dataFile, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    echo json_encode(["ok" => true]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "method not allowed"]);
