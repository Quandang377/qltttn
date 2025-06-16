<?php 
$stmt = $conn->prepare("SELECT ID, TIEUDE, NOIDUNG, NGAYDANG FROM THONGBAO ORDER BY NGAYDANG DESC");
$stmt->execute();
$thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
