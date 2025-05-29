<?php
session_start();

$area= ["admin","api"];
$URL = $_SERVER['REQUEST_URI'];
$splitURL = explode('/', $URL);
if(in_array($splitURL[2],$area)){
   if($splitURL[2]=='admin'){
    $_SESSION['login']='test';
    require_once 'admin/index.php';
   }
    
    exit;
}
else if($splitURL[2]=='pages'){
    $tempath='';
    if($splitURL[3]== 'canbo'){
        
        $tempath='canbo/';
    }
    else if($splitURL[3]== 'giaovien'){
        $tempath='giaovien/';
    } else if($splitURL[3]== 'sinhvien'){
        $tempath='sinhvien/';
    }
    if(empty($tempath)){
        $path = 'pages/'.$tempath.$splitURL[3].'.php';
    }
    else 
    $path = 'pages/'.$tempath.$splitURL[4].'.php';
    if(file_exists($path)) require_once($path);
    else require_once('404.php');
}
else {
    require_once('404.php');
}