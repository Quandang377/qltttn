<?php
session_start();
$area= array("admin","api");
$URL = $_SERVER['REQUEST_URI'];
$splitURL = explode('/', $URL);
if($splitURL[1]=='admin'){
    $_SESSION['login'] = 'admin';
}
else {
echo 'Đây là trang chủ user';
}