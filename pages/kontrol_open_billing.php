<?php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Default value
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$kd_pj = isset($_GET['kd_pj']) ? $_GET['kd_pj'] : '';
$limit = isset($_GET['limit']) ? $_GET['limit'] : '50';
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman < 1) $halaman = 1;

// Query daftar jenis bayar / penjab
$query_pj = "SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab ASC";
$result_pj = mysqli_query($koneksi, $query_pj);
?>

<div class="page-header">
    <h1 class="page-title">Log Hapus Billing</h1>
    <p class="page-subtitle">Kontrol dan riwayat penghapusan billing pasien.</p>
</div>

<div class="content-card">
    <form method="GET" action="index.php" id="formFilter" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <input type="hidden" name="page" value="kontrol_open_billing">
        <input type="hidden" name="filter" value="1">
        <input type="hidden" id="halaman" name="halaman" value="<?php echo htmlspecialchars($halaman); ?>">
        
        <div class="form-group" style="margin:0;">
            <label class="form-label" for="tanggal_awal">Tanggal Awal</label>
            <input type="date" id="tanggal_awal" name="tanggal_awal" class="form-control" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
        </div>
        
        <div class="form-group" style="margin:0;">
            <label class="form-label" for="tanggal_akhir">Tanggal Akhir</label>
            <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
        </div>

        <div class="form-group" style="margin:0;">
            <label class="form-label" for="kd_pj">Jenis Bayar (Penjab)</label>
            <select id="kd_pj" name="kd_pj" class="form-control">
                <option value="">-- Semua Jenis Bayar --</option>
                <?php
                if ($result_pj && mysqli_num_rows($result_pj) > 0) {
                    while ($row_pj = mysqli_fetch_assoc($result_pj)) {
                        $selected = ($kd_pj == $row_pj['kd_pj']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row_pj['kd_pj']) . "' {$selected}>" . htmlspecialchars($row_pj['png_jawab']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="form-group" style="margin:0;">
            <label class="form-label" for="limit">Tampilkan Data</label>
            <select id="limit" name="limit" class="form-control" onchange="document.getElementById('halaman').value=1; this.form.submit();">
                <option value="50" <?php echo ($limit == '50') ? 'selected' : ''; ?>>50 Data</option>
                <option value="100" <?php echo ($limit == '100') ? 'selected' : ''; ?>>100 Data</option>
                <option value="200" <?php echo ($limit == '200') ? 'selected' : ''; ?>>200 Data</option>
                <option value="semua" <?php echo ($limit == 'semua') ? 'selected' : ''; ?>>Semua Data</option>
            </select>
        </div>
        
        <div style="display: flex; align-items: flex-end; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="height:42px;"><i class="fas fa-search"></i> Tampilkan</button>
            <button type="button" class="btn btn-secondary" style="height:42px;" onclick="resetForm()"><i class="fas fa-redo"></i> Reset</button>
        </div>
    </form>

    <?php
    if (isset($_GET['filter'])) {
        $tgl_awal_esc = mysqli_real_escape_string($koneksi, $tanggal_awal);
        $tgl_akhir_esc = mysqli_real_escape_string($koneksi, $tanggal_akhir);
        $kd_pj_esc = mysqli_real_escape_string($koneksi, $kd_pj);
        
        $where_pj = "";
        if (!empty($kd_pj_esc)) {
            $where_pj = " AND rp.kd_pj = '$kd_pj_esc' ";
        }
        
        $query_base = "SELECT 
                    ob.tanggal,
                    peg.nama,
                    rp.no_rawat,
                    pas.no_rkm_medis,
                    pas.nm_pasien,
                    pj.png_jawab,
                    MAX(cb.tanggal) AS tanggal_close_bill
                FROM 
                    trackersql ob
                INNER JOIN reg_periksa rp 
                    ON rp.no_rawat = SUBSTRING_INDEX(SUBSTRING_INDEX(ob.sqle, \"no_rawat='\", -1), \"'\", 1)
                INNER JOIN pasien pas ON rp.no_rkm_medis = pas.no_rkm_medis
                LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
                LEFT JOIN pegawai peg ON ob.usere = peg.nik
                LEFT JOIN trackersql cb 
                    ON cb.sqle LIKE '%update reg_periksa set status_bayar=''Sudah Bayar''%'
                    AND cb.sqle LIKE CONCAT('%no_rawat=''', rp.no_rawat, '''%')
                    AND cb.tanggal <= ob.tanggal
                WHERE
                    ob.sqle LIKE '%delete from billing%'
                    AND ob.sqle LIKE '%no_rawat=''%'
                    AND ob.tanggal BETWEEN '$tgl_awal_esc 00:00:00' AND '$tgl_akhir_esc 23:59:59'
                    $where_pj
                GROUP BY ob.tanggal, peg.nama, rp.no_rawat, pas.no_rkm_medis, pas.nm_pasien, pj.png_jawab
                ORDER BY ob.tanggal DESC";
                
        $result_all = mysqli_query($koneksi, $query_base);
        if ($result_all) {
            $total_rows = mysqli_num_rows($result_all);
            
            // Hitung Pagination
            if ($limit === 'semua') {
                $total_pages = 1;
                $halaman = 1;
                $query = $query_base;
                $offset = 0;
            } else {
                $limit_val = (int)$limit;
                if ($limit_val <= 0) $limit_val = 50;
                $total_pages = (int)ceil($total_rows / $limit_val);
                if ($total_pages < 1) $total_pages = 1;
                if ($halaman > $total_pages) $halaman = $total_pages;
                
                $offset = ($halaman - 1) * $limit_val;
                $query = $query_base . " LIMIT $offset, $limit_val";
            }
            
            $result = mysqli_query($koneksi, $query);
            
            echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
            echo '<div style="font-weight: 600; color: var(--text-muted);">Total Data: <span style="color: var(--primary);">' . $total_rows . '</span> log hapus billing';
            if ($limit !== 'semua' && $total_pages > 1) {
                echo ' <span style="font-size: 13px;">(Halaman ' . $halaman . ' dari ' . $total_pages . ')</span>';
            }
            echo '</div>';
            
            echo '<div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">';
            if ($limit !== 'semua' && $total_pages > 1) {
                echo '<div style="display: flex; align-items: center; gap: 6px;">';
                echo '<label for="halaman_select" style="font-weight: 600; font-size: 13px; color: var(--text-muted);">Pilih Halaman:</label>';
                echo '<select id="halaman_select" class="form-control" onchange="changePage(this.value)" style="padding: 6px; height: auto;">';
                for ($i = 1; $i <= $total_pages; $i++) {
                    $selected_page = ($i == $halaman) ? 'selected' : '';
                    echo "<option value='{$i}' {$selected_page}>Halaman {$i}</option>";
                }
                echo '</select>';
                echo '</div>';
            }
            echo '<button type="button" onclick="copyTableData()" class="btn btn-success" style="background-color: var(--success);"><i class="fas fa-copy"></i> Copy Tabel</button>';
            echo '</div>';
            echo '</div>';
            
            echo "<div class='table-responsive' style='overflow-x: auto;'>
                <table id='log-table' style='width: 100%; border-collapse: collapse; min-width: 900px; color: var(--text-main);'>
                <thead>
                <tr style='background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--border);'>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border);'>No</th>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border);'>TANGGAL CLOSE BILL</th>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border);'>TANGGAL OPEN BILL</th>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border); text-align: center;'>SELISIH WAKTU</th>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border);'>NO RAWAT</th>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border);'>NO RKM MEDIS</th>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border);'>NAMA PASIEN</th>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border);'>JENIS BAYAR</th>
                    <th style='padding: 12px; text-align: left; border-bottom: 1px solid var(--border);'>NAMA PEGAWAI</th>
                </tr>
                </thead>
                <tbody>";
                
            $no = $offset + 1; 
            while ($row = mysqli_fetch_assoc($result)) {
                $tanggal       = htmlspecialchars($row['tanggal']);
                $no_rawat      = htmlspecialchars($row['no_rawat']);
                $no_rkm_medis  = htmlspecialchars($row['no_rkm_medis']);
                $nm_pasien     = htmlspecialchars($row['nm_pasien']);
                $png_jawab     = htmlspecialchars($row['png_jawab'] ?? '-');
                $nama          = htmlspecialchars($row['nama']);

                // Tanggal close bill & selisih waktu
                if ($row['tanggal_close_bill']) {
                    $tanggal_close_bill = htmlspecialchars($row['tanggal_close_bill']);

                    $dt_close = new DateTime($row['tanggal_close_bill']);
                    $dt_open  = new DateTime($row['tanggal']);
                    $diff     = $dt_close->diff($dt_open);

                    // Format selisih: hari / jam / menit
                    $selisih_parts = [];
                    if ($diff->days > 0)  $selisih_parts[] = $diff->days . ' hari';
                    if ($diff->h > 0)     $selisih_parts[] = $diff->h . ' jam';
                    if ($diff->i > 0)     $selisih_parts[] = $diff->i . ' menit';
                    if (empty($selisih_parts)) $selisih_parts[] = '< 1 menit';
                    $selisih_text = implode(' ', $selisih_parts);

                    // Warna indikator berdasarkan total menit (Disesuaikan untuk dark theme)
                    $total_menit = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
                    if ($total_menit <= 1440) {
                        $selisih_color = '#d4edda'; 
                        $selisih_bg    = 'rgba(40, 167, 69, 0.2)'; // hijau
                    } elseif ($total_menit <= 2880) {
                        $selisih_color = '#fff3cd'; 
                        $selisih_bg    = 'rgba(255, 193, 7, 0.2)'; // kuning
                    } else {
                        $selisih_color = '#f8d7da'; 
                        $selisih_bg    = 'rgba(220, 53, 69, 0.2)'; // merah
                    }
                    $selisih_html = "<span style='display:inline-block;padding:4px 10px;border-radius:12px;"
                        . "background:{$selisih_bg};color:{$selisih_color};font-weight:600;font-size:12px;'>"
                        . "⏱ {$selisih_text}</span>";
                } else {
                    $tanggal_close_bill = '<span style="color:var(--text-muted);font-style:italic;">-</span>';
                    $selisih_html       = '<span style="color:var(--text-muted);font-style:italic;">-</span>';
                }
                
                // Add hover effect via css class or inline hover (will use inline style for tr border)
                echo "<tr style='border-bottom: 1px solid var(--border); transition: background 0.3s;' onmouseover=\"this.style.background='rgba(255, 20, 147, 0.1)'\" onmouseout=\"this.style.background='transparent'\">
                        <td style='padding: 12px;'>{$no}</td>
                        <td style='padding: 12px;'>{$tanggal_close_bill}</td>
                        <td style='padding: 12px;'>{$tanggal}</td>
                        <td style='padding: 12px; text-align:center;'>{$selisih_html}</td>
                        <td style='padding: 12px;'>{$no_rawat}</td>
                        <td style='padding: 12px;'>{$no_rkm_medis}</td>
                        <td style='padding: 12px;'>{$nm_pasien}</td>
                        <td style='padding: 12px;'>{$png_jawab}</td>
                        <td style='padding: 12px;'>{$nama}</td>
                    </tr>";
                $no++;
            }
            echo "</tbody></table></div>";
            
            if ($limit !== 'semua' && $total_pages > 1) {
                echo '<div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: rgba(0,0,0,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">';
                echo '<div style="font-size: 13px; color: var(--text-muted);">Menampilkan halaman <strong>' . $halaman . '</strong> dari <strong>' . $total_pages . '</strong> (Total ' . $total_rows . ' data)</div>';
                echo '<div style="display: flex; align-items: center; gap: 6px;">';
                echo '<label for="halaman_select_bottom" style="font-weight: 600; font-size: 13px; color: var(--text-muted);">Pilih Halaman:</label>';
                echo '<select id="halaman_select_bottom" class="form-control" onchange="changePage(this.value)" style="padding: 6px; height: auto;">';
                for ($i = 1; $i <= $total_pages; $i++) {
                    $selected_page = ($i == $halaman) ? 'selected' : '';
                    echo "<option value='{$i}' {$selected_page}>Halaman {$i}</option>";
                }
                echo '</select>';
                echo '</div>';
                echo '</div>';
            }
            
            if ($total_rows == 0) {
                echo '<div style="text-align: center; color: var(--text-muted); font-style: italic; padding: 40px; background: rgba(0,0,0,0.1); border-radius: 8px; margin-top: 15px;">📋 Tidak ada log hapus billing pada rentang tanggal yang dipilih</div>';
            }
        } else {
            echo '<div style="background: rgba(220, 53, 69, 0.1); color: var(--danger); padding: 15px; border-radius: 8px; border: 1px solid rgba(220, 53, 69, 0.3); margin-top: 15px;">';
            echo "❌ Terjadi kesalahan dalam query: " . mysqli_error($koneksi);
            echo '</div>';
        }
    }
    ?>
</div>

<script>
    function copyTableData() {
        let table = document.getElementById("log-table");
        if (table) {
            let range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            try {
                document.execCommand("copy");
                alert("✅ Tabel berhasil disalin ke clipboard!");
            } catch(err) {
                alert("❌ Gagal menyalin tabel");
            }
            window.getSelection().removeAllRanges();
        }
    }

    function changePage(page) {
        let elem = document.getElementById('halaman');
        if (elem) {
            elem.value = page;
            document.getElementById('formFilter').submit();
        }
    }

    function resetForm() {
        document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('kd_pj').value = '';
        document.getElementById('limit').value = '50';
        if (document.getElementById('halaman')) {
            document.getElementById('halaman').value = '1';
        }
        window.location.href = 'index.php?page=kontrol_open_billing';
    }
</script>