# Hướng dẫn sử dụng chức năng quản lý giấy giới thiệu mới

## Các chức năng đã được thêm:

### 1. Cấu trúc database mới
- **id_nguoinhan**: ID sinh viên đại diện nhận giấy
- **ngay_nhan**: Thời gian nhận giấy
- **ghi_chu**: Ghi chú khi nhận giấy
- **TrangThai mở rộng**:
  - 0: Chưa duyệt
  - 1: Đã duyệt
  - 2: Đã in
  - 3: Đã nhận

### 2. Chức năng in mới

#### In theo công ty (Gộp sinh viên) 🆕
- **File**: `print_grouped_letters.php`
- **Chức năng**: Gộp tất cả sinh viên cùng công ty vào một giấy giới thiệu
- **Template**: Sử dụng cùng mẫu in như giấy từng cái
- **Đặc điểm**:
  - Mỗi công ty một giấy riêng biệt
  - Danh sách sinh viên dưới dạng bảng
  - Có phần ký tên cho người đại diện
  - Hiển thị số lượng sinh viên (nổi bật nếu > 1)

#### In tất cả (Theo từng sinh viên)
- **File**: `print_letter_template.php` (sử dụng template có sẵn)
- **Chức năng**: Mở từng giấy riêng cho mỗi sinh viên
- **Template**: Giữ nguyên mẫu in hiện tại

### 3. Quản lý trạng thái mới

#### Tab "Đã in" (TrangThai = 2)
- Hiển thị các giấy đã được in
- Có nút "Ghi nhận" để ghi lại thông tin khi sinh viên đến nhận

#### Tab "Đã nhận" (TrangThai = 3)  
- Hiển thị các giấy đã được nhận
- Hiển thị thông tin người nhận và thời gian nhận
- Trạng thái cuối cùng, không thể thay đổi

### 4. Modal ghi nhận thông tin

Khi nhấn "Ghi nhận" trên tab "Đã in":
- **Thông tin công ty**: Tự động hiển thị
- **Danh sách sinh viên**: Hiển thị tất cả sinh viên cùng công ty đã in
- **Tìm kiếm sinh viên**: Filter theo MSSV hoặc tên
- **Chọn người đại diện**: Dropdown chọn sinh viên đại diện nhận giấy
- **Ghi chú**: Thêm ghi chú nếu cần

### 5. Quy trình sử dụng

1. **Duyệt giấy**: Giáo viên duyệt giấy (TrangThai: 0 → 1)
2. **In giấy**: 
   - **"In theo công ty"**: Gộp sinh viên, mở file `print_grouped_letters.php`
   - **"In tất cả"**: Mở từng file `print_letter_template.php` riêng
   - **"In"** (từng cái): Mở file `print_letter_template.php` cho 1 giấy
   - Hệ thống tự động chuyển trạng thái (TrangThai: 1 → 2)
3. **Ghi nhận**: Khi sinh viên đến nhận:
   - Vào tab "Đã in"
   - Nhấn "Ghi nhận" 
   - Chọn sinh viên đại diện nhận
   - Lưu thông tin (TrangThai: 2 → 3)

## Cài đặt:

### 1. Chạy script SQL
```sql
-- Chạy file update_giaygioithieu_structure.sql
```

### 2. Kiểm tra các file mới
- ✅ `print_grouped_letters.php` - Template in theo công ty
- ✅ `get_company_students.php` - API lấy danh sách sinh viên
- ✅ `save_receive_info.php` - API lưu thông tin ghi nhận
- ✅ `mark_as_printed.php` - API đánh dấu đã in

### 3. Test chức năng
- Tạo vài giấy giới thiệu test
- Thử quy trình: Duyệt → In → Ghi nhận

## So sánh 2 chế độ in:

### 📄 In tất cả (từng sinh viên)
- ✅ Mỗi sinh viên có giấy riêng
- ✅ Giữ nguyên template hiện tại
- ✅ Phù hợp khi cần giấy riêng biệt
- ⚠️ Nhiều tab browser khi in hàng loạt

### 📋 In theo công ty (gộp sinh viên)
- ✅ Tiết kiệm giấy, giảm số lượng document
- ✅ Dễ quản lý khi nhiều sinh viên cùng công ty
- ✅ Có bảng danh sách và chữ ký
- ✅ Có phần ghi thông tin người đại diện nhận
- ⚠️ Phù hợp khi có nhiều sinh viên cùng công ty

## Lưu ý quan trọng:

1. **Template in**: Sử dụng cùng format với mẫu có sẵn
2. **Auto-print**: Cả 2 chế độ đều tự động đánh dấu "Đã in"
3. **Ghi nhận**: Cập nhật tất cả giấy cùng công ty cùng lúc
4. **Foreign key**: Đã thiết lập tự động với bảng sinh viên
5. **Responsive**: Giao diện responsive trên mobile

## Các file đã tạo/sửa:

### Files mới:
- ✅ `print_grouped_letters.php` - Template in gộp theo công ty
- ✅ `get_company_students.php` - API danh sách sinh viên  
- ✅ `save_receive_info.php` - API lưu thông tin nhận
- ✅ `mark_as_printed.php` - API đánh dấu in

### Files đã sửa:
- ✅ `quanlygiaygioithieu.php` - Giao diện và logic chính
- ✅ `update_giaygioithieu_structure.sql` - Script database

### Files giữ nguyên:
- ✅ `print_letter_template.php` - Template in từng cái (không đổi)

Chúc bạn sử dụng thành công! 🎉

## Ghi chú phát triển:
- Template in gộp sử dụng cùng style với template cũ
- Tự động phát hiện số lượng sinh viên và highlight khi > 1
- Hỗ trợ đầy đủ thông tin đợt thực tập
- Print-friendly với CSS @media print
