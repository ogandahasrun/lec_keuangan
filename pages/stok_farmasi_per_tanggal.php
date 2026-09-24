<?php
set_time_limit(600);

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Filter inputs
$tanggal        = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$kd_bangsal     = isset($_GET['kd_bangsal']) ? $_GET['kd_bangsal'] : '';
$hanya_ada_stok = isset($_GET['hanya_ada_stok']) ? $_GET['hanya_ada_stok'] : '0';

$tanggal_safe    = mysqli_real_escape_string($koneksi, $tanggal);
$kd_bangsal_safe = mysqli_real_escape_string($koneksi, $kd_bangsal);

// Ambil daftar bangsal untuk filter dropdown
$query_bangsal = "SELECT DISTINCT rbm.kd_bangsal, b.nm_bangsal 
                  FROM riwayat_barang_medis rbm 
                  LEFT JOIN bangsal b ON rbm.kd_bangsal = b.kd_bangsal 
                  ORDER BY rbm.kd_bangsal ASC";
$result_bangsal = mysqli_query($koneksi, $query_bangsal);
$list_bangsal   = [];
if ($result_bangsal) {
    while ($row_b = mysqli_fetch_assoc($result_bangsal)) {
        $list_bangsal[] = $row_b;
    }
}

// Logic pencarian stok per tanggal
$data_stok      = [];
$total_item     = 0;
$total_qty_stok = 0;

if (isset($_GET['tanggal'])) {
    $where_bangsal_sub = !empty($kd_bangsal_safe) ? "AND kd_bangsal = '$kd_bangsal_safe'" : "";
    
    // Batch query stok_akhir + harga master (h_beli) + harga beli terakhir (h_pesan + PPN 11%)
    $sql_stok = "SELECT 
                    r1.kode_brng, 
                    r1.kd_bangsal, 
                    r1.stok_akhir, 
                    db.nama_brng, 
                    db.kode_sat, 
                    db.h_beli,
                    b.nm_bangsal,
                    (
                        SELECT dp.h_pesan 
                        FROM detailpesan dp
                        INNER JOIN pemesanan p ON dp.no_faktur = p.no_faktur
                        WHERE dp.kode_brng = r1.kode_brng
                          AND p.tgl_faktur <= '$tanggal_safe'
                        ORDER BY p.tgl_faktur DESC, p.no_faktur DESC
                        LIMIT 1
                    ) AS h_pesan_terakhir
                 FROM riwayat_barang_medis r1
                 INNER JOIN (
                     SELECT rbm_inner.kode_brng, rbm_inner.kd_bangsal, rbm_inner.tanggal, MAX(rbm_inner.jam) as max_jam
                     FROM riwayat_barang_medis rbm_inner
                     INNER JOIN (
                         SELECT kode_brng, kd_bangsal, MAX(tanggal) as max_tgl
                         FROM riwayat_barang_medis
                         WHERE tanggal <= '$tanggal_safe' $where_bangsal_sub
                         GROUP BY kode_brng, kd_bangsal
                     ) r_tgl ON rbm_inner.kode_brng = r_tgl.kode_brng 
                            AND rbm_inner.kd_bangsal = r_tgl.kd_bangsal 
                            AND rbm_inner.tanggal = r_tgl.max_tgl
                     GROUP BY rbm_inner.kode_brng, rbm_inner.kd_bangsal, rbm_inner.tanggal
                 ) r2 ON r1.kode_brng = r2.kode_brng AND r1.kd_bangsal = r2.kd_bangsal AND r1.tanggal = r2.tanggal AND r1.jam = r2.max_jam
                 LEFT JOIN databarang db ON r1.kode_brng = db.kode_brng
                 LEFT JOIN bangsal b ON r1.kd_bangsal = b.kd_bangsal
                 GROUP BY r1.kode_brng, r1.kd_bangsal
                 ORDER BY db.nama_brng ASC, r1.kode_brng ASC";

    $result_stok = mysqli_query($koneksi, $sql_stok);

    if ($result_stok && mysqli_num_rows($result_stok) > 0) {
        while ($row = mysqli_fetch_assoc($result_stok)) {
            $stok_akhir = (float)$row['stok_akhir'];

            // Opsi filter hanya yang ada stok > 0
            if ($hanya_ada_stok === '1' && $stok_akhir <= 0) {
                continue;
            }

            $h_beli = (float)($row['h_beli'] ?? 0);
            $h_pesan_raw = $row['h_pesan_terakhir'] !== null ? (float)$row['h_pesan_terakhir'] : null;
            $h_pesan_ppn = $h_pesan_raw !== null ? $h_pesan_raw * 1.11 : null;

            $data_stok[] = [
                'kode_brng'        => $row['kode_brng'],
                'nama_brng'        => $row['nama_brng'] ?? '-',
                'kode_sat'         => $row['kode_sat'] ?? '-',
                'kd_bangsal'       => $row['kd_bangsal'],
                'nm_bangsal'       => $row['nm_bangsal'] ?? '-',
                'stok_akhir'       => $stok_akhir,
                'h_beli'           => $h_beli,
                'h_pesan_terakhir' => $h_pesan_ppn
            ];

            $total_item++;
            $total_qty_stok += $stok_akhir;
        }
    }
}
?>

<!-- DataTables CSS & Plugins -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">

<style>
/* CSS khusus menyelaraskan DataTables dengan tema aplikasi */
table.dataTable thead th {
    background: var(--sidebar-bg) !important;
    color: var(--text-main) !important;
    border-bottom: 2px solid var(--border) !important;
    font-size: 13px !important;
    text-transform: uppercase;
}
table.dataTable tbody td {
    border-bottom: 1px solid var(--border) !important;
    font-size: 14px;
    background-color: transparent !important;
    color: var(--text-main) !important;
}
table.dataTable tbody tr {
    background-color: var(--card-bg) !important;
}
table.dataTable.display tbody tr:hover,
table.dataTable tbody tr:hover {
    background-color: rgba(255, 20, 147, 0.15) !important;
}
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--border) !important;
    border-radius: 6px !important;
    padding: 6px 10px !important;
    background: var(--background) !important;
    color: var(--text-main) !important;
}
.dataTables_wrapper .dataTables_info, 
.dataTables_wrapper .dataTables_paginate {
    color: var(--text-muted) !important;
    font-size: 13px;
    margin-top: 15px;
}
.summary-box {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.summary-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 20px;
    flex: 1;
    min-width: 200px;
}
.summary-card .label {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 4px;
}
.summary-card .value {
    font-size: 22px;
    font-weight: 700;
    color: var(--primary);
}
</style>

<div class="page-header">
    <h1 class="page-title">Stok Farmasi Per Tanggal</h1>
    <p class="page-subtitle">Laporan posisi stok akhir barang medis / obat per tanggal, lokasi bangsal, serta riwayat harga master & pembelian terakhir.</p>
</div>

<!-- Ringkasan Singkat -->
<?php if (isset($_GET['tanggal'])): ?>
<div class="summary-box">
    <div class="summary-card">
        <div class="label">Total Item / Baris</div>
        <div class="value"><?= number_format($total_item, 0, ',', '.') ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Total Qty Stok Akhir</div>
        <div class="value"><?= number_format($total_qty_stok, 2, ',', '.') ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Tanggal Posisi</div>
        <div class="value" style="color:var(--text-main);"><?= date('d-m-Y', strtotime($tanggal)) ?></div>
    </div>
</div>
<?php endif; ?>

<div class="content-card">
    <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; margin-bottom: 20px;">
        <input type="hidden" name="page" value="stok_farmasi_per_tanggal">

        <div class="form-group" style="margin:0;">
            <label class="form-label">Pilih Tanggal</label>
            <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>" required>
        </div>

        <div class="form-group" style="margin:0; min-width: 220px;">
            <label class="form-label">Kode / Nama Bangsal</label>
            <select name="kd_bangsal" class="form-control">
                <option value="">-- Semua Bangsal --</option>
                <?php foreach ($list_bangsal as $b): ?>
                    <option value="<?= htmlspecialchars($b['kd_bangsal']) ?>" <?= $kd_bangsal === $b['kd_bangsal'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['kd_bangsal']) ?> <?= !empty($b['nm_bangsal']) ? '- ' . htmlspecialchars($b['nm_bangsal']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0; min-width: 180px;">
            <label class="form-label">Filter Stok</label>
            <select name="hanya_ada_stok" class="form-control">
                <option value="0" <?= $hanya_ada_stok === '0' ? 'selected' : '' ?>>Tampilkan Semua (Termasuk 0)</option>
                <option value="1" <?= $hanya_ada_stok === '1' ? 'selected' : '' ?>>Hanya Stok > 0</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="height: 42px;">
                <i class="fas fa-search"></i> Tampilkan
            </button>
            <?php if (isset($_GET['tanggal']) && !empty($data_stok)): ?>
                <button type="button" class="btn btn-secondary" style="height: 42px;" onclick="copyTableStok()">
                    <i class="fas fa-copy"></i> Copy Tabel
                </button>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table-custom" id="tabelStokFarmasi">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    <th>Kode Bangsal</th>
                    <th>Nama Bangsal</th>
                    <th style="text-align: right;">Stok Akhir</th>
                    <th style="text-align: right;">Harga Master</th>
                    <th style="text-align: right;">Harga Beli Terakhir (+PPN 11%)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!isset($_GET['tanggal'])): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 30px;">
                            Silakan tentukan tanggal dan lokasi bangsal, kemudian klik tombol <strong>Tampilkan</strong>.
                        </td>
                    </tr>
                <?php elseif (empty($data_stok)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Tidak ada data stok ditemukan untuk tanggal dan bangsal yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($data_stok as $row): 
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><code><?= htmlspecialchars($row['kode_brng']) ?></code></td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($row['nama_brng']) ?></td>
                            <td><?= htmlspecialchars($row['kode_sat']) ?></td>
                            <td><code><?= htmlspecialchars($row['kd_bangsal']) ?></code></td>
                            <td><?= htmlspecialchars($row['nm_bangsal']) ?></td>
                            <td style="text-align: right; font-weight: 700; color: <?= $row['stok_akhir'] > 0 ? 'var(--primary-hover)' : 'var(--text-muted)' ?>;">
                                <?= number_format($row['stok_akhir'], 2, ',', '.') ?>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: var(--text-main);">
                                <?= number_format($row['h_beli'], 2, ',', '.') ?>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: <?= $row['h_pesan_terakhir'] !== null ? 'var(--success)' : 'var(--text-muted)' ?>;">
                                <?= $row['h_pesan_terakhir'] !== null ? number_format($row['h_pesan_terakhir'], 2, ',', '.') : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($data_stok)): ?>
            <tfoot>
                <tr style="font-weight: 700; background: rgba(0,0,0,0.2);">
                    <td colspan="6" style="text-align: right; padding: 12px;">Total Stok Akhir:</td>
                    <td style="text-align: right; padding: 12px; color: var(--primary); font-size: 15px;">
                        <?= number_format($total_qty_stok, 2, ',', '.') ?>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- DataTables & Copy Script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.min.js"></script>

<script>
$(document.body).ready(function() {
    if ($('#tabelStokFarmasi tbody tr').length > 0 && !$('#tabelStokFarmasi tbody tr td').attr('colspan')) {
        $('#tabelStokFarmasi').DataTable({
            "language": {
                "search": "Cari data:",
                "lengthMenu": "Tampilkan _MENU_ baris",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": {
                    "first": "Awal",
                    "last": "Akhir",
                    "next": "Lanjut",
                    "previous": "Kembali"
                }
            },
            "pageLength": 50,
            "order": [[2, "asc"]]
        });
    }
});

function copyTableStok() {
    const table = document.getElementById("tabelStokFarmasi");
    if (!table) return;
    const range = document.createRange();
    range.selectNode(table);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
    try { 
        document.execCommand('copy'); 
        alert('Tabel berhasil disalin ke clipboard!'); 
    } catch(e) { 
        alert('Gagal menyalin: ' + e); 
    }
    selection.removeAllRanges();
}
</script>
