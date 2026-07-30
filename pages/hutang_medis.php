<?php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$tgl_pesan_start = isset($_GET['tgl_pesan_start']) ? $_GET['tgl_pesan_start'] : date('Y-m-01');
$tgl_pesan_end   = isset($_GET['tgl_pesan_end']) ? $_GET['tgl_pesan_end'] : date('Y-m-d');
$tgl_faktur_start = isset($_GET['tgl_faktur_start']) ? $_GET['tgl_faktur_start'] : '';
$tgl_faktur_end   = isset($_GET['tgl_faktur_end']) ? $_GET['tgl_faktur_end'] : '';
$suplier = isset($_GET['suplier']) ? $_GET['suplier'] : '';
$status  = isset($_GET['status']) ? $_GET['status'] : '';

$where_clauses = [];
$where_clauses[] = "pemesanan.tgl_pesan BETWEEN '$tgl_pesan_start' AND '$tgl_pesan_end'";

if (!empty($tgl_faktur_start) && !empty($tgl_faktur_end)) {
    $where_clauses[] = "pemesanan.tgl_faktur BETWEEN '$tgl_faktur_start' AND '$tgl_faktur_end'";
}
if (!empty($suplier)) {
    $where_clauses[] = "datasuplier.nama_suplier LIKE '%" . $koneksi->real_escape_string($suplier) . "%'";
}
if (!empty($status)) {
    $where_clauses[] = "pemesanan.status = '" . $koneksi->real_escape_string($status) . "'";
}

$where_sql = implode(' AND ', $where_clauses);

$query = "SELECT
            pemesanan.tgl_pesan,
            pemesanan.tgl_faktur,
            pemesanan.no_faktur,
            datasuplier.nama_suplier,
            pemesanan.total2,
            pemesanan.ppn,
            pemesanan.meterai,
            pemesanan.tagihan,
            pemesanan.status
          FROM
            pemesanan
          INNER JOIN datasuplier ON pemesanan.kode_suplier = datasuplier.kode_suplier
          WHERE $where_sql
          ORDER BY pemesanan.tgl_pesan DESC";

$result = $koneksi->query($query);
?>

<div class="page-header">
    <h1 class="page-title">Hutang Belanja Medis</h1>
    <p class="page-subtitle">Laporan hutang belanja obat dan BHP.</p>
</div>

<div class="content-card">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <input type="hidden" name="page" value="hutang_medis">
        
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tgl Datang (Pesan) Dari</label>
            <input type="date" name="tgl_pesan_start" class="form-control" value="<?= $tgl_pesan_start ?>" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tgl Datang (Pesan) Sampai</label>
            <input type="date" name="tgl_pesan_end" class="form-control" value="<?= $tgl_pesan_end ?>" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tgl Faktur Dari (Opsional)</label>
            <input type="date" name="tgl_faktur_start" class="form-control" value="<?= $tgl_faktur_start ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tgl Faktur Sampai (Opsional)</label>
            <input type="date" name="tgl_faktur_end" class="form-control" value="<?= $tgl_faktur_end ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Nama Suplier</label>
            <select name="suplier" class="form-control">
                <option value="">Semua Suplier</option>
                <?php
                $q_suplier = "SELECT kode_suplier, nama_suplier FROM datasuplier ORDER BY nama_suplier ASC";
                $rs_suplier = $koneksi->query($q_suplier);
                if ($rs_suplier) {
                    while ($sup = $rs_suplier->fetch_assoc()) {
                        $selected = ($suplier == $sup['nama_suplier']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($sup['nama_suplier']) . "' {$selected}>" . htmlspecialchars($sup['nama_suplier']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Status Bayar</label>
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="Belum Dibayar" <?= $status === 'Belum Dibayar' ? 'selected' : '' ?>>Belum Dibayar</option>
                <option value="Sudah Dibayar" <?= $status === 'Sudah Dibayar' ? 'selected' : '' ?>>Sudah Dibayar</option>
                <option value="Belum Lunas" <?= $status === 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                <option value="Titip Faktur" <?= $status === 'Titip Faktur' ? 'selected' : '' ?>>Titip Faktur</option>
            </select>
        </div>
        <div style="display: flex; align-items: flex-end; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="height: 42px;">Tampilkan</button>
            <button type="button" class="btn btn-secondary" style="height: 42px;" onclick="copyTableHutang()">
                <i class="fas fa-copy"></i> Copy
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table-custom" id="tabelHutang">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tgl Pesan</th>
                    <th>Tgl Faktur</th>
                    <th>No Faktur</th>
                    <th>Nama Suplier</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">PPN</th>
                    <th style="text-align: right;">Meterai</th>
                    <th style="text-align: right;">Tagihan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    $no = 1;
                    $sum_total2 = 0;
                    $sum_ppn = 0;
                    $sum_meterai = 0;
                    $sum_tagihan = 0;

                    while ($row = $result->fetch_assoc()) {
                        $sum_total2 += $row['total2'];
                        $sum_ppn += $row['ppn'];
                        $sum_meterai += $row['meterai'];
                        $sum_tagihan += $row['tagihan'];
                        
                        $status_badge = '';
                        if ($row['status'] == 'Sudah Dibayar') {
                            $status_badge = "<span style='background:rgba(56, 161, 105, 0.2); color:#38a169; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;'>Sudah Dibayar</span>";
                        } elseif ($row['status'] == 'Belum Dibayar') {
                            $status_badge = "<span style='background:rgba(229, 62, 62, 0.2); color:#e53e3e; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;'>Belum Dibayar</span>";
                        } elseif ($row['status'] == 'Belum Lunas') {
                            $status_badge = "<span style='background:rgba(221, 107, 32, 0.2); color:#dd6b20; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;'>Belum Lunas</span>";
                        } elseif ($row['status'] == 'Titip Faktur') {
                            $status_badge = "<span style='background:rgba(49, 130, 206, 0.2); color:#3182ce; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;'>Titip Faktur</span>";
                        } else {
                            $status_badge = "<span style='background:rgba(160, 174, 192, 0.2); color:#a0aec0; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;'>" . htmlspecialchars($row['status']) . "</span>";
                        }

                        echo "<tr>
                                <td>{$no}</td>
                                <td>" . date('d-m-Y', strtotime($row['tgl_pesan'])) . "</td>
                                <td>" . (!empty($row['tgl_faktur']) && $row['tgl_faktur'] != '0000-00-00' ? date('d-m-Y', strtotime($row['tgl_faktur'])) : '-') . "</td>
                                <td>" . htmlspecialchars($row['no_faktur']) . "</td>
                                <td>" . htmlspecialchars($row['nama_suplier']) . "</td>
                                <td style='text-align: right;'>" . number_format($row['total2'], 0, ',', '.') . "</td>
                                <td style='text-align: right;'>" . number_format($row['ppn'], 0, ',', '.') . "</td>
                                <td style='text-align: right;'>" . number_format($row['meterai'], 0, ',', '.') . "</td>
                                <td style='text-align: right;'><strong>" . number_format($row['tagihan'], 0, ',', '.') . "</strong></td>
                                <td>{$status_badge}</td>
                              </tr>";
                        $no++;
                    }
                    
                    echo "<tr style='background:rgba(0,0,0,0.2); font-weight: bold;'>
                            <td colspan='5' style='text-align: right; padding: 12px;'>TOTAL KESELURUHAN</td>
                            <td style='padding: 12px; text-align: right;'>" . number_format($sum_total2, 0, ',', '.') . "</td>
                            <td style='padding: 12px; text-align: right;'>" . number_format($sum_ppn, 0, ',', '.') . "</td>
                            <td style='padding: 12px; text-align: right;'>" . number_format($sum_meterai, 0, ',', '.') . "</td>
                            <td style='padding: 12px; text-align: right; color:var(--primary);'>" . number_format($sum_tagihan, 0, ',', '.') . "</td>
                            <td style='padding: 12px;'></td>
                          </tr>";
                } else {
                    echo "<tr><td colspan='10' style='text-align: center; padding: 20px;'>Data hutang medis tidak ditemukan.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function copyTableHutang() {
    const table = document.getElementById("tabelHutang");
    if (!table) return;
    const range = document.createRange();
    range.selectNode(table);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
    try { document.execCommand('copy'); alert('Tabel berhasil disalin ke clipboard!'); }
    catch(e) { alert('Gagal menyalin: ' + e); }
    selection.removeAllRanges();
}
</script>
