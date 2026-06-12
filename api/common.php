<?php
// 引入配置
require_once 'config.php';

// 统一跨域响应头
function set_cors_headers($allow_methods = 'POST', $allow_headers = 'Content-Type,token')
{
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: $allow_methods");
    header("Access-Control-Allow-Headers: $allow_headers");
}

// 统一JSON响应输出
function json_response($code, $msg, $data = null)
{
    $response = [
        'code' => $code,
        'msg' => $msg,
        'time' => time()
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 校验请求方法
function check_method($allow_method)
{
    if ($_SERVER["REQUEST_METHOD"] !== $allow_method) {
        json_response(400, '请求方式错误');
    }
}

// 数据库连接
function get_db()
{
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Exception $e) {
        json_response(500, '数据库连接失败');
    }
}

// JWT验证（返回uid）
function verify_jwt()
{
    $token = $_SERVER["HTTP_TOKEN"] ?? "";
    if (!$token) {
        json_response(401, '未授权，请登录');
    }

    $parts = explode(".", $token);
    if (count($parts) !== 3) {
        json_response(401, 'token格式错误');
    }

    $payload = json_decode(base64_decode($parts[1]), true);
    if (!$payload || !isset($payload["exp"]) || $payload["exp"] < time()) {
        $pdo = get_db();
        $pdo->prepare("UPDATE user SET islogin='N' WHERE uid=?")->execute([$payload["uid"] ?? 0]);
        json_response(401, '登录已过期');
    }

    // 验证签名
    $sign = hash_hmac("sha256", $parts[0] . '.' . $parts[1], JWT_KEY, true);
    if (str_replace("=", "", base64_encode($sign)) !== $parts[2]) {
        json_response(401, 'token无效');
    }

    $uid = $payload["uid"];
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT islogin FROM user WHERE uid=? LIMIT 1");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user["islogin"] !== "Y") {
        json_response(401, '您已退出登录');
    }

    return $uid;
}

// JWT生成（登录专用）
function create_jwt($uid)
{
    $header = json_encode(["alg" => "HS256", "typ" => "JWT"]);
    $payload = json_encode([
        "uid" => $uid,
        "iat" => time(),
        "exp" => time() + 12 * 3600
    ]);
    $base64_header = str_replace("=", "", base64_encode($header));
    $base64_payload = str_replace("=", "", base64_encode($payload));
    $signature = hash_hmac("sha256", $base64_header . "." . $base64_payload, JWT_KEY, true);
    $base64_sign = str_replace("=", "", base64_encode($signature));
    return $base64_header . "." . $base64_payload . "." . $base64_sign;
}