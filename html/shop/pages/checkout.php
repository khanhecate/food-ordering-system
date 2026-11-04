<?php
$user = $_SESSION['user'];
$error = '';

// Fetch user data for prefill
$userStmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$userStmt->execute([':id' => $user['id']]);
$userData = $userStmt->fetch();

$prefillNama = $userData['nama_penerima_default'] ?: $userData['nama_lengkap'];
$prefillTelp = $userData['no_telp'] ?? '';
$prefillAlamat = $userData['alamat'] ?? '';

// Get pajak setting
$pajakPersen = (float)($_settings['pajak_persen'] ?? 0);

// Handle POST checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartData = json_decode($_POST['cart_data'] ?? '[]', true);
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $telp_penerima = trim($_POST['telp_penerima'] ?? '');
    $alamat = trim($_POST['alamat_pengiriman'] ?? '');
    $metode = $_POST['metode_bayar'] ?? 'creditcard';
    $card_number = $_POST['card_number'] ?? '';
    $skip_validation = isset($_POST['skip_card_validation']);
    $catatan = trim($_POST['catatan'] ?? '');
    
    if (empty($cartData)) {
        $error = 'Keranjang kosong!';
    } elseif (empty($nama_penerima) || empty($telp_penerima) || empty($alamat)) {
        $error = 'Lengkapi data pengiriman!';
    } elseif (!$skip_validation && $metode === 'creditcard' && strlen(str_replace(' ', '', $card_number)) < 16) {
        $error = 'Nomor kartu tidak valid!';
    } else {
        try {
            $db->beginTransaction();
            $today = date('Ymd');
            $countStmt = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal) = CURDATE()");
            $countStmt->execute();
            $seq = $countStmt->fetchColumn() + 1;
            $no_transaksi = "TRX-{$today}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
            
            $subtotal = 0;
            $items = [];
            foreach ($cartData as $item) {
                $stmt = $db->prepare("SELECT id, nama_bunga, harga_jual, stok FROM produk WHERE id = :id AND is_active = 1");
                $stmt->execute([':id' => $item['id']]);
                $produk = $stmt->fetch();
                if (!$produk) throw new Exception("Produk tidak ditemukan");
                if ($produk['stok'] < $item['qty']) throw new Exception("Stok {$produk['nama_bunga']} tidak cukup");
                $itemSubtotal = $produk['harga_jual'] * $item['qty'];
                $subtotal += $itemSubtotal;
                $items[] = ['produk_id' => $produk['id'], 'nama_produk' => $produk['nama_bunga'], 'harga_jual' => $produk['harga_jual'], 'qty' => $item['qty'], 'subtotal' => $itemSubtotal];
            }
            
            $total = $subtotal;
            $pajak_nominal = $subtotal * ($pajakPersen / 100);
            $total = $subtotal + $pajak_nominal;
            $payment_ref = 'PAY-' . strtoupper(substr(md5(time() . rand()), 0, 10));
            
            $db->prepare("INSERT INTO transaksi (no_transaksi, user_id, subtotal, pajak_persen, pajak_nominal, total, metode_bayar, jumlah_bayar, kembalian, catatan, status, nama_penerima, telp_penerima, alamat_pengiriman, payment_ref) VALUES (:no, :uid, :sub, :ppersen, :pnom, :total, :metode, :bayar, 0, :cat, 'paid', :nama, :telp, :alamat, :ref)")
               ->execute([':no'=>$no_transaksi, ':uid'=>$user['id'], ':sub'=>$subtotal, ':ppersen'=>$pajakPersen, ':pnom'=>$pajak_nominal, ':total'=>$total, ':metode'=>$metode, ':bayar'=>$total, ':cat'=>$catatan, ':nama'=>$nama_penerima, ':telp'=>$telp_penerima, ':alamat'=>$alamat, ':ref'=>$payment_ref]);
            $trxId = $db->lastInsertId();
            
            $detailStmt = $db->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, nama_produk, harga_jual, qty, subtotal) VALUES (:tid, :pid, :nama, :harga, :qty, :sub)");
            $stokStmt = $db->prepare("UPDATE produk SET stok = stok - :qty WHERE id = :id");
            foreach ($items as $item) {
                $detailStmt->execute([':tid'=>$trxId, ':pid'=>$item['produk_id'], ':nama'=>$item['nama_produk'], ':harga'=>$item['harga_jual'], ':qty'=>$item['qty'], ':sub'=>$item['subtotal']]);
                $stokStmt->execute([':qty'=>$item['qty'], ':id'=>$item['produk_id']]);
            }
            
            $db->commit();
            echo "<script>localStorage.removeItem('cart'); window.location.href='/shop/?page=tracking&success={$no_transaksi}';</script>";
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="container checkout-page">
    <h1>Checkout</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" id="checkout-form">
        <input type="hidden" name="cart_data" id="cart_data">
        
        <div class="checkout-form">
            <section class="checkout-section">
                <h3>Ringkasan Pesanan</h3>
                <div id="checkout-items" class="checkout-items">
                    <p class="empty-cart">Keranjang kosong. <a href="?page=katalog">Belanja dulu</a></p>
                </div>
                <div class="checkout-total" id="checkout-total"></div>
            </section>

            <section class="checkout-section">
                <h3>Data Pengiriman</h3>
                <div class="form-grid">
                    <div class="form-group"><label>Nama Penerima</label><input type="text" name="nama_penerima" required value="<?php echo htmlspecialchars($prefillNama); ?>"></div>
                    <div class="form-group"><label>No. Telepon</label><input type="tel" name="telp_penerima" required value="<?php echo htmlspecialchars($prefillTelp); ?>"></div>
                    <div class="form-group full-width"><label>Alamat Pengiriman</label><textarea name="alamat_pengiriman" rows="3" required><?php echo htmlspecialchars($prefillAlamat); ?></textarea></div>
                    <div class="form-group full-width"><label>Catatan (opsional)</label><textarea name="catatan" rows="2" placeholder="Misal: kirim sore hari"></textarea></div>
                </div>
            </section>

            <section class="checkout-section">
                <h3>Pembayaran</h3>
                <div class="payment-methods">
                    <label class="payment-option selected"><input type="radio" name="metode_bayar" value="creditcard" checked onchange="togglePayment(this.value)"><span class="payment-label">Credit Card</span></label>
                    <label class="payment-option"><input type="radio" name="metode_bayar" value="qris" onchange="togglePayment(this.value)"><span class="payment-label">QRIS</span></label>
                    <label class="payment-option"><input type="radio" name="metode_bayar" value="transfer" onchange="togglePayment(this.value)"><span class="payment-label">Transfer Bank</span></label>
                </div>

                <div id="cc-form" class="cc-form">
                    <div class="skip-validation">
                        <label class="toggle-label"><input type="checkbox" name="skip_card_validation" checked><span class="toggle-switch"></span><span>Skip validasi kartu (testing mode)</span></label>
                    </div>
                    <div class="cc-card">
                        <div class="cc-card-inner">
                            <div class="cc-chip">CARD</div>
                            <div class="cc-number" id="cc-display">4242 4242 4242 4242</div>
                            <div class="cc-bottom"><div><small>NAMA</small><div id="cc-name-display">TEST USER</div></div><div><small>EXP</small><div id="cc-exp-display">12/28</div></div></div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group full-width"><label>Nomor Kartu</label><input type="text" name="card_number" id="card_number" value="4242 4242 4242 4242" maxlength="19" oninput="formatCard(this)"></div>
                        <div class="form-group"><label>Nama di Kartu</label><input type="text" name="card_name" value="TEST USER" oninput="document.getElementById('cc-name-display').textContent=this.value||'CARDHOLDER'"></div>
                        <div class="form-group"><label>Expired</label><input type="text" name="card_exp" value="12/28" maxlength="5" oninput="formatExp(this)"></div>
                        <div class="form-group"><label>CVV</label><input type="text" name="card_cvv" value="123" maxlength="3"></div>
                    </div>
                    <p class="cc-note">Dummy payment untuk testing. Toggle di atas untuk skip validasi kartu.</p>
                </div>

                <div id="alt-payment" class="alt-payment" style="display:none;">
                    <p>Scan QR Code atau transfer ke rekening berikut:</p>
                    <div class="qr-box">[ QR CODE DUMMY ]</div>
                    <p><strong>BCA:</strong> 123-456-7890 a.n. <?php echo htmlspecialchars($_namaToko); ?></p>
                </div>
            </section>

            <button type="submit" class="btn-pay" id="btn-pay">Bayar Sekarang</button>
        </div>
    </form>
</div>

<script>
const cart = JSON.parse(localStorage.getItem('cart') || '[]');
const pajakPersen = <?php echo $pajakPersen; ?>;

if (cart.length === 0) { document.getElementById('btn-pay').disabled = true; }
else { document.getElementById('cart_data').value = JSON.stringify(cart); renderCheckoutItems(); }

function renderCheckoutItems() {
    const container = document.getElementById('checkout-items');
    const totalEl = document.getElementById('checkout-total');
    let subtotal = 0;
    container.innerHTML = cart.map(item => {
        const sub = item.harga * item.qty;
        subtotal += sub;
        return `<div class="checkout-item"><span class="item-name">${item.nama} x ${item.qty}</span><span class="item-price">Rp ${Number(sub).toLocaleString('id-ID')}</span></div>`;
    }).join('');
    
    const pajak = subtotal * (pajakPersen / 100);
    const total = subtotal + pajak;
    
    let totalHtml = `<div class="checkout-item"><span>Subtotal</span><span>Rp ${Number(subtotal).toLocaleString('id-ID')}</span></div>`;
    if (pajakPersen > 0) {
        totalHtml += `<div class="checkout-item"><span>Pajak (${pajakPersen}%)</span><span>Rp ${Number(pajak).toLocaleString('id-ID')}</span></div>`;
    }
    totalHtml += `<div class="checkout-item total-row"><span><strong>Total</strong></span><span><strong>Rp ${Number(total).toLocaleString('id-ID')}</strong></span></div>`;
    totalEl.innerHTML = totalHtml;
}

function togglePayment(method) {
    document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
    event.target.closest('.payment-option').classList.add('selected');
    document.getElementById('cc-form').style.display = method === 'creditcard' ? 'block' : 'none';
    document.getElementById('alt-payment').style.display = method !== 'creditcard' ? 'block' : 'none';
}

function formatCard(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 16);
    input.value = v.replace(/(\d{4})/g, '$1 ').trim();
    document.getElementById('cc-display').textContent = input.value || '---- ---- ---- ----';
}

function formatExp(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 4);
    if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2);
    input.value = v;
    document.getElementById('cc-exp-display').textContent = v || 'MM/YY';
}
</script>
