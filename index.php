<?php
session_start();

$area= ["admin","api"];
$URL = $_SERVER['REQUEST_URI'];
$splitURL = explode('/', $URL);
if($splitURL[2]=='admin'){
    $_SESSION['login'] = 'concac';
    require_once 'admin/index.php';
    exit;
}
else if($splitURL[2]=='pages'){
    $path = 'pages/'.$splitURL[3].'.php';
    require_once($path);
}
else {
    require_once('404.php');
}