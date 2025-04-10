document.addEventListener('DOMContentLoaded', function() {
    const cartTable = document.querySelector('.cart-table');
    const subtotalElement = document.getElementById('subtotal');
    const totalElement = document.getElementById('total');
    const shipping = parseFloat(cartTable.dataset.shipping); // Lấy giá trị shipping từ data attribute

    // Hàm tính lại tổng tiền
    function updateTotals() {
        let subtotal = 0; // Đổi tên biến để rõ ràng hơn
        cartTable.querySelectorAll('tbody tr').forEach(row => {
            const price = parseFloat(row.querySelector('.price').dataset.price);
            const quantity = parseInt(row.querySelector('.quantity-input').value);
            const productTotal = price * quantity; // Tổng tiền của từng sản phẩm
            row.querySelector('.subtotal').textContent = `$${productTotal.toFixed(2)}`;
            subtotal += productTotal; // Cộng vào subtotal
        });
        subtotalElement.textContent = `$${subtotal.toFixed(2)}`; // Cập nhật subtotal
        totalElement.textContent = `$${(subtotal + shipping).toFixed(2)}`; // Cập nhật total
    }

    // Xử lý nút tăng số lượng
    cartTable.addEventListener('click', function(e) {
        if (e.target.classList.contains('increase-btn')) {
            const row = e.target.closest('tr');
            const input = row.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            if (quantity < 10) {
                quantity++;
                input.value = quantity;
                updateTotals(); // Cập nhật tổng tiền ngay lập tức
            }
        }
    });

    // Xử lý nút giảm số lượng
    cartTable.addEventListener('click', function(e) {
        if (e.target.classList.contains('decrease-btn')) {
            const row = e.target.closest('tr');
            const input = row.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            if (quantity > 1) {
                quantity--;
                input.value = quantity;
                updateTotals(); // Cập nhật tổng tiền ngay lập tức
            }
        }
    });
});