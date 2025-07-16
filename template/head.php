<?php
if (!function_exists('isLocalhost')) {
    function isLocalhost() {
        return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || 
               strpos($_SERVER['HTTP_HOST'], '.local') !== false;
    }
}
?>
<base href="<?= (isLocalhost() ? '/datn/' : '/datn/') ?>">
<link href="access/css/bootstrap.min.css" rel="stylesheet">
<link href="access/css/startmin.css" rel="stylesheet">
<link href="access/css/font-awesome.min.css" rel="stylesheet">
<link href="access/css/dataTables/dataTables.bootstrap.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<!-- <script src="access/ckeditor5/ckeditor.js?v=123"></script> -->
	
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
    }
</style>