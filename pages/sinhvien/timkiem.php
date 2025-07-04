<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/middleware/check_role.php";

$tuKhoa = trim($_GET['q'] ?? '');
$tuKhoaLike = '%' . $tuKhoa . '%';

$mapping = [
    'trang chủ' => 'pages/sinhvien/trangchu',
    'trang chu' => 'pages/sinhvien/trangchu',
    'tc' => 'pages/sinhvien/trangchu',
    'home' => 'pages/sinhvien/trangchu',
    'giấy giới thiệu' => 'pages/sinhvien/dangkygiaygioithieu',
    'giay gioi thieu' => 'pages/sinhvien/dangkygiaygioithieu',
    'ggt' => 'pages/sinhvien/dangkygiaygioithieu',
    'báo cáo tuần' => 'pages/sinhvien/baocaotuan',
    'bao cao tuan' => 'pages/sinhvien/baocaotuan',
    'bao cao' => 'pages/sinhvien/baocaotuan',
    'bc' => 'pages/sinhvien/baocaotuan',
    'tài nguyên' => 'pages/sinhvien/tainguyen',
    'tai nguyen' => 'pages/sinhvien/tainguyen',
    'tn' => 'pages/sinhvien/tainguyen',
    'nộp kết quả' => 'pages/sinhvien/nopketqua',
    'nop ket qua' => 'pages/sinhvien/nopketqua',
    'nkq' => 'pages/sinhvien/nopketqua',
    'kq' => 'pages/sinhvien/nopketqua',
    'khảo sát' => 'pages/sinhvien/khaosat',
    'khao sat' => 'pages/sinhvien/khaosat',
    'ks' => 'pages/sinhvien/khaosat',
    'trang ca nhan' => 'pages/sinhvien/thongtincanhan',
    'trang cá nhan' => 'pages/sinhvien/thongtincanhan',
    'trang cá nhân' => 'pages/sinhvien/thongtincanhan',
    'thông tin cá nhân' => 'pages/sinhvien/thongtincanhan',
    'tcn' => 'pages/sinhvien/thongtincanhan',
    'ttcn' => 'pages/sinhvien/thongtincanhan',
    'profile' => 'pages/sinhvien/thongtincanhan',
];

// Chuyển về chữ thường không dấu để so sánh
function bo_dau($str) {
    $str = strtolower($str);
    $str = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $str);
    $str = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $str);
    $str = preg_replace('/[ìíịỉĩ]/u', 'i', $str);
    $str = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $str);
    $str = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $str);
    $str = preg_replace('/[ỳýỵỷỹ]/u', 'y', $str);
    $str = preg_replace('/[đ]/u', 'd', $str);
    return $str;
}

$tuKhoaCheck = bo_dau($tuKhoa);
foreach ($mapping as $tu => $url) {
    if (strpos(bo_dau($tuKhoaCheck), bo_dau($tu)) !== false) {
        header("Location: /datn/" . $url);
        exit;
    }
}

// Lấy ID đợt thực tập của sinh viên
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$stmt = $conn->prepare("SELECT ID_Dot FROM sinhvien WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$idDot = $stmt->fetchColumn();

// 1. Tìm trong thông báo
$stmt = $conn->prepare("
    SELECT * FROM thongbao 
    WHERE ID_Dot = :id_dot AND (TIEUDE LIKE :keyword OR NOIDUNG LIKE :keyword)
    ORDER BY NGAYDANG DESC
");
$stmt->execute(['id_dot' => $idDot, 'keyword' => $tuKhoaLike]);
$ketQuaThongBao = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Tìm trong tài nguyên
$stmt = $conn->prepare("
    SELECT * FROM file 
    WHERE Loai='Tainguyen' AND TrangThai = 1 AND (Ten LIKE :keyword OR TenFile LIKE :keyword)
    ORDER BY NgayNop DESC
");
$stmt->execute(['keyword' => $tuKhoaLike]);
$ketQuaTaiNguyen = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
    .panel-tainguyen {
        padding: 20px;
        min-height: 140px;
        background: #f8fff8;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 20px;
        transition: box-shadow 0.2s;
        display: flex;
        align-items: flex-start;
    }

    .panel-tainguyen:hover {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        background: #eaffea;
    }

    .panel-tainguyen .fa {
        margin-right: 12px;
        font-size: 30px;
        color: #4caf50;
        margin-top: 3px;
    }

    .ten-tainguyen, .tenfile {
        max-width: 180px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

    .btn-xs {
        padding: 3px 8px;
        font-size: 12px;
    }

    @media (max-width: 576px) {
        .ten-tainguyen, .tenfile {
            max-width: 100%;
        }
    }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_SinhVien.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Kết quả tìm kiếm: <?= htmlspecialchars($tuKhoa) ?></h1>

                <?php if (empty($ketQuaThongBao) && empty($ketQuaTaiNguyen)): ?>
                    <div class="alert alert-warning">Không tìm thấy kết quả phù hợp.</div>
                <?php endif; ?>

                <?php if (!empty($ketQuaThongBao)): ?>
                    <h3>🔔 Thông báo:</h3>
                    <?php foreach ($ketQuaThongBao as $tb): ?>
                        <div class="row" style="margin-bottom: 15px; border: 1px solid #ddd; padding:10px 0; border-radius: 4px;">
                        <div class="col-md-2 text-center">
                            <a href="pages/sinhvien/chitietthongbao.php?id=<?= $tb['ID'] ?>">
                                <img src="/datn/uploads/Images/ThongBao.jpg" style="width: 100px; height: 70px; object-fit: cover;">
                            </a>
                        </div>
                        <div class="col-lg-10">
                            <p style="margin-bottom: 5px;">
                                <a href="pages/sinhvien/chitietthongbao.php?id=<?= $tb['ID'] ?>" style="font-weight: bold; text-decoration: none;">
                                    <?=$tb['TieuDe']?>
                                </a>
                            </p>
                            <ul class="list-inline" style="color: #888; font-size: 13px; margin: 0;">
                                <li>Thông báo</li>
                                <li>|</li>
                                <li><?= date('d/m/Y', strtotime($tb['NgayDang'])) ?></li>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($ketQuaTaiNguyen)): ?>
                    <h3>📁 Tài nguyên:</h3>
                    <?php foreach ($ketQuaTaiNguyen as $row):
                        $isFile = strpos($row['DIR'], '/datn/file/') !== false || strpos($row['DIR'], '\\datn\\file\\') !== false;
                        $fileUrl = $isFile ? '/datn/file/' . basename($row['DIR']) : $row['DIR'];
                        ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="panel panel-tainguyen d-flex align-items-start" style="gap: 10px;">
                                <i class="fa fa-file-o fa-fw"></i>
                                <div>
                                    <div class="ten-tainguyen" title="<?= htmlspecialchars($row['Ten']) ?>">
                                        <?= htmlspecialchars($row['Ten']) ?>
                                    </div>
                                    <div class="tenfile text-muted" title="<?= htmlspecialchars($row['TenFile']) ?>">
                                        <?= htmlspecialchars($row['TenFile']) ?>
                                    </div>
                                    <a href="javascript:void(0)" onclick="xemFileOnline('<?= htmlspecialchars($fileUrl) ?>')"
                                        class="btn btn-xs btn-info mt-1">Xem</a>
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" download
                                        class="btn btn-xs btn-success mt-1 ml-1">Tải xuống</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <script>
        function xemFileOnline(url) {
            window.open(url, '_blank');
        }
    </script>
</body>

</html>