<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$ID_TaiKhoan = "6";
$stmt = $conn->prepare("SELECT VaiTro FROM TaiKhoan WHERE ID_TaiKhoan = ?");
$stmt->execute([$ID_TaiKhoan]);
$vaiTro = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT ks.*
    FROM KhaoSat ks
    WHERE ks.TrangThai = 1
    AND ks.NguoiNhan IN (?, ?)  -- truyền vào 'Tất cả' và vai trò
    AND ks.ID NOT IN (
        SELECT ID_KhaoSat 
        FROM PhanHoiKhaoSat 
        WHERE ID_TaiKhoan = ?
    )
    ORDER BY ks.ThoiGianTao DESC
");
$stmt->execute(['Tất cả', $vaiTro, $ID_TaiKhoan]);

$dsKhaoSat = $stmt->fetchAll(PDO::FETCH_ASSOC);
$dsID = array_column($dsKhaoSat, 'ID');
$dsCauHoiTheoKhaoSat = [];
if (!empty($dsID)) {
    $placeholders = implode(',', array_fill(0, count($dsID), '?'));
    $sqlCauHoi = "SELECT * FROM CauHoiKhaoSat WHERE ID_KhaoSat IN ($placeholders)";
    $stmtCauHoi = $conn->prepare($sqlCauHoi);
    $stmtCauHoi->execute($dsID);
    $tatCaCauHoi = $stmtCauHoi->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tatCaCauHoi as $ch) {
        $dsCauHoiTheoKhaoSat[$ch['ID_KhaoSat']][] = $ch;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_khaosat_id'])) {
    $idKhaoSat = $_POST['xoa_khaosat_id'];

    $stmt = $conn->prepare("UPDATE KhaoSat SET TrangThai = 0 WHERE ID = ?");
    $stmt->execute([$idKhaoSat]);

    $_SESSION['success'] = "Xoá khảo sát thành công.";
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tạo khảo sát</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_GiaoVien.php";
    ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Tạo khảo sát
                    </h1>
                </div><div class="form-container">
                <form id="formKhaoSat" method="post">
                    <div class="form-group">
                        <label><strong>Gửi đến</strong></label>
                        <select id="for" name="Lọc" class="form-control" style="width: 200px;">
                            <option value="Sinh viên">Sinh Viên</option>
                            <option value="Giáo viên">Giáo Viên</option>
                            <option value="Tất cả">Tất cả</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>Tiêu đề</label>
                                <input class="form-control" id="tieude" name="tieude" type="text" placeholder="Nhập tiêu đề cho khảo sát">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>Mô tả</label>
                                <input class="form-control" id="mota" name="mota" type="text" placeholder="Nhập mô tả">
                            </div>
                        </div>
                    </div>
                    <div id="danhSachCauHoi">
                        <div class="form-group cau-hoi-item">
                            <label>Câu hỏi</label>
                            <div class="input-group">
                                <input type="text" name="cauhoi[]" class="form-control" placeholder="Nhập nội dung câu hỏi">
                                <span class="input-group-btn">
                                    <button class="btn btn-danger btn-remove" type="button">
                                        <i class="glyphicon glyphicon-remove"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-right">
                        <button type="button" class="btn btn-primary" id="btnThemCauHoi">Thêm câu hỏi</button>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-success btn-lg">Gửi</button>
                    </div>
                </form>
            </div>
            </div>
        <div id="notification" style="display: none;"></div>
        <div id="containerKhaoSat" class="mt-3">
            <h2>Danh sách các khảo sát</h2>
            <div id="listKhaoSat" class="row">
        </div>
        <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tiêu đề</th>
                                                    <th>Người tạo</th>
                                                    <th>ngày tạo</th>
                                                    <th>Phản hồi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr onclick="window.location='pages/canbo/chitietkhaosat';" style="cursor: pointer;">
                                                    <td>1</td>
                                                    <td>Khảo sát thực tập</td>
                                                    <td>Lữ Cao Tiến</td>
                                                    <td>1/1/2025</td>
                                                    <td>35</td>
                                                </tr>
                                                <tr onclick="window.location='pages/canbo/chitietkhaosat';" style="cursor: pointer;">
                                                    <td>3</td>
                                                    <td>Khảo sát thực tập</td>
                                                    <td>Lữ Cao Tiến</td>
                                                    <td>1/1/2025</td>
                                                    <td>35</td>
                                                </tr>
                                                <tr onclick="window.location='pages/canbo/chitietkhaosat';" style="cursor: pointer;">
                                                    <td>3</td>
                                                    <td>Khảo sát thực tập</td>
                                                    <td>Lữ Cao Tiến</td>
                                                    <td>1/1/2025</td>
                                                    <td>35</td>
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
                    </div>
</div>      
</div>
<script>
$(document).ready(function () {
    $('#btnThemCauHoi').click(function () {
        var cauHoiMoi = `
            <div class="row">
                <div class="col-lg-12 form-group cau-hoi-item">
                    <label>Câu hỏi</label>
                    <div class="input-group">
                        <input type="text" name="cauhoi[]" class="form-control" placeholder="Nhập nội dung câu hỏi">
                        <span class="input-group-btn">
                            <button class="btn btn-danger btn-remove" type="button">
                                <i class="glyphicon glyphicon-remove"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>`;
        $('#danhSachCauHoi').append(cauHoiMoi);
    });
    $(document).on('click', '.btn-remove', function () {
        $(this).closest('.cau-hoi-item').remove();
    });
});
</script>
