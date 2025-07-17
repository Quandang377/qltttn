<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id_taikhoan = $_SESSION['user_id'] ?? null;
$thongbaoHienTai = null;

$idEdit = $_GET['id'] ?? null;
if ($idEdit) {
  $stmt = $conn->prepare("SELECT * FROM thongbao WHERE ID = :id LIMIT 1");
  $stmt->execute(['id' => $idEdit]);
  $thongbaoHienTai = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$thongbaoHienTai) die("Không tìm thấy thông báo!");
}

$stmt = $conn->prepare("SELECT ID, TenDot FROM dotthuctap WHERE TrangThai >= 0 ORDER BY ID DESC");
$stmt->execute();
$dsDot = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nếu POST thì xử lý tạo hoặc cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tieude = $_POST['tieude'] ?? '';
  $noidung = $_POST['noidung'] ?? '';
  $id_dot = $_POST['id_dot'] ?? null;
  $id_sua = $_POST['id_sua'] ?? null;

  $validDot = false;
  foreach ($dsDot as $dot) {
    if ($dot['ID'] == $id_dot) {
      $validDot = true;
      break;
    }
  }
  if (!$validDot) die("Đợt thực tập không hợp lệ!");

  if ($id_sua) {
    $stmt = $conn->prepare("UPDATE thongbao SET TIEUDE = :tieude, NOIDUNG = :noidung, ID_Dot = :id_dot WHERE ID = :id_sua");
    $stmt->execute([
      'tieude' => $tieude,
      'noidung' => $noidung,
      'id_dot' => $id_dot,
      'id_sua' => $id_sua
    ]);
  } else {
    $stmt = $conn->prepare("INSERT INTO thongbao (TIEUDE, NOIDUNG, NGAYDANG, TRANGTHAI, ID_Dot, ID_TaiKhoan) VALUES (:tieude, :noidung, NOW(), 1, :id_dot, :id_taikhoan)");
    $stmt->execute([
      'tieude' => $tieude,
      'noidung' => $noidung,
      'id_dot' => $id_dot,
      'id_taikhoan' => $id_taikhoan
    ]);
  }

  header("Location: quanlythongbao");
  exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Đăng thông báo</title>
  <?php
  require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
  ?>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,400;0,700;1,400;1,700&display=swap');

    @media print {
      body {
        margin: 0 !important;
      }
    }

    :root {
      --ck-content-font-family: 'Lato';
    }

    .main-container {
      font-family: var(--ck-content-font-family);
      width: fit-content;
      margin-left: auto;
      margin-right: auto;
    }

    .editor-container_classic-editor .editor-container__editor {
      width: 100%;
      /* max-width: 800px; */
      margin: 0 auto;
      box-sizing: border-box;
    }



    .editor_container__word-count .ck-word-count {
      color: var(--ck-color-text);
      display: flex;
      height: 20px;
      gap: var(--ck-spacing-small);
      justify-content: flex-end;
      font-size: var(--ck-font-size-base);
      line-height: var(--ck-line-height-base);
      font-family: var(--ck-font-face);
      padding: var(--ck-spacing-small) var(--ck-spacing-standard);
    }

    .editor-container_include-word-count.editor-container_classic-editor .editor_container__word-count {
      border: 1px solid var(--ck-color-base-border);
      border-radius: var(--ck-border-radius);
      border-top-left-radius: 0;
      border-top-right-radius: 0;
      border-top: none;
    }

    .editor-container_include-word-count.editor-container_classic-editor .editor-container__editor .ck-editor .ck-editor__editable {
      border-radius: 0;
    }

    .ck-editor__editable_inline {
      min-height: 250px;
    }

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
      #page-wrapper {
        padding: 15px;
      }

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

      .page-header h1 {
        font-size: 1.8rem;
      }
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
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .search-bar {
      background: white;
      border-radius: 4px;
      min-width: 170px;
      padding: 17px;
      margin-bottom: 25px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
      width: 100%;
      max-width: 100%;
    }

    .search-bar input {
      flex: 1;
      min-width: 250px;
      padding: 10px 16px;
      font-size: 14px;
      background: #f9fafb;
      transition: all 0.2s ease;
    }

    .search-bar input:focus {
      outline: none;
      border-color: #3b82f6;
      background: white;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
  </style>
  <link rel="stylesheet" href="/datn/access/css/style.css">
  <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/46.0.0/ckeditor5.css" crossorigin>
</head>

<body>
  <div id="wrapper">

    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php";
    ?>
    <div id="page-wrapper">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <h1 class="page-header">Đăng thông báo</h1>
          </div>
          <div class="col-md-12">
            <div class="form-container mt-4">
              <form id="Formthongbao" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id_sua" value="<?= $thongbaoHienTai['ID'] ?? '' ?>">

                <!-- Chọn đợt thực tập -->
                <div class="form-group mb-3">
                  <label for="id_dot"><strong>Chọn đợt thực tập</strong></label>
                  <select class="search-bar" name="id_dot" id="id_dot" required>
                    <option value="">-- Chọn đợt --</option>
                    <?php foreach ($dsDot as $dot): ?>
                      <option value="<?= $dot['ID'] ?>" <?= ($thongbaoHienTai && $thongbaoHienTai['ID_Dot'] == $dot['ID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dot['TenDot']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                </div>

                <!-- Tiêu đề -->
                <div class="form-group mb-3">
                  <label for="tieude"><strong>Tiêu đề</strong></label>
                  <input class="search-bar" id="tieude" name="tieude" type="text"
  value="<?= htmlspecialchars($thongbaoHienTai['TIEUDE'] ?? '') ?>" placeholder="Nhập tiêu đề" required>
                </div>

                <!-- Nội dung thông báo -->
                <div class="form-group mb-3">
                  <label><strong>Nội dung thông báo</strong></label>
                      <div
                      class="editor-container editor-container_classic-editor editor-container_include-style editor-container_include-block-toolbar editor-container_include-word-count editor-container_include-fullscreen"
                      id="editor-container">
                      <div class="editor-container__editor">
                        <textarea id="editor" name="noidung"></textarea>
                      </div>
                      <div class="editor_container__word-count" id="editor-word-count"></div>
                    </div>
                  </div>

                  <!-- Tải CKEditor từ CDN (UMD build) -->
                  <!--   <script src="https://cdn.ckeditor.com/ckeditor5/46.0.0/ckeditor5.umd.js"></script>-->

                  <!-- Tải cấu hình main.js -->
                  </div>


                <!-- Nút Đăng tải -->
                <div class="form-group text-center mt-4">
                  <button type="submit" class="btn btn-primary btn-lg">
                    <?= isset($thongbaoHienTai) ? 'Cập nhật' : 'Đăng tải' ?>
                  </button>
                </div>

              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
  <?php
  require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
</body>

</html>
<script src="https://cdn.ckeditor.com/ckeditor5/46.0.0/ckeditor5.umd.js"></script>

<script>
  const noiDungThongBao = <?= json_encode($thongbaoHienTai['NOIDUNG'] ?? '') ?>;

  window.addEventListener('load', () => {
    if (window.ClassicEditor) {
      ClassicEditor.create(document.querySelector('#editor'))
        .then(editor => {
          if (noiDungThongBao) {
            editor.setData(noiDungThongBao);
          }
        })
        .catch(error => console.error(error));
    } else {
      console.error("CKEditor chưa được tải!");
    }
  });
</script>

    