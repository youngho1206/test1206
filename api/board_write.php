<?php
// 게시판 글을 저장합니다.
// content는 에디터에서 만들어진 HTML(서식 포함)이 그대로 들어옵니다.
// 이 안에 들어있는 이미지(붙여넣기한 네이버 이미지, base64 이미지)를 모두
// 서버로 내려받아 ../GPHOTO 폴더에 저장하고, 글 속 주소를 로컬 주소로 바꿔줍니다.

$dataFile  = __DIR__ . "/../data/board.json";
$gphotoDir = __DIR__ . "/../GPHOTO";
$gphotoRel = "GPHOTO"; // game.html 기준 상대경로

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "POST로만 요청할 수 있어요.";
    exit;
}

$title = isset($_POST["title"]) ? trim($_POST["title"]) : "";
$html  = isset($_POST["content"]) ? $_POST["content"] : "";
$link  = isset($_POST["link"]) ? trim($_POST["link"]) : "";

$hasText  = trim(strip_tags($html)) !== "";
$hasImage = strpos($html, "<img") !== false;

if ($title === "" || (!$hasText && !$hasImage)) {
    http_response_code(400);
    echo "제목과 내용을 입력해주세요.";
    exit;
}

if ($link !== "" && !preg_match('/^https?:\/\//i', $link)) {
    $link = "";
}

if (!is_dir($gphotoDir)) {
    mkdir($gphotoDir, 0755, true);
}

// 1) 위험한 태그/속성 제거 (간단한 정제 — 개인용 게시판 기준)
function sanitize_html($html) {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*"(?:[^"]*)"/i', '', $html);
    $html = preg_replace("/\son\w+\s*=\s*'(?:[^']*)'/i", '', $html);
    $html = preg_replace('/href\s*=\s*"(\s*javascript:[^"]*)"/i', 'href="#"', $html);
    return $html;
}

// 2) 글 속 이미지(base64로 붙여넣은 것, 외부 URL로 붙여넣은 것)를 내려받아
//    GPHOTO 폴더에 저장하고 주소를 로컬 경로로 교체
function localize_images($html, $gphotoDir, $gphotoRel) {

    // base64 (data:image/...) 이미지
    $html = preg_replace_callback(
        '/src\s*=\s*(["\'])data:image\/(png|jpe?g|gif|webp);base64,([^"\']+)\1/i',
        function ($m) use ($gphotoDir, $gphotoRel) {
            $ext  = strtolower($m[2]);
            if ($ext === 'jpeg') $ext = 'jpg';
            $data = base64_decode($m[3]);
            if ($data === false || strlen($data) < 20) return $m[0];
            $name = time() . '_' . substr(md5($m[3]), 0, 10) . '.' . $ext;
            file_put_contents($gphotoDir . '/' . $name, $data);
            return 'src="' . $gphotoRel . '/' . $name . '"';
        },
        $html
    );

    // 외부 URL(http/https) 이미지 — 네이버 블로그/카페 등에서 붙여넣은 이미지
    $html = preg_replace_callback(
        '/src\s*=\s*(["\'])(https?:\/\/[^"\']+)\1/i',
        function ($m) use ($gphotoDir, $gphotoRel) {
            $url = $m[2];
            if (!ini_get('allow_url_fopen')) return $m[0];

            $ctx = stream_context_create([
                "http" => [
                    "timeout" => 8,
                    "header"  => "User-Agent: Mozilla/5.0\r\nReferer: https://blog.naver.com/\r\n",
                ],
            ]);
            $data = @file_get_contents($url, false, $ctx);
            if ($data === false || strlen($data) < 100) return $m[0]; // 실패하면 원래 주소 유지

            $path = parse_url($url, PHP_URL_PATH);
            $ext  = strtolower(pathinfo($path ?: '', PATHINFO_EXTENSION));
            $ext  = preg_replace('/[^a-z0-9]/', '', $ext);
            if (!in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"], true)) $ext = "jpg";
            if ($ext === 'jpeg') $ext = 'jpg';

            $name = time() . '_' . substr(md5($url), 0, 10) . '.' . $ext;
            file_put_contents($gphotoDir . '/' . $name, $data);
            return 'src="' . $gphotoRel . '/' . $name . '"';
        },
        $html
    );

    return $html;
}

$html = sanitize_html($html);
$html = localize_images($html, $gphotoDir, $gphotoRel);

$posts = [];
if (file_exists($dataFile)) {
    $decoded = json_decode(file_get_contents($dataFile), true);
    if (is_array($decoded)) $posts = $decoded;
}

$posts[] = [
    "title"   => $title,
    "content" => $html,
    "link"    => $link,
    "date"    => date("Y-m-d H:i"),
];

if (!is_dir(dirname($dataFile))) {
    mkdir(dirname($dataFile), 0755, true);
}
file_put_contents($dataFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

header("Location: ../game.html");
exit;
