<style>
    /* Light mode */
    tr.new-order-row td {
        background-color: #fffbeb !important;
    }
    tr.new-order-row td:first-child {
        border-left: 4px solid #f59e0b !important;
    }

    /* Dark mode */
    .dark tr.new-order-row td {
        background-color: #0e1c0e !important;
    }
    .dark tr.new-order-row td:first-child {
        border-left: 4px solid #d97706 !important;
    }
</style>

<script>
    (function () {
        function highlightNewOrders() {
            const rows = document.querySelectorAll('table tbody tr');

            rows.forEach(function (row) {
                const dateCells = row.querySelectorAll('td');
                let matched = false;
                dateCells.forEach(function (cell) {
                    if (matched) return;
                    const text = cell.innerText || '';
                    if (/\d+\s+seconds?\s+ago/.test(text) || /\d+\s+minutes?\s+ago/.test(text) || /\bmoments?\s+ago\b/.test(text) || /\bjust\s+now\b/.test(text) || (/\d+\s+hours?\s+ago/.test(text) && parseInt(text.match(/(\d+)\s+hours?\s+ago/)?.[1]) < 24)) {
                        row.classList.add('new-order-row');
                        matched = true;
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', highlightNewOrders);
        document.addEventListener('livewire:navigated', highlightNewOrders);
        document.addEventListener('livewire:update', highlightNewOrders);
        setTimeout(highlightNewOrders, 500);
        setTimeout(highlightNewOrders, 1500);
    })();
</script>
