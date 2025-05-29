<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Tuần</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
        
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";
    ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Báo Cáo Tuần
                    </h1>
                </div>
                <div class="d-flex justify-content-center align-items-center" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <i class="fa fa-chevron-left" style="cursor: pointer;"></i>
                <h3 style="margin: 0;">Tuần 19</h3>
                    <i class="fa fa-chevron-right" style="cursor: pointer;"></i>
                </div>
                <div class="row">
                        <label for="Thu-2">Thứ 2</label>
                        <input type="text" class="form-control" id="Thu-2" placeholder="Công việc thực hiện">
                </div>
                <div class="row" style="padding-top: 10px;">
                        <label for="Thu-3">Thứ 3</label>
                        <input type="text" class="form-control" id="Thu-3" placeholder="Công việc thực hiện">
                </div>
                <div class="row" style="padding-top: 10px;">
                        <label for="Thu-4">Thứ 4</label>
                        <input type="text" class="form-control" id="Thu-4" placeholder="Công việc thực hiện">
                </div>
                <div class="row" style="padding-top: 10px;">
                        <label for="Thu-5">Thứ 5</label>
                        <input type="text" class="form-control" id="Thu-5" placeholder="Công việc thực hiện">
                </div>
                <div class="row" style="padding-top: 10px;">
                        <label for="Thu-6">Thứ 6</label>
                        <input type="text" class="form-control" id="Thu-6" placeholder="Công việc thực hiện">
                </div>
                <div class="row" style="padding-top: 10px;">
                        <label for="Thu-7">Thứ 7</label>
                        <input type="text" class="form-control" id="Thu-7" placeholder="Công việc thực hiện">
                </div>
                <div class="justify-content-center align-items-center" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 20px;">
                <button type="button" class="btn btn-success" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-pencil" style="margin-right: 5px;"></i> Lưu
                            </button>
                </div>
            </div>
            
        </div>
    </div>

    
</body>
</html>