<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');
 require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$ID_TaiKhoan = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT VaiTro FROM taikhoan WHERE ID_TaiKhoan = ?");
$stmt->execute([$ID_TaiKhoan]);
$vaiTro = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT ks.*, 
        COALESCE(gv.Ten, sv.Ten, cb.Ten, ad.Ten, tk.taikhoan) AS TenNguoiTao
    FROM khaosat ks
    JOIN taikhoan tk ON ks.NguoiTao = tk.ID_TaiKhoan
    LEFT JOIN giaovien gv ON gv.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN canbokhoa cb ON cb.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN admin ad ON ad.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN sinhvien sv ON sv.ID_TaiKhoan = tk.ID_TaiKhoan
    WHERE ks.TrangThai >= 1
    AND (
        (
            ks.NguoiNhan IN ('Tất cả', ?)
            AND EXISTS (
                SELECT 1 FROM sinhvien sv2
                WHERE sv2.ID_TaiKhoan = ?
                AND sv2.ID_Dot = ks.ID_Dot
            )
        )
        OR (
            ks.NguoiNhan = 'Sinh viên thuộc hướng dẫn'
            AND EXISTS (
                SELECT 1 FROM sinhvien sv2
                WHERE sv2.ID_TaiKhoan = ?
                AND sv2.ID_GVHD = ks.NguoiTao
                AND sv2.ID_Dot = ks.ID_Dot
            )
        )
    )
    AND ks.ID NOT IN (
        SELECT ID_KhaoSat FROM phanhoikhaosat WHERE ID_TaiKhoan = ?
    )
    ORDER BY ks.ThoiGianTao DESC
");

$stmt->execute([$vaiTro, $ID_TaiKhoan, $ID_TaiKhoan, $ID_TaiKhoan]);
$dsKhaoSat = $stmt->fetchAll(PDO::FETCH_ASSOC);


$dsID = array_column($dsKhaoSat, 'ID');
$dsCauHoiTheoKhaoSat = [];
if (!empty($dsID)) {
    $placeholders = implode(',', array_fill(0, count($dsID), '?'));
    $sqlCauHoi = "SELECT * FROM cauhoikhaosat WHERE ID_KhaoSat IN ($placeholders)";
    $stmtCauHoi = $conn->prepare($sqlCauHoi);
    $stmtCauHoi->execute($dsID);
    $tatCaCauHoi = $stmtCauHoi->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tatCaCauHoi as $ch) {
        $dsCauHoiTheoKhaoSat[$ch['ID_KhaoSat']][] = $ch;
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['id_khaosat'], $_POST['id_cauhoi'], $_POST['traloi'])
) {

    $idKhaoSat = $_POST['id_khaosat'];
    $idTaiKhoan = $ID_TaiKhoan;
    $dsIDCauHoi = $_POST['id_cauhoi'];
    $dsTraLoi = $_POST['traloi'];

    try {
        $conn->beginTransaction();

        $stmtPhanHoi = $conn->prepare("
            INSERT INTO phanhoikhaosat (ID_KhaoSat, ID_TaiKhoan, ThoiGianTraLoi, TrangThai)
            VALUES (?, ?, NOW(), 1)
        ");
        $stmtPhanHoi->execute([$idKhaoSat, $idTaiKhoan]);

        $idPhanHoi = $conn->lastInsertId();

        $stmtTraLoi = $conn->prepare("
            INSERT INTO cautraloi (ID_PhanHoi, ID_CauHoi, TraLoi, TrangThai)
            VALUES (?, ?, ?, 1)
        ");

        foreach ($dsIDCauHoi as $i => $idCauHoi) {
            $traLoi = $dsTraLoi[$i];
            if (is_array($traLoi)) {
                $traLoi = implode(';', $traLoi); // Nối các đáp án được chọn
            }
            $traLoi = trim($traLoi);
            if ($traLoi !== '') {
                $stmtTraLoi->execute([$idPhanHoi, $idCauHoi, $traLoi]);
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Phản hồi khảo sát thành công!";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Đã xảy ra lỗi khi phản hồi: " . $e->getMessage();
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Khảo sát</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
    <style>
        .survey-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }

        .survey-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .survey-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .survey-title {
            font-weight: bold;
            font-size: 18px;
            color: #1e40af;
        }

        .survey-meta {
            font-size: 13px;
            color: #666;
        }

        .btn-respond {
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div id="wrapper">

        <div id="page-wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h2 class="page-header">Khảo sát đang chờ phản hồi</h2>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: '<?= $_SESSION['success'] ?>',
                            confirmButtonText: 'Đóng'
                        });
                    </script>
                    <?php unset($_SESSION['success']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: '<?= $_SESSION['error'] ?>',
                            confirmButtonText: 'Đóng'
                        });
                    </script>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="row">
                    <?php if (empty($dsKhaoSat)): ?>
                        <div class="col-md-12">
                            <div class="alert alert-info">Bạn không có khảo sát nào cần phản hồi.</div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($dsKhaoSat as $ks): ?>
                        <div class="col-md-6">
                            <div class="survey-card">
                                <div class="survey-header">
                                    <div class="survey-title"><?= htmlspecialchars($ks['TieuDe']) ?></div>
                                    <div class="survey-meta"><?= date("d/m/Y H:i", strtotime($ks['ThoiGianTao'])) ?></div>
                                </div>
                                <p><?= nl2br(htmlspecialchars($ks['MoTa'])) ?></p>
                                <div class="survey-meta">
                                    Người gửi: <strong><?= htmlspecialchars($ks['TenNguoiTao']) ?></strong>
                                </div>
                                <div class="survey-meta">
                                    Thời hạn phản hồi: <strong><?= date("d/m/Y H:i", strtotime($ks['ThoiHan'])) ?></strong>
                                </div>

                                <?php if ($ks['TrangThai'] == 1): ?>
                                    <button class="btn btn-sm btn-primary btn-respond" data-toggle="modal"
                                        data-target="#modalPhanHoi<?= $ks['ID'] ?>">Phản hồi</button>
                                <?php elseif ($ks['TrangThai'] == 2): ?>
                                    <div class="alert alert-warning mt-2 mb-0 py-1 px-2" style="font-size: 14px;">
                                        <i class="fa fa-clock-o"></i> Đã hết hạn phản hồi
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>


                <!-- Modal phản hồi khảo sát -->
                <?php foreach ($dsKhaoSat as $ks): ?>
                    <div class="modal fade" id="modalPhanHoi<?= $ks['ID'] ?>" tabindex="-1" role="dialog"
                        data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog">
                            <form method="post">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title"><strong><?= htmlspecialchars($ks['TieuDe']) ?></strong></h4>
                                        <p class="text-muted"><?= htmlspecialchars($ks['MoTa']) ?></p>
                                        <input type="hidden" name="id_khaosat" value="<?= $ks['ID'] ?>">
                                    </div>
                                    <div class="modal-body">
                                        <?php
                                        $cauHoi = $dsCauHoiTheoKhaoSat[$ks['ID']] ?? [];
                                        if (!empty($cauHoi)):
                                            foreach ($cauHoi as $index => $ch): ?>
                                                <div class="form-group">
                                                    <label>Câu <?= $index + 1 ?>: <?= htmlspecialchars($ch['NoiDung']) ?></label>
                                                    <input type="hidden" name="id_cauhoi[]" value="<?= $ch['ID'] ?>">
                                                    <br>
                                                    <?php
                                                    if (($ch['Loai'] ?? 'text') === 'choice' && !empty($ch['DapAn'])):
                                                        $dapanArr = array_map('trim', explode(';', $ch['DapAn']));
                                                        foreach ($dapanArr as $da): ?>
                                                            <label class="form-check-inline mr-3">
                                                                <input type="radio" name="traloi[<?= $index ?>]"
                                                                    value="<?= htmlspecialchars($da) ?>" required>
                                                                <?= htmlspecialchars($da) ?>
                                                            </label>
                                                        <?php endforeach;
                                                    elseif (($ch['Loai'] ?? 'text') === 'multiple' && !empty($ch['DapAn'])):
                                                        $dapanArr = array_map('trim', explode(';', $ch['DapAn']));
                                                        foreach ($dapanArr as $da): ?>
                                                            <label class="form-check-inline mr-3">
                                                                <input type="checkbox" name="traloi[<?= $index ?>][]"
                                                                    value="<?= htmlspecialchars($da) ?>">
                                                                <?= htmlspecialchars($da) ?>
                                                            </label>
                                                        <?php endforeach;
                                                    else: ?>
                                                        <input type="text" class="form-control" name="traloi[<?= $index ?>]" required>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach;
                                        else: ?>
                                            <p class="text-danger">Không có câu hỏi nào cho khảo sát này.</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success">Gửi phản hồi</button>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
    <?php require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const forms = document.querySelectorAll("form");
            forms.forEach(form => {
                if (form.id === "menuSearchForm") return;
                form.addEventListener("submit", function (e) {
                    e.preventDefault(); // Ngăn submit mặc định
                    Swal.fire({
                        title: "Xác nhận gửi phản hồi?",
                        text: "Bạn sẽ không thể chỉnh sửa sau khi gửi!",
                        icon: "question",
                        showCancelButton: true,
                        confirmButtonText: "Gửi",
                        cancelButtonText: "Hủy",
                        reverseButtons: true
                    }).then(result => {
                        if (result.isConfirmed) {
                            form.submit(); // Gửi nếu xác nhận
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>