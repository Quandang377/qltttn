<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký giấy giới thiệu</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";        
    ?>
    <style>
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
        }
        tr.selected {
            background-color: #007bff !important;
            color: white;
        }
    </style>
</head>
<body>
    <div id="wrapper">  
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";
        ?>
    
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Quản lý danh sách sinh viên</h1>
                
                <br>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="ten-cong-ty">Tên sinh viên</label>
                        <input type="text" class="form-control" id="ten-sinh-vien" placeholder="Tên sinh viên">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="dia-chi">Lớp</label>
                        <input type="text" class="form-control" id="lop" placeholder="Lớp">
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="ten-cong-ty">Nghành</label>
                        <input type="text" class="form-control" id="Nghanh" placeholder="Nghành">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="dia-chi">MSSV</label>
                        <input type="text" class="form-control" id="MSSV" placeholder="MSSV">
                    </div>
                </div>
                
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <button type="button" class="btn btn-primary" style="margin-bottom: 10px">Thêm</button>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách phiếu giới thiệu thực tập
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="table-dot">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tên</th>
                                        <th>MSSV</th>
                                        <th>Xếp loại</th>
                                        <th>Tên đợt</th>
                                        <th>Tên Giáo viên hướng dẫn</th>
                                    </tr>
                                </thead>
                                <?php
                                    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                                    try {
                                        $sql = "SELECT 
                                                    sv.ID_TaiKhoan,
                                                    sv.Ten,
                                                    sv.MSSV,
                                                    sv.XepLoai,
                                                    dt.TenDot AS DotThucTap,
                                                    gv.Ten AS TenGVHD,
                                                    IF(sv.TrangThai = 1, 'Hoạt động', 'Không hoạt động') AS TrangThai
                                                FROM 
                                                    SinhVien sv
                                                LEFT JOIN 
                                                    DotThucTap dt ON sv.ID_Dot = dt.ID
                                                LEFT JOIN 
                                                    GiaoVien gv ON sv.ID_GVHD = gv.ID_TaiKhoan";
                                        
                                        $stmt = $conn->prepare($sql);
                                        $stmt->execute();
                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<tr>
                                                    <td>".htmlspecialchars($row['ID_TaiKhoan'])."</td>
                                                    <td>".htmlspecialchars($row['Ten'])."</td>
                                                    <td>".htmlspecialchars($row['MSSV'])."</td>
                                                    <td>".htmlspecialchars($row['XepLoai'])."</td>
                                                    <td>".htmlspecialchars($row['DotThucTap'])."</td>
                                                    <td>".htmlspecialchars($row['TenGVHD'])."</td>
                                                </tr>";
                                        }
                                        echo "</table>";
                                        
                                    } catch(PDOException $e) {
                                        echo "Lỗi: " . $e->getMessage();
                                    }
                                    $conn = null;
                                 ?>
                            </table>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <?php
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php";
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/datatable_init.php";
        initDataTableJS('table-dot',btnID:'btn-dot'); 
    ?>
</body>
</html>
