<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idTaiKhoan = $_SESSION['user_id'] ?? null;

// Lấy danh sách đợt mà cán bộ này quản lý
$stmt = $conn->prepare("SELECT ID, TenDot FROM DotThucTap WHERE NguoiQuanLy = ?");
$stmt->execute([$idTaiKhoan]);
$dsDot = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách ID đợt
$dsDotID = array_column($dsDot, 'ID');

$thongbaos = [];

if (!empty($dsDotID)) {
  // Nếu lọc theo đợt cụ thể
  if (!empty($_GET['dot_filter'])) {
    $placeholders = '?';
    $params = [$_GET['dot_filter']];
  } else {
    // Nếu không, lấy theo tất cả các đợt quản lý
    $placeholders = implode(',', array_fill(0, count($dsDotID), '?'));
    $params = $dsDotID;
  }

  $stmt = $conn->prepare("
        SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.NGAYDANG, tb.ID_Dot,
            COALESCE(ad.Ten, cbk.Ten, tk.TaiKhoan) AS NguoiTao,
            dt.TenDot
        FROM THONGBAO tb
        LEFT JOIN admin ad ON tb.ID_TaiKhoan = ad.ID_TaiKhoan
        LEFT JOIN canbokhoa cbk ON tb.ID_TaiKhoan = cbk.ID_TaiKhoan
        LEFT JOIN TaiKhoan tk ON tb.ID_TaiKhoan = tk.ID_TaiKhoan
        LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
        WHERE tb.TRANGTHAI = 1 AND tb.ID_Dot IN ($placeholders)
        ORDER BY tb.NGAYDANG DESC
    ");
  $stmt->execute($params);
  $thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Xử lý xóa thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_thongbao_id'])) {
  $idThongBao = $_POST['xoa_thongbao_id'];
  $stmt = $conn->prepare("UPDATE ThongBao SET TrangThai = 0 WHERE ID = ?");
  $stmt->execute([$idThongBao]);
  $_SESSION['success'] = "Xoá thông báo thành công.";
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Quản lý thông báo</title>
  <?php
  require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
  ?>
  <style>
        /* === RESET & BASE === */
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
        }
        
        /* === LAYOUT === */
        #page-wrapper {
            padding: 20px;
            min-height: 100vh;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
            text-shadow: 0 2px 4px rgba(30, 64, 175, 0.1);
        }
        
        /* === FILTER BAR === */
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .status-toggle {
            display: flex;
            gap: 5px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            padding: 4px;
        }
        
        .status-btn {
            padding: 10px 16px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .status-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .status-btn.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }
        
        .status-btn .badge {
            background: #6b7280;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .status-btn.active .badge {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: #f9fafb;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* === CARDS GRID === */
        .cards-container {
            margin-bottom: 30px;
        }
        
        .status-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .letter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            opacity: 0;
            transform: translateY(10px);
        }
        
        .letter-card.fade-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .letter-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #3b82f6;
        }
        
        .letter-card.clickable:hover {
            background: #f8fafc;
            cursor: pointer;
        }
        
        .card-header {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        
        .company-address {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .student-info {
            color: #3b82f6;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-printed {
            background: #e0e7ff;
            color: #4f46e5;
        }
        
        .status-received {
            background: #f3e8ff;
            color: #7c3aed;
        }
        
        .received-note {
            background: #f8fafc;
            border-left: 3px solid #3b82f6;
            padding: 8px 12px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        
        /* === MODAL STYLES === */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            border: none;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            grid-column: 1 / -1;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #4b5563;
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            #page-wrapper { padding: 15px; }
            .filter-bar { 
                flex-direction: column; 
                align-items: stretch;
                gap: 15px;
            }
            .action-buttons { 
                margin-left: 0;
                justify-content: center;
            }
            .status-section { 
                grid-template-columns: 1fr; 
                gap: 15px;
            }
            .page-header h1 { font-size: 1.8rem; }
        }
        
        @media (max-width: 480px) {
            .status-toggle { 
                flex-direction: column; 
                gap: 5px;
            }
            .status-btn { 
                padding: 12px 16px; 
                text-align: center;
            }
            .letter-card {
                padding: 15px;
            }
        }
        

        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .page-header {
            color: #2c3e50;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
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
            <h1 class="page-header">Quản Lý Thông Báo</h1>
          </div>
        </div>
        <div class="row">
          <div class="col-md-10 ">
            <form method="get" class="form-inline" style="margin-bottom: 15px;">
              <label for="dot_filter">Lọc theo đợt: </label>
              <select name="dot_filter" id="dot_filter" class="form-control" onchange="this.form.submit()">
                <option value="">-- Tất cả --</option>
                <?php foreach ($dsDot as $dot): ?>
                  <option value="<?= $dot['ID'] ?>" <?= (isset($_GET['dot_filter']) && $_GET['dot_filter'] == $dot['ID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dot['TenDot']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
            </div>
            <div class="col-md-2" style="">
          <a href="pages/canbo/taothongbao" class="fixed-button btn btn-primary btn-lg">
            Tạo thông báo
          </a>
          </div>
          </div>
        <div class="row">

            <div class="panel panel-default">
              <div class="panel-heading">
                Danh sách thông báo đã tạo
              </div>
              <div class="panel-body">
                <div class="table-responsive">
                  <table class="table" id="TableDotTT">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Tiêu đề</th>
                        <th>Đợt</th>
                        <th>Người đăng</th>
                        <th>Thời gian đăng</th>
                        <th>Hành động</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i = 1;
                      foreach ($thongbaos as $thongbao): ?>
                        <?php $link = 'pages/canbo/chitietthongbao?id=' . urlencode($thongbao['ID']); ?>
                        <tr>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;"><?= $i++ ?></td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                            <?= htmlspecialchars($thongbao['TIEUDE']) ?>
                          </td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                            <?= htmlspecialchars($thongbao['TenDot'] ?? '') ?>
                          </td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                          <?= htmlspecialchars($thongbao['NguoiTao']) ?>

                          </td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                            <?= htmlspecialchars($thongbao['NGAYDANG']) ?>
                          </td>
                          <td>
                            <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thông báo này?');">
                              <input type="hidden" name="xoa_thongbao_id" value="<?= $thongbao['ID'] ?>">
                              <button type="submit" class="btn btn-danger btn-sm">Xoá</button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
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
      </div>

    </div>
  </div>
  <?php
  require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
  <script>
    $(document).ready(function () {
      var table = $('#TableDotTT').DataTable({
        responsive: true,
        pageLength: 20,
        language: {
          url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
        }
      });

    });
  </script>
</body>

</html>
<style>
  .noidung-rutgon {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
  }

  .ls-list {
    align-items: flex-start;
    margin-bottom: 20px;
  }

  .ls-img img {
    border-radius: 4px;
    border: 1px solid #ddd;
  }

  .img-content a:hover {
    color: #007bff;
  }
</style>