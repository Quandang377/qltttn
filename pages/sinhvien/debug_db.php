<?php
// Debug file để kiểm tra cấu trúc database
session_start();
require_once __DIR__ . "/../../template/config.php";

// Giả lập session
$_SESSION['user'] = [
    'ID_TaiKhoan' => 1,
    'VaiTro' => 'Sinh viên'
];

echo "<h2>Debug Database Structure</h2>";

// Test 1: Kiểm tra bảng file
echo "<h3>1. Kiểm tra bảng file:</h3>";
$sql = "SELECT * FROM file WHERE Loai = 'Tainguyen' AND TrangThai = 1 LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    echo "Số record trong bảng file: " . $result->rowCount() . "<br>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['ID'] . " - TenFile: " . $row['TenFile'] . " - DIR: " . $row['DIR'] . "<br>";
    }
} else {
    echo "Lỗi query bảng file<br>";
}

// Test 2: Kiểm tra bảng tainguyen_dot
echo "<h3>2. Kiểm tra bảng tainguyen_dot:</h3>";
$sql = "SELECT * FROM tainguyen_dot LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    echo "Số record trong bảng tainguyen_dot: " . $result->rowCount() . "<br>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "id_file: " . $row['id_file'] . " - id_dot: " . $row['id_dot'] . "<br>";
    }
} else {
    echo "Lỗi query bảng tainguyen_dot<br>";
}

// Test 3: Kiểm tra bảng dotthuctap
echo "<h3>3. Kiểm tra bảng dotthuctap:</h3>";
$sql = "SELECT * FROM dotthuctap LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    echo "Số record trong bảng dotthuctap: " . $result->rowCount() . "<br>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['ID'] . " - TenDot: " . $row['TenDot'] . "<br>";
    }
} else {
    echo "Lỗi query bảng dotthuctap<br>";
}

// Test 4: Kiểm tra bảng sinhvien
echo "<h3>4. Kiểm tra bảng sinhvien:</h3>";
$sql = "SELECT * FROM sinhvien LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    echo "Số record trong bảng sinhvien: " . $result->rowCount() . "<br>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "ID_TaiKhoan: " . $row['ID_TaiKhoan'] . " - ID_Dot: " . $row['ID_Dot'] . "<br>";
    }
} else {
    echo "Lỗi query bảng sinhvien<br>";
}

// Test 5: Thử query chính
echo "<h3>5. Test query chính:</h3>";
$idTaiKhoan = 1; // Giả sử
$stmt = $conn->prepare("SELECT ID_Dot FROM sinhvien WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$idDot = $stmt->fetchColumn();
echo "ID_Dot của sinh viên: " . $idDot . "<br>";

if ($idDot) {
    $stmt = $conn->prepare("
        SELECT DISTINCT
            f.ID,
            f.TenFile,
            f.TenHienThi,
            f.NgayNop,
            f.DIR,
            dt.TenDot
        FROM file f
        INNER JOIN tainguyen_dot td ON f.ID = td.id_file
        INNER JOIN dotthuctap dt ON td.id_dot = dt.ID
        WHERE f.Loai = 'Tainguyen' 
        AND f.TrangThai = 1 
        AND td.id_dot = ?
        ORDER BY f.NgayNop DESC
    ");
    $stmt->execute([$idDot]);
    $taiNguyenList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Số tài nguyên tìm thấy: " . count($taiNguyenList) . "<br>";
    foreach ($taiNguyenList as $tn) {
        echo "- " . $tn['TenFile'] . " (ID: " . $tn['ID'] . ")<br>";
    }
}

$conn = null;
?>
