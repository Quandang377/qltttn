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
                        <h1 class="page-header">Thống Kê</h1>
                    </div>
                </div>
                <div class="row panel-row">
                    <div class="col-md-3 panel-container text-center">
                        <a href="pages/canbo/quanlycongty" style="text-decoration: none; color: inherit;">
                            <div class="panel panel-default">
                                <div class="panel-heading">Tổng số sinh viên</div>
                                <div class="panel-body">
                                    <h2>120</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 panel-container text-center">
                        <a href="pages/canbo/quanlycongty" style="text-decoration: none; color: inherit;">
                            <div class="panel panel-default">
                                <div class="panel-heading">Giáo viên hướng dẫn</div>
                                <div class="panel-body">
                                    <h2>12</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 panel-container text-center">
                        <a href="pages/canbo/quanlycongty" style="text-decoration: none; color: inherit;">
                            <div class="panel panel-default">
                                <div class="panel-heading">Hoàn thành</div>
                                <div class="panel-body">
                                    <h2>100</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 panel-container text-center">
                        <a href="pages/canbo/quanlycongty" style="text-decoration: none; color: inherit;">
                            <div class="panel panel-default">
                                <div class="panel-heading">Chưa hoàn thành</div>
                                <div class="panel-body">
                                    <h2>20</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <h3 style="text-align: center;">Biểu đồ xếp loại sinh viên</h3>
                <div class="chart-container">
                    <canvas id="myChart" style="width:100%;max-width:600px"></canvas>
                </div>
            </div>
        </div>
    </div>

     <script>
    const xValues = ["Xuất Xắc", "Giỏi", "Khá", "Trung Bình", "Yếu"];
    const yValues = [55, 49, 44, 24, 15];
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
