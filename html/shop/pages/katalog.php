<!-- Main Layout: Content + Sidebar Cart -->
<div class="page-layout">
    <!-- Content Area -->
    <div class="content-area">
        <!-- Hero -->
        <?php
        $heroBg = $_settings['hero_bg'] ?? '';
        $heroGradient = $_settings['hero_gradient'] ?? '#fce4ec,#f8bbd0';
        $heroTextColor = $_settings['hero_text_color'] ?? '#c2185b';
        if ($heroBg && $heroBg !== 'gradient') {
            $heroStyle = "background:url('/assets/{$heroBg}') center/cover no-repeat;";
        } else {
            $heroStyle = "background:linear-gradient(135deg, {$heroGradient});";
        }
        $heroStyle .= "color:{$heroTextColor};";
        ?>
        <section class="hero" style="<?php echo $heroStyle; ?>">
            <div class="container">
                <h1 style="color:inherit;"><?php echo htmlspecialchars($_settings['hero_title'] ?? 'Bunga Segar untuk Setiap Momen'); ?></h1>
                <p style="color:inherit;opacity:0.85;"><?php echo htmlspecialchars($_settings['hero_subtitle'] ?? 'Pilih dari koleksi bunga terbaik kami, langsung dari kebun ke tangan Anda'); ?></p>
            </div>
        </section>

        <!-- Filter & Search -->
        <section class="container filter-section">
            <div class="filter-bar">
                <input type="text" id="search-input" placeholder="Cari bunga..." class="search-input">
                <div class="kategori-filter">
                    <button class="filter-btn active" data-kategori="">Semua</button>
                </div>
            </div>
        </section>

        <!-- Product Grid -->
        <section class="container">
            <div id="product-grid" class="product-grid">
                <div class="loading">Memuat produk...</div>
            </div>
            <div id="pagination" class="pagination"></div>
        </section>
    </div>

    <!-- Right Column: Cart + CTA -->
    <div class="right-column">
        <!-- Sidebar Keranjang -->
        <aside class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h3><img src="/assets/cart-icon.png" alt="" style="height:18px;vertical-align:middle;margin-right:4px;">Keranjang</h3>
            <span id="cart-count" class="cart-count">0 item</span>
            <button class="cart-close-btn" id="cart-close-btn" onclick="toggleCartMobile()">&times;</button>
        </div>
        <div class="cart-items" id="cart-items">
            <div class="cart-empty">
                <p>Keranjang kosong</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total</span>
                <span id="cart-total" class="cart-total-value">Rp 0</span>
            </div>
            <button class="btn-checkout" id="btn-checkout" disabled><img src="/assets/cart-icon.png" alt="" style="height:16px;vertical-align:middle;margin-right:4px;">Checkout</button>
        </div>
    </aside>

    <!-- Custom Order CTA (separate box) -->
    <div class="cta-box">
        <p>Butuh custom made bunga?</p>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $_settings['no_telp_toko'] ?? ''); ?>?text=Halo%2C%20saya%20mau%20order%20bunga%20custom" class="btn-whatsapp" target="_blank">
            <img src="/assets/wa-icon.png" alt="" onerror="this.style.display='none'" style="height:16px;vertical-align:middle;margin-right:4px;">Contact via WhatsApp
        </a>
    </div>
    </div><!-- end right-column -->
</div>

<!-- Mobile Cart Toggle Button -->
<button class="cart-fab" id="cart-fab" onclick="toggleCartMobile()">
    <img src="/assets/cart-icon.png" alt="" style="height:24px;"> <span id="cart-fab-count">0</span>
</button>
