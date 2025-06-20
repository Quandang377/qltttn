<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý giấy giới thiệu</title>
   <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

    function shortAddress($address, $max = 50) {
        $address = trim($address);
        if (mb_strlen($address, 'UTF-8') > $max) {
            return mb_substr($address, 0, $max, 'UTF-8') . '...';
        }
        return $address;
    }   
    ?>
    <style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
    }
    .print-all-btn {
        margin-left: 10px;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        #print-section, #print-section * {
            visibility: visible;
        }
        #print-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .panel {
            page-break-inside: avoid;
            margin-bottom: 20px;
        }
    }
    </style>
</head>
<body>
    <div id="wrapper">
       <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php";

            // Lấy danh sách giấy giới thiệu đã duyệt kèm tên sinh viên và MSSV
            $stmtApproved = $conn->prepare("
                SELECT g.ID, g.TenCty, g.DiaChi, g.IdSinhVien, s.Ten AS TenSinhVien, s.MSSV
                FROM GiayGioiThieu g
                LEFT JOIN SinhVien s ON g.IdSinhVien = s.ID_TaiKhoan
                WHERE g.TrangThai = 1
                ORDER BY g.ID DESC
            ");
            $stmtApproved->execute();
            $approvedList = $stmtApproved->fetchAll(PDO::FETCH_ASSOC);

            // Lấy danh sách giấy giới thiệu chưa duyệt kèm tên sinh viên và MSSV
            $stmtPending = $conn->prepare("
                SELECT g.ID, g.TenCty, g.DiaChi, g.IdSinhVien, s.Ten AS TenSinhVien, s.MSSV
                FROM GiayGioiThieu g
                LEFT JOIN SinhVien s ON g.IdSinhVien = s.ID_TaiKhoan
                WHERE g.TrangThai = 0
                ORDER BY g.ID DESC
            ");
            $stmtPending->execute();
            $pendingList = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

            // Lấy danh sách giấy giới thiệu đã in kèm tên sinh viên và MSSV
            $stmtPrinted = $conn->prepare("
                SELECT g.ID, g.TenCty, g.DiaChi, g.IdSinhVien, s.Ten AS TenSinhVien, s.MSSV
                FROM GiayGioiThieu g
                LEFT JOIN SinhVien s ON g.IdSinhVien = s.ID_TaiKhoan
                WHERE g.TrangThai = 2 
                ORDER BY g.ID DESC
            ");
            $stmtPrinted->execute();
            $printedList = $stmtPrinted->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Quản lý giấy giới thiệu
                    </h1>
                </div>
                <div class="row">
                    <div class="btn-group col-md-4" data-toggle="buttons">
                        <label class="btn btn-default active" id="btnapp">
                            <input type="radio" name="status" id="approved" autocomplete="off" checked> Đã duyệt
                        </label>
                        <label class="btn btn-default" id="btnpen">
                            <input type="radio" name="status" id="pending" autocomplete="off"> Chưa duyệt
                        </label>
                        <label class="btn btn-default" id="btnprinted">
                            <input type="radio" name="status" id="printed" autocomplete="off"> Đã in
                        </label>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control col-md-8" id="name" placeholder="Tìm kiếm theo MSSV">
                    </div>
                    <div class="col-md-4">
                        <div class="btn btn-success">
                            <i class="fa fa-search"></i>
                        </div>
                        <button id="printAllBtn" class="btn btn-primary print-all-btn">
                            <i class="fa fa-print"></i> In tất cả
                        </button>
                    </div>
                </div>
                
                <!-- Phần hiển thị để in -->
                <div id="print-section" style="display: none;">
                    <h2 class="text-center">DANH SÁCH GIẤY GIỚI THIỆU ĐÃ DUYỆT</h2>
                    <hr>
                    <?php foreach ($approvedList as $row): ?>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo htmlspecialchars($row['TenCty']); ?></h3>
                            </div>
                            <div class="panel-body">
                                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($row['DiaChi']); ?></p>
                                <p><strong>Sinh viên:</strong> <?php echo htmlspecialchars($row['TenSinhVien']); ?> (<?php echo htmlspecialchars($row['MSSV']); ?>)</p>
                                <p><strong>Trạng thái:</strong> <?php echo $row['DaNhan'] ? 'Đã nhận' : 'Chưa nhận'; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="panel-approved">
                    <div class="row">
                    <?php if (count($approvedList) > 0): ?>
                        <?php foreach ($approvedList as $row): ?>
                            <div class="col-md-4">
                                <form method="post" action="/datn/pages/giaovien/chitietgiaygioithieu" id="form-<?php echo $row['ID']; ?>">
                                    <input type="hidden" name="giay_id" value="<?php echo $row['ID']; ?>">
                                    <div class="panel panel-default" 
                                         data-mssv="<?php echo htmlspecialchars($row['MSSV']); ?>"
                                         style="margin-top: 15px;">
                                         <div class="pannel-header" style="padding: 15px 15px 0px 15px;">
                                            <strong><?php echo htmlspecialchars($row['TenCty']); ?></strong>
                                         </div>
                                        <div class="panel-body" style="padding: 15px">
                                            <div style="font-size: 13px; color: #555; margin-top: 5px;">
                                                <?php echo htmlspecialchars(shortAddress($row['DiaChi'])); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #007bff; margin-top: 5px;">
                                                SV: <?php echo htmlspecialchars($row['TenSinhVien']); ?> (<?php echo htmlspecialchars($row['MSSV']); ?>)
                                            </div>
                                            
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-md-12 text-center" style="padding:20px;">Không có giấy giới thiệu đã duyệt</div>
                    <?php endif; ?>
                    </div>
                </div>
                <div id="panel-pending" style="display: none;">
                    <div class="row">
                    <?php if (count($pendingList) > 0): ?>
                        <?php foreach ($pendingList as $row): ?>
                            <div class="col-md-4">
                                <form method="post" action="/datn/pages/giaovien/chitietgiaygioithieu" id="form-<?php echo $row['ID']; ?>">
                                    <input type="hidden" name="giay_id" value="<?php echo $row['ID']; ?>">
                                    <div class="panel panel-default" style="margin-top: 15px; cursor:pointer;"
                                         onclick="document.getElementById('form-<?php echo $row['ID']; ?>').submit();">
                                         <div class="pannel-header" style="padding: 15px 15px 0px 15px;">
                                            <strong><?php echo htmlspecialchars($row['TenCty']); ?></strong>
                                         </div>
                                        <div class="panel-body" style="padding: 15px">
                                            <div style="font-size: 13px; color: #555; margin-top: 5px;">
                                                <?php echo htmlspecialchars(shortAddress($row['DiaChi'])); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #007bff; margin-top: 5px;">
                                                SV: <?php echo htmlspecialchars($row['TenSinhVien']); ?> (<?php echo htmlspecialchars($row['MSSV']); ?>)
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-md-12 text-center" style="padding:20px;">Không có giấy giới thiệu chưa duyệt</div>
                    <?php endif; ?>
                    </div>
                </div>
                <div id="panel-printed" style="display: none;">
                    <div class="row">
                    <?php if (count($printedList) > 0): ?>
                        <?php foreach ($printedList as $row): ?>
                            <div class="col-md-4">
                                <form method="post" action="/datn/pages/giaovien/chitietgiaygioithieu" id="form-printed-<?php echo $row['ID']; ?>">
                                    <input type="hidden" name="giay_id" value="<?php echo $row['ID']; ?>">
                                    <div class="panel panel-default" 
                                         data-mssv="<?php echo htmlspecialchars($row['MSSV']); ?>"
                                         style="margin-top: 15px;">
                                         <div class="pannel-header" style="padding: 15px 15px 0px 15px;">
                                            <strong><?php echo htmlspecialchars($row['TenCty']); ?></strong>
                                         </div>
                                        <div class="panel-body" style="padding: 15px">
                                            <div style="font-size: 13px; color: #555; margin-top: 5px;">
                                                <?php echo htmlspecialchars(shortAddress($row['DiaChi'])); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #007bff; margin-top: 5px;">
                                                SV: <?php echo htmlspecialchars($row['TenSinhVien']); ?> (<?php echo htmlspecialchars($row['MSSV']); ?>)
                                            </div>
                                            <button type="button" class="btn btn-success" >
                                                <i class="fa fa-check"></i> Đã nhận
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-md-12 text-center" style="padding:20px;">Không có giấy giới thiệu đã in</div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    document.getElementById('btnapp').addEventListener('click', function () {
        document.getElementById('panel-approved').style.display = 'block';
        document.getElementById('panel-pending').style.display = 'none';
        document.getElementById('panel-printed').style.display = 'none';
        document.getElementById('btnapp').classList.add('active');
        document.getElementById('btnpen').classList.remove('active');
        document.getElementById('btnprinted').classList.remove('active');
        document.getElementById('printAllBtn').style.display = 'inline-block';
    });

    document.getElementById('btnpen').addEventListener('click', function () {
        document.getElementById('panel-approved').style.display = 'none';
        document.getElementById('panel-pending').style.display = 'block';
        document.getElementById('panel-printed').style.display = 'none';
        document.getElementById('btnpen').classList.add('active');
        document.getElementById('btnapp').classList.remove('active');
        document.getElementById('btnprinted').classList.remove('active');
        document.getElementById('printAllBtn').style.display = 'none';
    });

    document.getElementById('btnprinted').addEventListener('click', function () {
        document.getElementById('panel-approved').style.display = 'none';
        document.getElementById('panel-pending').style.display = 'none';
        document.getElementById('panel-printed').style.display = 'block';
        document.getElementById('btnprinted').classList.add('active');
        document.getElementById('btnapp').classList.remove('active');
        document.getElementById('btnpen').classList.remove('active');
        document.getElementById('printAllBtn').style.display = 'none';
    });

    document.getElementById('name').addEventListener('input', function () {
        var keyword = this.value.trim().toLowerCase();
        // Lọc cả ba panel
        ['panel-approved', 'panel-pending', 'panel-printed'].forEach(function(panelId) {
            var panels = document.querySelectorAll('#' + panelId + ' .panel-default');
            panels.forEach(function(panel) {
                var mssv = (panel.getAttribute('data-mssv') || '').toLowerCase();
                if (mssv.indexOf(keyword) !== -1 || keyword === '') {
                    panel.parentElement.style.display = '';
                } else {
                    panel.parentElement.style.display = 'none';
                }
            });
        });
    });

    // Xử lý in tất cả
    document.getElementById('printAllBtn').addEventListener('click', function() {
        // Kiểm tra xem có giấy nào để in không
        if (<?php echo count($approvedList); ?> === 0) {
            alert('Không có giấy giới thiệu nào để in!');
            return;
        }
        
        // Kích hoạt chức năng in
        var printContents = document.getElementById('print-section').innerHTML;
        var originalContents = document.body.innerHTML;
        
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        
        // Sau khi in xong, load lại trang để đảm bảo mọi thứ hoạt động bình thường
        location.reload();
    });

    function markAsReceived(id) {
        if (confirm('Bạn có chắc chắn muốn xác nhận sinh viên đã nhận giấy giới thiệu này?')) {
            // Gửi yêu cầu AJAX để cập nhật trạng thái
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/datn/pages/giaovien/mark_as_received.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('Đã xác nhận sinh viên nhận giấy giới thiệu!');
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + xhr.responseText);
                }
            };
            xhr.send('id=' + id);
        }
    }
    </script>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/startmin.js"></script>
</body>
</html>