<?php
$URL = $_SERVER['REQUEST_URI'];
$splitURL = explode('/', $URL);
if($splitURL[3]=='pages'){
    echo $_SESSION['login'];
    $path = 'admin/pages/'.$splitURL[4].'.php';
    require_once($path);
}else {
    require_once('404.php');
}