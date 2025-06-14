

<?php
$URL = $_SERVER['REQUEST_URI'];
$splitURL = explode('/', $URL);

if (isset($splitURL[3]) && $splitURL[3] == 'pages') {

    if (!isset($_SESSION['login'])) 
        echo "Bạn chưa đăng nhập!";
    $page = isset($splitURL[4]) ? $splitURL[4] : null;
    if ($page) {
        $path = 'admin/pages/'.$page.'.php';
        if (file_exists($path)) {
            require_once($path);
        } else {
            require_once('404.php'); 
        }
    } else {
        require_once('404.php');  
    }
} else {
    require_once('404.php'); 
}
?>
