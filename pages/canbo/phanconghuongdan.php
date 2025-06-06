<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/database.php"; 
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/funtions.php"; 

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID đợt thực tập.");
}

$stmt = $pdo->prepare("SELECT * FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();
$successMessage="";
$notification = "";
if (!$dot) {
    die("Không tìm thấy đợt thực tập.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['GiaoVien'], $_POST['chon'])) {
    
    $gvId = $_POST['GiaoVien'];
    $sinhVienIds = $_POST['chon'];
    if(empty($sinhVienIds))
    $notification = "Hãy chọn sinh viên!";
    else{
    $update = $pdo->prepare("UPDATE SinhVien SET ID_GVHD = :gvId WHERE ID_TaiKhoan IN (" . implode(",", array_map('intval', $sinhVienIds)) . ")");
    $update->execute(['gvId' => $gvId]);

    $successMessage="Phân công thành công!";
    }
}

$sinhviens = $pdo->prepare("SELECT ID_TaiKhoan,Ten,MSSV,ID_Dot,ID_GVHD,Lop,TrangThai FROM SinhVien WHERE ID_DOT = :id AND (ID_GVHD IS NULL or ID_GVHD='')");
$sinhviens->execute(['id' => $id]);

$giaoviens = $pdo->query("SELECT ID_TaiKhoan,Ten FROM giaovien where TrangThai=1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phân công hướng dẫn</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>
<body>
<div id="wrapper">
<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row mt-5">
            <div class="col-lg-12">
                <h1 class="page-header"><?= htmlspecialchars($dot['TenDot']) ?></h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-offset">
                <?php if (!empty($successMessage)) echo "<div class='alert alert-success'>$successMessage</div>"; ?>
                <?php if (!empty($notification)) echo "<div class='alert alert-danger'>$notification</div>"; ?>
            </div>
        <form method="post" id="FormPhanCong">
            <div class="row">
            <div class="form-group col-md-4 col-md-offset-4">
                <label>Chọn Giáo Viên</label>
                <select name="GiaoVien" class="form-control" required>
                    <?php foreach ($giaoviens as $gv): ?>
                        <option value="<?= $gv['ID_TaiKhoan'] ?>"><?= htmlspecialchars($gv['Ten']) ?></option>
                    <?php endforeach; ?>
                </select>
                </div>
            </div>
        <div class="row">
            <h3>Chọn sinh viên</h3>
            <div class="panel panel-default">
            <div class="panel-heading">
                <input class="form-control mb-3" id="timkiem" type="text" placeholder="Nhập tên, mssv hoặc lớp...">
            </div>
            <div class="panel-body">
            <div class="table-responsive">
                <table class="table" id="tableSinhVien">
                    <thead>
                    <tr>
                        <th></th>
                        <th onclick="sortTable(1)"style="cursor: pointer;">MSSV ⬍</th>
                        <th onclick="sortTable(2)"style="cursor: pointer;">Họ Tên ⬍</th>
                        <th onclick="sortTable(3)"style="cursor: pointer;">Lớp ⬍</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sinhviens as $sv): ?>
                        <tr onclick="toggleCheckbox(this)">
                            <td style="cursor: pointer;"><input type="checkbox" name="chon[]" value="<?= $sv['ID_TaiKhoan'] ?>"></td>
                            <td style="cursor: pointer;"><?= htmlspecialchars($sv['MSSV']) ?></td>
                            <td style="cursor: pointer;"><?= htmlspecialchars($sv['Ten']) ?></td>
                            <td style="cursor: pointer;"><?= htmlspecialchars($sv['Lop']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
</div>
        <div class="row">
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg mt-3">Xác nhận</button>
                <a href="/datn/pages/canbo/chitietdot?id=<?= urlencode($id) ?>" class="btn btn-default btn-lg">Thoát</a>
            </div>
            </div>
        </form>
    </div>
</div>
</body>
</html>

<script>
    function toggleCheckbox(row) {
        if (event.target.type === 'checkbox') return;
        const checkbox = row.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
    }
    function toggleCheckbox(row) {
        if (event.target.type === 'checkbox') return;
        const checkbox = row.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
    }

    document.getElementById("timkiem").addEventListener("keyup", function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll("#tableSinhVien tbody tr");
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });

    let sortDirection = {};

    function sortTable(colIndex) {
        const table = document.getElementById("tableSinhVien");
        const tbody = table.tBodies[0];
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const isAsc = !sortDirection[colIndex];
        sortDirection[colIndex] = isAsc;

        rows.sort((a, b) => {
            const aText = a.children[colIndex].textContent.trim().toLowerCase();
            const bText = b.children[colIndex].textContent.trim().toLowerCase();

            return isAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
        });
        rows.forEach(row => tbody.appendChild(row));
    }
    document.getElementById("FormPhanCong").addEventListener("submit", function (e) {
        e.preventDefault();

        const checkboxes = document.querySelectorAll('input[name="chon[]"]:checked');
        const selectGV = document.querySelector('select[name="GiaoVien"]');
        const gvTen = selectGV.options[selectGV.selectedIndex].text;

        if (checkboxes.length === 0) {
            Swal.fire({
                title: 'Chưa chọn sinh viên!',
                text: 'Bạn phải chọn ít nhất một sinh viên để phân công.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        let svInfo = "";
        checkboxes.forEach(cb => {
            const row = cb.closest("tr");
            const mssv = row.children[1].textContent.trim();
            const ten = row.children[2].textContent.trim();
            const lop = row.children[3].textContent.trim();
            svInfo += `• ${ten} ${mssv} ${lop}\n`;
        });

        Swal.fire({
            title: 'Xác nhận phân công?',
            html: `<b>Giáo viên:</b> ${gvTen}<br><b>Sinh viên:</b><pre style="text-align:left;">${svInfo}</pre>`,
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#3085d6',
        }).then((result) => {
            if (result.isConfirmed) {
                e.target.submit();
            }
        });
    });
</script>
