<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

// Kiểm tra đăng nhập
$id_gvhd = $_SESSION['user']['ID_TaiKhoan'] ?? null;
if (!$id_gvhd) {
    die('Bạn chưa đăng nhập!');
}

// Lấy ID sinh viên và ID đợt từ URL
$id_sv = isset($_GET['id_sv']) ? (int)$_GET['id_sv'] : 0;
$id_dot = isset($_GET['id_dot']) ? (int)$_GET['id_dot'] : 0;

if (!$id_sv || !$id_dot) {
    die('Thiếu thông tin sinh viên hoặc đợt!');
}

// Kiểm tra sinh viên thuộc quyền quản lý của giáo viên này
$stmt = $conn->prepare("SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV 
                        FROM SinhVien sv 
                        WHERE sv.ID_TaiKhoan = ? AND sv.ID_GVHD = ? AND sv.ID_Dot = ?");
$stmt->execute([$id_sv, $id_gvhd, $id_dot]);
$sinh_vien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sinh_vien) {
    die('Sinh viên không thuộc quyền quản lý của bạn!');
}

// Lấy thông tin đợt
$stmt = $conn->prepare("SELECT TenDot FROM DotThucTap WHERE ID = ?");
$stmt->execute([$id_dot]);
$dot_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dot_name = $dot_info ? $dot_info['TenDot'] : "Dot-$id_dot";

// Tạo file ZIP
$zip = new ZipArchive();
// Chỉ sử dụng MSSV cho tên file
$folder_name = $sinh_vien['MSSV'];
$zipName = $folder_name . '_' . date('Ymd_His') . '.zip';
$zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die('Không thể tạo file ZIP!');
}

// Lấy tất cả các loại file của sinh viên
$loai_files = ['Baocao', 'khoasat', 'phieuthuctap', 'nhanxet'];
$file_count = 0;

foreach ($loai_files as $loai) {
    $stmt = $conn->prepare("SELECT TenFile, Dir FROM file 
                           WHERE ID_SV = ? AND Loai = ? AND TrangThai = 1 
                           ORDER BY ID DESC LIMIT 1");
    $stmt->execute([$id_sv, $loai]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file && !empty($file['Dir']) && file_exists($file['Dir'])) {
        $file_name = $loai . '_' . $file['TenFile'];
        // Read file contents instead of directly adding the file
        $file_contents = file_get_contents($file['Dir']);
        if ($file_contents !== false) {
            $zip->addFromString($file_name, $file_contents);
            $file_count++;
        }
    }
}

if ($file_count === 0) {
    $zip->close();
    @unlink($zipPath);
    die('Không có file nào để tải xuống!');
}

$zip->close();

// Trả về file ZIP để tải xuống
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($zipPath));
header('Pragma: no-cache');
header('Expires: 0');

// Read file in chunks to handle large files
$handle = fopen($zipPath, 'rb');
if ($handle) {
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
}

// Xóa file tạm sau khi đã tải xuống
@unlink($zipPath);
exit;
?>
