<?php declare(strict_types=1);
$pageTitle = "Inventory (API)";
require __DIR__ . "/../layout.php";
require __DIR__ . "/../layout/header.php";
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Items</h1>
        <p class="text-sm text-zinc-400 mt-1"><span id="total-items">0</span> total item<span id="item-plural">s</span></p>
    </div>
    <button id="btn-create" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
        + Add item
    </button>
</div>

<!-- Search -->
<div class="mb-5">
    <div class="relative">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500 pointer-events-none"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <input
            type="search"
            id="search-input"
            placeholder="Search items…"
            autocomplete="off"
            class="w-full bg-zinc-900 border border-zinc-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-100
                   placeholder-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
        >
    </div>
</div>

<!-- Loading state -->
<div id="loading" class="text-center py-8 text-zinc-500">
    <svg class="w-6 h-6 animate-spin mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
</div>

<!-- Table -->
<div id="table-wrapper" class="rounded-2xl border border-zinc-800 overflow-hidden bg-zinc-900">
    <!-- Content loaded via JS -->
</div>

<!-- Pagination -->
<div id="pagination" class="mt-5 flex items-center justify-between text-xs text-zinc-500">
    <!-- Content loaded via JS -->
</div>

<!-- Error message -->
<div id="error-toast" class="hidden fixed bottom-4 right-4 bg-red-900/80 text-red-100 px-4 py-3 rounded-lg text-sm border border-red-800 animate-pulse"></div>

<!-- Success message -->
<div id="success-toast" class="hidden fixed bottom-4 right-4 bg-green-900/80 text-green-100 px-4 py-3 rounded-lg text-sm border border-green-800"></div>

<!-- Modal: Create/Edit Item -->
<div id="modal-form" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-zinc-950 rounded-2xl border border-zinc-800 p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h2 id="modal-title" class="text-lg font-semibold">Add item</h2>
            <button id="modal-close" class="text-zinc-500 hover:text-zinc-300 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="form-item" class="space-y-4">
            <input type="hidden" id="form-id" value="">

            <div>
                <label for="form-item-name" class="block text-xs font-medium text-zinc-400 mb-1.5">Item name</label>
                <input
                    type="text"
                    id="form-item-name"
                    placeholder="e.g. Wireless Mouse"
                    class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
                >
                <span id="error-item_name" class="text-xs text-red-400 mt-1 block hidden"></span>
            </div>

            <div>
                <label for="form-quantity" class="block text-xs font-medium text-zinc-400 mb-1.5">Quantity</label>
                <input
                    type="number"
                    id="form-quantity"
                    placeholder="0"
                    min="0"
                    step="any"
                    class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
                >
                <span id="error-quantity" class="text-xs text-red-400 mt-1 block hidden"></span>
            </div>

            <div>
                <label for="form-price" class="block text-xs font-medium text-zinc-400 mb-1.5">Price (Rp)</label>
                <input
                    type="number"
                    id="form-price"
                    placeholder="0"
                    min="0"
                    step="any"
                    class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
                >
                <span id="error-price" class="text-xs text-red-400 mt-1 block hidden"></span>
            </div>

            <div>
                <label for="form-date" class="block text-xs font-medium text-zinc-400 mb-1.5">Entry date</label>
                <input
                    type="date"
                    id="form-date"
                    class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
                >
                <span id="error-entry_date" class="text-xs text-red-400 mt-1 block hidden"></span>
            </div>

            <fieldset class="border border-zinc-800 rounded-xl p-4">
                <legend class="text-sm font-medium text-zinc-300 px-2">Image</legend>
                <div class="mt-3">
                    <label for="form-image" class="block text-xs font-medium text-zinc-400 mb-2">Select image</label>
                    <input
                        type="file"
                        id="form-image"
                        accept="image/*"
                        class="block w-full text-sm text-zinc-400
                               file:mr-4 file:py-2.5 file:px-4
                               file:rounded-xl file:border-0
                               file:text-sm file:font-medium
                               file:bg-zinc-800 file:text-zinc-200
                               file:cursor-pointer
                               hover:file:bg-zinc-700 file:transition-colors"
                    >
                    <p class="mt-2 text-xs text-zinc-500">
                        Optional. JPG, PNG, WebP, or GIF — max 2 MB
                    </p>
                    <span id="error-image" class="text-xs text-red-400 mt-1 block hidden"></span>
                </div>
            </fieldset>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" id="form-submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
                    Save item
                </button>
                <button type="button" id="form-cancel" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const API_BASE = '<?= BASE_URL ?>/api';
let currentPage = 1;
let currentSearch = '';
let itemData = {};

// Show toast
function showToast(message, type = 'success') {
    const id = type === 'success' ? 'success-toast' : 'error-toast';
    const toast = document.getElementById(id);
    toast.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

// Load items
async function loadItems(page = 1, search = '') {
    try {
        document.getElementById('loading').style.display = 'block';
        document.getElementById('table-wrapper').innerHTML = '';

        const params = new URLSearchParams({ page, search });
        const res = await fetch(`${API_BASE}/items?${params}`);

        if (!res.ok) throw new Error('Failed to load items');

        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        const { items, pagination } = json.data;
        itemData = { items, pagination, search };
        currentPage = pagination.current_page;
        currentSearch = search;

        renderTable(items);
        renderPagination(pagination, search);
        document.getElementById('total-items').textContent = pagination.total;
        document.getElementById('item-plural').textContent = pagination.total !== 1 ? 's' : '';

        document.getElementById('loading').style.display = 'none';
    } catch (err) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('table-wrapper').innerHTML = `<div class="py-8 text-center text-red-400 text-sm">Error loading items</div>`;
        showToast(err.message, 'error');
    }
}

// Render table
function renderTable(items) {
    if (items.length === 0) {
        document.getElementById('table-wrapper').innerHTML = `<div class="py-20 text-center text-zinc-500 text-sm">No items found</div>`;
        return;
    }

    const rows = items.map(item => `
        <tr class="border-b border-zinc-800 hover:bg-zinc-800/40 transition-colors duration-150">
            <td class="px-5 py-4 font-mono text-xs text-zinc-600">${item.id}</td>
            <td class="px-5 py-4 font-medium text-zinc-100">${escapeHtml(item.item_name)}</td>
            <td class="px-5 py-4 text-right font-mono text-zinc-300">${Number(item.quantity).toLocaleString()}</td>
            <td class="px-5 py-4 text-right font-mono text-zinc-300">Rp ${Number(item.price).toLocaleString()}</td>
            <td class="px-5 py-4 text-zinc-400">${new Date(item.entry_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
            <td class="px-5 py-4">
                ${item.image_path ? `<img src="${item.image_path}" alt="${escapeHtml(item.item_name)}" class="w-12 h-12 object-cover rounded-lg" loading="lazy">` : '<span class="text-zinc-600">—</span>'}
            </td>
            <td class="px-5 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${item.low_stock ? 'bg-amber-950/80 text-amber-400 border border-amber-800/50' : 'bg-brand-900/40 text-brand-600 border border-brand-700/30'}">
                    <span class="w-1.5 h-1.5 rounded-full ${item.low_stock ? 'bg-amber-400' : 'bg-brand-600'}"></span>
                    ${item.low_stock ? 'Low stock' : 'OK'}
                </span>
            </td>
            <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                    <button onclick="editItem(${item.id})" class="text-xs text-zinc-400 hover:text-zinc-100 transition-colors">Edit</button>
                    <button onclick="deleteItem(${item.id}, '${escapeHtml(item.item_name)}')" class="text-xs text-red-500 hover:text-red-400 transition-colors">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');

    document.getElementById('table-wrapper').innerHTML = `
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest w-12">#</th>
                    <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Item name</th>
                    <th class="text-right px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Quantity</th>
                    <th class="text-right px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Price</th>
                    <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Entry date</th>
                    <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Photo</th>
                    <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Status</th>
                    <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/70">${rows}</tbody>
        </table>
    `;
}

// Render pagination
function renderPagination(pagination, search) {
    const { current_page, total_pages, per_page, total } = pagination;
    if (total_pages <= 1) {
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    const start = (current_page - 1) * per_page + 1;
    const end = Math.min(current_page * per_page, total);

    let pages = '';
    for (let p = 1; p <= total_pages; p++) {
        const active = p === current_page;
        pages += `<button onclick="loadItems(${p}, '${search}')" class="px-3 py-1.5 rounded-lg font-mono transition-colors ${active ? 'bg-zinc-700 text-zinc-100' : 'hover:bg-zinc-800 text-zinc-500 hover:text-zinc-300'}">${p}</button>`;
    }

    document.getElementById('pagination').innerHTML = `
        <span>Showing ${start}–${end} of ${total} items</span>
        <div class="flex items-center gap-1">${pages}</div>
    `;
}

// Modal functions
function openModal(title = 'Add item', id = null) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('form-id').value = id || '';
    document.getElementById('form-item').reset();
    clearErrors();
    document.getElementById('modal-form').classList.remove('hidden');

    if (id) {
        const item = itemData.items.find(i => i.id === id);
        if (item) {
            document.getElementById('form-item-name').value = item.item_name;
            document.getElementById('form-quantity').value = item.quantity;
            document.getElementById('form-price').value = item.price;
            document.getElementById('form-date').value = item.entry_date;
        }
    }
}

function closeModal() {
    document.getElementById('modal-form').classList.add('hidden');
}

function clearErrors() {
    document.querySelectorAll('[id^="error-"]').forEach(el => el.classList.add('hidden'));
}

// Form submission
document.getElementById('form-item').addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('form-id').value;
    const formData = new FormData();
    formData.append('item_name', document.getElementById('form-item-name').value);
    formData.append('quantity', document.getElementById('form-quantity').value);
    formData.append('price', document.getElementById('form-price').value);
    formData.append('entry_date', document.getElementById('form-date').value);

    const imageFile = document.getElementById('form-image').files[0];
    if (imageFile) formData.append('image', imageFile);

    try {
        const url = id ? `${API_BASE}/items/update` : `${API_BASE}/items`;
        const method = id ? 'PUT' : 'POST';
        if (id) formData.append('id', id);

        const res = await fetch(url, { method, body: formData });
        const json = await res.json();

        if (!json.success) {
            if (json.errors) {
                clearErrors();
                Object.entries(json.errors).forEach(([field, msg]) => {
                    const el = document.getElementById(`error-${field}`);
                    if (el) {
                        el.textContent = msg;
                        el.classList.remove('hidden');
                    }
                });
                return;
            }
            throw new Error(json.message);
        }

        closeModal();
        showToast(json.message);
        loadItems(currentPage, currentSearch);
    } catch (err) {
        showToast(err.message, 'error');
    }
});

// Edit item
function editItem(id) {
    openModal('Edit item', id);
}

// Delete item
async function deleteItem(id, name) {
    if (!confirm(`Delete "${name}"?`)) return;

    try {
        const res = await fetch(`${API_BASE}/items/delete`, {
            method: 'DELETE',
            body: JSON.stringify({ id }),
            headers: { 'Content-Type': 'application/json' }
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        showToast(json.message);
        loadItems(currentPage, currentSearch);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// Event listeners
document.getElementById('btn-create').addEventListener('click', () => openModal());
document.getElementById('modal-close').addEventListener('click', closeModal);
document.getElementById('form-cancel').addEventListener('click', closeModal);

document.getElementById('search-input').addEventListener('input', (e) => {
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => loadItems(1, e.target.value), 300);
});

window.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modal-form')) closeModal();
});

// Escape key closes modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initial load
loadItems();
</script>

<?php require __DIR__ . "/../layout/footer.php"; ?>
