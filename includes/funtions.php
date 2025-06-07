<?php 
function getAllInternships($pdo) {
    $stmt = $pdo->prepare("SELECT ID,TenDot,Nam,Loai,NguoiQuanLy,ThoiGianKetThuc,TenNguoiMoDot,TrangThai FROM DOTTHUCTAP where TrangThai !=-1 ORDER BY ID DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>