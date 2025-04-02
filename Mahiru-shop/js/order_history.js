document.addEventListener('DOMContentLoaded', function () {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const fromDate = document.getElementById('from-date');
    const toDate = document.getElementById('to-date');
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

            const matchesStatus = currentFilter === 'all' || status === currentFilter;
            const matchesDate = !fromDate.value || !toDate.value || (date >= from && date <= to);

            if (matchesStatus && matchesDate) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Thêm sự kiện cho input ngày
    fromDate.addEventListener('change', filterOrders);
    toDate.addEventListener('change', filterOrders);

    // Áp dụng lọc ban đầu
    filterOrders();
});
