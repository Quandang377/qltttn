<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>404 - Không tìm thấy trang</title>
     <?php
  require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
  ?>
    <style>
        body {
            background: #f4f6f8;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: "Segoe UI", sans-serif;
        }

        .notfound-container {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .notfound-container h1 {
            font-size: 100px;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .notfound-container h3 {
            font-size: 26px;
            color: #333;
            margin-bottom: 20px;
        }

        .notfound-container p {
            font-size: 16px;
            color: #777;
        }

        .btn-back {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="notfound-container">
        <h1>404</h1>
        <h3>Trang không tồn tại</h3>
        <p>Xin lỗi, chúng tôi không thể tìm thấy trang bạn yêu cầu.<br>
            Vui lòng kiểm tra lại đường dẫn hoặc quay lại trang chủ.</p>
        <a href="/datn" class="btn btn-primary btn-back">
            <i class="glyphicon glyphicon-home"></i> Về trang chủ
        </a>
    </div>
</body>

</html>
