<?php


// Default values for date range
$tgl_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$kd_pj = isset($_GET['kd_pj']) ? $_GET['kd_pj'] : '';

// Retrieve name of organization / institution
$nama_organisasi = 'RSUD Pringsewu'; // default value
$org_query = mysqli_query($koneksi, "SELECT nama_instansi FROM setting LIMIT 1");
if ($org_query && $org_row = mysqli_fetch_assoc($org_query)) {
    $nama_organisasi = $org_row['nama_instansi'];
}

// Retrieve list of insurance / payers (penjab) for the filter dropdown
$penjab_options = [];
$query_pj = "SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab ASC";
$result_pj = mysqli_query($koneksi, $query_pj);
if ($result_pj) {
    while ($row_pj = mysqli_fetch_assoc($result_pj)) {
        $penjab_options[] = $row_pj;
    }
}

// Retrieve list of payment accounts (akun_bayar)
$akun_bayar_options = [];
$query_ab = "SELECT nama_bayar FROM akun_bayar ORDER BY nama_bayar ASC";
$result_ab = mysqli_query($koneksi, $query_ab);
if ($result_ab) {
    while ($row_ab = mysqli_fetch_assoc($result_ab)) {
        $akun_bayar_options[] = $row_ab['nama_bayar'];
    }
}
?>

<!-- DataTables CSS untuk halaman ini -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">

<style>
/* Reset DataTables basic styles to match theme */
table.dataTable thead th {
    background: rgba(0,0,0,0.2) !important;
    color: var(--text-muted) !important;
    border-bottom: 1px solid var(--border) !important;
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
table.dataTable.display tbody tr.odd > .sorting_1, 
table.dataTable.order-column.stripe tbody tr.odd > .sorting_1,
table.dataTable.display tbody tr.even > .sorting_1, 
table.dataTable.order-column.stripe tbody tr.even > .sorting_1,
table.dataTable.display tbody tr.odd,
table.dataTable.display tbody tr.even,
table.dataTable.stripe tbody tr.odd,
table.dataTable.stripe tbody tr.even {
    background-color: transparent !important;
}
table.dataTable.display tbody tr:hover > .sorting_1, 
table.dataTable.order-column.hover tbody tr:hover > .sorting_1,
table.dataTable.display tbody tr:hover,
table.dataTable tbody tr:hover {
    background-color: rgba(255, 20, 147, 0.15) !important;
}
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--border) !important;
    border-radius: 6px !important;
    padding: 6px 10px !important;
    background: var(--background) !important;
    color: var(--text-main) !important;
}
.dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate {
    color: var(--text-muted) !important;
    font-size: 13px;
}
.dt-buttons .dt-button {
    background: var(--secondary) !important;
    border: none !important;
    color: white !important;
    border-radius: 6px !important;
    padding: 6px 12px !important;
}
.dt-buttons .dt-button:hover {
    background: #4a5568 !important;
}
/* Style adjustments for filter form */
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    align-items: flex-end;
}
.filter-form .form-group { margin: 0; }
.subtotal-row td, tr.subtotal-row td { 
    background-color: rgba(0,0,0,0.3) !important; 
    font-weight: 700 !important; 
    color: var(--text-main) !important; 
}
</style>

<div class="page-header">
    <h1 class="page-title">Laporan Keuangan</h1>
    <p class="page-subtitle">Data laporan dari sistem.</p>
</div>
<div class="content-card">

            <!-- Filter Form -->
            <form method="GET" action="index.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <input type="hidden" name="page" value="pendapatan_billing">
                
                <div class="form-group" style="margin:0;">
                    <label class="form-label" for="tanggal_awal">Tanggal Awal (tgl_byr)</label>
                    <input type="date" id="tanggal_awal" name="tanggal_awal" class="form-control" required value="<?php echo htmlspecialchars($tgl_awal); ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" for="tanggal_akhir">Tanggal Akhir (tgl_byr)</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control" required value="<?php echo htmlspecialchars($tgl_akhir); ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" for="kd_pj">Penanggung Jawab</label>
                    <select id="kd_pj" name="kd_pj" class="form-control">
                        <option value="">-- Semua Penjab --</option>
                        <?php foreach ($penjab_options as $pj) { ?>
                            <option value="<?php echo htmlspecialchars($pj['kd_pj']); ?>" <?php echo ($kd_pj == $pj['kd_pj']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pj['png_jawab']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div style="display: flex; align-items: flex-end; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="height:42px;"><i class="fas fa-search"></i> Tampilkan</button>
                    <button type="button" class="btn btn-secondary" style="height:42px;" onclick="copyToClipboard()"><i class="fas fa-copy"></i> Copy</button>
                    <button type="button" class="btn btn-secondary" style="height:42px;" onclick="resetForm()"><i class="fas fa-redo"></i> Reset</button>
                </div>
            </form>

            <?php
            function formatRupiah($angka) {
                return $angka;
            }

            // Build main query to fetch transactions
            $query_main = "SELECT
                            billing.tgl_byr,
                            reg_periksa.no_rawat,
                            pasien.no_rkm_medis,
                            pasien.nm_pasien,
                            billing.nm_perawatan,
                            penjab.png_jawab
                          FROM
                            billing
                            INNER JOIN reg_periksa ON billing.no_rawat = reg_periksa.no_rawat
                            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                            INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                          WHERE
                            billing.tgl_byr BETWEEN ? AND ?
                            AND billing.`no` = 'No.Nota'";

            if (!empty($kd_pj)) {
                $query_main .= " AND reg_periksa.kd_pj = ?";
            }
            $query_main .= " ORDER BY billing.tgl_byr ASC, reg_periksa.no_rawat ASC";

            $combined_rows = [];

            $stmt_main = mysqli_prepare($koneksi, $query_main);
            if ($stmt_main) {
                if (!empty($kd_pj)) {
                    mysqli_stmt_bind_param($stmt_main, "sss", $tgl_awal, $tgl_akhir, $kd_pj);
                } else {
                    mysqli_stmt_bind_param($stmt_main, "ss", $tgl_awal, $tgl_akhir);
                }
                
                mysqli_stmt_execute($stmt_main);
                $result_main = mysqli_stmt_get_result($stmt_main);

                if ($result_main) {
                    while ($row = mysqli_fetch_assoc($result_main)) {
                        $combined_rows[] = [
                            'type' => 'billing',
                            'tgl_byr' => $row['tgl_byr'],
                            'no_rawat' => $row['no_rawat'],
                            'no_rkm_medis' => $row['no_rkm_medis'],
                            'nm_pasien' => $row['nm_pasien'],
                            'png_jawab' => $row['png_jawab'],
                            'nm_perawatan' => $row['nm_perawatan']
                        ];
                    }
                }
                mysqli_stmt_close($stmt_main);
            }

            // Fetch Penjualan Bebas data if Penjab filter is empty or 'UMU' (Umum)
            if (empty($kd_pj) || $kd_pj == 'UMU') {
                $query_penjualan = "SELECT 
                                        p.tgl_jual,
                                        p.no_rkm_medis,
                                        p.nm_pasien,
                                        p.nota_jual,
                                        p.ppn,
                                        p.nama_bayar,
                                        COALESCE(SUM(d.total), 0) as total_obat_bhp
                                    FROM penjualan p
                                    LEFT JOIN detailjual d ON p.nota_jual = d.nota_jual
                                    WHERE p.tgl_jual BETWEEN ? AND ?
                                      AND p.status = 'Sudah Dibayar'
                                    GROUP BY p.nota_jual, p.tgl_jual, p.no_rkm_medis, p.nm_pasien, p.ppn, p.nama_bayar";
                $stmt_pj = mysqli_prepare($koneksi, $query_penjualan);
                if ($stmt_pj) {
                    mysqli_stmt_bind_param($stmt_pj, "ss", $tgl_awal, $tgl_akhir);
                    mysqli_stmt_execute($stmt_pj);
                    $res_pj = mysqli_stmt_get_result($stmt_pj);
                    if ($res_pj) {
                        while ($r_pj = mysqli_fetch_assoc($res_pj)) {
                            $combined_rows[] = [
                                'type' => 'penjualan',
                                'tgl_byr' => $r_pj['tgl_jual'],
                                'no_rawat' => '-',
                                'no_rkm_medis' => (!empty($r_pj['no_rkm_medis']) && $r_pj['no_rkm_medis'] !== '-') ? $r_pj['no_rkm_medis'] : '-',
                                'nm_pasien' => $r_pj['nm_pasien'],
                                'png_jawab' => 'Penjualan Bebas',
                                'nm_perawatan' => $r_pj['nota_jual'],
                                'total_obat_bhp' => (float)$r_pj['total_obat_bhp'],
                                'ppn' => (float)$r_pj['ppn'],
                                'nama_bayar' => $r_pj['nama_bayar']
                            ];
                        }
                    }
                    mysqli_stmt_close($stmt_pj);
                }
            }

            // Sort combined rows by tgl_byr ASC, then nm_perawatan ASC
            usort($combined_rows, function($a, $b) {
                if ($a['tgl_byr'] === $b['tgl_byr']) {
                    return strcmp($a['nm_perawatan'], $b['nm_perawatan']);
                }
                return strcmp($a['tgl_byr'], $b['tgl_byr']);
            });

            if (count($combined_rows) > 0) {
            ?>
                    <div class="table-responsive">
                        <!-- Custom pagination controls -->
                        <div id="custom-pagination" style="display: flex; align-items: center; justify-content: space-between; gap: 15px; margin: 15px 15px 10px 15px; flex-wrap: wrap;">
                            <div>
                                <label for="custom-page-length" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Tampilkan: </label>
                                <select id="custom-page-length" style="padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; outline: none; background: white;">
                                    <option value="10">10 data per halaman</option>
                                    <option value="25">25 data per halaman</option>
                                    <option value="50" selected>50 data per halaman</option>
                                    <option value="100">100 data per halaman</option>
                                    <option value="-1">Semua data</option>
                                </select>
                            </div>
                            <div>
                                <label for="custom-page-select" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Pilih Halaman: </label>
                                <select id="custom-page-select" style="padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; outline: none; background: white;">
                                    <!-- Dynamic options -->
                                </select>
                            </div>
                        </div>
                        <table id="main-table" class="display nowrap">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Tgl Bayar</th>
                                    <th>No. Rawat</th>
                                    <th>No. RM</th>
                                    <th>Nama Pasien</th>
                                    <th>Penjab</th>
                                    <th>Nomor Nota</th>
                                    <th class="text-right">Rawat Jalan</th>
                                    <th class="text-right">Penunjang</th>
                                    <th class="text-right">Operasi</th>
                                    <th class="text-right">Lensa</th>
                                    <th class="text-right">Obat & BHP</th>
                                    <th class="text-right">Ranap</th>
                                    <th class="text-right">Narkose</th>
                                    <th class="text-right">Laboratorium</th>
                                    <th class="text-right">PPN Obat</th>
                                    <th class="text-right">Potongan</th>
                                    <th class="text-right">Sub Total</th>
                                    <th>Keterangan Potongan</th>
                                    <th>Dokter</th>
                                    <th>Nama Tindakan</th>
                                    <?php foreach ($akun_bayar_options as $ab) { ?>
                                        <th class="text-right"><?php echo htmlspecialchars($ab); ?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $totals = [
                                    'ralan' => 0, 'penunjang' => 0, 'operasi' => 0, 'lensa' => 0,
                                    'obat_bhp' => 0, 'ranap' => 0, 'narkose' => 0, 'laborat' => 0,
                                    'ppn_obat' => 0, 'potongan' => 0, 'sub_total' => 0,
                                    'bayar' => []
                                ];
                                $current_date = null;
                                $date_totals = [
                                    'ralan' => 0, 'penunjang' => 0, 'operasi' => 0, 'lensa' => 0,
                                    'obat_bhp' => 0, 'ranap' => 0, 'narkose' => 0, 'laborat' => 0,
                                    'ppn_obat' => 0, 'potongan' => 0, 'sub_total' => 0,
                                    'bayar' => []
                                ];
                                foreach ($akun_bayar_options as $ab) {
                                    $totals['bayar'][$ab] = 0;
                                    $date_totals['bayar'][$ab] = 0;
                                }

                                // Prepare subqueries to avoid database latency/redundancy for billing rows
                                // Query 1: Billing details for a specific no_rawat
                                $query_billing_sub = "SELECT 
                                                        Sum(CASE WHEN status = 'registrasi' THEN totalbiaya ELSE 0 END) as registrasi_total,
                                                        Sum(CASE WHEN status = 'operasi' AND nm_perawatan NOT LIKE '%Pemeriksaan NCT%' THEN totalbiaya ELSE 0 END) as operasi_total,
                                                        Sum(CASE WHEN status = 'operasi' AND nm_perawatan LIKE '%Pemeriksaan NCT%' THEN totalbiaya ELSE 0 END) as nct_total,
                                                        Sum(CASE WHEN status = 'obat' AND nm_perawatan LIKE '%lensa%' AND nm_perawatan <> 'PPN Obat' THEN totalbiaya ELSE 0 END) as lensa_total,
                                                        Sum(CASE WHEN status = 'obat' AND nm_perawatan NOT LIKE '%lensa%' AND nm_perawatan <> 'PPN Obat' THEN totalbiaya ELSE 0 END) as obat_bhp_total,
                                                        Sum(CASE WHEN status = 'kamar' THEN totalbiaya ELSE 0 END) as kamar_total,
                                                        Sum(CASE WHEN status = 'operasi' AND nm_perawatan LIKE '%narkose%' THEN totalbiaya ELSE 0 END) as narkose_total,
                                                        Sum(CASE WHEN status = 'Laborat' THEN totalbiaya ELSE 0 END) as laborat_total,
                                                        Sum(CASE WHEN status = 'obat' AND nm_perawatan = 'PPN Obat' THEN totalbiaya ELSE 0 END) as ppn_obat_total,
                                                        Sum(CASE WHEN status = 'Potongan' THEN totalbiaya ELSE 0 END) as potongan_total
                                                      FROM billing
                                                      WHERE no_rawat = ?";
                                $stmt_billing_sub = mysqli_prepare($koneksi, $query_billing_sub);

                                // Query 2: Outpatient treatments for a specific no_rawat
                                $query_ralan_sub = "SELECT 
                                                        Sum(CASE WHEN jns_perawatan.kd_kategori NOT IN ('PNJ01', 'KP042') THEN rawat_jl_drpr.biaya_rawat ELSE 0 END) as ralan_tindakan,
                                                        Sum(CASE WHEN jns_perawatan.kd_kategori = 'PNJ01' THEN rawat_jl_drpr.biaya_rawat ELSE 0 END) as penunjang,
                                                        Sum(CASE WHEN jns_perawatan.kd_kategori = 'KP042' THEN rawat_jl_drpr.biaya_rawat ELSE 0 END) as ralan_operasi
                                                      FROM rawat_jl_drpr
                                                      INNER JOIN jns_perawatan ON rawat_jl_drpr.kd_jenis_prw = jns_perawatan.kd_jenis_prw
                                                      WHERE rawat_jl_drpr.no_rawat = ?";
                                $stmt_ralan_sub = mysqli_prepare($koneksi, $query_ralan_sub);

                                // Query 3: Inpatient treatments for a specific no_rawat
                                $query_ranap_sub = "SELECT 
                                                        Sum(rawat_inap_drpr.biaya_rawat) as ranap_tindakan
                                                      FROM rawat_inap_drpr
                                                      WHERE rawat_inap_drpr.no_rawat = ?";
                                $stmt_ranap_sub = mysqli_prepare($koneksi, $query_ranap_sub);

                                // Query 4: Discount description for a specific no_rawat
                                $query_potongan_ket_sub = "SELECT GROUP_CONCAT(nama_pengurangan SEPARATOR ', ') as nama_pengurangan FROM pengurangan_biaya WHERE no_rawat = ?";
                                $stmt_potongan_ket_sub = mysqli_prepare($koneksi, $query_potongan_ket_sub);

                                // Query 5: Payment account details for outpatient
                                $query_nota_jl_sub = "SELECT nama_bayar, Sum(besar_bayar) as bayar FROM detail_nota_jalan WHERE no_rawat = ? GROUP BY nama_bayar";
                                $stmt_nota_jl_sub = mysqli_prepare($koneksi, $query_nota_jl_sub);

                                // Query 6: Payment account details for inpatient
                                $query_nota_in_sub = "SELECT nama_bayar, Sum(besar_bayar) as bayar FROM detail_nota_inap WHERE no_rawat = ? GROUP BY nama_bayar";
                                $stmt_nota_in_sub = mysqli_prepare($koneksi, $query_nota_in_sub);

                                // Query 7: Dokter
                                $query_dokter_sub = "SELECT dokter.nm_dokter 
                                                     FROM reg_periksa 
                                                     INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter 
                                                     WHERE reg_periksa.no_rawat = ?";
                                $stmt_dokter_sub = mysqli_prepare($koneksi, $query_dokter_sub);

                                // Query 8: Tindakan Operasi from billing
                                $query_tindakan_op_bill_sub = "SELECT GROUP_CONCAT(nm_perawatan SEPARATOR ', ') as tindakan_op 
                                                               FROM billing 
                                                               WHERE no_rawat = ? AND status = 'operasi' AND nm_perawatan NOT LIKE '%Pemeriksaan NCT%'";
                                $stmt_tindakan_op_bill_sub = mysqli_prepare($koneksi, $query_tindakan_op_bill_sub);

                                // Query 9: Tindakan Operasi from rawat_jl_drpr
                                $query_tindakan_op_ralan_sub = "SELECT GROUP_CONCAT(jns_perawatan.nm_perawatan SEPARATOR ', ') as tindakan_op 
                                                                FROM rawat_jl_drpr 
                                                                INNER JOIN jns_perawatan ON rawat_jl_drpr.kd_jenis_prw = jns_perawatan.kd_jenis_prw 
                                                                WHERE rawat_jl_drpr.no_rawat = ? AND jns_perawatan.kd_kategori = 'KP042'";
                                $stmt_tindakan_op_ralan_sub = mysqli_prepare($koneksi, $query_tindakan_op_ralan_sub);

                                foreach ($combined_rows as $row) {
                                    $row_bayar = [];
                                    foreach ($akun_bayar_options as $ab) {
                                        $row_bayar[$ab] = 0;
                                    }

                                    if ($row['type'] === 'billing') {
                                        $no_rawat = $row['no_rawat'];

                                        // 1. Fetch from billing table
                                        $registrasi_total = 0; $operasi_total = 0; $nct_total = 0; $lensa_total = 0;
                                        $obat_bhp_total = 0; $kamar_total = 0; $narkose_total = 0;
                                        $laborat_total = 0; $ppn_obat_total = 0; $potongan_total = 0;

                                        if ($stmt_billing_sub) {
                                            mysqli_stmt_bind_param($stmt_billing_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_billing_sub);
                                            $res_bill = mysqli_stmt_get_result($stmt_billing_sub);
                                            if ($r_bill = mysqli_fetch_assoc($res_bill)) {
                                                $registrasi_total = $r_bill['registrasi_total'] ?? 0;
                                                $operasi_total = $r_bill['operasi_total'] ?? 0;
                                                $nct_total = $r_bill['nct_total'] ?? 0;
                                                $lensa_total = $r_bill['lensa_total'] ?? 0;
                                                $obat_bhp_total = $r_bill['obat_bhp_total'] ?? 0;
                                                $kamar_total = $r_bill['kamar_total'] ?? 0;
                                                $narkose_total = $r_bill['narkose_total'] ?? 0;
                                                $laborat_total = $r_bill['laborat_total'] ?? 0;
                                                $ppn_obat_total = $r_bill['ppn_obat_total'] ?? 0;
                                                $potongan_total = $r_bill['potongan_total'] ?? 0;
                                            }
                                        }

                                        // 2. Fetch from rawat_jl_drpr
                                        $ralan_tindakan = 0;
                                        $penunjang = 0;
                                        $ralan_operasi = 0;
                                        if ($stmt_ralan_sub) {
                                            mysqli_stmt_bind_param($stmt_ralan_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_ralan_sub);
                                            $res_ralan = mysqli_stmt_get_result($stmt_ralan_sub);
                                            if ($r_ralan = mysqli_fetch_assoc($res_ralan)) {
                                                $ralan_tindakan = $r_ralan['ralan_tindakan'] ?? 0;
                                                $penunjang = $r_ralan['penunjang'] ?? 0;
                                                $ralan_operasi = $r_ralan['ralan_operasi'] ?? 0;
                                            }
                                        }

                                        // 3. Fetch from rawat_inap_drpr
                                        $ranap_tindakan = 0;
                                        if ($stmt_ranap_sub) {
                                            mysqli_stmt_bind_param($stmt_ranap_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_ranap_sub);
                                            $res_ranap = mysqli_stmt_get_result($stmt_ranap_sub);
                                            if ($r_ranap = mysqli_fetch_assoc($res_ranap)) {
                                                $ranap_tindakan = $r_ranap['ranap_tindakan'] ?? 0;
                                            }
                                        }

                                        // 4. Fetch discount description from pengurangan_biaya
                                        $ket_potongan = '';
                                        if ($stmt_potongan_ket_sub) {
                                            mysqli_stmt_bind_param($stmt_potongan_ket_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_potongan_ket_sub);
                                            $res_ket = mysqli_stmt_get_result($stmt_potongan_ket_sub);
                                            if ($r_ket = mysqli_fetch_assoc($res_ket)) {
                                                $ket_potongan = $r_ket['nama_pengurangan'] ?? '';
                                            }
                                        }

                                        // 5. Fetch payment account details from detail_nota_jalan
                                        if ($stmt_nota_jl_sub) {
                                            mysqli_stmt_bind_param($stmt_nota_jl_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_nota_jl_sub);
                                            $res_njl = mysqli_stmt_get_result($stmt_nota_jl_sub);
                                            while ($r_njl = mysqli_fetch_assoc($res_njl)) {
                                                $nm_b = $r_njl['nama_bayar'];
                                                if (isset($row_bayar[$nm_b])) {
                                                    $row_bayar[$nm_b] += (float)$r_njl['bayar'];
                                                }
                                            }
                                        }

                                        // 6. Fetch payment account details from detail_nota_inap
                                        if ($stmt_nota_in_sub) {
                                            mysqli_stmt_bind_param($stmt_nota_in_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_nota_in_sub);
                                            $res_nin = mysqli_stmt_get_result($stmt_nota_in_sub);
                                            while ($r_nin = mysqli_fetch_assoc($res_nin)) {
                                                $nm_b = $r_nin['nama_bayar'];
                                                if (isset($row_bayar[$nm_b])) {
                                                    $row_bayar[$nm_b] += (float)$r_nin['bayar'];
                                                }
                                            }
                                        }

                                        // 7. Fetch Dokter
                                        $nm_dokter = '-';
                                        if ($stmt_dokter_sub) {
                                            mysqli_stmt_bind_param($stmt_dokter_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_dokter_sub);
                                            $res_dokter = mysqli_stmt_get_result($stmt_dokter_sub);
                                            if ($r_dokter = mysqli_fetch_assoc($res_dokter)) {
                                                $nm_dokter = $r_dokter['nm_dokter'] ?? '-';
                                            }
                                        }

                                        // 8 & 9. Fetch Tindakan Operasi
                                        $tindakan_op_arr = [];
                                        if ($stmt_tindakan_op_bill_sub) {
                                            mysqli_stmt_bind_param($stmt_tindakan_op_bill_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_tindakan_op_bill_sub);
                                            $res_topb = mysqli_stmt_get_result($stmt_tindakan_op_bill_sub);
                                            if ($r_topb = mysqli_fetch_assoc($res_topb)) {
                                                if (!empty($r_topb['tindakan_op'])) {
                                                    $tindakan_op_arr[] = $r_topb['tindakan_op'];
                                                }
                                            }
                                        }

                                        if ($stmt_tindakan_op_ralan_sub) {
                                            mysqli_stmt_bind_param($stmt_tindakan_op_ralan_sub, "s", $no_rawat);
                                            mysqli_stmt_execute($stmt_tindakan_op_ralan_sub);
                                            $res_topr = mysqli_stmt_get_result($stmt_tindakan_op_ralan_sub);
                                            if ($r_topr = mysqli_fetch_assoc($res_topr)) {
                                                if (!empty($r_topr['tindakan_op'])) {
                                                    $tindakan_op_arr[] = $r_topr['tindakan_op'];
                                                }
                                            }
                                        }
                                        $tindakan_operasi_str = empty($tindakan_op_arr) ? '-' : implode(', ', $tindakan_op_arr);

                                        // Calculate columns based on rules
                                        $col_rawat_jalan = $registrasi_total + $ralan_tindakan;
                                        $col_pelayanan_penunjang = $penunjang + $nct_total;
                                        $col_operasi = $operasi_total + $ralan_operasi;
                                        $col_lensa = $lensa_total;
                                        $col_obat_bhp = $obat_bhp_total;
                                        $col_ranap = $kamar_total + $ranap_tindakan;
                                        $col_narkose = $narkose_total;
                                        $col_laboratorium = $laborat_total;
                                        $col_ppn_obat = $ppn_obat_total;
                                        $col_potongan = $potongan_total;

                                        $col_subtotal = ($col_rawat_jalan + $col_pelayanan_penunjang + $col_operasi + $col_lensa + 
                                                         $col_obat_bhp + $col_ranap + $col_narkose + $col_laboratorium + $col_ppn_obat) + $col_potongan;
                                    } else {
                                        // Penjualan Bebas Row
                                        $col_rawat_jalan = 0;
                                        $col_pelayanan_penunjang = 0;
                                        $col_operasi = 0;
                                        $col_lensa = 0;
                                        $col_obat_bhp = $row['total_obat_bhp'];
                                        $col_ranap = 0;
                                        $col_narkose = 0;
                                        $col_laboratorium = 0;
                                        $col_ppn_obat = $row['ppn'];
                                        $col_potongan = 0;
                                        $col_subtotal = $col_obat_bhp + $col_ppn_obat;
                                        $ket_potongan = '';
                                        $nm_dokter = '-';
                                        $tindakan_operasi_str = '-';

                                        $nm_b = $row['nama_bayar'] ?? '';
                                        if (isset($row_bayar[$nm_b])) {
                                            $row_bayar[$nm_b] = $col_subtotal;
                                        }
                                    }

                                    $tgl_byr = $row['tgl_byr'];
                                    if ($current_date !== null && $current_date !== $tgl_byr) {
                                        // Output subtotal row for the previous date
                                        echo "<tr class='subtotal-row' style='background-color: #f1f5f9; font-weight: 700; border-top: 2px solid var(--border); border-bottom: 2px solid var(--border);'>
                                                <td colspan='7' class='text-center'>SUBTOTAL TANGGAL " . htmlspecialchars($current_date) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['ralan']) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['penunjang']) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['operasi']) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['lensa']) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['obat_bhp']) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['ranap']) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['narkose']) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['laborat']) . "</td>
                                                <td class='text-right'>" . formatRupiah($date_totals['ppn_obat']) . "</td>
                                                <td class='text-right' style='color: var(--danger);'>" . formatRupiah($date_totals['potongan']) . "</td>
                                                <td class='text-right' style='color: var(--primary);'>" . formatRupiah($date_totals['sub_total']) . "</td>
                                                <td></td><td></td><td></td>";
                                        foreach ($akun_bayar_options as $ab) {
                                            echo "<td class='text-right' style='color: var(--primary);'>" . formatRupiah($date_totals['bayar'][$ab] ?? 0) . "</td>";
                                        }
                                        echo "</tr>";
                                              
                                        // Reset date totals
                                        foreach ($date_totals as $k => $v) {
                                            if ($k === 'bayar') {
                                                foreach ($akun_bayar_options as $ab) {
                                                    $date_totals['bayar'][$ab] = 0;
                                                }
                                            } else {
                                                $date_totals[$k] = 0;
                                            }
                                        }
                                    }
                                    
                                    $current_date = $tgl_byr;

                                    // Accumulate date totals
                                    $date_totals['ralan'] += $col_rawat_jalan;
                                    $date_totals['penunjang'] += $col_pelayanan_penunjang;
                                    $date_totals['operasi'] += $col_operasi;
                                    $date_totals['lensa'] += $col_lensa;
                                    $date_totals['obat_bhp'] += $col_obat_bhp;
                                    $date_totals['ranap'] += $col_ranap;
                                    $date_totals['narkose'] += $col_narkose;
                                    $date_totals['laborat'] += $col_laboratorium;
                                    $date_totals['ppn_obat'] += $col_ppn_obat;
                                    $date_totals['potongan'] += $col_potongan;
                                    $date_totals['sub_total'] += $col_subtotal;

                                    // Accumulate column totals
                                    $totals['ralan'] += $col_rawat_jalan;
                                    $totals['penunjang'] += $col_pelayanan_penunjang;
                                    $totals['operasi'] += $col_operasi;
                                    $totals['lensa'] += $col_lensa;
                                    $totals['obat_bhp'] += $col_obat_bhp;
                                    $totals['ranap'] += $col_ranap;
                                    $totals['narkose'] += $col_narkose;
                                    $totals['laborat'] += $col_laboratorium;
                                    $totals['ppn_obat'] += $col_ppn_obat;
                                    $totals['potongan'] += $col_potongan;
                                    $totals['sub_total'] += $col_subtotal;

                                    foreach ($akun_bayar_options as $ab) {
                                        $val = $row_bayar[$ab] ?? 0;
                                        $date_totals['bayar'][$ab] += $val;
                                        $totals['bayar'][$ab] += $val;
                                    }
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['tgl_byr']); ?></td>
                                        <td><span class="badge"><?php echo htmlspecialchars($row['no_rawat']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                        <td><?php echo htmlspecialchars($row['png_jawab']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nm_perawatan']); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_rawat_jalan); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_pelayanan_penunjang); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_operasi); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_lensa); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_obat_bhp); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_ranap); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_narkose); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_laboratorium); ?></td>
                                        <td class="text-right"><?php echo formatRupiah($col_ppn_obat); ?></td>
                                        <td class="text-right" style="color: var(--danger);"><?php echo formatRupiah($col_potongan); ?></td>
                                        <td class="text-right" style="font-weight: 600; color: var(--primary);"><?php echo formatRupiah($col_subtotal); ?></td>
                                        <td><?php echo htmlspecialchars($ket_potongan); ?></td>
                                        <td><?php echo htmlspecialchars($nm_dokter); ?></td>
                                        <td><?php echo htmlspecialchars($tindakan_operasi_str); ?></td>
                                        <?php foreach ($akun_bayar_options as $ab) { ?>
                                            <td class="text-right"><?php echo formatRupiah($row_bayar[$ab] ?? 0); ?></td>
                                        <?php } ?>
                                    </tr>
                                <?php
                                }

                                if ($current_date !== null) {
                                    echo "<tr class='subtotal-row' style='background-color: #f1f5f9; font-weight: 700; border-top: 2px solid var(--border); border-bottom: 2px solid var(--border);'>
                                            <td colspan='7' class='text-center'>SUBTOTAL TANGGAL " . htmlspecialchars($current_date) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['ralan']) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['penunjang']) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['operasi']) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['lensa']) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['obat_bhp']) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['ranap']) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['narkose']) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['laborat']) . "</td>
                                            <td class='text-right'>" . formatRupiah($date_totals['ppn_obat']) . "</td>
                                            <td class='text-right' style='color: var(--danger);'>" . formatRupiah($date_totals['potongan']) . "</td>
                                            <td class='text-right' style='color: var(--primary);'>" . formatRupiah($date_totals['sub_total']) . "</td>
                                            <td></td><td></td><td></td>";
                                    foreach ($akun_bayar_options as $ab) {
                                        echo "<td class='text-right' style='color: var(--primary);'>" . formatRupiah($date_totals['bayar'][$ab] ?? 0) . "</td>";
                                    }
                                    echo "</tr>";
                                }

                                if ($stmt_billing_sub) mysqli_stmt_close($stmt_billing_sub);
                                if ($stmt_ralan_sub) mysqli_stmt_close($stmt_ralan_sub);
                                if ($stmt_ranap_sub) mysqli_stmt_close($stmt_ranap_sub);
                                if ($stmt_potongan_ket_sub) mysqli_stmt_close($stmt_potongan_ket_sub);
                                if ($stmt_nota_jl_sub) mysqli_stmt_close($stmt_nota_jl_sub);
                                if ($stmt_nota_in_sub) mysqli_stmt_close($stmt_nota_in_sub);
                                if ($stmt_dokter_sub) mysqli_stmt_close($stmt_dokter_sub);
                                if ($stmt_tindakan_op_bill_sub) mysqli_stmt_close($stmt_tindakan_op_bill_sub);
                                if ($stmt_tindakan_op_ralan_sub) mysqli_stmt_close($stmt_tindakan_op_ralan_sub);
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="7" class="text-center">GRAND TOTAL</th>
                                    <th class="text-right"><?php echo formatRupiah($totals['ralan']); ?></th>
                                    <th class="text-right"><?php echo formatRupiah($totals['penunjang']); ?></th>
                                    <th class="text-right"><?php echo formatRupiah($totals['operasi']); ?></th>
                                    <th class="text-right"><?php echo formatRupiah($totals['lensa']); ?></th>
                                    <th class="text-right"><?php echo formatRupiah($totals['obat_bhp']); ?></th>
                                    <th class="text-right"><?php echo formatRupiah($totals['ranap']); ?></th>
                                    <th class="text-right"><?php echo formatRupiah($totals['narkose']); ?></th>
                                    <th class="text-right"><?php echo formatRupiah($totals['laborat']); ?></th>
                                    <th class="text-right"><?php echo formatRupiah($totals['ppn_obat']); ?></th>
                                    <th class="text-right" style="color: white; background: linear-gradient(135deg, #ef4444, #dc2626);"><?php echo formatRupiah($totals['potongan']); ?></th>
                                    <th class="text-right" style="color: white; background: linear-gradient(135deg, #0284c7, #0369a1); font-weight: bold;"><?php echo formatRupiah($totals['sub_total']); ?></th>
                                    <th></th><th></th><th></th>
                                    <?php foreach ($akun_bayar_options as $ab) { ?>
                                        <th class="text-right" style="color: white; background: linear-gradient(135deg, #0284c7, #0369a1); font-weight: bold;"><?php echo formatRupiah($totals['bayar'][$ab] ?? 0); ?></th>
                                    <?php } ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
            <?php
                } else {
                    echo '<div class="no-data">
                            <i class="fas fa-folder-open"></i>
                            <h3>Data Tidak Ditemukan</h3>
                            <p>Tidak ada transaksi billing atau penjualan bebas yang sesuai pada periode tanggal dan penjab yang dipilih.</p>
                          </div>';
                }
            mysqli_close($koneksi);
            ?>
        </div>
    </div>

    <!-- DataTables & Dependencies scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#main-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copyHtml5',
                        text: '<i class="fas fa-copy"></i> Salin',
                        titleAttr: 'Salin data ke clipboard'
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        titleAttr: 'Ekspor ke file Excel',
                        title: 'Laporan Pendapatan Berdasarkan Billing (<?php echo $tgl_awal; ?> s.d <?php echo $tgl_akhir; ?>)'
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        titleAttr: 'Ekspor ke PDF',
                        orientation: 'landscape',
                        pageSize: 'A3',
                        title: 'Laporan Pendapatan Berdasarkan Billing (<?php echo $tgl_awal; ?> s.d <?php echo $tgl_akhir; ?>)',
                        customize: function(doc) {
                            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                            doc.styles.tableHeader.fillColor = '#0284c7';
                            doc.styles.tableHeader.color = 'white';
                        }
                    }
                ],
                stateSave: true,
                paging: true,
                pageLength: 50,
                ordering: false, // disabled to keep chronological subtotal grouping intact
                responsive: false,
                scrollX: true,
                drawCallback: function(settings) {
                    var api = this.api();
                    var pageInfo = api.page.info();
                    var select = $('#custom-page-select');
                    select.empty();
                    
                    // Show or hide custom pagination controls depending on content size
                    if (pageInfo.recordsTotal <= pageInfo.length) {
                        $('#custom-pagination').hide();
                    } else {
                        $('#custom-pagination').show();
                        
                        for (var i = 0; i < pageInfo.pages; i++) {
                            var option = $('<option></option>')
                                .attr('value', i)
                                .text('Halaman ' + (i + 1) + ' dari ' + pageInfo.pages);
                            if (i === pageInfo.page) {
                                option.attr('selected', 'selected');
                            }
                            select.append(option);
                        }
                    }
                },
                language: {
                    search: "Cari data:",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                    zeroRecords: "Tidak ada data yang cocok ditemukan",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });

            // Bind handlers for custom pagination controls
            $('#custom-page-length').on('change', function() {
                var len = parseInt($(this).val());
                table.page.len(len).draw();
            });

            $('#custom-page-select').on('change', function() {
                var p = parseInt($(this).val());
                table.page(p).draw('page');
            });
        });

        function resetForm() {
            document.getElementById('tanggal_awal').value = "<?php echo date('Y-m-d'); ?>";
            document.getElementById('tanggal_akhir').value = "<?php echo date('Y-m-d'); ?>";
            document.getElementById('kd_pj').value = "";
        }

        function copyToClipboard() {
            const table = document.getElementById('main-table');
            if (table) {
                const range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                window.getSelection().removeAllRanges();
                
                // Show notification
                alert('📋 Data berhasil disalin ke clipboard!');
            } else {
                alert('⚠️ Tidak ada data untuk disalin!');
            }
        }
    </script>

