<<<<<<< HEAD
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$ID_TaiKhoan = "3";

$stmt = $conn->prepare("SELECT VaiTro FROM TaiKhoan WHERE ID_TaiKhoan = ?");
$stmt->execute([$ID_TaiKhoan]);
$vaiTro = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT ks.*, 
    COALESCE(gv.Ten, sv.Ten,cb.Ten, tk.TaiKhoan) AS TenNguoiTao
    FROM KhaoSat ks
    JOIN TaiKhoan tk ON ks.NguoiTao = tk.ID_TaiKhoan
    LEFT JOIN GiaoVien gv ON gv.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN CanBoKhoa cb ON cb.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN SinhVien sv ON sv.ID_TaiKhoan = tk.ID_TaiKhoan
    WHERE ks.TrangThai = 1
    AND (
        ks.NguoiNhan IN ('Tất cả', ?) -- Vai trò
        OR (
            ks.NguoiNhan = 'Sinh viên thuộc hướng dẫn'
            AND EXISTS (
                SELECT 1
                FROM SinhVien sv2
                WHERE sv2.ID_TaiKhoan = ?
                AND sv2.ID_GVHD = ks.NguoiTao
            )
        )
    )
    AND ks.ID NOT IN (
        SELECT ID_KhaoSat 
        FROM PhanHoiKhaoSat 
        WHERE ID_TaiKhoan = ?
    )
    ORDER BY ks.ThoiGianTao DESC
");

$stmt->execute([$vaiTro, $ID_TaiKhoan, $ID_TaiKhoan]);
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

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['id_khaosat'], $_POST['id_cauhoi'], $_POST['traloi'])
) {

    $idKhaoSat = $_POST['id_khaosat'];
    $idTaiKhoan = $_SESSION['ID_TaiKhoan'] ?? "3";
    $dsIDCauHoi = $_POST['id_cauhoi'];
    $dsTraLoi = $_POST['traloi'];

    try {
        $conn->beginTransaction();

        $stmtPhanHoi = $conn->prepare("
            INSERT INTO PhanHoiKhaoSat (ID_KhaoSat, ID_TaiKhoan, ThoiGianTraLoi, TrangThai)
            VALUES (?, ?, NOW(), 1)
        ");
        $stmtPhanHoi->execute([$idKhaoSat, $idTaiKhoan]);

        $idPhanHoi = $conn->lastInsertId();

        $stmtTraLoi = $conn->prepare("
            INSERT INTO CauTraLoi (ID_PhanHoi, ID_CauHoi, TraLoi, TrangThai)
            VALUES (?, ?, ?, 1)
        ");

        foreach ($dsIDCauHoi as $i => $idCauHoi) {
            $traLoi = trim($dsTraLoi[$i]);
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

=======
<!DOCTYPE html>
<html lang="vi">
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
<head>
    <meta charset="UTF-8">
    <title>Khảo sát</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<<<<<<< HEAD

<body>

    <div id="wrapper">
        <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_SinhVien.php";
        ?>
=======
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_SinhVien.php";
    ?>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Khảo Sát
                    </h1>
                </div>
<<<<<<< HEAD
                <?php if (isset($_SESSION['success'])): ?>
                    <div id="notificationAlert" class="alert alert-success">
                        <?= $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </div>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div id="notificationAlert" class="alert alert-danger">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>

                <?php endif; ?>
                <script>
                    setTimeout(function () {
                        const alertBox = document.getElementById('notificationAlert');
                        if (alertBox) {
                            alertBox.style.transition = 'opacity 0.5s ease';
                            alertBox.style.opacity = '0';
                            setTimeout(() => alertBox.remove(), 500);
                        }
                    }, 2000);
                </script>
                <div id="containerKhaoSat" class="mt-3">
                    <div id="listKhaoSat" class="row">
                    </div>
                    <div class="row">

                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4>Danh sách khảo sát cần phản hồi</h4>

                                </div>
                                <table class="table" id="bangKhaoSat">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tiêu đề</th>
                                            <th>Người gửi</th>
                                            <th>Vào lúc</th>
                                            <th>Phản hồi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dsKhaoSat as $index => $ks): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($ks['TieuDe']) ?></td>
                                                <td><?= htmlspecialchars($ks['TenNguoiTao']) ?></td>
                                                <td><?= $ks['ThoiGianTao'] ?></td>
                                                <td>
                                                    <button class="btn btn-primary" data-toggle="modal"
                                                        data-target="#modalPhanHoi<?= $ks['ID'] ?>">Phản hồi</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php foreach ($dsKhaoSat as $ks): ?>
                            <div class="modal fade" id="modalPhanHoi<?= $ks['ID'] ?>" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
                                <div class="modal-dialog">
                                    <form method="post">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">
                                                    <strong><?= htmlspecialchars($ks['TieuDe']) ?></strong></h4>
                                                <p class="text-muted"><?= htmlspecialchars($ks['MoTa']) ?></p>
                                                <input type="hidden" name="id_khaosat" value="<?= $ks['ID'] ?>">
                                            </div>
                                            <div class="modal-body">
                                                <?php
                                                $cauHoi = $dsCauHoiTheoKhaoSat[$ks['ID']] ?? [];
                                                if (!empty($cauHoi)):
                                                    foreach ($cauHoi as $index => $ch): ?>
                                                        <div class="form-group">
                                                            <label>Câu <?= $index + 1 ?>:
                                                                <?= htmlspecialchars($ch['NoiDung']) ?></label>
                                                            <input type="hidden" name="id_cauhoi[]" value="<?= $ch['ID'] ?>">
                                                            <input type="text" class="form-control" name="traloi[]" required>
                                                        </div>
                                                    <?php endforeach;
                                                else: ?>
                                                    <p class="text-danger">Không có câu hỏi nào cho khảo sát này.</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-success">Gửi phản hồi</button>
                                                <button type="button" class="btn btn-default"
                                                    data-dismiss="modal">Đóng</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
            <?php
            require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
                ?>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const forms = document.querySelectorAll("form");

                    forms.forEach(form => {
                        form.addEventListener("submit", function (e) {
                            const xacNhan = confirm("Xác nhận phản hồi.");
                            if (!xacNhan) {
                                e.preventDefault();
                            }
                        });
                    });
                });
                $('.btnPhanHoi').click(function () {
                    const id = $(this).data('id');
                    const ten = $(this).data('ten');
                    alert("Mở modal phản hồi khảo sát ID " + id + " - " + ten);
                });


            </script>
        </div>
    </div>
</body>

</html>
=======
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

>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
