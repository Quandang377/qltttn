<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$idDot = $_GET['id'];
$stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien WHERE ID_Dot = ? AND TrangThai = 1");
$stmt->execute([$idDot]);
$soSinhVien = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT TenDot FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $idDot]);
$dot = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT gv.ID_TaiKhoan)
    FROM GiaoVien gv
    JOIN SinhVien sv ON gv.ID_TaiKhoan = sv.ID_GVHD
    WHERE sv.ID_Dot = ? AND gv.TrangThai = 1
");
$stmt->execute([$idDot]);
$soGiaoVien = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM TongKet tk
    JOIN SinhVien sv ON sv.ID_TaiKhoan = tk.IDSV
    WHERE sv.ID_Dot = ? AND tk.TrangThai = 1
");
$stmt->execute([$idDot]);
$soHoanThanh = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM SinhVien sv
    WHERE sv.ID_Dot = ? 
    AND sv.TrangThai = 1 
    AND sv.ID_TaiKhoan NOT IN (
        SELECT IDSV FROM TongKet WHERE TrangThai = 1
    )
");
$stmt->execute([$idDot]);
$soChuaHoanThanh = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COALESCE(sv.XepLoai, 'Chưa có') AS XepLoai, COUNT(*) AS SoLuong
    FROM SinhVien sv
    WHERE sv.ID_Dot = ? AND sv.TrangThai = 1
    GROUP BY COALESCE(sv.XepLoai, 'Chưa có')
");
$stmt->execute([$idDot]);
$dsXepLoai = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data = [];
foreach ($dsXepLoai as $row) {
    $labels[] = $row['XepLoai'];
    $data[] = $row['SoLuong'];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thống kê</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@0.7.0"></script>

    <style>
        .chart-container {
            width: 500px;
            margin: 0 auto;
        }

        =
    </style>
</head>

<body>
    <div id="wrapper">
        <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
        ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Thống Kê Đợt <?= htmlspecialchars($dot['TenDot']) ?></h1>
                    </div>
                </div>
                <div class="row panel-row">
                    <div class="col-md-3 panel-container text-center">
                        <a href="pages/canbo/quanlydanhsachsinhvien" style="text-decoration: none; color: inherit;">
                            <div class="panel panel-default">
                                <div class="panel-heading">Tổng số sinh viên</div>
                                <div class="panel-body">
                                    <?= $soSinhVien ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 panel-container text-center">
                        <a href="pages/canbo/" style="text-decoration: none; color: inherit;">
                            <div class="panel panel-default">
                                <div class="panel-heading">Giáo viên hướng dẫn</div>
                                <div class="panel-body">
                                    <?= $soGiaoVien ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 panel-container text-center">
                        <div class="panel panel-default">
                            <div class="panel-heading">Hoàn thành</div>
                            <div class="panel-body">
                                <?= $soHoanThanh ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 panel-container text-center">
                        <div class="panel panel-default">
                            <div class="panel-heading">Chưa hoàn thành</div>
                            <div class="panel-body">
                                <?= $soChuaHoanThanh ?>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 style="text-align: center;">Biểu đồ xếp loại sinh viên</h3>
                <div class="chart-container">
                    <canvas id="myChart" style="width:100%;max-width:600px"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php
    require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
        ?>
    <script>
        const xValues = <?= json_encode($labels) ?>;
        const yValues = <?= json_encode($data) ?>;
        const barColors = [
            "#b91d47",
            "#00aba9",
            "#2b5797",
            "#e8c3b9",
            "#1e7145"
        ];

        new Chart("myChart", {
            type: "pie",
            data: {
                labels: xValues,
                datasets: [{
                    backgroundColor: barColors,
                    data: yValues
                }]
            },
            options: {
                plugins: {
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: (value, context) => {
                            return value; // hoặc `${value} SV`
                        }
                    },
                    legend: {
                        position: 'right'
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    </script>
</body>

</html>