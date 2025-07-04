<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/datn');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Yêu cầu đăng nhập</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
Swal.fire({
    title: 'Yêu cầu đăng nhập',
    text: 'Bạn cần đăng nhập để truy cập trang này.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Đăng nhập',
    cancelButtonText: 'Hủy',
    allowOutsideClick: false
}).then((result) => {
    if (result.isConfirmed) {
        window.location.href = "<?= BASE_PATH ?>/login";
    } else {
        window.history.back();
    }
});
</script>
</body>
</html>
