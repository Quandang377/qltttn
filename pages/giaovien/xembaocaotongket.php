<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

// Lấy id tài khoản giáo viên đang đăng nhập
$id_gvhd = $_SESSION['user']['ID_TaiKhoan'] ?? null;

// Nếu chưa đăng nhập thì chuyển hướng hoặc báo lỗi
if (!$id_gvhd) {
    die('Bạn chưa đăng nhập!');
}
$errorMsg = '';

// Đóng/mở cho phép nộp báo cáo tổng kết
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['luu_trangthai_tongket']) && isset($_POST['id_dot'])) {
    $id_dot = (int)$_POST['id_dot'];
    $trangthai_tongket = isset($_POST['trangthai_tongket']) ? 1 : 0;
    $stmt = $conn->prepare("SELECT ID FROM Baocaotongket WHERE ID_TaiKhoan = ? AND ID_Dot = ?");
    $stmt->execute([$id_gvhd, $id_dot]);
    if ($stmt->fetch()) {
        $stmt = $conn->prepare("UPDATE Baocaotongket SET TrangThai = ? WHERE ID_TaiKhoan = ? AND ID_Dot = ?");
        $stmt->execute([$trangthai_tongket, $id_gvhd, $id_dot]);
    } else {
        $stmt = $conn->prepare("INSERT INTO Baocaotongket (ID_TaiKhoan, ID_Dot, TrangThai) VALUES (?, ?, ?)");
        $stmt->execute([$id_gvhd, $id_dot, $trangthai_tongket]);
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Lấy trạng thái cho phép nộp báo cáo tổng kết
$stmt = $conn->prepare("SELECT TrangThai FROM Baocaotongket WHERE ID_TaiKhoan = ?");
$stmt->execute([$id_gvhd]);
$trangthai_tongket = $stmt->fetchColumn();
if ($trangthai_tongket === false) $trangthai_tongket = 0;

// Lấy danh sách sinh viên thuộc giáo viên này và cùng đợt
$stmt = $conn->prepare("
    SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV
    FROM SinhVien sv
    WHERE sv.ID_GVHD = ? AND sv.ID_Dot = ?
");
$sinhviens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy trạng thái, đường dẫn báo cáo tổng kết, ngày nộp và số lần nộp/xóa/sửa của sinh viên
$baocao_tongket = [];
foreach ($sinhviens as $sv) {
    // Lấy file báo cáo mới nhất
    $stmt2 = $conn->prepare("SELECT TenFile, Dir, NgayNop FROM file WHERE ID_SV = ? AND Loai = 'Baocao' AND TrangThai = 1 ORDER BY ID DESC LIMIT 1");
    $stmt2->execute([$sv['ID_TaiKhoan']]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Đếm tổng số file báo cáo đã nộp (bao gồm cả đã xóa/sửa)
    $stmt3 = $conn->prepare("SELECT COUNT(*) FROM file WHERE ID_SV = ? AND Loai = 'Baocao'");
    $stmt3->execute([$sv['ID_TaiKhoan']]);
    $solan = $stmt3->fetchColumn();

    if ($row) {
        $baocao_tongket[$sv['ID_TaiKhoan']] = [
            'TenFile' => $row['TenFile'],
            'Dir' => $row['Dir'],
            'NgayNop' => $row['NgayNop'],
            'SoLan' => $solan
        ];
    } else {
        $baocao_tongket[$sv['ID_TaiKhoan']] = [
            'TenFile' => null,
            'Dir' => null,
            'NgayNop' => null,
            'SoLan' => $solan
        ];
    }
}

// Xử lý tải xuống tất cả báo cáo thành file zip
if (isset($_GET['download_all']) && $_GET['download_all'] == 1) {
    $zip = new ZipArchive();
    $zipName = 'baocao_tongket_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;
    if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
        foreach ($baocao_tongket as $sv_id => $bc) {
            if ($bc && file_exists($bc['Dir'])) {
                // Đặt tên file trong zip là đúng tên file gốc (không thêm MSSV)
                $zip->addFile($bc['Dir'], $bc['TenFile']);
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        exit;
    } else {
        $errorMsg = "Không thể tạo file zip!";
    }
}

// Lấy tất cả các đợt mà giáo viên này đang hướng dẫn sinh viên, trạng thái >= 3
$stmt = $conn->prepare("
    SELECT dt.ID, dt.TenDot, dt.TrangThai
    FROM DotThucTap dt
    WHERE dt.ID IN (
        SELECT DISTINCT sv.ID_Dot
        FROM SinhVien sv
        WHERE sv.ID_GVHD = ?
    ) AND dt.TrangThai >= 3
    ORDER BY dt.ID DESC
");
$stmt->execute([$id_gvhd]);
$dots = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ds_sinhvien_theo_dot = [];
foreach ($dots as $dot) {
    $stmt = $conn->prepare("
        SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV
        FROM SinhVien sv
        WHERE sv.ID_GVHD = ? AND sv.ID_Dot = ?
    ");
    $stmt->execute([$id_gvhd, $dot['ID']]);
    $ds_sinhvien_theo_dot[$dot['ID']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$baocao_tongket = [];
$diemData = [];
foreach ($ds_sinhvien_theo_dot as $dot_id => $sinhviens) {
    foreach ($sinhviens as $sv) {
        // Lấy file báo cáo mới nhất và điểm (join)
        $stmt2 = $conn->prepare("
            SELECT 
                f.TenFile, f.Dir, f.NgayNop,
                d.Diem_BaoCao, d.Diem_ChuyenCan, d.Diem_ChuanNghe, d.Diem_ThucTe, d.GhiChu
            FROM file f
            LEFT JOIN diem_tongket d ON d.ID_SV = f.ID_SV AND d.ID_Dot = ?
            WHERE f.ID_SV = ? AND f.Loai = 'Baocao' AND f.TrangThai = 1
            ORDER BY f.ID DESC
            LIMIT 1
        ");
        $stmt2->execute([$dot_id, $sv['ID_TaiKhoan']]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        // Lưu thông tin file báo cáo
        $baocao_tongket[$dot_id][$sv['ID_TaiKhoan']] = [
            'TenFile' => $row['TenFile'] ?? null,
            'Dir' => $row['Dir'] ?? null,
            'NgayNop' => $row['NgayNop'] ?? null
        ];

        // Lưu thông tin điểm (nếu có)
        $diemData[$dot_id][$sv['ID_TaiKhoan']] = [
            'diem_baocao' => $row['Diem_BaoCao'] ?? null,
            'diem_chuyencan' => $row['Diem_ChuyenCan'] ?? null,
            'diem_chuannghe' => $row['Diem_ChuanNghe'] ?? null,
            'diem_thucte' => $row['Diem_ThucTe'] ?? null,
            'ghichu' => $row['GhiChu'] ?? null
        ];
    }
}

// Lấy trạng thái nộp báo cáo tổng kết cho từng đợt của giáo viên
$trangthai_tongket_dot = [];
$stmt = $conn->prepare("SELECT ID_Dot, TrangThai FROM Baocaotongket WHERE ID_TaiKhoan = ?");
$stmt->execute([$id_gvhd]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $trangthai_tongket_dot[$row['ID_Dot']] = $row['TrangThai'];
}

// Kiểm tra và xử lý lưu điểm từ form/modal nhập điểm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['diem_baocao'], $_POST['diem_chuyencan'], $_POST['diem_chuannghe'], $_POST['diem_thucte'], $_POST['id_sv'])) {
    $id_sv = (int)$_POST['id_sv'];
    $diem_baocao = $_POST['diem_baocao'];
    $diem_chuyencan = $_POST['diem_chuyencan'];
    $diem_chuannghe = $_POST['diem_chuannghe'];
    $diem_thucte = $_POST['diem_thucte'];
    $ghichu = $_POST['ghichu'] ?? null;

    // Nếu có ID_Dot, lấy từ POST hoặc truyền thêm vào form/modal
    $id_dot = null;
    if (isset($_POST['id_dot'])) {
        $id_dot = (int)$_POST['id_dot'];
    }

    // Kiểm tra đã có điểm chưa (theo ID_SV và ID_Dot nếu có)
    $sql_check = "SELECT ID FROM diem_tongket WHERE ID_SV = ?" . ($id_dot ? " AND ID_Dot = ?" : "");
    $params = $id_dot ? [$id_sv, $id_dot] : [$id_sv];
    $stmt = $conn->prepare($sql_check);
    $stmt->execute($params);

    if ($stmt->fetch()) {
        // Update
        $sql_update = "UPDATE diem_tongket SET Diem_BaoCao=?, Diem_ChuyenCan=?, Diem_ChuanNghe=?, Diem_ThucTe=?, GhiChu=? WHERE ID_SV=?"
            . ($id_dot ? " AND ID_Dot=?" : "");
        $params_update = [$diem_baocao, $diem_chuyencan, $diem_chuannghe, $diem_thucte, $ghichu, $id_sv];
        if ($id_dot) $params_update[] = $id_dot;
        $stmt = $conn->prepare($sql_update);
        $stmt->execute($params_update);
    } else {
        // Insert
        $sql_insert = "INSERT INTO diem_tongket (ID_SV, Diem_BaoCao, Diem_ChuyenCan, Diem_ChuanNghe, Diem_ThucTe, GhiChu" . ($id_dot ? ", ID_Dot" : "") . ")
            VALUES (?, ?, ?, ?, ?, ?" . ($id_dot ? ", ?" : "") . ")";
        $params_insert = [$id_sv, $diem_baocao, $diem_chuyencan, $diem_chuannghe, $diem_thucte, $ghichu];
        if ($id_dot) $params_insert[] = $id_dot;
        $stmt = $conn->prepare($sql_insert);
        $stmt->execute($params_insert);
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tổng kết sinh viên</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
    body {
        background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
        font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        background: none;
    }
    .page-header, h1.page-header {
        font-size: 2.2rem;
        font-weight: 700;
        color: #007bff;
        letter-spacing: 1px;
        margin-bottom: 32px;
        text-align: center;
        text-shadow: 0 2px 8px #b6d4fe44;
    }
    .panel {
        border-radius: 18px !important;
        border: 2px solid #e3eafc !important;
        background: #fff;
        box-shadow: 0 2px 16px rgba(0,123,255,0.07);
        margin-bottom: 28px;
        transition: box-shadow 0.2s, border-color 0.2s, background 0.2s;
    }
    .panel-heading {
        font-size: 18px;
        color: #007bff;
        font-weight: 700;
        background: linear-gradient(90deg, #e3f0ff 70%, #f8fafc 100%);
        border-radius: 18px 18px 0 0;
        padding: 14px 24px;
        border-bottom: 1.5px solid #e3eafc;
    }
    .panel-body {
        background: #fafdff;
        border-radius: 0 0 18px 18px;
        font-size: 15px;
        padding: 18px 24px;
    }
    .btn, .btn-success {
        border-radius: 8px !important;
        font-weight: 600;
        box-shadow: 0 2px 8px #007bff22;
        border: none;
        background: linear-gradient(90deg, #007bff 70%, #5bc0f7 100%) !important;
        color: #fff !important;
        transition: background 0.2s, box-shadow 0.2s;
    }
    .btn:hover, .btn-success:hover {
        background: linear-gradient(90deg, #0056d2 70%, #3fa9f5 100%) !important;
        color: #fff !important;
        box-shadow: 0 4px 16px #007bff33;
    }
    .table {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 12px #007bff11;
    }
    .table thead th {
        background: #e3f0ff;
        color: #007bff;
        font-weight: 700;
        border-bottom: 2px solid #b6d4fe;
        font-size: 16px;
    }
    .table-striped tbody tr:nth-of-type(odd) {
        background: #fafdff;
    }
    .table-striped tbody tr:nth-of-type(even) {
        background: #f3f8ff;
    }
    .table td, .table th {
        vertical-align: middle !important;
    }
    .alert {
        border-radius: 10px;
        font-size: 16px;
    }
    @media (max-width: 991px) {
        #page-wrapper { padding: 10px; }
        .panel-body { padding: 12px 6px; }
        .panel-heading { padding: 10px 12px; }
        .table { font-size: 14px; }
        #page-wrapper .card.shadow-sm {
            max-height: none;
        }
    }
    @media (min-width: 768px) {
        .row .col-md-6:first-child {
            border-right: 2.5px dashed #b6d4fe;
            /* hoặc dùng solid nếu muốn nét liền: border-right: 2.5px solid #b6d4fe; */
        }
    }
    #page-wrapper .card.shadow-sm {
        min-height: unset !important;
        max-height: 70vh;
        overflow-y: auto;
        box-sizing: border-box;
    }
    .row-detail:hover {
        background: #e3f0ff !important;
        cursor: pointer;
    }
    .student-detail-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }
    .student-detail-table tr:not(:last-child) td {
        border-bottom: 1px dashed #b6d4fe;
        padding-bottom: 8px;
    }
    .student-detail-table td {
        padding-top: 8px;
        background: transparent;
    }
    .student-detail-label {
        color: #007bff;
        font-weight: 600;
        width: 160px;
        white-space: nowrap;
        padding-right: 12px;
    }
    .student-detail-value {
        color: #222;
        font-weight: 500;
    }
    .student-detail-icon {
        margin-right: 6px;
        color: #5bc0f7;
        font-size: 17px;
    }
    .student-detail-action {
        margin-top: 18px;
    }
    /* Tab đợt đơn giản, nhẹ nhàng, chủ đạo xanh dương */
.nav-tabs {
    border-bottom: 2px solid #b6d4fe;
    background: #fafdff;
    border-radius: 10px 10px 0 0;
    padding: 4px 8px 0 8px;
}
.nav-tabs .nav-link {
    color: #1565c0;
    font-weight: 600;
    border: 1.5px solid transparent;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    margin-right: 6px;
    background: #fafdff;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
    padding: 9px 20px 8px 20px;
    font-size: 15px;
}
.nav-tabs .nav-link.active, .nav-tabs .nav-link:focus {
    background: #e3f0ff;
    color: #0d47a1 !important;
    border-color: #2196f3 #2196f3 #fafdff #2196f3;
    font-weight: 700;
    box-shadow: 0 2px 8px #2196f322;
    z-index: 2;
}
.nav-tabs .nav-link:not(.active):hover {
    background: #e3f0ff;
    color: #1976d2;
    border-color: #b6d4fe #b6d4fe #fafdff #b6d4fe;
}
    .form-control.border-primary:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 2px #b6d4fe55;
}
    .btn-close-custom span {
    transition: background 0.2s, color 0.2s;
    box-shadow: 0 2px 8px #dc354555;
}
.btn-close-custom span:hover {
    background: #b52a37;
    color: #fff;
}
.modal-header {
    border-radius: 18px 18px 0 0 !important;
    background: linear-gradient(90deg,#e3f0ff 70%,#f8fafc 100%);
    padding-right: 2.5rem !important;
    overflow: visible;
}
    </style>
</head>
<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php"; ?>

        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Báo cáo tổng kết sinh viên</h1>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                <?php endif; ?>

                <?php if (empty($dots)): ?>
                    <div class="alert alert-warning text-center mb-4">
                        Không có đợt nào bạn đang hướng dẫn sinh viên (hoặc chưa có đợt nào trạng thái >= 3).
                    </div>
                <?php else: ?>
                    <!-- Nút tải xuống tất cả -->
                    <form method="get" style="margin-bottom: 15px;">
                        <button type="submit" name="download_all" value="1" class="btn btn-success">
                            <i class="fa fa-download"></i> Tải xuống tất cả báo cáo (ZIP)
                        </button>
                    </form>
                    <!-- Tabs và nội dung các đợt -->
                    <ul class="nav nav-tabs mb-3" id="dotTab" role="tablist">
                        <?php foreach ($dots as $i => $dot): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $i==0?'active':'' ?>" id="dot-tab-<?= $dot['ID'] ?>" data-toggle="tab" href="#dot-<?= $dot['ID'] ?>" role="tab">
                                    <?= htmlspecialchars($dot['TenDot']) ?> (Trạng thái: <?= $dot['TrangThai'] ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="tab-content" id="dotTabContent">
                        <?php foreach ($dots as $i => $dot): ?>
                            <div class="tab-pane fade <?= $i==0?'show active':'' ?>" id="dot-<?= $dot['ID'] ?>" role="tabpanel">
                                <div class="panel panel-default" style="max-width:1200px;margin:auto;">
                                    <!-- Toggle đóng/mở báo cáo tổng kết cho từng đợt -->
                                    <form method="post" class="mb-3 d-inline" id="form-trangthai-tongket-<?= $dot['ID'] ?>">
                                        <div class="panel panel-default" style="max-width: 350px; margin-bottom: 20px;">
                                            <div class="panel-heading"><strong>Đóng/mở nộp báo cáo tổng kết</strong></div>
                                            <div class="panel-body" style="display: flex; align-items: center; justify-content: center; padding: 18px 0;">
                                                <span style="margin-right: 12px;">Trạng thái:</span>
                                                <input type="checkbox" name="trangthai_tongket" value="1" id="toggle-trangthai-<?= $dot['ID'] ?>"
                                                    <?php if (!empty($trangthai_tongket_dot[$dot['ID']])) echo 'checked'; ?>
                                                    data-toggle="toggle" data-on="Mở" data-off="Đóng"
                                                    data-onstyle="success" data-offstyle="danger"
                                                >
                                                <input type="hidden" name="luu_trangthai_tongket" value="1">
                                                <input type="hidden" name="id_dot" value="<?= $dot['ID'] ?>">
                                            </div>
                                        </div>
                                    </form>
                                    <!-- Tiêu đề và thống kê -->
                                    <div class="panel-heading d-flex align-items-center" style="flex-wrap: wrap; justify-content: space-between;">
                                        <span>
                                            Danh sách sinh viên thuộc quản lí - <?= htmlspecialchars($dot['TenDot']) ?>
                                        </span>
                                        <?php
                                            $tong_sv = count($ds_sinhvien_theo_dot[$dot['ID']]);
                                            $so_nop = 0;
                                            foreach ($ds_sinhvien_theo_dot[$dot['ID']] as $sv) {
                                                if (!empty($baocao_tongket[$dot['ID']][$sv['ID_TaiKhoan']]['TenFile'])) $so_nop++;
                                            }
                                        ?>
                                        <span class="ml-auto" style="font-size:15px; color:#1976d2; min-width:220px; text-align:right;">
                                            <i class="fa fa-users"></i> Số lượng SV: <b><?= $tong_sv ?></b>
                                            &nbsp;|&nbsp;
                                            <i class="fa fa-file-text"></i> Đã nộp: <b><?= $so_nop ?></b>
                                        </span>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <!-- Bảng danh sách sinh viên -->
                                            <div class="col-md-6">
                                                <div class="table-responsive">
                                                    <?php if (empty($ds_sinhvien_theo_dot[$dot['ID']])): ?>
                                                        <div class="alert alert-warning text-center mb-0">
                                                            Không có sinh viên nào thuộc đợt này do bạn hướng dẫn.
                                                        </div>
                                                    <?php else: ?>
                                                    <table class="table table-striped table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>MSSV</th>
                                                                <th>Trạng thái báo cáo tổng kết</th>
                                                                <th>Thao tác</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php $stt = 1; foreach ($ds_sinhvien_theo_dot[$dot['ID']] as $sv): ?>
                                                                <tr class="row-detail"
                                                                    data-id="<?= $sv['ID_TaiKhoan'] ?>"
                                                                    data-dot="<?= $dot['ID'] ?>"
                                                                    style="cursor: pointer;">
                                                                    <td><?= $stt++ ?></td>
                                                                    <td><?= htmlspecialchars($sv['MSSV']) ?></td>
                                                                    <td>
                                                                        <?php if ($baocao_tongket[$dot['ID']][$sv['ID_TaiKhoan']]['TenFile']): ?>
                                                                            <span class="text-success">Đã nộp</span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">Chưa nộp</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($baocao_tongket[$dot['ID']][$sv['ID_TaiKhoan']]['TenFile']): ?>
                                                                            <a href="/datn/download.php?file=<?= urlencode(basename($baocao_tongket[$dot['ID']][$sv['ID_TaiKhoan']]['Dir'])) ?>"
                                                                               class="btn btn-success btn-xs" title="Tải xuống báo cáo">
                                                                                <i class="fa fa-download"></i> Tải xuống
                                                                            </a>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <!-- Chi tiết sinh viên -->
                                            <div class="col-md-6">
                                                <div id="student-detail-panel-<?= $dot['ID'] ?>" class="card shadow-sm p-4" style="border-radius:18px;background:#fff;min-height:420px;">
                                                    <h4 class="mb-3 text-primary">Chi tiết sinh viên</h4>
                                                    <div id="student-detail-content-<?= $dot['ID'] ?>">
                                                        <div class="text-muted text-center">Chọn sinh viên để xem chi tiết</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>                                               
    <link href="https://cdn.jsdelivr.net/npm/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
    <script>
    $(function() {
        $('[id^=toggle-trangthai-]').bootstrapToggle();
        $('[id^=toggle-trangthai-]').change(function() {
            var dotId = $(this).attr('id').replace('toggle-trangthai-', '');
            $('#form-trangthai-tongket-' + dotId).submit();
        });
    });
    $(document).ready(function () {
        $('#table-dsbaocao').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });
    });
    $(document).ready(function() {
        // Nếu không có hash trên URL, luôn mở tab đầu tiên
        if (!window.location.hash) {
            $('#dotTab a:first').tab('show');
        }
    });
    </script>

    <!-- Modal nhập điểm -->
    <div class="modal fade" id="modalNhapDiem" tabindex="-1" role="dialog" aria-labelledby="modalNhapDiemLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <form id="form-nhap-diem" method="post">
          <div class="modal-content" style="border-radius:18px; box-shadow:0 4px 24px #007bff33;">
            <div class="modal-header d-flex align-items-center justify-content-between" style="background:linear-gradient(90deg,#e3f0ff 70%,#f8fafc 100%); border-radius:18px 18px 0 0;">
              <h5 class="modal-title text-primary" id="modalNhapDiemLabel" style="font-weight:700;">
                <i class="fa fa-pencil-square-o"></i> Nhập điểm sinh viên
              </h5>
              <button type="button" class="close btn-close-custom" data-dismiss="modal" aria-label="Đóng" style="outline:none;">
                <span aria-hidden="true" style="font-size:2rem; color:#fff; background:#dc3545; border-radius:50%; width:36px; height:36px; display:inline-block; text-align:center; line-height:36px;">&times;</span>
              </button>
            </div>
            <div class="modal-body" style="background:#fafdff;">
              <input type="hidden" name="id_sv" id="modal-id-sv">
              <input type="hidden" name="id_dot" id="modal-id-dot">
              <div class="form-group mb-3">
                <label class="font-weight-bold text-primary">Điểm báo cáo <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="4" class="form-control border-primary" name="diem_baocao" required>
              </div>
              <div class="form-group mb-3">
                <label class="font-weight-bold text-primary">Điểm chuyên cần <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="2" class="form-control border-primary" name="diem_chuyencan" required>
              </div>
              <div class="form-group mb-3">
                <label class="font-weight-bold text-primary">Điểm chuẩn nghề <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="2" class="form-control border-primary" name="diem_chuannghe" required>
              </div>
              <div class="form-group mb-3">
                <label class="font-weight-bold text-primary">Điểm thực tế <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="2" class="form-control border-primary" name="diem_thucte" required>
              </div>
              <div class="form-group mb-2">
                <label class="font-weight-bold text-primary">Ghi chú</label>
                <textarea class="form-control border-primary" name="ghichu" rows="2" style="resize:vertical;"></textarea>
              </div>
            </div>
            <div class="modal-footer" style="background:#e3f0ff; border-radius:0 0 18px 18px;">
              <button type="submit" class="btn btn-primary" style="border-radius:8px; min-width:120px; font-weight:600;">
                <i class="fa fa-save"></i> Lưu lại
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <script>
    const sinhvienData = <?php echo json_encode($ds_sinhvien_theo_dot); ?>;
    const baocaoData = <?php echo json_encode($baocao_tongket); ?>;
    const diemData = <?php echo json_encode($diemData); ?>;

    function renderStudentDetail(id_sv, dot_id) {
        const sv = (sinhvienData[dot_id] || []).find(s => s.ID_TaiKhoan == id_sv);
        const bc = (baocaoData[dot_id] && baocaoData[dot_id][id_sv]) ? baocaoData[dot_id][id_sv] : {};
        const diem = (diemData[dot_id] && diemData[dot_id][id_sv]) ? diemData[dot_id][id_sv] : {};
        let html = '';
        if (!sv) {
            html = '<div class="text-muted text-center">Không tìm thấy sinh viên</div>';
        } else {
            html = `
        <table class="student-detail-table">
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-user"></i></span>Họ và tên:</td>
                <td class="student-detail-value">${sv.Ten || ''}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-id-card"></i></span>MSSV:</td>
                <td class="student-detail-value">${sv.MSSV || ''}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-clock-o"></i></span>Ngày giờ nộp:</td>
                <td class="student-detail-value">${bc.NgayNop || '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-file-text"></i></span>Điểm báo cáo:</td>
                <td class="student-detail-value">${diem.diem_baocao ?? '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-check-square-o"></i></span>Điểm chuyên cần:</td>
                <td class="student-detail-value">${diem.diem_chuyencan ?? '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-graduation-cap"></i></span>Điểm chuẩn nghề:</td>
                <td class="student-detail-value">${diem.diem_chuannghe ?? '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-briefcase"></i></span>Điểm thực tế:</td>
                <td class="student-detail-value">${diem.diem_thucte ?? '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-sticky-note"></i></span>Ghi chú:</td>
                <td class="student-detail-value">${diem.ghichu ?? '-'}</td>
            </tr>
        </table>
        <div class="student-detail-action text-center">
            <button type="button" class="btn btn-primary" id="btn-nhap-diem" data-id="${id_sv}">Nhập/Sửa điểm</button>
        </div>
        `;
        }
        document.getElementById('student-detail-content-' + dot_id).innerHTML = html;
    }

    $(document).on('click', '.row-detail', function() {
        const id_sv = $(this).data('id');
        const dot_id = $(this).data('dot');
        renderStudentDetail(id_sv, dot_id);
    });

    // Khi click nút nhập điểm
    $(document).on('click', '#btn-nhap-diem', function() {
        const id_sv = $(this).data('id');
        const dot_id = $(this).closest('.tab-pane').attr('id').replace('dot-', '');
        $('#modal-id-sv').val(id_sv);
        $('#modal-id-dot').val(dot_id);
        // Nếu có dữ liệu điểm thì fill vào modal
        const diem = (diemData[dot_id] && diemData[dot_id][id_sv]) ? diemData[dot_id][id_sv] : {};
        $('input[name="diem_baocao"]').val(diem.diem_baocao ?? '');
        $('input[name="diem_chuyencan"]').val(diem.diem_chuyencan ?? '');
        $('input[name="diem_chuannghe"]').val(diem.diem_chuannghe ?? '');
        $('input[name="diem_thucte"]').val(diem.diem_thucte ?? '');
        $('textarea[name="ghichu"]').val(diem.ghichu ?? '');
        $('#modalNhapDiem').modal('show');
    });

    // Khi submit modal, bạn cần xử lý lưu điểm qua AJAX hoặc POST về PHP (tùy bạn triển khai backend)
    $('#form-nhap-diem').on('submit', function(e) {
        const diem_baocao = parseFloat($('input[name="diem_baocao"]').val());
        const diem_chuyencan = parseFloat($('input[name="diem_chuyencan"]').val());
        const diem_chuannghe = parseFloat($('input[name="diem_chuannghe"]').val());
        const diem_thucte = parseFloat($('input[name="diem_thucte"]').val());

        if (diem_baocao < 0 || diem_baocao > 4) {
            alert('Điểm báo cáo phải từ 0 đến 4');
            $('input[name="diem_baocao"]').focus();
            e.preventDefault();
            return false;
        }
        if (diem_chuyencan < 0 || diem_chuyencan > 2) {
            alert('Điểm chuyên cần phải từ 0 đến 2');
            $('input[name="diem_chuyencan"]').focus();
            e.preventDefault();
            return false;
        }
        if (diem_chuannghe < 0 || diem_chuannghe > 2) {
            alert('Điểm chuẩn nghề phải từ 0 đến 2');
            $('input[name="diem_chuannghe"]').focus();
            e.preventDefault();
            return false;
        }
        if (diem_thucte < 0 || diem_thucte > 2) {
            alert('Điểm thực tế phải từ 0 đến 2');
            $('input[name="diem_thucte"]').focus();
            e.preventDefault();
            return false;
        }
    });
    </script>
</body>
</html>
