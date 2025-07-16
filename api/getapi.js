/**
 * Gọi API VietQR để lấy thông tin doanh nghiệp theo mã số thuế
 * @param {string} taxCode - Mã số thuế doanh nghiệp
 * @returns {Promise<Object>} - Trả về Promise chứa dữ liệu doanh nghiệp hoặc null nếu lỗi
 */
async function getBusinessInfoByTaxCode(taxCode) {
    try {
        // Sử dụng proxy PHP để tránh CORS
        const proxyUrl = window.location.protocol + '//' + window.location.host + window.location.pathname.replace(/\/[^\/]*$/, '') + '/../../api/proxy.php';
        const response = await fetch(`${proxyUrl}?taxCode=${encodeURIComponent(taxCode)}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (data && data.data) {
            return data.data;
        }
        return null;
    } catch (error) {
        console.error('Lỗi khi gọi API qua proxy:', error);
        
        // Fallback: Thử gọi trực tiếp API với CORS proxy
        try {
            const corsProxy = 'https://cors-anywhere.herokuapp.com/';
            const targetUrl = `https://api.vietqr.io/v2/business/${taxCode}`;
            
            const response = await fetch(corsProxy + targetUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (data && data.data) {
                return data.data;
            }
            return null;
        } catch (fallbackError) {
            console.error('Lỗi fallback:', fallbackError);
            
            // Fallback cuối: Thử gọi trực tiếp (có thể bị CORS)
            try {
                const response = await fetch(`https://api.vietqr.io/v2/business/${taxCode}`);
                const data = await response.json();
                if (data && data.data) {
                    return data.data;
                }
                return null;
            } catch (directError) {
                console.error('Lỗi direct call:', directError);
                return null;
            }
        }
    }
}