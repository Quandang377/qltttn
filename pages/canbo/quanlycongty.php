<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý công ty</title>
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
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
    ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Quản lý công ty</h1>
                <div class="row">
                    <div class="form-group col-md-12">
                         
                        <div class="row">
                            <div class="col-md-4">
                                <label for="ma-so-thue">Tên công ty</label>
                                <input type="text" class="form-control" id="ten-cong-ty" placeholder="Tên công ty">
                            </div>
                            <div class="col-md-4">
                                <label for="ma-so-thue">SĐT</label>
                                <input type="text" class="form-control" id="sdt" placeholder="SĐt">
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
                
                <div class="row">
                            <div class="col-md-4">
                                <label for="ma-so-thue">Mã số thuế</label>
                                <input type="text" class="form-control" id="ma-so-thue" placeholder="Mã số thuế">
                            </div>
                            <div class="col-md-4">
                                <label for="ma-so-thue">Địa chỉ</label>
                                <textarea class="form-control" maxlength="200" id="dia-chi" rows="4" style="resize: none;" placeholder="Địa chỉ"></textarea>
                            </div>
                        </div>
                
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-plus" style="margin-right: 5px;"></i> Thêm
                            </button>
                    </div>
                    <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-trash-o" style="margin-right: 5px;"></i> Xóa
                            </button>
                    </div>
                    <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-pencil" style="margin-right: 5px;"></i> Sửa
                            </button>
                    </div><div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button"  class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-search" style="margin-right: 5px;"></i> Duyệt
                            </button>
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
                                        <th>SĐT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>093333333333</td>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>093333333333</td>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>093333333333</td>
                                    </tr><tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>093333333333</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('dia-chi').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') e.preventDefault();
    });
</script>
</body>
</html>