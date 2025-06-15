<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý giấy giới thiệu</title>
   <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
<<<<<<< HEAD
=======
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

    function shortAddress($address, $max = 50) {
        $address = trim($address);
        if (mb_strlen($address, 'UTF-8') > $max) {
            return mb_substr($address, 0, $max, 'UTF-8') . '...';
        }
        return $address;
    }   
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
    ?>
    <style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
    }
<<<<<<< HEAD
</style>
=======
    </style>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
</head>
<body>
    <div id="wrapper">
        <?php
<<<<<<< HEAD
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php";
    ?>
=======
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
        ?>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
        
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
                    </div>
                    <div class="col-md-4">
<<<<<<< HEAD
                        <input type="text" class="form-control col-md-8" id="name" placeholder="Tìm kiếm theo tên">
=======
                        <input type="text" class="form-control col-md-8" id="name" placeholder="Tìm kiếm theo MSSV">
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
                    </div>
                    <div class="col-md-4">
                        <div class="btn btn-success">
                            <i class="fa fa-search"></i>
                        </div>
                    </div>
                </div>
                
                <div id="panel-approved">
<<<<<<< HEAD
                    <div class="col-md-4">
                        <div class="panel panel-default" style="margin-top: 15px;">
                            <div class="panel-heading">
                            Đặng Minh Quân
                            </div>
                            <div class="pannel-body" style="padding: 15px;">
                            Công ty VNG
                            </div>
                        
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default" style="margin-top: 15px;">
                            <div class="panel-heading">
                            Đặng Minh Quân
                            </div>
                            <div class="pannel-body" style="padding: 15px;">
                            Công ty VNG
                            </div>
                        
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default" style="margin-top: 15px;">
                            <div class="panel-heading">
                            Đặng Minh Quân
                            </div>
                            <div class="pannel-body" style="padding: 15px;">
                            Công ty VNG
                            </div>
                        
                        </div>
                    </div>
                    
                <div id="panel-pending" style="display: none;">

=======
                    <div class="row">
                    <?php if (count($approvedList) > 0): ?>
                        <?php foreach ($approvedList as $row): ?>
                            <div class="col-md-4">
                                <form method="post" action="/datn/pages/giaovien/chitietgiaygioithieu" id="form-<?php echo $row['ID']; ?>">
                                    <input type="hidden" name="giay_id" value="<?php echo $row['ID']; ?>">
                                    <div class="panel panel-default" 
                                         data-mssv="<?php echo htmlspecialchars($row['MSSV']); ?>"
                                         style="margin-top: 15px; cursor:pointer;"
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
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
                </div>
            </div>
            
        </div>
    </div>

    <!-- Scripts -->
<<<<<<< HEAD
     <script>
    document.getElementById('approved').addEventListener('change', function () {
    document.getElementById('panel-approved').style.display = 'block';
    document.getElementById('panel-pending').style.display = 'none';
    document.getElementById('btnapp').classList.add('active');
    document.getElementById('btnpen').classList.remove('active');
});

document.getElementById('pending').addEventListener('change', function () {
    document.getElementById('panel-approved').style.display = 'none';
    document.getElementById('panel-pending').style.display = 'block';
    document.getElementById('btnpen').classList.add('active');
    document.getElementById('btnapp').classList.remove('active');
});

</script>
=======
    <script>
    document.getElementById('btnapp').addEventListener('click', function () {
        document.getElementById('panel-approved').style.display = 'block';
        document.getElementById('panel-pending').style.display = 'none';
        document.getElementById('btnapp').classList.add('active');
        document.getElementById('btnpen').classList.remove('active');
    });

    document.getElementById('btnpen').addEventListener('click', function () {
        document.getElementById('panel-approved').style.display = 'none';
        document.getElementById('panel-pending').style.display = 'block';
        document.getElementById('btnpen').classList.add('active');
        document.getElementById('btnapp').classList.remove('active');
    });

    document.getElementById('name').addEventListener('input', function () {
    var keyword = this.value.trim().toLowerCase();
    // Lọc cả hai panel
    ['panel-approved', 'panel-pending'].forEach(function(panelId) {
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
    </script>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/startmin.js"></script>
</body>
</html>