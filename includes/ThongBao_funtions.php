<?php 
$stmt = $conn->prepare("SELECT ID, TIEUDE, NOIDUNG, NGAYDANG FROM THONGBAO WHERE TRANGTHAI=1 ORDER BY NGAYDANG DESC");
$stmt->execute();
$thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
