<div class="lookup-modal" id="lookupModal" aria-hidden="true">
    <div class="lookup-dialog">
        <div class="lookup-head">
            <h2 style="margin:0" id="lookupTitle">Select</h2>
            <button type="button" class="btn secondary" id="lookupModalClose" data-no-loading>Close</button>
        </div>
        <div class="lookup-search">
            <input type="text" id="lookupModalSearch" placeholder="Search by name, SKU, or barcode..." autocomplete="off">
        </div>
        <div class="lookup-body">
            <table class="table lookup-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Info</th>
                    </tr>
                </thead>
                <tbody id="lookupModalRows"></tbody>
            </table>
        </div>
        <div class="lookup-foot">
            <button type="button" class="btn secondary" id="lookupModalPrev" data-no-loading>Prev</button>
            <span class="muted" id="lookupModalPage">Page 1 / 1</span>
            <button type="button" class="btn secondary" id="lookupModalNext" data-no-loading>Next</button>
        </div>
    </div>
</div>

<style>
    .lookup-modal { position: fixed; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center; background: rgba(15, 23, 42, .45); padding: 18px; }
    .lookup-modal.open { display: flex; }
    .lookup-dialog { width: min(780px, 100%); max-height: min(720px, 92vh); overflow: hidden; background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 22px 70px rgba(15, 23, 42, .28); display: grid; grid-template-rows: auto auto minmax(0, 1fr) auto; }
    .lookup-head, .lookup-foot { padding: 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .lookup-head { border-bottom: 1px solid #e2e8f0; }
    .lookup-foot { border-top: 1px solid #e2e8f0; }
    .lookup-search { padding: 12px 14px; }
    .lookup-search input { width: 100%; }
    .lookup-body { overflow: auto; padding: 0 14px 14px; }
    .lookup-table tr { cursor: pointer; }
    .lookup-table tr:hover td { background: #fff7ed; }
    .lookup-field { display: flex; gap: 6px; align-items: center; }
    .lookup-field input.lookup-display { flex: 1; cursor: pointer; background: #fff; }
    .lookup-field input.lookup-display:placeholder-shown { color: var(--muted); }
</style>

<script>
    (function () {
        const modal = document.getElementById('lookupModal');
        const title = document.getElementById('lookupTitle');
        const search = document.getElementById('lookupModalSearch');
        const rowsBody = document.getElementById('lookupModalRows');
        const pageText = document.getElementById('lookupModalPage');
        const prevBtn = document.getElementById('lookupModalPrev');
        const nextBtn = document.getElementById('lookupModalNext');
        const closeBtn = document.getElementById('lookupModalClose');
        let activeData = [];
        let activeCallback = null;
        let page = 1;
        const perPage = 15;

        window.openLookupModal = function (config) {
            title.textContent = config.title || 'Select';
            activeData = config.data;
            activeCallback = config.onSelect;
            page = 1;
            search.value = '';
            render();
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            search.focus();
        };

        function closeModal() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            activeCallback = null;
        }

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        search.addEventListener('input', function () {
            page = 1;
            render();
        });

        prevBtn.addEventListener('click', function () {
            if (page > 1) {
                page--;
                render();
            }
        });

        nextBtn.addEventListener('click', function () {
            const total = Math.ceil(filtered().length / perPage);
            if (page < total) {
                page++;
                render();
            }
        });

        function filtered() {
            const term = search.value.toLowerCase().trim();
            if (!term) return activeData;
            return activeData.filter(function (item) {
                return (item.search || item.label || '').toLowerCase().includes(term);
            });
        }

        function render() {
            const data = filtered();
            const totalPages = Math.max(1, Math.ceil(data.length / perPage));
            if (page > totalPages) page = totalPages;
            if (page < 1) page = 1;
            const start = (page - 1) * perPage;
            const paged = data.slice(start, start + perPage);

            rowsBody.innerHTML = paged.map(function (item) {
                return '<tr class="lookup-row" data-id="' + item.id + '" data-label="' + item.label.replace(/"/g, '&quot;') + '">' +
                    '<td>' + item.label + '</td>' +
                    '<td>' + (item.info || '') + '</td>' +
                    '</tr>';
            }).join('');

            pageText.textContent = 'Page ' + page + ' / ' + totalPages;

            rowsBody.querySelectorAll('.lookup-row').forEach(function (row) {
                row.addEventListener('click', function () {
                    if (activeCallback) activeCallback(row.dataset.id, row.dataset.label);
                    closeModal();
                });
            });
        }
    })();
</script>
