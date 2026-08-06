<?php

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


            <form method="GET" action="index.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <input type="hidden" name="page" value="umbal">
                
                <div class="form-group" style="margin:0;">
                    <label class="form-label" for="bulanklaim">Bulan Klaim:</label>
                    <select name="bulanklaim" id="bulanklaim" class="form-control" required>
                        <option value="">-- Pilih Bulan --</option>
                        <?php
                        $query_bulan = "SELECT DISTINCT bulanklaim FROM rspsw_umbal ORDER BY bulanklaim DESC";
                        $result_bulan = mysqli_query($koneksi, $query_bulan);
                        while ($row_bulan = mysqli_fetch_assoc($result_bulan)) {
                            $selected = (isset($_GET['bulanklaim']) && $_GET['bulanklaim'] == $row_bulan['bulanklaim']) ? 'selected' : '';
                            echo "<option value='{$row_bulan['bulanklaim']}' $selected>{$row_bulan['bulanklaim']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin:0;">
                    <label class="form-label" for="nm_dokter">Nama Dokter:</label>
                    <select name="nm_dokter" id="nm_dokter" class="form-control">
                        <option value="">-- Semua Dokter --</option>
                        <?php
                        $query_dokter = "SELECT DISTINCT DPJP FROM inacbg_unencrypted WHERE DPJP IS NOT NULL AND DPJP != '' ORDER BY DPJP";
                        $result_dokter = mysqli_query($koneksi, $query_dokter);
                        while ($row_dokter = mysqli_fetch_assoc($result_dokter)) {
                            $selected = (isset($_GET['nm_dokter']) && $_GET['nm_dokter'] == $row_dokter['DPJP']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row_dokter['DPJP']) . "' $selected>" . htmlspecialchars($row_dokter['DPJP']) . "</option>";
                        }
                        ?>
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
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    if (isset($_GET['bulanklaim']) && !empty($_GET['bulanklaim'])) {
        $bulanklaim = mysqli_real_escape_string($koneksi, $_GET['bulanklaim']);
        $nm_dokter = isset($_GET['nm_dokter']) ? mysqli_real_escape_string($koneksi, $_GET['nm_dokter']) : '';

        $query = "SELECT 
                    rspsw_umbal.no_sep,
                    rspsw_umbal.no_rawat,
                    CASE 
                        WHEN nota_inap.no_rawat IS NOT NULL THEN nota_inap.no_nota
                        WHEN nota_jalan.no_rawat IS NOT NULL THEN nota_jalan.no_nota
                        ELSE ''
                    END as no_nota,
                    inacbg_unencrypted.ADMISSION_DATE as tgl_registrasi,
                    inacbg_unencrypted.NAMA_PASIEN as nm_pasien,
                    inacbg_unencrypted.MRN as no_rkm_medis,
                    inacbg_unencrypted.DPJP as nm_dokter,
                    inacbg_unencrypted.DIAGLIST as diagnosa,
                    inacbg_unencrypted.PROCLIST,
                    lec_kelompok_prosedur.prosedur,
                    inacbg_unencrypted.TOTAL_TARIF as diajukan,
                    inacbg_unencrypted.TARIF_INACBG as disetujui,
                    inacbg_unencrypted.PTD
                FROM 
                    inacbg_unencrypted
                    INNER JOIN rspsw_umbal ON inacbg_unencrypted.SEP = rspsw_umbal.no_sep
                    LEFT JOIN lec_kelompok_prosedur ON LEFT(inacbg_unencrypted.PROCLIST, 5) = lec_kelompok_prosedur.kd_prosedur
                    LEFT JOIN nota_inap ON rspsw_umbal.no_rawat = nota_inap.no_rawat
                    LEFT JOIN nota_jalan ON rspsw_umbal.no_rawat = nota_jalan.no_rawat
                WHERE
                    rspsw_umbal.bulanklaim = '$bulanklaim'";

        if (!empty($nm_dokter)) {
            $query .= " AND inacbg_unencrypted.DPJP = '$nm_dokter'";
        }

        $result = mysqli_query($koneksi, $query);

        if (mysqli_num_rows($result) > 0) {
            echo "<div class='table-responsive'>
                  <table id='data-table' class='display nowrap' style='width:100%'>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No SEP</th>
                            <th>No Rawat</th>
                            <th>No Nota</th>
                            <th>Tgl Registrasi</th>
                            <th>Nama Pasien</th>                            
                            <th>Nomor RM</th>
                            <th>Nama Dokter</th>
                            <th>Diagnosa</th>
                            <th>Prosedur</th>
                            <th>Diajukan</th>
                            <th>Disetujui</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>";
            $no = 1;
            $total_diajukan = 0;
            $total_disetujui = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $status = '';
                if ($row['PTD'] == 1 || $row['PTD'] == '1') {
                    $status = 'RANAP';
                } elseif ($row['PTD'] == 2 || $row['PTD'] == '2') {
                    $status = 'RALAN';
                } else {
                    $status = $row['PTD'];
                }

                $prosedur_text = !empty($row['prosedur']) ? $row['prosedur'] : $row['PROCLIST'];

                echo "<tr>
                        <td>{$no}</td>
                        <td>{$row['no_sep']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_nota']}</td>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_dokter']}</td>
                        <td>{$row['diagnosa']}</td>
                        <td>{$prosedur_text}</td>
                        <td>" . formatRupiah($row['diajukan']) . "</td>
                        <td>" . formatRupiah($row['disetujui']) . "</td>
                        <td>{$status}</td>
                    </tr>";
                $no++;
                $total_diajukan += $row['diajukan'];
                $total_disetujui += $row['disetujui'];
            }

            echo "</tbody>
                <tfoot>
                    <tr>
                        <th colspan='10'>Total</th>
                        <th>" . formatRupiah($total_diajukan) . "</th>
                        <th>" . formatRupiah($total_disetujui) . "</th>
                        <th></th>
                    </tr>
                </tfoot>
                </table>
                </div>";
        } else {
            echo "<div class='no-data'>📊 Data tidak ditemukan untuk periode yang dipilih.</div>";
        }
    }
    ?>

        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#data-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copyHtml5',
                    {
                        extend: 'pdfHtml5',
                        orientation: 'landscape',
                        pageSize: 'A3',
                        title: 'Data Klaim RS Lampung Eye Center Periode: <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?>',
                        customize: function(doc) {
                            doc.content.unshift({
                                text: 'LAMPUNG EYE CENTER\nRINCIAN PASIEN BPJS\nPeriode: <?php echo isset($_GET['bulanklaim']) ? $_GET['bulanklaim'] : ''; ?>\nDokter: <?php echo isset($_GET['nm_dokter']) && $_GET['nm_dokter'] != '' ? $_GET['nm_dokter'] : 'Semua Dokter'; ?>',
                                fontSize: 14,
                                bold: true,
                                margin: [0, 0, 0, 12]
                            });
                        }
                    }
                ],
                paging: false,
                responsive: false,
                scrollX: true
            });
        });

        // Function to reset form
        function resetForm() {
            document.getElementById('bulanklaim').value = '';
            document.getElementById('nm_dokter').value = '';
        }

        // Function to copy table data to clipboard
        function copyToClipboard() {
            const table = document.getElementById('data-table');
            if (table) {
                const range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand('copy');
                    alert('📋 Data berhasil disalin ke clipboard!');
                } catch (err) {
                    alert('❌ Gagal menyalin data ke clipboard!');
                }
                window.getSelection().removeAllRanges();
            } else {
                alert('⚠️ Tidak ada data untuk disalin!');
            }
        }
    </script>

