<?php
// 系统基础配置
date_default_timezone_set("Asia/Shanghai");

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'vimg_hosting');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// JWT配置
define('JWT_KEY', 'MNDg1m9fJnlV1574de9I7o61RF9jrmbvuuMvQ3eLIF5dyQ2D0Wwo4XcPyB1SocZfqBACIzi1nsCUlZpacT5fKCdmCOTPjibfB4JM');

// 上传配置
define('UPLOAD_PATH', str_replace('\\', '/', __DIR__) . "/uploads/");
define('ALLOW_EXT', ['jpg', 'png', 'jpeg', 'webp', 'bmp', 'gif']);
define('MAX_SIZE', 20 * 1024 * 1024); // 20MB