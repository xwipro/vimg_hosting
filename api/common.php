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

// 获取ip
function get_ip() {
    if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ip_list[0]);
    }

    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }

    // 无代理直连兜底
    $ip = $_SERVER['REMOTE_ADDR'];
    return $ip === '::1' ? '127.0.0.1' : $ip;
}

// 获取省市
function get_area($ip) {
    if (in_array($ip, ['127.0.0.1', '::1']) || preg_match('/^(192\.168|10\.|172\.1[6-9]\.|172\.2[0-9]\.|172\.3[0-1]\.)/', $ip)) {
        return [
            "country" => "中国",
            "province" => "局域网",
            "city" => "",
            "district" => "",
            "isp" => "本地内网",
            "big_area" => ""
        ];
    }

    $url = "https://ip9.com.cn/get?ip={$ip}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $res = curl_exec($ch);
    curl_close($ch);

    if ($res === false) {
        return [
            "country" => "未知",
            "province" => "地址查询失败",
            "city" => "",
            "district" => "",
            "isp" => "",
            "big_area" => ""
        ];
    }

    $apiData = json_decode($res, true);
    if (!isset($apiData['ret']) || $apiData['ret'] !== 200 || empty($apiData['data'])) {
        return [
            "country" => "未知",
            "province" => "接口数据异常",
            "city" => "",
            "district" => "",
            "isp" => "",
            "big_area" => ""
        ];
    }

    $d = $apiData['data'];
    return [
        "country"    => $d['country'] ?? "",
        "province"   => $d['prov'] ?? "",
        "city"       => $d['city'] ?? "",
        "district"   => $d['area'] ?? "",
        "isp"        => $d['isp'] ?? "",
        "big_area"   => $d['big_area'] ?? ""
    ];
}


