/**
 * Gọi API VietQR để lấy thông tin doanh nghiệp theo mã số thuế
 * @param {string} taxCode - Mã số thuế doanh nghiệp
 * @returns {Promise<Object>} - Trả về Promise chứa dữ liệu doanh nghiệp hoặc null nếu lỗi
 */
async function getBusinessInfoByTaxCode(taxCode) {
    try {
        const response = await fetch(`https://api.vietqr.io/v2/business/${taxCode}`);
        const data = await response.json();
        if (data && data.data) {
            return data.data;
        }
        return null;
    } catch (error) {
        console.error('Lỗi khi gọi API:', error);
        return null;
    }
}

// Ví dụ sử dụng:
// getBusinessInfoByTaxCode('123456789').then(info =