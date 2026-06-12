<?php
// 引入公共文件
require_once 'common.php';
set_cors_headers('GET', 'Content-Type,token');
check_method('GET');

// 验证JWT
$uid = verify_jwt();
$pdo = get_db();

// 分页处理
$page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$limit = max(1, isset($_GET["limit"]) ? (int)$_GET["limit"] : 35);
$offset = ($page - 1) * $limit;

// 查询数据
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM imgs WHERE uid=?");
$stmt->execute([$uid]);
$total = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT uid, img_name, curr_time FROM imgs WHERE uid=? ORDER BY curr_time DESC LIMIT ?, ?");
$stmt->bindParam(1, $uid, PDO::PARAM_STR);
$stmt->bindParam(2, $offset, PDO::PARAM_INT);
$stmt->bindParam(3, $limit, PDO::PARAM_INT);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 拼接URL
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME']));
$img_list = [];

foreach ($images as $img) {
    $file_path = "uploads/" . $img["img_name"];
    $full_path = UPLOAD_PATH . $img["img_name"];
    $img_list[] = [
        "uid" => $img["uid"],
        "name" => $img["img_name"],
        "url" => $base_url . $file_path,
        "upload_time" => $img["curr_time"],
        "file_size" => file_exists($full_path) ? filesize($full_path) : 0,
        "exists" => file_exists($full_path)
    ];
}

// 分页信息
$total_pages = $total > 0 ? ceil($total / $limit) : 1;
$data = [
    "list" => $img_list,
    "pagination" => [
        "total" => $total,
        "page" => $page,
        "limit" => $limit,
        "total_pages" => $total_pages,
        "has_more" => $page < $total_pages
    ]
];

json_response(200, '获取图片列表成功', $data);