<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
 
$stmt = $conn->prepare("SELECT ID, TIEUDE, NOIDUNG, NGAYDANG FROM THONGBAO WHERE TRANGTHAI=1 ORDER BY NGAYDANG DESC");
=======
$stmt = $conn->prepare("SELECT ID, TIEUDE, NOIDUNG, NGAYDANG FROM THONGBAO ORDER BY NGAYDANG DESC");
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
$stmt->execute();
$thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
