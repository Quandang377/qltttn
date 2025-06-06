<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký giấy giới thiệu</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
    <style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
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
                <h1 class="page-header">Đăng ký giấy giới thiệu</h1>
                <div class="row">
                    <div class="form-group col-md-4">
                         <label for="ma-so-thue">Nhập mã số thuế</label>
                        <div class="row">
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="ma-so-thue" placeholder="Mã số thuế">
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">Cập nhật</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="ten-cong-ty">Tên công ty</label>
                        <input type="text" class="form-control" id="ten-cong-ty" placeholder="Tên công ty">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="dia-chi">Địa chỉ</label>
                        <input type="text" class="form-control" id="dia-chi" placeholder="Địa chỉ">
                    </div>
                </div>
                
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <button type="button" class="btn btn-primary">Gửi yêu cầu</button>
                    </div>
                </div>

                <br>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách phiếu giới thiệu thực tập
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tên công ty</th>
                                        <th>Mã số thuế</th>
                                        <th>Địa chỉ</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>Đã duyệt</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>Riotgame</td>
                                        <td>0314419070</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>Từ chối</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>Tencent</td>
                                        <td>3702686925</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>Đã duyệt</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>