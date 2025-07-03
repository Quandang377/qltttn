<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Tạm thời gán id tài khoản sinh viên là 3
$id_taikhoan = 3;
$baocao = null;
$baocao_dir = null;
$baocao_trangthai = null;
$ten_sv = '';
$cho_phep_nop = false;
$errorMsg = ''; // Thêm biến này ở đầu file

// Lấy tên sinh viên và id tài khoản giáo viên hướng dẫn
$stmt = $conn->prepare("SELECT Ten, ID_GVHD FROM sinhvien WHERE ID_TaiKhoan = ?");
$stmt->execute([$id_taikhoan]);
$row_sv = $stmt->fetch(PDO::FETCH_ASSOC);
$ten_sv = $row_sv['Ten'] ?? 'Không xác định';
$id_gvhd = $row_sv['ID_GVHD'] ?? null;
    
// Kiểm tra trạng thái cho phép nộp báo cáo tổng kết của giáo viên hướng dẫn
if ($id_gvhd) {
    $stmt = $conn->prepare("SELECT TrangThai FROM Baocaotongket WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id_gvhd]);
    $trangthai_baocaotongket = $stmt->fetchColumn();
    $cho_phep_nop = ($trangthai_baocaotongket == 1);
}

// Xử lý xóa file (cập nhật trạng thái về false và xóa file vật lý)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_baocao'])) {
    // Kiểm tra lại trạng thái cho phép nộp báo cáo tổng kết của giáo viên hướng dẫn
    $stmt = $conn->prepare("SELECT TrangThai FROM Baocaotongket WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id_gvhd]);
    $trangthai_baocaotongket = $stmt->fetchColumn();
    $cho_phep_nop = ($trangthai_baocaotongket == 1);

    if (!$cho_phep_nop) {
        $errorMsg = "Giáo viên đã đóng chức năng, bạn không thể xóa báo cáo!";
    } else {
        $stmt = $conn->prepare("SELECT Dir FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'Baocao' ORDER BY ID DESC LIMIT 1");
        $stmt->execute([$id_taikhoan]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $filePath = $row['Dir'] ?? null;

        $stmt = $conn->prepare("UPDATE file SET TrangThai = 0 WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'Baocao'");
        $success = $stmt->execute([$id_taikhoan]);

        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }

        if ($success) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            echo "<script>alert('Xóa báo cáo thất bại!');</script>";
        }
    }
}

// Lấy báo cáo mới nhất có trạng thái true (1) và loại 'Baocao'
$stmt = $conn->prepare("SELECT TenFile, Dir, TrangThai FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'Baocao' ORDER BY ID DESC LIMIT 1");
$stmt->execute([$id_taikhoan]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$baocao = $row['TenFile'] ?? null;
$baocao_dir = $row['Dir'] ?? null;
$baocao_trangthai = $row['TrangThai'] ?? null;

// Xử lý upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_baocao'])) {
    if (!$cho_phep_nop) {
        echo "<script>alert('Giáo viên đã khóa chức năng!');</script>";
    } else if ($baocao_trangthai) {
        echo "<script>alert('Bạn đã nộp báo cáo rồi, không thể nộp thêm!');</script>";
    } else {
        // Xử lý báo cáo
        if (isset($_FILES['baocao_file']) && $_FILES['baocao_file']['error'] === UPLOAD_ERR_OK) {
            $tenFile = $_FILES['baocao_file']['name'];
            $ext = strtolower(pathinfo($tenFile, PATHINFO_EXTENSION));
            $allowed = ['doc', 'docx'];
            if (in_array($ext, $allowed)) {
                $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/datn/file/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                // Lưu đúng tên file gốc, nếu trùng thì thêm hậu tố thời gian
                $targetFile = $targetDir . basename($tenFile);
                if (file_exists($targetFile)) {
                    $fileNameNoExt = pathinfo($tenFile, PATHINFO_FILENAME);
                    $targetFile = $targetDir . $fileNameNoExt . '_' . time() . '.' . $ext;
                    $tenFile = basename($targetFile); // cập nhật lại tên file lưu vào DB
                }
                if (move_uploaded_file($_FILES['baocao_file']['tmp_name'], $targetFile)) {
                    $dirForDB = realpath($targetFile);
                    $stmt = $conn->prepare("INSERT INTO file (TenFile, Dir, ID_SV, TrangThai, Loai,NgayNop) VALUES (?, ?, ?, 1, 'Baocao',?)");
                    if ($stmt->execute([$tenFile, $dirForDB, $id_taikhoan, date('Y-m-d H:i:s')])) {
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    } else {
                        unlink($targetFile);
                        echo "<script>alert('Lưu vào cơ sở dữ liệu thất bại!');</script>";
                    }
                } else {
                    echo "<script>alert('Không thể lưu file lên máy chủ!');</script>";
                }
            } else {
                echo "<script>alert('Chỉ chấp nhận file Word (.doc, .docx)!');</script>";
            }
        } else {
            echo "<script>alert('Vui lòng chọn file hợp lệ!');</script>";
        }
        // Xử lý nhận xét công ty
        if (isset($_FILES['nhanxet_file']) && $_FILES['nhanxet_file']['error'] === UPLOAD_ERR_OK) {
            $tenFile = $_FILES['nhanxet_file']['name'];
            $ext = strtolower(pathinfo($tenFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/datn/file/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $targetFile = $targetDir . basename($tenFile);
                if (file_exists($targetFile)) {
                    $fileNameNoExt = pathinfo($tenFile, PATHINFO_FILENAME);
                    $targetFile = $targetDir . $fileNameNoExt . '_' . time() . '.' . $ext;
                    $tenFile = basename($targetFile);
                }
                if (move_uploaded_file($_FILES['nhanxet_file']['tmp_name'], $targetFile)) {
                    $dirForDB = realpath($targetFile);
                    $stmt = $conn->prepare("INSERT INTO file (TenFile, Dir, ID_SV, TrangThai, Loai, NgayNop) VALUES (?, ?, ?, 1, 'nhanxet', ?)");
                    $stmt->execute([$tenFile, $dirForDB, $id_taikhoan, date('Y-m-d H:i:s')]);
                }
            }
        }
        // Xử lý phiếu thực tập
        if (isset($_FILES['phieuthuctap_file']) && $_FILES['phieuthuctap_file']['error'] === UPLOAD_ERR_OK) {
            $tenFile = $_FILES['phieuthuctap_file']['name'];
            $ext = strtolower(pathinfo($tenFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/datn/file/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $targetFile = $targetDir . basename($tenFile);
                if (file_exists($targetFile)) {
                    $fileNameNoExt = pathinfo($tenFile, PATHINFO_FILENAME);
                    $targetFile = $targetDir . $fileNameNoExt . '_' . time() . '.' . $ext;
                    $tenFile = basename($targetFile);
                }
                if (move_uploaded_file($_FILES['phieuthuctap_file']['tmp_name'], $targetFile)) {
                    $dirForDB = realpath($targetFile);
                    $stmt = $conn->prepare("INSERT INTO file (TenFile, Dir, ID_SV, TrangThai, Loai, NgayNop) VALUES (?, ?, ?, 1, 'phieuthuctap', ?)");
                    $stmt->execute([$tenFile, $dirForDB, $id_taikhoan, date('Y-m-d H:i:s')]);
                }
            }
        }
        // Xử lý phiếu khảo sát
        if (isset($_FILES['khoasat_file']) && $_FILES['khoasat_file']['error'] === UPLOAD_ERR_OK) {
            $tenFile = $_FILES['khoasat_file']['name'];
            $ext = strtolower(pathinfo($tenFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/datn/file/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $targetFile = $targetDir . basename($tenFile);
                if (file_exists($targetFile)) {
                    $fileNameNoExt = pathinfo($tenFile, PATHINFO_FILENAME);
                    $targetFile = $targetDir . $fileNameNoExt . '_' . time() . '.' . $ext;
                    $tenFile = basename($targetFile);
                }
                if (move_uploaded_file($_FILES['khoasat_file']['tmp_name'], $targetFile)) {
                    $dirForDB = realpath($targetFile);
                    $stmt = $conn->prepare("INSERT INTO file (TenFile, Dir, ID_SV, TrangThai, Loai, NgayNop) VALUES (?, ?, ?, 1, 'khoasat', ?)");
                    $stmt->execute([$tenFile, $dirForDB, $id_taikhoan, date('Y-m-d H:i:s')]);
                }
            }
        }
        // Sau khi upload xong, reload lại trang
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Lấy các file đã nộp cho từng loại
$nhanxet = $phieuthuctap = $khoasat = null;
$nhanxet_dir = $phieuthuctap_dir = $khoasat_dir = null;

foreach (['nhanxet', 'phieuthuctap', 'khoasat'] as $loai) {
    $stmt = $conn->prepare("SELECT TenFile, Dir, TrangThai FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = ? ORDER BY ID DESC LIMIT 1");
    $stmt->execute([$id_taikhoan, $loai]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($loai == 'nhanxet') {
        $nhanxet = $row['TenFile'] ?? null;
        $nhanxet_dir = $row['Dir'] ?? null;
    } elseif ($loai == 'phieuthuctap') {
        $phieuthuctap = $row['TenFile'] ?? null;
        $phieuthuctap_dir = $row['Dir'] ?? null;
    } elseif ($loai == 'khoasat') {
        $khoasat = $row['TenFile'] ?? null;
        $khoasat_dir = $row['Dir'] ?? null;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nộp kết quả</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
    <style>
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div id="wrapper">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>
    <div id="page-wrapper">
        <div class="container-fluid">
            <div class="row">
                <h1 class="page-header ">Nộp kết quả</h1>
            </div>
            <div class="row">
                <!-- Hiển thị báo cáo (panel) -->
                <?php if ($baocao_trangthai): ?>
                    <div class="col-md-4">
                        <div class="panel panel-default" style="padding: 20px;background-color: #7ae98c;">
                            <div style="display: flex; align-items: center;">
                                <i class="fa fa-file-o fa-fw" style="margin-right: 12px; font-size: 28px; color: white"></i>
                                <div>
                                    <div style="font-size: 20px; font-weight: bold; display: flex; align-items: center;">
                                        <a href="<?php echo htmlspecialchars($baocao_dir); ?>" target="_blank" style="color: #222; margin-right: 10px;">
    <?php
        $maxLen = 10;
        $tenHienThi = (mb_strlen($baocao) > $maxLen)
            ? mb_substr($baocao, 0, $maxLen) . '...'
            : $baocao;
        echo htmlspecialchars($tenHienThi);
    ?>
                                        </a>
                                        <?php if ($baocao_dir): ?>
    <a href="/datn/download.php?file=<?php echo urlencode(basename($baocao_dir)); ?>" download style="color: #222;" title="Tải xuống">
        <i class="fa fa-download" style="font-size: 20px; margin-left: 5px;"></i>
    </a>
    <?php endif; ?>
                                        <!-- Nút xóa file -->
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_baocao" class="btn btn-danger btn-xs" style="margin-left: 10px;" onclick="return confirm('Bạn có chắc muốn xóa báo cáo này?');">
                                                <i class="fa fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    </div>
                                    <div style="font-size: 12px; font-weight: bold;">
                                        <?php echo htmlspecialchars($ten_sv ?: ''); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div> 
            <div class="row">
                <!-- Panel Báo cáo tổng kết: chỉ hiện khi chưa nộp -->
                <?php if (!$baocao_trangthai): ?>
                <div class="col-md-3">
                    <div class="panel panel-default upload-panel" data-type="baocao" style="padding: 20px; background-color: #7ae98c; cursor:pointer; <?php if($baocao) echo 'opacity:0.6;pointer-events:none;'; ?>">
                        <div style="display: flex; align-items: center;">
                            <i class="fa fa-file-o fa-fw" style="margin-right: 12px; font-size: 28px; color: #white"></i>
                            <div>
                                <div style="font-size: 18px; font-weight: bold;">
                                    Báo cáo tổng kết:
                                    <?php if ($baocao): ?>
                                        <a href="<?php echo htmlspecialchars($baocao_dir); ?>" target="_blank" style="color: #222; margin-left: 10px;">
                                            <?php
                                                $maxLen = 10;
                                                $tenHienThi = (mb_strlen($baocao) > $maxLen)
                                                    ? mb_substr($baocao, 0, $maxLen) . '...'
                                                    : $baocao;
                                                echo htmlspecialchars($tenHienThi);
                                            ?>
                                        </a>
                                        <a href="/datn/download.php?file=<?php echo urlencode(basename($baocao_dir)); ?>" download style="color: #222;" title="Tải xuống">
                                            <i class="fa fa-download" style="font-size: 18px; margin-left: 5px;"></i>
                                        </a>
                                        <!-- Nút xóa file -->
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_baocao" class="btn btn-danger btn-xs" style="margin-left: 10px;" onclick="return confirm('Bạn có chắc muốn xóa báo cáo này?');">
                                                <i class="fa fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                        <span class="badge badge-success" style="margin-left:10px;">Đã nộp</span>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa nộp (bấm để nộp)</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 12px; font-weight: bold;">
                                    <?php echo htmlspecialchars($ten_sv ?: ''); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Panel Nhận xét công ty -->
                <div class="col-md-3">
                    <div class="panel panel-default upload-panel" data-type="nhanxet" style="padding: 20px; background-color: #e3f0ff; cursor:pointer; <?php if($nhanxet) echo 'opacity:0.6;pointer-events:none;'; ?>">
                        <div style="display: flex; align-items: center;">
                            <i class="fa fa-file-image-o fa-fw" style="margin-right: 12px; font-size: 28px; color: #1976d2"></i>
                            <div>
                                <div style="font-size: 18px; font-weight: bold;">
                                    Nhận xét công ty:
                                    <?php if ($nhanxet): ?>
                                        <a href="<?php echo htmlspecialchars($nhanxet_dir); ?>" target="_blank" style="color: #222; margin-left: 10px;">
                                            <?php echo htmlspecialchars($nhanxet); ?>
                                        </a>
                                        <a href="/datn/download.php?file=<?php echo urlencode(basename($nhanxet_dir)); ?>" download style="color: #222;" title="Tải xuống">
                                            <i class="fa fa-download" style="font-size: 18px; margin-left: 5px;"></i>
                                        </a>
                                        <!-- Nút xóa file -->
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_nhanxet" class="btn btn-danger btn-xs" style="margin-left: 10px;" onclick="return confirm('Bạn có chắc muốn xóa file này?');">
                                                <i class="fa fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa nộp (bấm để nộp)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Panel Phiếu thực tập -->
                <div class="col-md-3">
                    <div class="panel panel-default upload-panel" data-type="phieuthuctap" style="padding: 20px; background-color: #e3f0ff; cursor:pointer; <?php if($phieuthuctap) echo 'opacity:0.6;pointer-events:none;'; ?>">
                        <div style="display: flex; align-items: center;">
                            <i class="fa fa-file-image-o fa-fw" style="margin-right: 12px; font-size: 28px; color: #1976d2"></i>
                            <div>
                                <div style="font-size: 18px; font-weight: bold;">
                                    Phiếu thực tập:
                                    <?php if ($phieuthuctap): ?>
                                        <a href="<?php echo htmlspecialchars($phieuthuctap_dir); ?>" target="_blank" style="color: #222; margin-left: 10px;">
                                            <?php echo htmlspecialchars($phieuthuctap); ?>
                                        </a>
                                        <a href="/datn/download.php?file=<?php echo urlencode(basename($phieuthuctap_dir)); ?>" download style="color: #222;" title="Tải xuống">
                                            <i class="fa fa-download" style="font-size: 18px; margin-left: 5px;"></i>
                                        </a>
                                        <!-- Nút xóa file -->
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_phieuthuctap" class="btn btn-danger btn-xs" style="margin-left: 10px;" onclick="return confirm('Bạn có chắc muốn xóa file này?');">
                                                <i class="fa fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa nộp (bấm để nộp)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Panel Phiếu khảo sát -->
                <div class="col-md-3">
                    <div class="panel panel-default upload-panel" data-type="khoasat" style="padding: 20px; background-color: #e3f0ff; cursor:pointer; <?php if($khoasat) echo 'opacity:0.6;pointer-events:none;'; ?>">
                        <div style="display: flex; align-items: center;">
                            <i class="fa fa-file-image-o fa-fw" style="margin-right: 12px; font-size: 28px; color: #1976d2"></i>
                            <div>
                                <div style="font-size: 18px; font-weight: bold;">
                                    Phiếu khảo sát:
                                    <?php if ($khoasat): ?>
                                        <a href="<?php echo htmlspecialchars($khoasat_dir); ?>" target="_blank" style="color: #222; margin-left: 10px;">
                                            <?php echo htmlspecialchars($khoasat); ?>
                                        </a>
                                        <a href="/datn/download.php?file=<?php echo urlencode(basename($khoasat_dir)); ?>" download style="color: #222;" title="Tải xuống">
                                            <i class="fa fa-download" style="font-size: 18px; margin-left: 5px;"></i>
                                        </a>
                                        <!-- Nút xóa file -->
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_khoasat" class="btn btn-danger btn-xs" style="margin-left: 10px;" onclick="return confirm('Bạn có chắc muốn xóa file này?');">
                                                <i class="fa fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa nộp (bấm để nộp)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($baocao_trangthai): ?>
    <div class="col-md-12">
        <div class="alert alert-success" style="font-size:18px;">
            <i class="fa fa-check-circle"></i> Bạn đã nộp báo cáo tổng kết.
        </div>
    </div>
<?php endif; ?>
        </div>
    </div>
    <!-- Modal upload file từng loại -->
<div class="modal fade" id="uploadFileModal" tabindex="-1" role="dialog" aria-labelledby="uploadFileModalLabel">
  <div class="modal-dialog" role="document">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="upload_type" id="upload_type">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="uploadFileModalLabel">Tải lên file</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body" id="upload-modal-body">
          <!-- Nội dung sẽ được thay đổi bằng JS -->
        </div>
        <div class="modal-footer">
          <button type="submit" name="upload_file_panel" class="btn btn-success">Tải lên</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
        </div>
      </div>
    </form>
  </div>
</div>
    </div>  
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>                                               
</body>
</html>
<script>
$(document).ready(function() {
    $('.upload-panel').click(function() {
        // Nếu panel đã có file thì không cho upload nữa
        if ($(this).css('pointer-events') === 'none') return;

        var type = $(this).data('type');
        var label = '';
        var accept = '';
        if(type === 'baocao') {
            label = 'Chọn file báo cáo (.doc, .docx):';
            accept = '.doc,.docx';
        } else if(type === 'nhanxet') {
            label = 'Chọn ảnh nhận xét công ty (.jpg, .jpeg, .png):';
            accept = '.jpg,.jpeg,.png';
        } else if(type === 'phieuthuctap') {
            label = 'Chọn ảnh phiếu thực tập (.jpg, .jpeg, .png):';
            accept = '.jpg,.jpeg,.png';
        } else if(type === 'khoasat') {
            label = 'Chọn ảnh phiếu khảo sát (.jpg, .jpeg, .png):';
            accept = '.jpg,.jpeg,.png';
        }
        $('#upload_type').val(type);
        $('#upload-modal-body').html('<label>'+label+'</label><input type="file" name="upload_file" class="form-control" accept="'+accept+'" required>');
        $('#uploadFileModal').modal('show');
    });
});
</script>