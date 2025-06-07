<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nộp kết quả</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
      <style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
        overflow-y: auto;
    }
    
</style>
</head>
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";
    ?>
        
        <div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
        <h1 class="page-header ">Nộp kết quả</h1>
        

        </div>
        <button id="uploadButton" class="btn btn-success" style="margin-bottom: 10px">
         <i class="fa fa-upload"></i> Tải lên
                </button>
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-default" style="padding: 20px;background-color: #7ae98c;">
                <div style="display: flex; align-items: center;">
                    <i class="fa fa-file-o fa-fw" style="margin-right: 12px; font-size: 28px; color: white"></i>
                <div>
                    <div style="font-size: 20px; font-weight: bold;">Báo cáo của tôi</div>
                    <div style="font-size: 12px; font-weight: bold;">Đặng Minh Quân</div>
                </div>
                </div>
            </div>

        </div>
    </div> 
    </div>
</div>
    </div>
</body>
</html>