// ==========================================
// POS Toko Bunga - Frontend JS
// ==========================================

const API_URL = '/api'; // path relatif dari root web server

// State
let cart = JSON.parse(localStorage.getItem('cart') || '[]');
let currentPage = 1;
let currentKategori = '';
let searchTimeout = null;

// ==========================================
// INIT
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    loadKategori();
    loadProduk();
    updateCartBadge();
    
    // Search input debounce
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                loadProduk();
            }, 400);
        });
    }
});

// ==========================================
// LOAD KATEGORI
// ==========================================
async function loadKategori() {
    try {
        const res = await fetch(`${API_URL}/kategori.php`);
        const data = await res.json();
        
        if (data.success) {
            const filterContainer = document.querySelector('.kategori-filter');
            if (!filterContainer) return;
            
            let html = '<button class="filter-btn active" data-kategori="" onclick="filterKategori(this, \'\')">Semua</button>';
            
            data.data.forEach(kat => {
                html += `<button class="filter-btn" data-kategori="${kat.id}" onclick="filterKategori(this, '${kat.id}')">${kat.nama_kategori}</button>`;
            });
            
            filterContainer.innerHTML = html;
        }
    } catch (err) {
        console.log('Gagal load kategori:', err);
    }
}

// ==========================================
// LOAD PRODUK
// ==========================================
async function loadProduk() {
    const grid = document.getElementById('product-grid');
    if (!grid) return;
    
    grid.innerHTML = '<div class="loading">Memuat produk...</div>';
    
    const search = document.getElementById('search-input')?.value || '';
    
    let url = `${API_URL}/produk.php?page=${currentPage}&limit=20`;
    if (currentKategori) url += `&kategori=${currentKategori}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        
        if (data.success && data.data.length > 0) {
            grid.innerHTML = data.data.map(produk => renderProductCard(produk)).join('');
            renderPagination(data.pagination);
            loadRatings(); // Load star ratings
        } else {
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="emoji">-</div>
                    <p>Tidak ada produk ditemukan</p>
                </div>
            `;
            document.getElementById('pagination').innerHTML = '';
        }
    } catch (err) {
        grid.innerHTML = `
            <div class="empty-state">
                <div class="emoji"></div>
                <p>Gagal memuat produk. Pastikan server berjalan.</p>
            </div>
        `;
    }
}

// ==========================================
// RENDER PRODUCT CARD
// ==========================================
function renderProductCard(produk) {
    const harga = formatRupiah(produk.harga_jual);
    const stokClass = produk.stok <= 5 ? 'low' : '';
    const stokText = produk.stok <= 5 ? `Sisa ${produk.stok}` : `Stok: ${produk.stok}`;
    const emoji = getEmojiByKategori(produk.nama_kategori);
    
    // Foto: pakai img kalau ada, fallback ke emoji
    const fotoHTML = produk.foto
        ? `<div class="product-img" onclick="previewImage('/assets/produk/${produk.foto}', '${escapeStr(produk.nama_bunga)}')"><img src="/assets/produk/${produk.foto}" alt="${produk.nama_bunga}"></div>`
        : `<div class="product-img">${emoji}</div>`;
    
    return `
        <div class="product-card">
            ${fotoHTML}
            <div class="product-info">
                <p class="product-kategori">${produk.nama_kategori || ''}</p>
                <h3 class="product-nama">${produk.nama_bunga}</h3>
                <p class="product-desc">${produk.deskripsi || ''}</p>
                <div class="product-rating" id="rating-${produk.id}" data-produk-id="${produk.id}">
                    ${renderStars(0, produk.id)}
                    <span class="rating-text"></span>
                </div>
                <div class="product-bottom">
                    <span class="product-harga">${harga}</span>
                    <span class="product-stok ${stokClass}">${stokText}</span>
                </div>
                <button class="btn-cart" onclick="addToCart(${produk.id}, '${escapeStr(produk.nama_bunga)}', ${produk.harga_jual}, ${produk.stok})">
                    + Keranjang
                </button>
            </div>
        </div>
    `;
}

// ==========================================
// FILTER KATEGORI
// ==========================================
function filterKategori(btn, kategoriId) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentKategori = kategoriId;
    currentPage = 1;
    loadProduk();
}

// ==========================================
// PAGINATION
// ==========================================
function renderPagination(pagination) {
    const container = document.getElementById('pagination');
    if (!container || pagination.total_pages <= 1) {
        if (container) container.innerHTML = '';
        return;
    }
    
    let html = '';
    for (let i = 1; i <= pagination.total_pages; i++) {
        const activeClass = i === pagination.page ? 'active' : '';
        html += `<button class="page-btn ${activeClass}" onclick="goToPage(${i})">${i}</button>`;
    }
    container.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadProduk();
    window.scrollTo({ top: 300, behavior: 'smooth' });
}

// ==========================================
// CART
// ==========================================
function addToCart(id, nama, harga, stokMax) {
    const existing = cart.find(item => item.id === id);
    
    if (existing) {
        if (existing.qty >= stokMax) {
            alert('Stok tidak mencukupi!');
            return;
        }
        existing.qty++;
    } else {
        cart.push({ id, nama, harga, qty: 1, stokMax });
    }
    
    saveCart();
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    saveCart();
    renderCart();
}

function updateQty(id, delta) {
    const item = cart.find(item => item.id === id);
    if (!item) return;
    
    item.qty += delta;
    
    if (item.qty <= 0) {
        removeFromCart(id);
        return;
    }
    
    if (item.qty > item.stokMax) {
        item.qty = item.stokMax;
        alert('Stok tidak mencukupi!');
    }
    
    saveCart();
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cart-items');
    const countEl = document.getElementById('cart-count');
    const totalEl = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('btn-checkout');
    const fabCount = document.getElementById('cart-fab-count');
    
    if (!container) return;
    
    const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
    const totalHarga = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    // Update count
    if (countEl) countEl.textContent = `${totalItems} item`;
    if (fabCount) fabCount.textContent = totalItems;
    
    // Update total
    if (totalEl) totalEl.textContent = formatRupiah(totalHarga);
    
    // Enable/disable checkout
    if (checkoutBtn) {
        checkoutBtn.disabled = cart.length === 0;
        checkoutBtn.onclick = () => {
            window.location.href = '/shop/?page=checkout';
        };
    }
    
    // Render items
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="cart-empty">
                <span></span>
                <p>Keranjang kosong</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.nama}</div>
                <div class="cart-item-price">${formatRupiah(item.harga)}</div>
            </div>
            <div class="cart-item-qty">
                <button onclick="updateQty(${item.id}, -1)">−</button>
                <span>${item.qty}</span>
                <button onclick="updateQty(${item.id}, 1)">+</button>
            </div>
            <div class="cart-item-subtotal">${formatRupiah(item.harga * item.qty)}</div>
        </div>
    `).join('');
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function updateCartBadge() {
    // Replaced by renderCart()
    renderCart();
}

// ==========================================
// HELPERS
// ==========================================
function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function escapeStr(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function getEmojiByKategori(kategori) {
    const map = {
        'Mawar': '-',
        'Lily': '-',
        'Tulip': '-',
        'Buket': '-',
        'Anggrek': '-',
        'Aksesoris': '-'
    };
    return map[kategori] || '';
}

// ==========================================
// IMAGE PREVIEW (LIGHTBOX)
// ==========================================
function previewImage(src, title) {
    // Buat overlay
    const overlay = document.createElement('div');
    overlay.className = 'lightbox-overlay';
    overlay.innerHTML = `
        <div class="lightbox-content">
            <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
            <img src="${src}" alt="${title}">
            <p class="lightbox-title">${title}</p>
        </div>
    `;
    
    // Tutup kalau klik di luar gambar
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeLightbox();
    });
    
    // Tutup dengan Escape
    document.addEventListener('keydown', handleEscKey);
    
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    
    // Animate in
    requestAnimationFrame(() => overlay.classList.add('active'));
}

function closeLightbox() {
    const overlay = document.querySelector('.lightbox-overlay');
    if (overlay) {
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.remove();
            document.body.style.overflow = '';
        }, 200);
    }
    document.removeEventListener('keydown', handleEscKey);
}

function handleEscKey(e) {
    if (e.key === 'Escape') closeLightbox();
}

// ==========================================
// RATING (STARS)
// ==========================================
function renderStars(rating, produkId) {
    let html = '';
    for (let i = 1; i <= 5; i++) {
        const filled = i <= rating ? 'filled' : '';
        html += `<span class="star ${filled}" data-star="${i}" onmouseover="hoverStar(${produkId}, ${i})" onmouseout="unhoverStar(${produkId})" onclick="submitRating(${produkId}, ${i})">★</span>`;
    }
    return html;
}

function hoverStar(produkId, star) {
    const container = document.getElementById(`rating-${produkId}`);
    if (!container) return;
    const stars = container.querySelectorAll('.star');
    stars.forEach((s, i) => {
        s.classList.toggle('hover', i < star);
    });
}

function unhoverStar(produkId) {
    const container = document.getElementById(`rating-${produkId}`);
    if (!container) return;
    const stars = container.querySelectorAll('.star');
    stars.forEach(s => s.classList.remove('hover'));
}

async function submitRating(produkId, rating) {
    try {
        const res = await fetch(`${API_URL}/rating.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ produk_id: produkId, rating: rating })
        });
        const data = await res.json();
        
        if (data.success) {
            updateStarDisplay(produkId, data.data.avg_rating, data.data.total_rating, data.data.user_rating);
        } else {
            alert(data.message || 'Gagal memberi rating. Silakan login terlebih dahulu.');
        }
    } catch (err) {
        alert('Silakan login untuk memberi rating.');
    }
}

function updateStarDisplay(produkId, avgRating, totalRating, userRating) {
    const container = document.getElementById(`rating-${produkId}`);
    if (!container) return;
    
    const stars = container.querySelectorAll('.star');
    stars.forEach((s, i) => {
        s.classList.toggle('filled', i < Math.round(avgRating));
    });
    
    const text = container.querySelector('.rating-text');
    if (text) {
        text.textContent = `${avgRating} (${totalRating})`;
    }
}

async function loadRatings() {
    try {
        const res = await fetch(`${API_URL}/rating.php`);
        const data = await res.json();
        if (data.success && data.data) {
            Object.keys(data.data).forEach(produkId => {
                const r = data.data[produkId];
                updateStarDisplay(produkId, r.avg_rating, r.total_rating, 0);
            });
        }
    } catch (err) {
        // silent fail
    }
}

// ==========================================
// MOBILE CART TOGGLE
// ==========================================
function toggleCartMobile() {
    const sidebar = document.getElementById('cart-sidebar');
    const isOpen = sidebar.classList.contains('open');
    
    if (isOpen) {
        sidebar.classList.remove('open');
        // Remove overlay
        const overlay = document.querySelector('.cart-overlay');
        if (overlay) overlay.remove();
    } else {
        sidebar.classList.add('open');
        // Add overlay
        const overlay = document.createElement('div');
        overlay.className = 'cart-overlay';
        overlay.onclick = () => toggleCartMobile();
        document.body.appendChild(overlay);
    }
}
