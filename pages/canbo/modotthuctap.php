<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/database.php"; 
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/funtions.php"; 

function countSimilar($pdo, $tendot) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM DOTTHUCTAP WHERE TENDOT LIKE :tendot");
    $stmt->execute(['tendot' => $tendot . '%']);
    return $stmt->fetchColumn();
}
function saveInternship($pdo, $tendot, $loai, $namHoc, $nganh, $thoigian, $nguoiquanly, $nguoitao) {
    $stmt = $pdo->prepare("INSERT INTO DOTTHUCTAP (TENDOT, NAM, LOAI, Nganh, NGUOIQUANLY, THOIGIANKETTHUC, TENNGUOIMODOT, TRANGTHAI) 
                           VALUES (:tendot, :nam, :loai, :nganh, :nguoiquanly, :thoigianketthuc, :tennguoimodot, 1)");
    if ($stmt->execute([
        'tendot' => $tendot,
        'nam' => $namHoc,
        'loai' => $loai,
        'nganh' => $nganh,
        'nguoiquanly' => $nguoiquanly,
        'thoigianketthuc' => $thoigian,
        'tennguoimodot' => $nguoitao
    ])) {
        return $pdo->lastInsertId();
    }
    return false;
}
$successMessage="";
$notification = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loai = $_POST['loai'];
    $namHoc = $_POST['namhoc'];
    $nganh = $_POST['nganh'];
    $nguoitao = $_POST['nguoitao'];
    $nguoiquanly = $_POST['nguoiquanly'];
    $thoigian = $_POST['thoigian'];
    if ($loai ==""|| $namHoc=="" || $nganh=="" || $nguoitao==""||$thoigian=="") {
        $notification = "Vui lòng điền tất cả các trường.";
    }else {
        $nganhArr = explode(' ', trim($nganh));
        $lastWord = array_pop($nganhArr);
        $nganhModified = implode(' ', $nganhArr);
        $tendot = ($loai === 'Cao đẳng' ? 'CĐ' : 'CĐN') . substr($namHoc, -2) . $lastWord;
        
        $count = countSimilar($pdo, $tendot);
        $tendot .= ($count + 1);
        
        $idDot = saveInternship($pdo, $tendot, $loai, $namHoc, $nganhModified, $thoigian, $nguoiquanly, $nguoitao);
        if ($idDot) {
            session_start();
            $_SESSION['success'] = "Đợt thực tập $tendot được mở thành công!";
            header("Location: /datn/pages/canbo/chitietdot?id=" . urlencode($idDot));
            exit;
        } else {
            $notification = "Mở đợt thực tập thất bại!";
            unset($_POST);
        }
    }
    
}
$today = date('Y-m-d');
$updateStmt = $pdo->prepare("UPDATE DOTTHUCTAP SET TRANGTHAI = 0 WHERE THOIGIANKETTHUC < :today AND TRANGTHAI = 1");
$updateStmt->execute(['today' => $today]);
$danhSachDotThucTap = getAllInternships($pdo);
$canbokhoa = $pdo->query("SELECT ID_TaiKhoan,Ten FROM canbokhoa where TrangThai=1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Mở đợt thực tập</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
    ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Mở Đợt Thực Tập
                    </h1>
                </div>
                <div class="row">
                <div class="form-container">
                <form id="FormMoDot" method="post">
                    <div class="row mb-3">
                    <div class="col-lg-6">
                        <div class="form-group">
                        <div class="form-group">
                            <label >Năm</label>
                            <input class="form-control"id="namhoc"name="namhoc" type="number" min="1000" max="9999"placeholder="Nhập năm học" >
                        </div>
                        <div class="form-group">
                            <label >Chuyên ngành</label>
                            <select id="nganh" name="nganh"class="form-control">
                            <option value="Lập trình di động DĐ">Lập trình di động</option>
                            <option value="Lập trình website WEB">Lập trình website</option>
                            <option value="Mạng máy tính MMT">Mạng máy tính</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label >Thời gian kết thúc</label>
                            <input class="form-control"id="thoigian"name="thoigian" type="date" placeholder="Chọn thời gian kết thúc" >
                        </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label >Loại</label>
                            <select id="loai" name="loai"class="form-control">
                            <option value="Cao đẳng">Cao đẳng</option>
                            <option value="Cao đẳng ngành">Cao đẳng ngành</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label >Người quản lý đợt</label>
                            <select id="nguoiquanly" name="nguoiquanly"class="form-control">
                            <?php foreach ($canbokhoa as $cb): ?>
                            <option value="<?=$cb['Ten'] ?>"><?= htmlspecialchars($cb['Ten']) ?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        </div>
                        <input type="hidden" class="form-control"id="nguoitao"name="nguoitao"value="Lữ Cao Tiến">
                        </div>       
                        <div class="row">
                        <div class="col-md-offset text-center">
                            <button type="submit" class="btn btn-primary btn-lg mt-3">Xác nhận</button>
                        </div>
                        </div>
                    </div>
                </form>
            </div>
        <div id="containerDotThucTap" class="mt-3">
            <h2>Danh sách các đợt thực tập</h2>
            <div id="listDotThucTap" class="row">
        </div>
        <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <input class="form-control mb-3" id="timkiem" type="text" placeholder="TÌm tên đợt...">
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table"id="TableDotTT">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tên đợt</th>
                                                    <th>Năm</th>
                                                    <th>Ngành</th>
                                                    <th>Người quản lý</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php $i = 1; foreach ($danhSachDotThucTap as $dot): ?>
                                                <?php $link = 'pages/canbo/chitietdot?id=' . urlencode($dot['ID']); ?>
                                                <tr onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                                                    <td><?= $i++ ?></td>
                                                    <td><?= htmlspecialchars($dot['TenDot']) ?></td>
                                                    <td><?= htmlspecialchars($dot['Nam']) ?></td>
                                                    <td><?= htmlspecialchars($dot['Nganh']) ?></td>
                                                    <td><?= htmlspecialchars($dot['NguoiQuanLy']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
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
</div>      </div>
<script>
    document.getElementById("timkiem").addEventListener("keyup", function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll("#TableDotTT tbody tr");
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });

    <?php if (!empty($notification)): ?>
    Swal.fire({
        title: 'Thất bại!',
        text: '<?= addslashes($notification) ?>',
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc3545'
    });
    <?php endif ?>
    document.getElementById('FormMoDot').addEventListener('submit', function (e) {
    e.preventDefault();
    
    const loai = document.getElementById('loai').value;
    const nam = document.getElementById('namhoc').value;
    const nganh = document.getElementById('nganh').options[document.getElementById('nganh').selectedIndex].text;
    const thoigian = document.getElementById('thoigian').value;
    const nguoiquanly = document.getElementById('nguoiquanly').value;
       
    Swal.fire({
        title: 'Xác nhận mở đợt?',
        html: `
            <p><strong>Loại:</strong> ${loai}</p>
            <p><strong>Năm:</strong> ${nam}</p>
            <p><strong>Ngành:</strong> ${nganh}</p>
            <p><strong>Thời gian kết thúc:</strong> ${thoigian}</p>
            <p><strong>Người quản lý:</strong> ${nguoiquanly}</p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Xác nhận',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#3085d6',
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
});
</script>