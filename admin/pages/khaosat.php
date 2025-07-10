<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Border as StyleBorder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
$ID_TaiKhoan = $_SESSION['user_id'];


// Lấy danh sách phản hồi của sinh viên
$stmt = $conn->prepare("
    SELECT tk.ID_TaiKhoan, tk.VaiTro, sv.MSSV, sv.Ten, sv.Lop, ph.ID AS ID_PhanHoi
    FROM phanhoikhaosat ph
    JOIN taikhoan tk ON ph.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN sinhvien sv ON sv.ID_TaiKhoan = tk.ID_TaiKhoan
    WHERE ph.ID_KhaoSat = ? AND tk.VaiTro = 'Sinh viên'
    ORDER BY sv.MSSV ASC
");
$dsPhanHoi = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export_excel'])) {
    $id_KhaoSat = intval($_GET['export_excel']);
    // Lấy câu hỏi
    $stmt = $conn->prepare("SELECT * FROM cauhoikhaosat WHERE ID_KhaoSat = ? AND TrangThai = 1");
    $stmt->execute([$id_KhaoSat]);
    $cauHoi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Phản hồi khảo sát");

    // Header sinh viên
    $headerStudent = ['MSSV', 'Họ tên', 'Lớp', 'Thời gian trả lời'];
    foreach ($cauHoi as $ch) {
        $headerStudent[] = $ch['NoiDung'];
    }

    // Ghi header sinh viên
    $sheet->fromArray($headerStudent, null, 'A1');
    $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
    // ==== PHẢN HỒI SINH VIÊN ====
    $stmt = $conn->prepare("
    SELECT tk.ID_TaiKhoan, sv.MSSV, sv.Ten, sv.Lop, ph.ID AS ID_PhanHoi, ph.ThoiGianTraLoi
    FROM phanhoikhaosat ph
    JOIN taikhoan tk ON ph.ID_TaiKhoan = tk.ID_TaiKhoan
    JOIN sinhvien sv ON tk.ID_TaiKhoan = sv.ID_TaiKhoan
    WHERE ph.ID_KhaoSat = ? AND tk.VaiTro = 'Sinh viên'
    ORDER BY ph.ThoiGianTraLoi ASC
");
    $stmt->execute([$id_KhaoSat]);
    $dsPhanHoiSV = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rowNum = 2;
    foreach ($dsPhanHoiSV as $sv) { 
        $rowData = [$sv['MSSV'], $sv['Ten'], $sv['Lop'],date('d/m/Y H:i', strtotime($sv['ThoiGianTraLoi']))];
        foreach ($cauHoi as $ch) {
            $stmt = $conn->prepare("SELECT TraLoi FROM cautraloi WHERE ID_PhanHoi = ? AND ID_CauHoi = ?");
            $stmt->execute([$sv['ID_PhanHoi'], $ch['ID']]);
            $traloi = $stmt->fetchColumn();
            $rowData[] = $traloi ?? '';
            
        }
        $sheet->fromArray($rowData, null, 'A' . $rowNum++);
    }
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '000000'],
            ],
        ],
    ];
    $sheet->getStyle('A1:E' . ($rowNum - 1))->applyFromArray($styleArray);

    // ==== PHẢN HỒI GIÁO VIÊN ====
    // Ghi header giáo viên ở dòng trống tiếp theo
    $sheet->fromArray([''], null, 'A' . $rowNum++);
    $headerTeacher = ['Email', 'Tên', 'Thời gian trả lời'];
    foreach ($cauHoi as $ch) {
        $headerTeacher[] = $ch['NoiDung'];
    }

    $sheet->fromArray($headerTeacher, null, 'A' . $rowNum++);
    $teacherHeaderRow = $rowNum - 1;
    $sheet->getStyle('A' . $teacherHeaderRow . ':' . $sheet->getHighestColumn() . $teacherHeaderRow)->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
    $stmt = $conn->prepare("
    SELECT tk.ID_TaiKhoan, tk.TaiKhoan AS Email, gv.Ten, ph.ID AS ID_PhanHoi, ph.ThoiGianTraLoi
    FROM phanhoikhaosat ph
    JOIN taikhoan tk ON ph.ID_TaiKhoan = tk.ID_TaiKhoan
    JOIN giaovien gv ON tk.ID_TaiKhoan = gv.ID_TaiKhoan
    WHERE ph.ID_KhaoSat = ? AND tk.VaiTro = 'Giáo viên'
    ORDER BY ph.ThoiGianTraLoi ASC
");
    $stmt->execute([$id_KhaoSat]);
    $dsPhanHoiGV = $stmt->fetchAll(PDO::FETCH_ASSOC);

   $startRowGV = $rowNum -1; // Ghi lại dòng bắt đầu của giáo viên

foreach ($dsPhanHoiGV as $gv) {
    $rowData = [
        $gv['Email'],
        $gv['Ten'],
        date('d/m/Y H:i', strtotime($gv['ThoiGianTraLoi']))
    ];
    foreach ($cauHoi as $ch) {
        $stmt = $conn->prepare("SELECT TraLoi FROM cautraloi WHERE ID_PhanHoi = ? AND ID_CauHoi = ?");
        $stmt->execute([$gv['ID_PhanHoi'], $ch['ID']]);
        $traloi = $stmt->fetchColumn();
        $rowData[] = $traloi ?? '';
    }
    $sheet->fromArray($rowData, null, 'A' . $rowNum++);
}
$endRowGV = $rowNum - 1; // Dòng kết thúc giáo viên

// Áp dụng căn giữa và border cho phần giáo viên
$sheet->getStyle("A{$startRowGV}:{$sheet->getHighestColumn()}{$endRowGV}")
    ->applyFromArray([
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '000000'],
            ],
        ],
    ]);
    // Tự căn chiều rộng
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    ob_clean();
    // Xuất file
    $filename = 'phanhoi_khaosat_' . $id_KhaoSat . '.xlsx';
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment;filename=\"$filename\"");
    header("Cache-Control: max-age=0");

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
// Lấy danh sách đợt thực tập
$stmt = $conn->prepare("SELECT ID, TenDot FROM DotThucTap WHERE TrangThai >= 0 ORDER BY ID DESC");
$stmt->execute();
$dsDot = $stmt->fetchAll(PDO::FETCH_ASSOC);

// AJAX: Tạo khảo sát
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tao') {
    $tieude = trim($_POST['tieude'] ?? '');
    $mota = trim($_POST['mota'] ?? '');
    $nguoiNhan = $_POST['to'] ?? '';
    $cauHoiList = $_POST['cauhoi'] ?? [];
    $loaiList = $_POST['loaicauhoi'] ?? [];
    $dapanList = $_POST['dapan'] ?? [];
    $nguoiTao = $ID_TaiKhoan;
    $idDot = $_POST['id_dot'] ?? null;

    try {
        $conn->beginTransaction();

        // 1. Tạo khảo sát trước
        $stmt = $conn->prepare("INSERT INTO KhaoSat (TieuDe, MoTa, NguoiNhan, NguoiTao, ThoiGianTao, TrangThai, ID_Dot) 
            VALUES (?, ?, ?, ?, NOW(), 1, ?)");
        $stmt->execute([$tieude, $mota, $nguoiNhan, $nguoiTao, $idDot]);
        $idKhaoSat = $conn->lastInsertId();

        // 2. Thêm câu hỏi (có loại và đáp án)
        $stmtCauHoi = $conn->prepare("INSERT INTO CauHoiKhaoSat (ID_KhaoSat, NoiDung, Loai, DapAn, TrangThai) VALUES (?, ?, ?, ?, 1)");
        foreach ($cauHoiList as $i => $cauhoi) {
            $noiDung = trim($cauhoi);
            $loai = $loaiList[$i] ?? 'text';
            $dapan = ($loai === 'choice') ? trim($dapanList[$i] ?? '') : null;
            // Xử lý đáp án: bỏ khoảng trắng và dấu ; ở cuối

            if ($loai === 'choice' || $loai === 'multiple') {
                $dapan = trim($dapanList[$i] ?? '');
                $dapan = preg_replace('/\s*;\s*$/', '', $dapan); // Xóa dấu ; và khoảng trắng cuối
            } else {
                $dapan = null;
            }
            if ($noiDung !== '') {
                $stmtCauHoi->execute([$idKhaoSat, $noiDung, $loai, $dapan]);
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'OK']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Xóa khảo sát
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'xoa') {
    $idKhaoSat = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("UPDATE KhaoSat SET TrangThai = 0 WHERE ID = ?");
    $stmt->execute([$idKhaoSat]);
    echo json_encode(['status' => 'OK']);
    exit;
}

// AJAX: Lấy danh sách khảo sát
if (isset($_GET['ajax'])) {
    $whereDot = "";
    $params = [$ID_TaiKhoan];
    if (!empty($_GET['dot_filter'])) {
        $whereDot = " AND ks.ID_Dot = ? ";
        $params[] = $_GET['dot_filter'];
    }
    $stmt = $conn->prepare("SELECT ks.ID, ks.TieuDe, ks.ThoiGianTao,ks.NguoiNhan,
        (SELECT COUNT(*) FROM PhanHoiKhaoSat WHERE ID_KhaoSat = ks.ID) AS SoLuongPhanHoi,
        ks.ID_Dot
        FROM KhaoSat ks
        WHERE ks.NguoiTao = ? and ks.TrangThai=1 $whereDot
        ORDER BY ks.ThoiGianTao DESC");
    $stmt->execute($params);
    $dsKhaoSatTao = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>
    <table class="table" id="bangkhaosat">
        <thead>
            <tr>
                <th>#</th>
                <th>Tiêu đề</th>
                <th>Ngày tạo</th>
                <th>Đợt thực tập</th>
                <th>Người nhận</th>
                <th>Phản hồi</th>
                <th>Xem phản hồi</th>
                <th>Xóa</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dsKhaoSatTao as $index => $ks): ?>
                <tr>
                    <td>
                        <?= $index + 1 ?>
                    </td>
                    <td >
                        <?= htmlspecialchars($ks['TieuDe']) ?>
                    </td>
                    <td >
                        <?= date('d/m/Y', strtotime($ks['ThoiGianTao'])) ?>
                    </td>
                    <td >
                        <?php
                        $tenDot = '';
                        foreach ($dsDot as $dot) {
                            if ($dot['ID'] == $ks['ID_Dot']) {
                                $tenDot = $dot['TenDot'];
                                break;
                            }
                        }
                        echo htmlspecialchars($tenDot);
                        ?>
                    </td>
                    <td >
                        <?= $ks['NguoiNhan'] ?>
                    </td>
                    <td >
                        <?= $ks['SoLuongPhanHoi'] ?>
                    </td>
                    <td><a href="admin/pages/khaosat?export_excel=<?= $ks['ID'] ?>" class="btn btn-success btn-sm"
                            title="Xuất phản hồi">
                            <i class="glyphicon glyphicon-download-alt"></i> Xem
                        </a></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="xoaKhaoSat(<?= $ks['ID'] ?>)">Xoá</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($dsKhaoSatTao)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">Chưa có khảo sát nào được tạo.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tạo khảo sát</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        /* ======= Bảng khảo sát ======= */
        #bangkhaosat {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            font-family: 'Segoe UI', sans-serif;
        }

        #bangkhaosat th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            padding: 12px;
            text-align: center;
        }

        #bangkhaosat td {
            padding: 12px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid #ddd;
            transition: background-color 0.2s;
            cursor: default;
        }

        #bangkhaosat tr:hover td {
            background-color: #f1f1f1;
        }

        #bangkhaosat .btn-danger {
            transition: background-color 0.3s;
        }

        #bangkhaosat .btn-danger:hover {
            background-color: #c0392b;
        }

        /* ======= Form tạo khảo sát ======= */
        .form-container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 28px;
            color: #2c3e50;
        }

        label {
            font-weight: 600;
            color: #34495e;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #ccc;
            padding: 10px 12px;
            font-size: 15px;
        }

        .btn {
            border-radius: 6px;
            font-weight: bold;
        }

        .btn-success {
            background-color: #27ae60;
            border-color: #27ae60;
        }

        .btn-success:hover {
            background-color: #219150;
        }

        .btn-primary {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .btn-primary:hover {
            background-color: #1f6390;
        }

        .btn-remove {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }

        .btn-remove:hover {
            background-color: #c0392b;
        }

        /* ======= Responsive & Style Dropdown ======= */

        select.form-control {
            font-size: 16px;
            line-height: 1.4;
            padding: 10px 12px;
            height: auto;
            /* hoặc bỏ hẳn height nếu bị cố định */
            box-sizing: border-box;
        }

        #dot_filter {
            margin-left: 10px;
            padding: 5px 10px;
            border-radius: 6px;
        }

        /* Căn giữa nội dung chưa có khảo sát */
        #bangkhaosat .text-muted {
            font-style: italic;
            color: #7f8c8d !important;
        }
    </style>

</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>Tạo khảo sát</h1>
                </div>
                <div class="form-container">
                    <form id="formTaoKhaoSat" method="post" autocomplete="off">
                        <div class="row text-left">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label><strong>Chọn đợt thực tập</strong></label>
                                    <select id="id_dot" name="id_dot" class="form-control"
                                        required>
                                        <option value="">-- Chọn đợt --</option>
                                        <?php foreach ($dsDot as $dot): ?>
                                            <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3" style="margin-left: 20px;">
                                <div class="form-group">
                                    <label><strong>Gửi đến</strong></label>
                                    <select id="to" name="to" class="form-control" style="width: 200px;" required>
                                        <option value="Sinh viên" selected>Sinh Viên</option>
                                        <option value="Giáo viên">Giáo Viên</option>
                                        <option value="Tất cả">Tất cả</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Tiêu đề</label>
                                    <input class="form-control" id="tieude" name="tieude" type="text" required
                                        placeholder="Nhập tiêu đề cho khảo sát">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Mô tả</label>
                                    <input class="form-control" id="mota" name="mota" type="text" required
                                        placeholder="Nhập mô tả">
                                </div>
                            </div>
                        </div>
                        <div id="danhSachCauHoi">
                            <div class="form-group cau-hoi-item">
                                <label>Câu hỏi</label>
                                <div class="row" style="margin-bottom: 5px;">
                                    <div class="col-md-5">
                                        <input type="text" name="cauhoi[]" class="form-control" required
                                            placeholder="Nhập nội dung câu hỏi">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="loaicauhoi[]" class="form-control">
                                            <option value="text">Tự luận</option>
                                            <option value="choice">Chọn một</option>
                                            <option value="multiple">Chọn nhiều</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="dapan[]" class="form-control nhap-dapan"
                                            style="display:none;"
                                            placeholder="Nhập các câu trả lời, cách nhau bởi dấu ;">
                                    </div>
                                    <div class="col-md-1">
                                        <button class="btn btn-danger btn-remove" type="button" style="width:100%;">
                                            <i class="glyphicon glyphicon-remove"></i>
                                        </button>
                                    </div>
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
                <div class="row">
                    <div class="col-lg-12">
                        <form class="form-inline" style="margin-bottom: 15px;">
                            <label for="dot_filter">Lọc theo đợt: </label>
                            <select name="dot_filter" id="dot_filter" class="form-control">
                                <option value="">-- Tất cả --</option>
                                <?php foreach ($dsDot as $dot): ?>
                                    <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Danh sách các khảo sát đã tạo</h4>
                            </div>
                            <div class="panel-body">
                                <div id="bangKhaoSat"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php" ?>
        <script>
            // Tạo khảo sát
            $('#formTaoKhaoSat').on('submit', function (e) {
                e.preventDefault();
                $.post('/datn/admin/pages/khaosat', $(this).serialize() + '&action=tao', function (res) {
                    if (res.status === 'OK') {
                        Swal.fire('Tạo thành công!', '', 'success');
                        loadBangKhaoSat();
                        $('#formTaoKhaoSat')[0].reset();
                    } else {
                        Swal.fire('Lỗi', res.message || 'Không thể tạo khảo sát', 'error');
                    }
                }, 'json');
            });

            // Lọc khảo sát theo đợt
            $('#dot_filter').on('change', function () {
                loadBangKhaoSat();
            });

            // Hàm load bảng khảo sát
            function loadBangKhaoSat() {
                $.get('/datn/admin/pages/khaosat', {
                    ajax: 1,
                    dot_filter: $('#dot_filter').val()
                }, function (html) {
                    $('#bangKhaoSat').html(html);
                    // Khởi tạo lại DataTable sau khi bảng đã được render
                    if ($('#bangkhaosat').length) {
                        $('#bangkhaosat').DataTable({
                            info: false,
                            destroy: true, // Thêm dòng này để tránh lỗi khi khởi tạo lại
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                            }
                        });
                    }
                });
            }

            // Xóa khảo sát
            function xoaKhaoSat(id) {
                Swal.fire({
                    title: 'Xác nhận xóa?',
                    icon: 'warning',
                    showCancelButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('/datn/admin/pages/khaosat', { action: 'xoa', id: id }, function (res) {
                            if (res.status === 'OK') {
                                loadBangKhaoSat();
                            } else {
                                Swal.fire('Lỗi', res.message || 'Không thể xóa', 'error');
                            }
                        }, 'json');
                    }
                });
            }

            // Khi trang load
            $(function () {
                loadBangKhaoSat();
            });

            document.addEventListener("DOMContentLoaded", function () {
                const danhSachCauHoi = document.getElementById("danhSachCauHoi");
                const btnThem = document.getElementById("btnThemCauHoi");

                btnThem.addEventListener("click", function () {
                    const cauHoiItem = danhSachCauHoi.querySelector(".cau-hoi-item");
                    const html = cauHoiItem.outerHTML;
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const newItem = temp.firstElementChild;
                    // Reset giá trị các trường
                    newItem.querySelector("input[name='cauhoi[]']").value = "";
                    newItem.querySelector("select[name='loaicauhoi[]']").value = "text";
                    newItem.querySelector("input[name='dapan[]']").style.display = "none";
                    newItem.querySelector("input[name='dapan[]']").value = "";
                    newItem.querySelector("input[name='dapan[]']").required = false;
                    danhSachCauHoi.appendChild(newItem);
                    capNhatTrangThaiNutXoa();
                });

                danhSachCauHoi.addEventListener("click", function (e) {
                    if (e.target.closest(".btn-remove")) {
                        const items = danhSachCauHoi.querySelectorAll(".cau-hoi-item");
                        if (items.length > 1) {
                            e.target.closest(".cau-hoi-item").remove();
                            capNhatTrangThaiNutXoa();
                        }
                    }
                });

                // Hiện ô nhập đáp án nếu chọn trắc nghiệm
                danhSachCauHoi.addEventListener('change', function (e) {
                    if (e.target.name === 'loaicauhoi[]') {
                        const $item = e.target.closest('.cau-hoi-item');
                        const dapAnInput = $item.querySelector("input[name='dapan[]']");
                        if (e.target.value === 'choice' || e.target.value === 'multiple') {
                            dapAnInput.style.display = '';
                            dapAnInput.required = true;
                        } else {
                            dapAnInput.style.display = 'none';
                            dapAnInput.required = false;
                        }
                    }
                });

                function capNhatTrangThaiNutXoa() {
                    const items = danhSachCauHoi.querySelectorAll(".cau-hoi-item");
                    items.forEach((item, index) => {
                        const btn = item.querySelector(".btn-remove");
                        btn.disabled = (items.length === 1);
                    });
                }
                capNhatTrangThaiNutXoa();
            });
        </script>
</body>