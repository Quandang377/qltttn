<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id_taikhoan = $_SESSION['user_id'] ?? null;

$stmt = $conn->prepare("SELECT ID, TenDot FROM dotthuctap WHERE TrangThai >= 0 ORDER BY ID DESC");
$stmt->execute();
$dsDot = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  file_put_contents('debug_post.log', print_r($_POST, true));

  $tieude = $_POST['tieude'] ?? '';
  $noidung = $_POST['noidung'] ?? '';
  $id_dot = $_POST['id_dot'] ?? null;

  // Kiểm tra id_dot có hợp lệ không
  $validDot = false;
  foreach ($dsDot as $dot) {
    if ($dot['ID'] == $id_dot) {
      $validDot = true;
      break;
    }
  }
  if (!$validDot) {
    die("Đợt thực tập không hợp lệ!");
  }

  $stmt = $conn->prepare("INSERT INTO thongbao (TIEUDE, NOIDUNG, NGAYDANG, TRANGTHAI, ID_Dot, ID_TaiKhoan) VALUES (:tieude, :noidung, NOW(),1, :id_dot, :id_taikhoan)");
  $stmt->execute([
    'tieude' => $tieude,
    'noidung' => $noidung,
    'id_dot' => $id_dot,
    'id_taikhoan' => $id_taikhoan,
  ]);

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
  </style>
</head>

<body>
  <div id="wrapper">

    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
    ?>
    <div id="page-wrapper">
      <div class="container-fluid">
        <div class="row mt-5">
          <div class="col-lg-12">
            <h1 class="page-header">Đăng thông báo</h1>
          </div>
          <div class="row">
            <div class="col-md-12 ">
              <div class="form-container" style="margin-top: 20px;">
                <form id="FormThongBao" method="post" enctype="multipart/form-data">
                  <div class="form-group">
                    <label>Chọn đợt thực tập</label>
                    <select class="form-control" name="id_dot" required>
                      <option value="">-- Chọn đợt --</option>
                      <?php foreach ($dsDot as $dot): ?>
                        <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Tiêu đề</label>
                    <input class="form-control" id="tieude" name="tieude" type="text" placeholder="Nhập tiêu đề"
                      required>
                  </div>
                  <div class="form-group">
                    <label>Nội dung thông báo</label>
                      <div
                        class=" editor-container editor-container_classic-editor editor-container_include-block-toolbar editor-container_include-word-count editor-container_include-fullscreen"
                        id="editor-container">
                        <div>
                        <div class="editor-container__editor">
                          <div id="editor" ></div>
                        </div>
                        <div class="editor_container__word-count" id="editor-word-count"></div>
                        </div>
                      </div>
                    <script type="importmap">
                        {
                          "imports": {
                            "ckeditor5": "/datn/access/ckeditor5/ckeditor5.js",
                            "ckeditor5/": "/datn/access/ckeditor5/"
                          }
                        }
                        </script>

                  </div>
                  <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary btn-lg mt-3">Đăng tải</button>
                  </div>
                </form>
              </div>
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
<script>
  const form = document.querySelector('#FormThongBao');

  form.addEventListener('submit', (e) => {
    const textarea = document.createElement('textarea');
    textarea.name = 'noidung';
    textarea.style.display = 'none';
    textarea.value = editor.getData();
    form.appendChild(textarea);
  });
</script>