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
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";
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
        } else if (isset($_FILES['baocao_file']) && $_FILES['baocao_file']['error'] === UPLOAD_ERR_OK) {
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
    }
    ?>
        
    <div id="page-wrapper">
        <div class="container-fluid">
            <div class="row">
                <h1 class="page-header ">Nộp kết quả</h1>
            </div>
            <!-- Nút tải lên -->
            <?php if (!$baocao_trangthai && $cho_phep_nop): ?>
            <button id="uploadButton" type="button" class="btn btn-success" style="margin-bottom: 10px" data-toggle="modal" data-target="#uploadModal">
                <i class="fa fa-upload"></i> Tải lên
            </button>
            <?php elseif (!$cho_phep_nop): ?>
            <div class="alert alert-warning" style="margin-bottom: 10px">Giáo viên đã khóa chức năng!</div>
            <?php else: ?>
            <div class="alert alert-info" style="margin-bottom: 10px">Bạn đã nộp báo cáo, không thể nộp thêm!</div>
            <?php endif; ?>
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
                <?php else: ?>
                    <div class="col-md-12">
                        <span style="font-size:16px; color:#888; font-style:italic;">Không có báo cáo nào</span>
                    </div>
                <?php endif; ?>
            </div> 
        </div>
    </div>

    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel">
      <div class="modal-dialog" role="document">
        <form method="post" enctype="multipart/form-data">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title" id="uploadModalLabel">Tải lên báo cáo</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <input type="file" name="baocao_file" class="form-control" required accept=".doc,.docx">
            </div>
            <div class="modal-footer">
              <button type="submit" name="upload_baocao" class="btn btn-success">Tải lên</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    </div>  
                    
</body>
</html>