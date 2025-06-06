<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý thông báo</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
        
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
    ?>
<div id="page-wrapper">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Quản Lý Thông Báo</h1>
        </div>
    </div>
<div class="row Notification">
    <div class="col-md-9">
        <a href="pages/canbo/chitietthongbao" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default">
          <div class="panel-heading">Thông báo số 1</div>
          <div class="panel-body">
            <p>Nội dung thông báo số 1.</p>
          </div>
        </div>
      </a>
      <a href="pages/canbo/chitietthongbao" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default">
          <div class="panel-heading">Thông báo số 1</div>
          <div class="panel-body">
            <p>Nội dung thông báo số 1.</p>
          </div>
        </div>
      </a>
      <a href="pages/canbo/chitietthongbao" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default">
          <div class="panel-heading">Thông báo số 1</div>
          <div class="panel-body">
            <p>Nội dung thông báo số 1.</p>
          </div>
        </div>
      </a>
      <a href="pages/canbo/chitietthongbao" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default">
          <div class="panel-heading">Thông báo số 1</div>
          <div class="panel-body">
            <p>Nội dung thông báo số 1.</p>
          </div>
        </div>
      </a>
      </div>
      <div class="col-md-2">
        <div class="fill">
            <select id="Lọc" name="Lọc"class="form-control">
            <option value="Mới nhất">Mới nhất</option>
            <option value="Cũ nhất">Thông báo của tôi</option>
            <option value="Cũ nhất">Cũ nhất</option>
            </select>
        </div>
    </div>
    <div style="position: fixed; bottom: 15%; right: 10%;">
    <a href="pages/canbo/taothongbao" class="fixed-button btn btn-primary btn-lg">
    Tạo thông báo
    </a>
    </div>
    </div>
