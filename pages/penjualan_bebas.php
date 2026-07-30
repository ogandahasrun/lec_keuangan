<?php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$tgl_awal  = isset($_GET['tgl_awal'])  ? $_GET['tgl_awal']  : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

$tgl_awal_safe  = mysqli_real_escape_string($koneksi, $tgl_awal);
$tgl_akhir_safe = mysqli_real_escape_string($koneksi, $tgl_akhir);

$sql = "SELECT
            penjualan.tgl_jual,
            detailjual.nota_jual,
            detailjual.kode_brng,
            databarang.nama_brng,
            databarang.kode_sat,
            detailjual.h_jual,
            detailjual.jumlah,
            detailjual.subtotal
        FROM penjualan
        INNER JOIN detailjual  ON detailjual.nota_jual  = penjualan.nota_jual
        INNER JOIN databarang  ON detailjual.kode_brng  = databarang.kode_brng
        WHERE penjualan.tgl_jual BETWEEN '$tgl_awal_safe' AND '$tgl_akhir_safe'
        ORDER BY penjualan.tgl_jual, detailjual.nota_jual, databarang.nama_brng";

$result = mysqli_query($koneksi, $sql);

$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $nota = $row['nota_jual'];
        if (!isset($data[$nota])) {
            $data[$nota] = [
                'tgl_jual'       => $row['tgl_jual'],
                'nota_jual'      => $row['nota_jual'],
                'items'          => [],
                'total_nota_jual' => 0
            ];
        }
        $subtotal   = $row['subtotal'];
        $ppn        = $subtotal * 0.11;
        $total_item = $subtotal + $ppn;

        $data[$nota]['items'][] = [
            'kode_brng' => $row['kode_brng'],
            'nama_brng' => $row['nama_brng'],
            'kode_sat'  => $row['kode_sat'],
            'h_jual'    => $row['h_jual'],
            'jumlah'    => $row['jumlah'],
            'subtotal'  => $subtotal,
            'ppn'       => $ppn,
            'total'     => $total_item
        ];
        $data[$nota]['total_nota_jual'] += $total_item;
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Penjualan Bebas</h1>
    <p class="page-subtitle">Laporan detail penjualan obat bebas beserta perhitungan PPN.</p>
</div>

<div class="content-card">
    <form method="get" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:20px;">
        <input type="hidden" name="page" value="penjualan_bebas">

        <div class="form-group" style="margin:0;">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="height:42px;">Tampilkan</button>
            <button type="button" class="btn btn-secondary" style="height:42px;" onclick="copyTablePenjualan()">
                <i class="fas fa-copy"></i> Copy Tabel
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table-custom" id="tabelpenjualan">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tgl Jual</th>
                    <th>Nota Jual</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    <th style="text-align:right;">Harga Jual</th>
                    <th style="text-align:right;">Jumlah</th>
                    <th style="text-align:right;">Subtotal</th>
                    <th style="text-align:right;">PPN (11%)</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:right;">Total Per Nota</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no                   = 1;
            $grand_total_jumlah   = 0;
            $grand_total_subtotal = 0;
            $grand_total_ppn      = 0;
            $grand_total_all      = 0;
            $grand_total_nota     = 0;

            if (empty($data)): ?>
                <tr><td colspan="12" style="text-align:center; padding:30px;">
                    Tidak ada data penjualan untuk rentang tanggal yang dipilih.
                </td></tr>
            <?php else:
                foreach ($data as $nota => $row):
                    $rowspan = count($row['items']);
                    $first   = true;
                    $grand_total_nota += $row['total_nota_jual'];

                    foreach ($row['items'] as $item):
                        $grand_total_jumlah   += $item['jumlah'];
                        $grand_total_subtotal += $item['subtotal'];
                        $grand_total_ppn      += $item['ppn'];
                        $grand_total_all      += $item['total'];

                        echo '<tr>';
                        if ($first) {
                            echo '<td rowspan="'.$rowspan.'">'.$no.'</td>';
                            echo '<td rowspan="'.$rowspan.'">'.htmlspecialchars($row['tgl_jual']).'</td>';
                            echo '<td rowspan="'.$rowspan.'">'.htmlspecialchars($row['nota_jual']).'</td>';
                        }
                        echo '<td>'.htmlspecialchars($item['kode_brng']).'</td>';
                        echo '<td>'.htmlspecialchars($item['nama_brng']).'</td>';
                        echo '<td>'.htmlspecialchars($item['kode_sat']).'</td>';
                        echo '<td style="text-align:right;">'.number_format($item['h_jual'], 2, ',', '.').'</td>';
                        echo '<td style="text-align:right;">'.$item['jumlah'].'</td>';
                        echo '<td style="text-align:right;">'.number_format($item['subtotal'], 2, ',', '.').'</td>';
                        echo '<td style="text-align:right;">'.number_format($item['ppn'], 2, ',', '.').'</td>';
                        echo '<td style="text-align:right;">'.number_format($item['total'], 2, ',', '.').'</td>';
                        if ($first) {
                            echo '<td rowspan="'.$rowspan.'" style="text-align:right; font-weight:600;">'.number_format($row['total_nota_jual'], 2, ',', '.').'</td>';
                            $first = false;
                            $no++;
                        }
                        echo '</tr>';
                    endforeach;
                endforeach;
            endif;
            ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700; background:rgba(0,0,0,0.2);">
                    <td colspan="7" style="text-align:right; padding:12px;">Total Keseluruhan:</td>
                    <td style="text-align:right; padding:12px;"><?= number_format($grand_total_jumlah,   0, ',', '.') ?></td>
                    <td style="text-align:right; padding:12px;"><?= number_format($grand_total_subtotal, 2, ',', '.') ?></td>
                    <td style="text-align:right; padding:12px;"><?= number_format($grand_total_ppn,      2, ',', '.') ?></td>
                    <td style="text-align:right; padding:12px;"><?= number_format($grand_total_all,      2, ',', '.') ?></td>
                    <td style="text-align:right; padding:12px; color:var(--primary);"><?= number_format($grand_total_nota,     2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
function copyTablePenjualan() {
    var table = document.getElementById('tabelpenjualan');
    if (!table) return;
    var range = document.createRange();
    range.selectNode(table);
    var sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
    try { document.execCommand('copy'); alert('Tabel berhasil disalin ke clipboard!'); }
    catch(e) { alert('Gagal menyalin: ' + e); }
    sel.removeAllRanges();
}
</script>
