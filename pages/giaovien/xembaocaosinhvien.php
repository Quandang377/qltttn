<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id_gvhd = '6';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['luu_tuan'])) {
    $tuanArr = $_POST['tuan'] ?? [];
    try {
        for ($i = 1; $i <= 8; $i++) {
            $trangThai = isset($tuanArr[$i]) ? 1 : 0;
            $stmt = $conn->prepare("SELECT ID FROM TuanBaoCao WHERE ID_GVHD = ? AND Tuan = ?");
            $stmt->execute([$id_gvhd, $i]);
            if ($stmt->fetch()) {
                $stmt = $conn->prepare("UPDATE TuanBaoCao SET TrangThai = ? WHERE ID_GVHD = ? AND Tuan = ?");
                $stmt->execute([$trangThai, $id_gvhd, $i]);
            } else {
                $stmt = $conn->prepare("INSERT INTO TuanBaoCao (ID_GVHD, Tuan, TrangThai) VALUES (?, ?, ?)");
                $stmt->execute([$id_gvhd, $i, $trangThai]);
            }
        }
        echo "<script>location.href=location.href;</script>";
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Có lỗi khi lưu trạng thái tuần: " . htmlspecialchars($e->getMessage());
    }
}

// Lấy trạng thái tuần đã lưu cho giáo viên này
$trangThaiTuan = [];
try {
    $stmt = $conn->prepare("SELECT Tuan, TrangThai FROM TuanBaoCao WHERE ID_GVHD = ?");
    $stmt->execute([$id_gvhd]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $trangThaiTuan[$row['Tuan']] = $row['TrangThai'];
    }
} catch (PDOException $e) {
    $errorMsg = "Có lỗi khi lấy trạng thái tuần: " . htmlspecialchars($e->getMessage());
}

// Lấy danh sách sinh viên thuộc giáo viên này
$stmt = $conn->prepare("
    SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV
    FROM SinhVien sv
    WHERE sv.ID_GVHD = ?
");
$stmt->execute([$id_gvhd]);
$sinhviens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy các tuần đã mở
$weeks = [];
$stmt = $conn->prepare("SELECT DISTINCT Tuan FROM TuanBaoCao WHERE ID_GVHD = ? ORDER BY Tuan ASC");
$stmt->execute([$id_gvhd]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $weeks[] = $row['Tuan'];
}
$tuan_cao_nhat = !empty($weeks) ? max($weeks) : 0;
// Thống kê theo tuần (giả định mỗi tuần có 1 báo cáo)
$tuan_thongke = [];
for ($tuan = 1; $tuan <= $tuan_cao_nhat; $tuan++) { // Giả sử có 4 tuần
    $da_nop = 0;
    $chua_nop = 0;
    
    foreach ($sinhviens as $sv) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM baocao WHERE IDSV = ?  AND Tuan = ?");
        $stmt->execute([$sv['ID_TaiKhoan'], $tuan]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $da_nop++;
        } else {
            $chua_nop++;
        }
    }
    
    $tuan_thongke[$tuan] = [
        'da_nop' => $da_nop,
        'chua_nop' => $chua_nop
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách sinh viên</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
    }
    </style>
    <style>
    .stat-container {
    margin-bottom: 30px;
}
.stat-card {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #f9f9f9;
}
.stat-header {
    font-weight: bold;
    margin-bottom: 10px;
    color: #333;
}
.stat-value {
    font-size: 18px;
}
.stat-success {
    color: #28a745;
}
.stat-danger {
    color: #dc3545;
}
    </style>
</head>
<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php"; ?>

        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Danh sách sinh viên</h1>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                <?php endif; ?>
                <!-- Form đóng/mở tuần -->
                <form id="form-tuan" class="mb-3" method="post">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Đóng/mở tuần báo cáo</strong></div>
                        <div class="panel-body">
                            <div class="row">
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom:10px;">
                                        <label>
                                            <input type="checkbox" class="toggle-tuan" name="tuan[<?php echo $i; ?>]" value="1"
                                                <?php if (isset($trangThaiTuan[$i]) && $trangThaiTuan[$i]) echo 'checked'; ?>
                                            >
                                            Tuần <?php echo $i; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="text-right" style="margin-top:15px;">
                                <button type="submit" name="luu_tuan" class="btn btn-primary">
                                    Lưu trạng thái tuần
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <!-- Thống kê theo tuần -->
                    <div class="stat-container">
                        <h3>Thống kê nộp báo cáo theo tuần</h3>
                        <div class="row">
                            <?php foreach ($tuan_thongke as $tuan => $thongke): ?>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-header">Tuần <?php echo $tuan; ?></div>
                                        <div class="stat-value stat-success">Đã nộp: <?php echo $thongke['da_nop']; ?></div>
                                        <div class="stat-value stat-danger">Chưa nộp: <?php echo $thongke['chua_nop']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <!-- Bảng sinh viên phía dưới -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-md-4">
                                Danh sách sinh viên thuộc quản lí 
                            </div>
                          <div id="filter-container" class="col-md-4 col-md-offset-4"></div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="table-dsbaocao">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>MSSV</th>
                                        <th>Họ tên</th>
                                        <th>Tuần</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $stt = 1; foreach ($sinhviens as $sv): ?>
                                        <?php foreach ($weeks as $tuan): ?>
                                            <?php
                                            // Kiểm tra sinh viên này đã có báo cáo tuần này chưa
                                            $stmt2 = $conn->prepare("SELECT COUNT(*) FROM BaoCao WHERE IDSV = ? AND IdGVHD = ? AND Tuan = ?");
                                            $stmt2->execute([$sv['ID_TaiKhoan'], $id_gvhd, 'Tuần '.$tuan]);
                                            $hasReport = $stmt2->fetchColumn() > 0;
                                            ?>
                                            <tr>
                                                <td><?php echo $stt++; ?></td>
                                                <td><?php echo htmlspecialchars($sv['MSSV']); ?></td>
                                                <td><?php echo htmlspecialchars($sv['Ten']); ?></td>
                                                <td>Tuần <?php echo $tuan; ?></td>
                                                <td>
                                                    <?php if ($hasReport): ?>
                                                        <span class="text-success">Đã nộp</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Chưa nộp</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($hasReport): ?>
                                                        <form method="post" action="/datn/pages/giaovien/chitietbaocaosinhvien" style="display:inline;">
                                                            <input type="hidden" name="idsv" value="<?php echo $sv['ID_TaiKhoan']; ?>">
                                                            <input type="hidden" name="tuan" value="<?php echo $tuan; ?>">
                                                            <button type="submit" class="btn btn-info btn-xs">Xem</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php";

    ?>
    <script>
$(document).ready(function () {
    // Kiểm tra và destroy nếu đã khởi tạo
    if ($.fn.DataTable.isDataTable('#table-dsbaocao')) {
        $('#table-dsbaocao').DataTable().destroy();
    }

    // Khởi tạo DataTable
    var table = $('#table-dsbaocao').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        initComplete: function() {
            // Thêm dropdown lọc tuần
            var select = $('<select class="form-control"><option value="">Tất cả tuần</option></select>')
                .appendTo('#filter-container')
                .on('change', function() {
                    var week = $(this).val();
                    table.column(3).search(week ? '^Tuần ' + week + '$' : '', true, false).draw();
                });

            // Thêm các tuần từ biến PHP $weeks (đã sắp xếp giảm dần)
            <?php 
            rsort($weeks);
            foreach ($weeks as $tuan): ?>
                select.append('<option value="<?php echo $tuan; ?>" <?php echo ($tuan == $tuan_cao_nhat) ? 'selected' : ''; ?>>Tuần <?php echo $tuan; ?></option>');
            <?php endforeach; ?>

            // Tự động lọc theo tuần cao nhất khi trang tải
            <?php if ($tuan_cao_nhat > 0): ?>
                table.column(3).search('^Tuần <?php echo $tuan_cao_nhat; ?>$', true, false).draw();
            <?php endif; ?>
        }
    });
});
</script>
    
</body>
</html>
