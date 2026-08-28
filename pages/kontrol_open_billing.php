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
    <h1 class="page-title">Kontrol Open Billing</h1>
    <p class="page-subtitle">Riwayat close &amp; open billing pasien — dikelompokkan per nomor rawat.</p>
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
            <button type="button" class="btn btn-secondary" style="height:42px;" onclick="copyAllData()"><i class="fas fa-copy"></i> Copy</button>
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
        
        // ================================================================
        // STEP 1: Find no_rawat with open billing events in date range
        // Ordered by most recent open event first
        // ================================================================
        $query_find = "SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(ts.sqle, \"no_rawat='\", -1), \"'\", 1) AS no_rawat,
            MAX(ts.tanggal) AS last_open
        FROM trackersql ts
        INNER JOIN reg_periksa rp 
            ON rp.no_rawat = SUBSTRING_INDEX(SUBSTRING_INDEX(ts.sqle, \"no_rawat='\", -1), \"'\", 1)
        WHERE (ts.sqle LIKE '%delete from nota_jalan%' OR ts.sqle LIKE '%delete from nota_inap%')
          AND ts.sqle LIKE \"%no_rawat=%\"
          AND ts.tanggal BETWEEN '$tgl_awal_esc 00:00:00' AND '$tgl_akhir_esc 23:59:59'
          $where_pj
        GROUP BY no_rawat
        ORDER BY last_open DESC";
        
        $result_find = mysqli_query($koneksi, $query_find);
        
        if ($result_find) {
            $all_no_rawat = [];
            while ($row = mysqli_fetch_assoc($result_find)) {
                $all_no_rawat[] = $row['no_rawat'];
            }
            
            $total_no_rawat = count($all_no_rawat);
            
            // ================================================================
            // PAGINATION (based on no_rawat count)
            // ================================================================
            if ($limit === 'semua') {
                $total_pages = 1;
                $halaman = 1;
                $page_no_rawats = $all_no_rawat;
                $offset = 0;
            } else {
                $limit_val = (int)$limit;
                if ($limit_val <= 0) $limit_val = 50;
                $total_pages = max(1, (int)ceil($total_no_rawat / $limit_val));
                if ($halaman > $total_pages) $halaman = $total_pages;
                
                $offset = ($halaman - 1) * $limit_val;
                $page_no_rawats = array_slice($all_no_rawat, $offset, $limit_val);
            }
            
            if (!empty($page_no_rawats)) {
                // Build IN clause for SQL
                $in_parts = [];
                foreach ($page_no_rawats as $nr) {
                    $in_parts[] = "'" . mysqli_real_escape_string($koneksi, $nr) . "'";
                }
                $in_clause = implode(',', $in_parts);
                
                // ================================================================
                // STEP 2: Get ALL events (close & open) for these no_rawat
                // No date restriction — show full history
                // Deduplication: GROUP BY per minute per event type
                //
                // Format SQL log berbeda per event:
                //   Close: insert into detail_nota_inap values(|2026/08/27/000096|Transfer|0.0|...)
                //          → no_rawat di-extract dari values(|...|
                //   Open:  delete from nota_jalan where ... no_rawat='2026/08/27/000096' ...
                //          → no_rawat di-extract dari no_rawat='...'
                // ================================================================
                $query_events = "SELECT * FROM (
                    SELECT 
                        MIN(ts.tanggal) AS tanggal,
                        'CLOSE' AS tipe_event,
                        SUBSTRING_INDEX(SUBSTRING_INDEX(ts.sqle, 'values(|', -1), '|', 1) AS no_rawat,
                        peg.nama AS nama_pegawai
                    FROM trackersql ts
                    LEFT JOIN pegawai peg ON ts.usere = peg.nik
                    WHERE (ts.sqle LIKE '%insert into detail_nota_jalan%' OR ts.sqle LIKE '%insert into detail_nota_inap%')
                    AND SUBSTRING_INDEX(SUBSTRING_INDEX(ts.sqle, 'values(|', -1), '|', 1) IN ($in_clause)
                    GROUP BY no_rawat, DATE_FORMAT(ts.tanggal, '%Y-%m-%d %H:%i'), peg.nama
                    
                    UNION ALL
                    
                    SELECT 
                        MIN(ts.tanggal) AS tanggal,
                        'CLOSE' AS tipe_event,
                        SUBSTRING_INDEX(SUBSTRING_INDEX(ts.sqle, \"values ('\", -1), \"'\", 1) AS no_rawat,
                        peg.nama AS nama_pegawai
                    FROM trackersql ts
                    LEFT JOIN pegawai peg ON ts.usere = peg.nik
                    WHERE ts.sqle LIKE '%insert into piutang_pasien%'
                    AND SUBSTRING_INDEX(SUBSTRING_INDEX(ts.sqle, \"values ('\", -1), \"'\", 1) IN ($in_clause)
                    GROUP BY no_rawat, DATE_FORMAT(ts.tanggal, '%Y-%m-%d %H:%i'), peg.nama
                    
                    UNION ALL
                    
                    SELECT 
                        MIN(ts.tanggal) AS tanggal,
                        'OPEN' AS tipe_event,
                        SUBSTRING_INDEX(SUBSTRING_INDEX(ts.sqle, \"no_rawat='\", -1), \"'\", 1) AS no_rawat,
                        peg.nama AS nama_pegawai
                    FROM trackersql ts
                    LEFT JOIN pegawai peg ON ts.usere = peg.nik
                    WHERE (ts.sqle LIKE '%delete from nota_jalan%' OR ts.sqle LIKE '%delete from nota_inap%')
                    AND SUBSTRING_INDEX(SUBSTRING_INDEX(ts.sqle, \"no_rawat='\", -1), \"'\", 1) IN ($in_clause)
                    GROUP BY no_rawat, DATE_FORMAT(ts.tanggal, '%Y-%m-%d %H:%i'), peg.nama
                ) AS events
                ORDER BY no_rawat, tanggal ASC";
                
                $result_events = mysqli_query($koneksi, $query_events);
                
                // Group events by no_rawat
                $grouped = [];
                $total_close = 0;
                $total_open = 0;
                if ($result_events) {
                    while ($row = mysqli_fetch_assoc($result_events)) {
                        $grouped[$row['no_rawat']][] = $row;
                        if ($row['tipe_event'] === 'CLOSE') $total_close++;
                        else $total_open++;
                    }
                }
                
                // ================================================================
                // Get patient info for all no_rawat on this page
                // ================================================================
                $query_pasien = "SELECT rp.no_rawat, pas.no_rkm_medis, pas.nm_pasien, pj.png_jawab, rp.status_lanjut
                    FROM reg_periksa rp
                    INNER JOIN pasien pas ON rp.no_rkm_medis = pas.no_rkm_medis
                    LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
                    WHERE rp.no_rawat IN ($in_clause)";
                
                $result_pasien = mysqli_query($koneksi, $query_pasien);
                $pasien_info = [];
                if ($result_pasien) {
                    while ($row = mysqli_fetch_assoc($result_pasien)) {
                        $pasien_info[$row['no_rawat']] = $row;
                    }
                }
                
                // ================================================================
                // RENDER: Summary bar
                // ================================================================
                echo '<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">';
                
                // Left: stats
                echo '<div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">';
                echo '<div style="font-weight: 600; color: var(--text-muted);">Total No Rawat: <span style="color: var(--primary);">' . $total_no_rawat . '</span>';
                if ($limit !== 'semua' && $total_pages > 1) {
                    echo ' <span style="font-size: 13px;">(Halaman ' . $halaman . ' dari ' . $total_pages . ')</span>';
                }
                echo '</div>';
                echo '<div style="display: flex; gap: 8px;">';
                echo '<span style="display:inline-block;padding:4px 12px;border-radius:12px;background:rgba(40, 167, 69, 0.2);color:#d4edda;font-weight:600;font-size:12px;">🟢 Close: ' . $total_close . '</span>';
                echo '<span style="display:inline-block;padding:4px 12px;border-radius:12px;background:rgba(220, 53, 69, 0.2);color:#f8d7da;font-weight:600;font-size:12px;">🔴 Open: ' . $total_open . '</span>';
                echo '</div>';
                echo '</div>';
                
                // Right: controls
                echo '<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">';
                if ($limit !== 'semua' && $total_pages > 1) {
                    echo '<select id="halaman_select" class="form-control" onchange="changePage(this.value)" style="padding: 6px; height: auto; width: auto;">';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $selected_page = ($i == $halaman) ? 'selected' : '';
                        echo "<option value='{$i}' {$selected_page}>Halaman {$i}</option>";
                    }
                    echo '</select>';
                }
                echo '<button type="button" onclick="toggleAll(true)" class="btn btn-secondary" style="font-size:12px;padding:6px 12px;"><i class="fas fa-expand-alt"></i> Buka Semua</button>';
                echo '<button type="button" onclick="toggleAll(false)" class="btn btn-secondary" style="font-size:12px;padding:6px 12px;"><i class="fas fa-compress-alt"></i> Tutup Semua</button>';
                echo '<button type="button" onclick="copyAllData()" class="btn btn-success" style="background-color: var(--success); font-size:12px;padding:6px 12px;"><i class="fas fa-copy"></i> Copy Semua</button>';
                echo '</div>';
                
                echo '</div>';
                
                // ================================================================
                // RENDER: Grouped cards
                // ================================================================
                echo '<div id="all-groups">';
                $group_no = $offset + 1;
                
                foreach ($page_no_rawats as $no_rawat) {
                    $events = $grouped[$no_rawat] ?? [];
                    $info = $pasien_info[$no_rawat] ?? null;
                    
                    $nm_pasien = $info ? htmlspecialchars($info['nm_pasien']) : '-';
                    $no_rkm_medis = $info ? htmlspecialchars($info['no_rkm_medis']) : '-';
                    $png_jawab = $info ? htmlspecialchars($info['png_jawab'] ?? '-') : '-';
                    $status_lanjut = $info ? $info['status_lanjut'] : '-';
                    
                    if ($status_lanjut === 'Ralan') {
                        $jenis_label = 'Rawat Jalan';
                        $jenis_icon = '🏥';
                    } elseif ($status_lanjut === 'Ranap') {
                        $jenis_label = 'Rawat Inap';
                        $jenis_icon = '🛏️';
                    } else {
                        $jenis_label = htmlspecialchars($status_lanjut);
                        $jenis_icon = '📋';
                    }
                    
                    // Count close/open for this group
                    $g_close = 0;
                    $g_open = 0;
                    foreach ($events as $ev) {
                        if ($ev['tipe_event'] === 'CLOSE') $g_close++;
                        else $g_open++;
                    }
                    
                    $no_rawat_esc = htmlspecialchars($no_rawat);
                    
                    // ---- Card container ----
                    echo "<div class='billing-group' style='margin-bottom: 16px; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: var(--card-bg);'>";
                    
                    // ---- Card header (clickable) ----
                    echo "<div class='group-header' onclick='toggleGroup({$group_no})' style='
                        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
                        padding: 16px 20px; cursor: pointer; transition: background 0.2s;
                        background: rgba(0,0,0,0.25); border-left: 4px solid var(--primary);
                    ' onmouseover=\"this.style.background='rgba(255, 20, 147, 0.08)'\" onmouseout=\"this.style.background='rgba(0,0,0,0.25)'\">";
                    
                    // Left side: patient info
                    echo "<div style='display: flex; flex-direction: column; gap: 6px;'>";
                    echo "<div style='display: flex; align-items: center; gap: 10px; flex-wrap: wrap;'>";
                    echo "<span style='font-weight: 700; color: var(--primary); font-size: 15px;'>#{$group_no}</span>";
                    echo "<span style='font-weight: 700; color: var(--text-main); font-size: 15px;'>{$no_rawat_esc}</span>";
                    echo "<span style='color: var(--text-muted); font-size: 13px;'>|</span>";
                    echo "<span style='color: var(--text-main); font-weight: 600;'>{$nm_pasien}</span>";
                    echo "<span style='color: var(--text-muted); font-size: 13px;'>(RM: {$no_rkm_medis})</span>";
                    echo "</div>";
                    
                    echo "<div style='display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 13px;'>";
                    echo "<span style='color: var(--text-muted);'>{$jenis_icon} {$jenis_label}</span>";
                    echo "<span style='color: var(--text-muted);'>•</span>";
                    echo "<span style='color: var(--text-muted);'>💳 {$png_jawab}</span>";
                    echo "<span style='color: var(--text-muted);'>•</span>";
                    echo "<span style='display:inline-block;padding:2px 8px;border-radius:8px;background:rgba(40, 167, 69, 0.15);color:#a3d9a5;font-weight:600;font-size:11px;'>Close: {$g_close}</span>";
                    echo "<span style='display:inline-block;padding:2px 8px;border-radius:8px;background:rgba(220, 53, 69, 0.15);color:#f5a0a0;font-weight:600;font-size:11px;'>Open: {$g_open}</span>";
                    echo "</div>";
                    echo "</div>";
                    
                    // Right side: toggle icon
                    echo "<i id='group-icon-{$group_no}' class='fas fa-chevron-up group-toggle-icon' style='color: var(--text-muted); font-size: 14px; transition: transform 0.3s;'></i>";
                    
                    echo "</div>"; // end header
                    
                    // ---- Card body (events table) ----
                    echo "<div class='group-body' id='group-body-{$group_no}' style='padding: 0;'>";
                    
                    if (!empty($events)) {
                        echo "<div style='overflow-x: auto;'>";
                        echo "<table style='width: 100%; border-collapse: collapse; min-width: 700px; color: var(--text-main);'>";
                        echo "<thead>";
                        echo "<tr style='background: rgba(0,0,0,0.15); border-bottom: 1px solid var(--border);'>";
                        echo "<th style='padding: 10px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; font-weight: 600;'>No</th>";
                        echo "<th style='padding: 10px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; font-weight: 600;'>Waktu</th>";
                        echo "<th style='padding: 10px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; font-weight: 600;'>Event</th>";
                        echo "<th style='padding: 10px 16px; text-align: center; font-size: 12px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; font-weight: 600;'>Selisih dari Event Sebelumnya</th>";
                        echo "<th style='padding: 10px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; font-weight: 600;'>Pegawai</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        
                        $event_no = 1;
                        foreach ($events as $i => $event) {
                            $tanggal_event = htmlspecialchars($event['tanggal']);
                            $tipe = $event['tipe_event'];
                            $pegawai = htmlspecialchars($event['nama_pegawai'] ?? '-');
                            
                            // --- Event badge ---
                            if ($tipe === 'CLOSE') {
                                $event_badge = '<span style="display:inline-block;padding:4px 12px;border-radius:12px;background:rgba(40, 167, 69, 0.2);color:#a3d9a5;font-weight:600;font-size:12px;">🟢 CLOSE BILLING</span>';
                                $row_accent = 'border-left: 3px solid rgba(40, 167, 69, 0.5);';
                            } else {
                                $event_badge = '<span style="display:inline-block;padding:4px 12px;border-radius:12px;background:rgba(220, 53, 69, 0.2);color:#f5a0a0;font-weight:600;font-size:12px;">🔴 OPEN BILLING</span>';
                                $row_accent = 'border-left: 3px solid rgba(220, 53, 69, 0.5);';
                            }
                            
                            // --- Selisih waktu ---
                            $selisih_html = '<span style="color:var(--text-muted);font-style:italic;">-</span>';
                            if ($i > 0) {
                                $dt_prev = new DateTime($events[$i - 1]['tanggal']);
                                $dt_curr = new DateTime($event['tanggal']);
                                $diff = $dt_prev->diff($dt_curr);
                                
                                $selisih_parts = [];
                                if ($diff->days > 0)  $selisih_parts[] = $diff->days . ' hari';
                                if ($diff->h > 0)     $selisih_parts[] = $diff->h . ' jam';
                                if ($diff->i > 0)     $selisih_parts[] = $diff->i . ' menit';
                                if (empty($selisih_parts)) $selisih_parts[] = '< 1 menit';
                                $selisih_text = implode(' ', $selisih_parts);
                                
                                $total_menit = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
                                if ($total_menit <= 1440) {
                                    $selisih_color = '#d4edda'; 
                                    $selisih_bg    = 'rgba(40, 167, 69, 0.2)';
                                } elseif ($total_menit <= 2880) {
                                    $selisih_color = '#fff3cd'; 
                                    $selisih_bg    = 'rgba(255, 193, 7, 0.2)';
                                } else {
                                    $selisih_color = '#f8d7da'; 
                                    $selisih_bg    = 'rgba(220, 53, 69, 0.2)';
                                }
                                $selisih_html = "<span style='display:inline-block;padding:4px 10px;border-radius:12px;"
                                    . "background:{$selisih_bg};color:{$selisih_color};font-weight:600;font-size:12px;'>"
                                    . "⏱ {$selisih_text}</span>";
                            }
                            
                            echo "<tr style='border-bottom: 1px solid var(--border); {$row_accent} transition: background 0.2s;' 
                                onmouseover=\"this.style.background='rgba(255, 20, 147, 0.06)'\" 
                                onmouseout=\"this.style.background='transparent'\">";
                            echo "<td style='padding: 10px 16px; font-size: 13px;'>{$event_no}</td>";
                            echo "<td style='padding: 10px 16px; font-size: 13px; font-family: monospace;'>{$tanggal_event}</td>";
                            echo "<td style='padding: 10px 16px;'>{$event_badge}</td>";
                            echo "<td style='padding: 10px 16px; text-align: center;'>{$selisih_html}</td>";
                            echo "<td style='padding: 10px 16px; font-size: 13px;'>{$pegawai}</td>";
                            echo "</tr>";
                            
                            $event_no++;
                        }
                        
                        echo "</tbody></table></div>";
                    } else {
                        echo '<div style="padding: 20px; text-align: center; color: var(--text-muted); font-style: italic;">Tidak ada event ditemukan</div>';
                    }
                    
                    echo "</div>"; // end body
                    echo "</div>"; // end card
                    
                    $group_no++;
                }
                
                echo '</div>'; // end #all-groups
                
                // ================================================================
                // Pagination bottom
                // ================================================================
                if ($limit !== 'semua' && $total_pages > 1) {
                    echo '<div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: rgba(0,0,0,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">';
                    echo '<div style="font-size: 13px; color: var(--text-muted);">Menampilkan halaman <strong>' . $halaman . '</strong> dari <strong>' . $total_pages . '</strong> (Total ' . $total_no_rawat . ' no rawat)</div>';
                    echo '<div style="display: flex; align-items: center; gap: 6px;">';
                    echo '<label for="halaman_select_bottom" style="font-weight: 600; font-size: 13px; color: var(--text-muted);">Pilih Halaman:</label>';
                    echo '<select id="halaman_select_bottom" class="form-control" onchange="changePage(this.value)" style="padding: 6px; height: auto; width: auto;">';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $selected_page = ($i == $halaman) ? 'selected' : '';
                        echo "<option value='{$i}' {$selected_page}>Halaman {$i}</option>";
                    }
                    echo '</select>';
                    echo '</div>';
                    echo '</div>';
                }
                
            } // end if (!empty($page_no_rawats))
            
            if ($total_no_rawat == 0) {
                echo '<div style="text-align: center; color: var(--text-muted); font-style: italic; padding: 40px; background: rgba(0,0,0,0.1); border-radius: 8px; margin-top: 15px;">📋 Tidak ada data open billing pada rentang tanggal yang dipilih</div>';
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
    // Toggle individual group
    function toggleGroup(id) {
        var body = document.getElementById('group-body-' + id);
        var icon = document.getElementById('group-icon-' + id);
        if (!body) return;
        if (body.style.display === 'none') {
            body.style.display = 'block';
            if (icon) icon.className = 'fas fa-chevron-up group-toggle-icon';
        } else {
            body.style.display = 'none';
            if (icon) icon.className = 'fas fa-chevron-down group-toggle-icon';
        }
    }

    // Toggle all groups
    function toggleAll(expand) {
        var bodies = document.querySelectorAll('.group-body');
        var icons = document.querySelectorAll('.group-toggle-icon');
        bodies.forEach(function(body) {
            body.style.display = expand ? 'block' : 'none';
        });
        icons.forEach(function(icon) {
            icon.className = expand ? 'fas fa-chevron-up group-toggle-icon' : 'fas fa-chevron-down group-toggle-icon';
        });
    }

    // Copy all data
    function copyAllData() {
        var container = document.getElementById('all-groups');
        if (container) {
            var range = document.createRange();
            range.selectNode(container);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            try {
                document.execCommand('copy');
                alert('✅ Data berhasil disalin ke clipboard!');
            } catch(err) {
                alert('❌ Gagal menyalin data');
            }
            window.getSelection().removeAllRanges();
        }
    }

    // Change page
    function changePage(page) {
        var elem = document.getElementById('halaman');
        if (elem) {
            elem.value = page;
            document.getElementById('formFilter').submit();
        }
    }

    // Reset form
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