<?php
session_start();

$area = ["admin", "api"];
$URL = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$splitURL = explode('/', trim($URL, '/'));

if (in_array($splitURL[1], $area)) {
    if ($splitURL[1] == 'admin') {
        $_SESSION['login'] = 'test';
        require_once 'admin/index.php';
    }
    exit;
} else if ($splitURL[1] == 'pages') {
    $tempath = '';
    if ($splitURL[2] == 'canbo') {
        $tempath = 'canbo/';
    } else if ($splitURL[2] == 'giaovien') {
        $tempath = 'giaovien/';
    } else if ($splitURL[2] == 'sinhvien') {
        $tempath = 'sinhvien/';
    } else if ($splitURL[2] == 'chung') {
        $tempath = 'chung/';
    }

    if (empty($tempath)) {
        $path = 'pages/' . $tempath . $splitURL[2] . '.php';
    } else {
        $path = 'pages/' . $tempath . $splitURL[3];
        if (!str_ends_with($path, '.php')) {
            $path .= '.php';
        }
    }

    if (file_exists($path)) {
        require_once($path);
    } else {
        require_once('404.php');
    }
} else {
    require_once('404.php');
}
