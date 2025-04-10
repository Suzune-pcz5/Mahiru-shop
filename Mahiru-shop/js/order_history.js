document.addEventListener('DOMContentLoaded', function () {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const fromDate = document.getElementById('from-date');
    const toDate = document.getElementById('to-date');
    const searchButton = document.getElementById('date-search-btn'); // Nút Search theo ngày
    let currentFilter = 'all';

    // Xử lý lọc theo trạng thái
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-status');
            filterOrders();
        });
    });

    // Xử lý lọc theo ngày
    function filterOrders() {
        const from = new Date(fromDate.value);
        const to = new Date(toDate.value);
        const tableRows = document.querySelectorAll('.order-table tbody tr');

        tableRows.forEach(row => {
            const status = row.querySelector('td:nth-child(4) .status-badge').textContent.trim().toLowerCase();
            const date = new Date(row.querySelector('td:nth-child(2)').textContent);

            // Kiểm tra trạng thái
            const matchesStatus = currentFilter === 'all' || status === currentFilter;

            // Kiểm tra ngày
            const matchesDate = !fromDate.value || !toDate.value || (date >= from && date <= to);

            // Hiển thị hoặc ẩn hàng
            if (matchesStatus && matchesDate) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Thêm sự kiện cho nút Search
    searchButton.addEventListener('click', filterOrders);

    // Áp dụng lọc ban đầu
    filterOrders();
});
