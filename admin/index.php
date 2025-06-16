
<?php
$splitURL = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$indexAdmin = array_search('admin', $splitURL);

if ($indexAdmin !== false && isset($splitURL[$indexAdmin + 1]) && $splitURL[$indexAdmin + 1] === 'pages') {
    $page = isset($splitURL[$indexAdmin + 2]) ? $splitURL[$indexAdmin + 2] : null;

    if ($page) {
        $path = 'admin/pages/' . $page . '.php';
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
