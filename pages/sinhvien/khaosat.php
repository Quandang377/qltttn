<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Khảo sát</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_SinhVien.php";
    ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Khảo Sát
                    </h1>
                </div>
        <div id="containerKhaoSat" class="mt-3">
            <div id="listKhaoSat" class="row">
        </div>
        <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                   Các khảo sát cần phản hồi
            <div id="listKhaoSat" class="row">
        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tiêu đề</th>
                                        <th>Người gửi</th>
                                        <th>ngày tạo</th>
                                        <th>Phản hồi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="cursor: pointer;" onclick="hienPhanHoi('Khảo sát thực tập')">
                                        <td>1</td>
                                        <td>Khảo sát thực tập</td>
                                        <td>Lữ Cao Tiến</td>
                                        <td>1/1/2025</td>
                                    </tr>
                                    <tr style="cursor: pointer;" onclick="hienPhanHoi('Khảo sát thực tập')">
                                        <td>2</td>
                                        <td>Khảo sát thực tập</td>
                                        <td>Lữ Cao Tiến</td>
                                        <td>1/1/2025</td>
                                    </tr>
                                    <tr style="cursor: pointer;" onclick="hienPhanHoi('Khảo sát thực tập')">
                                        <td>3</td>
                                        <td>Khảo sát thực tập</td>
                                        <td>Lữ Cao Tiến</td>
                                        <td>1/1/2025</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- /.table-responsive -->
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            </div>
            <!-- /.col-lg-6 -->
        </div><!-- /.col-lg-6 -->
        </div>
<div class="modal fade" id="modalPhanHoi" tabindex="-1" role="dialog" aria-labelledby="modalPhanHoiLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modalPhanHoiLabel">Khảo sát thực tập</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label>Câu hỏi số 1</label>
            <input type="text" class="form-control" name="traloi1" placeholder="Nhập câu trả lời...">
        </div>
        <div class="form-group">
            <label>Câu hỏi số 2</label>
            <input type="text" class="form-control" name="traloi2" placeholder="Nhập câu trả lời...">
        </div>
        <div class="form-group">
            <label>Câu hỏi số 3</label>
            <input type="text" class="form-control" name="traloi3" placeholder="Nhập câu trả lời...">
        </div>
        </div>
        <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Gửi</button>
        </div>
    </div>
  </div>
</div>
</div>  
<script>
function hienPhanHoi(ten) {
    document.getElementById('modalPhanHoiLabel').textContent = ten;
    $('#modalPhanHoi').modal('show');
}
</script>
</div

