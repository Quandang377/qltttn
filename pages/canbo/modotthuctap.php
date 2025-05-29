<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Mở đợt thực tập</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/qltttn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
        
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/qltttn/template/slidebar_CanBo.php";
    ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Mở Đợt Thực Tập
                    </h1>
                </div>
                <div class="form-container">
                <form id="intershipForm" method="post">
                    <div class="row mb-3">
                    <div class="col-lg-6">
                        <div class="form-group">
                        <div class="form-group">
                            <label >Năm</label>
                            <input class="form-control"id="namhoc"name="namhoc" type="text" placeholder="Nhập năm học" >
                        </div>
                        <div class="form-group">
                            <label >Ngành:</label>
                            <select id="nganh" name="nganh"class="form-control">
                            <option value="Công nghệ Kỹ thuật Cơ khí CK">Công nghệ Kỹ thuật Cơ khí</option>
                            <option value="Công nghệ Kỹ thuật Ô tô KTOT">Công nghệ Kỹ thuật Ô tô</option>
                            <option value="Công nghệ Kỹ thuật Điện, Điện tử DT">Công nghệ Kỹ thuật Điện, Điện tử</option>
                            <option value="Công nghệ Thông tin TH">Công nghệ Thông tin</option>
                            <option value="Công nghệ Kỹ thuật Nhiệt (Cơ điện lạnh) DL">Công nghệ Kỹ Thuật Nhiệt (Cơ điện lạnh)</option>
                            <option value="Công nghệ Kỹ thuật Cơ điện tử CDT">Công nghệ Kỹ thuật Cơ điện tử</option>
                            <option value="Công nghệ Kỹ thuật Điều khiển và Tự động hóa TDH">Công nghệ Kỹ thuật Điều khiển và Tự động hóa</option>
                            <option value="Công nghệ Kỹ thuật Điện tử - Viễn thông DVVT">Công nghệ Kỹ thuật Điện tử - Viễn thông</option>
                            <option value="Kế toán doanh nghiệp (Kế toán tin học) KT">Kế toán doanh nghiệp (Kế toán tin học)</option>
                            <option value="Cơ khí chế tạo (Cắt gọt kim loại) CKCT">Cơ khí chế tạo (Cắt gọt kim loại)</option>
                            <option value="Sửa chữa cơ khí (Nguội sửa chữa máy công cụ) SCCK">Sửa chữa cơ khí (Nguội sửa chữa máy công cụ)</option>
                            <option value="Hàn (Công nghệ cao) HCN">Hàn (Công nghệ cao)</option>
                            <option value="Kỹ thuật máy lạnh và điều hòa không khí KTML">Kỹ thuật máy lạnh và điều hòa không khí</option>
                            <option value="Bảo trì, sửa chữa Ô tô (Công nghệ Ô tô) CNOT">Bảo trì, sửa chữa Ô tô (Công nghệ Ô tô)</option>
                            <option value="Điện Công nghiệp DCN">Điện Công nghiệp</option>
                            <option value="Điện tử công nghiệp DTCN">Điện tử công nghiệp</option>
                            <option value="Quản trị mạng máy tính MMT">Quản trị mạng máy tính</option>
                            <option value="Kỹ thuật sửa chữa, lắp ráp máy tính SCMT">Kỹ thuật sửa chữa, lắp ráp máy tính</option>
                            </select>
                        </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label >Loại:</label>
                            <select id="loai" name="loai"class="form-control">
                            <option value="Cao đẳng">Cao đẳng</option>
                            <option value="Cao đẳng ngành">Cao đẳng ngành</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label >Người quản lý đợt</label>
                            <select id="nguoiQL" name="nguoiQl"class="form-control">
                            <option value="Lữ Cao Tiến">Lữ Cao Tiến</option>
                            <option value="Lữ Cao Tiến">Lữ Cao Tiến</option>
                            </select>
                        </div>
                        </div>
                        <div class="col-lg-6 col-md-offset-3">
                        <div class="form-group">
                            <label >Thời gian kết thúc</label>
                            <input class="form-control"id="endTime"name="endTime" type="date" placeholder="Chọn thời gian kết thúc" >
                        </div>
                        </div>
                        </div>
                        <div class="row">
                        <div class="col-md-offset text-center">
                            <button type="button" class="btn btn-primary btn-lg mt-3">Xác nhận</button>
                        </div>
                        </div>
                    </div>
                </form>
            </div>
        <div id="notification" style="display: none;"></div>
        <div id="containerDotThucTap" class="mt-3">
            <h2>Danh sách các đợt thực tập</h2>
            <div id="listDotThucTap" class="row">
        </div>
        <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    
                                </div>
                                <!-- /.panel-heading -->
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tên đợt</th>
                                                    <th>Năm</th>
                                                    <th>Ngành</th>
                                                    <th>Người quản lý</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr onclick="window.location='pages/canbo/chitietdot';" style="cursor: pointer;">
                                                    <td>1</td>
                                                    <td>CĐTH21K1</td>
                                                    <td>2021</td>
                                                    <td>Công nghệ thông tin</td>
                                                    <td>Lữ Cao Tiến</td>
                                                    
                                                </tr>
                                                <tr onclick="window.location='pages/canbo/chitietdot';" style="cursor: pointer;">
                                                    <td>2</td>
                                                    <td>CĐTH21K1</td>
                                                    <td>2021</td>
                                                    <td>Công nghệ thông tin</td>
                                                    <td>Lữ Cao Tiến</td>
                                                </tr>
                                                <tr onclick="window.location='pages/canbo/chitietdot';" style="cursor: pointer;">
                                                    <td>3</td>
                                                    <td>CĐTH21K1</td>
                                                    <td>2021</td>
                                                    <td>Công nghệ thông tin</td>
                                                    <td>Lữ Cao Tiến</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.table-responsive -->
                                </div>
                                <!-- /.panel-body -->
                            </div>
                            <!-- /.panel -->
                        </div>
                        <!-- /.col-lg-6 -->
                    </div>
</div>      </div>