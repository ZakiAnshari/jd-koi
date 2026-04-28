<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Transaksi Koi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            width: 100%;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1e7e34;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #1e7e34;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .page-break {
            page-break-after: always;
            margin: 40px 0;
        }
        .koi-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            background-color: #f9f9f9;
        }
        .koi-header {
            border-bottom: 2px solid #1e7e34;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .koi-code {
            font-size: 18px;
            font-weight: bold;
            color: #1e7e34;
        }
        .koi-name {
            font-size: 14px;
            color: #666;
            margin-top: 3px;
        }
        .koi-content {
            display: flex;
            gap: 20px;
        }
        .koi-images {
            flex: 0 0 250px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .koi-image {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            background-color: #fff;
            text-align: center;
        }
        .koi-image img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .image-placeholder {
            width: 100%;
            height: 200px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 12px;
        }
        .koi-details {
            flex: 1;
        }
        .details-section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            background-color: #e7f3e7;
            padding: 8px 12px;
            border-left: 3px solid #1e7e34;
            margin-bottom: 10px;
            color: #1e7e34;
        }
        .detail-row {
            display: flex;
            padding: 6px 0;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            width: 150px;
            font-weight: 500;
            color: #555;
        }
        .detail-value {
            flex: 1;
            color: #333;
            word-break: break-word;
        }
        .detail-value.highlight {
            color: #1e7e34;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-shipped {
            background-color: #cce5ff;
            color: #004085;
        }
        .badge-azukari {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-dead {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge-cancel {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .badge-refund {
            background-color: #ffe5cc;
            color: #b85e0c;
        }
        .badge-dead-refund {
            background-color: #f5c6cb;
            color: #721c24;
        }
        .badge-cancel-refund {
            background-color: #d3d4d6;
            color: #383d41;
        }
        .price-highlight {
            background-color: #f0f7ff;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #0066cc;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            color: #999;
        }
        @page {
            margin: 80px 30px 80px 30px;
        }
        @page :first {
            margin-top: 30px;
        }
        body::before {
            content: '';
        }
        .container {
            width: 100%;
            padding: 20px;
            position: relative;
        }
        .page-counter {
            position: fixed;
            bottom: 20px;
            right: 30px;
            font-size: 10px;
            color: #999;
            text-align: right;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            .page-break {
                page-break-after: always;
                margin: 40px 0;
            }
            .container {
                padding: 0;
                margin: 0;
            }
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .page-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            font-size: 11px;
            color: #666;
        }
        .repeating-footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #999;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Rekap Transaksi Koi</h1>
            <p style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 13px;">
                <strong>Tanggal Cetak:</strong> <?php echo e(now()->format('d-m-Y H:i')); ?> | 
                <strong>Total Item:</strong> <?php echo e(count($kois)); ?> koi
            </p>
        </div>

        <!-- Summary Page -->
        <div style="background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="color: #1e7e34; margin-bottom: 15px; font-size: 18px;">📊 Ringkasan Dokumen</h2>
            <table style="width: 100%; font-size: 12px; line-height: 1.8;">
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; width: 40%; font-weight: bold; color: #333;">Nama Dokumen:</td>
                    <td style="padding: 8px;">Rekap Transaksi Koi</td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: bold; color: #333;">Tanggal Cetak:</td>
                    <td style="padding: 8px;"><?php echo e(now()->format('d-m-Y H:i:s')); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: bold; color: #333;">Total Item:</td>
                    <td style="padding: 8px;"><strong style="color: #1e7e34; font-size: 14px;"><?php echo e(count($kois)); ?> koi</strong></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; font-weight: bold; color: #333;">Diunduh oleh:</td>
                    <td style="padding: 8px;"><?php echo e($user->name ?? 'Pengguna'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold; color: #333;">Sistem:</td>
                    <td style="padding: 8px;">Koi Umeda Data Management System</td>
                </tr>
            </table>
            <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 11px; color: #666;">
                <strong>Catatan:</strong> Dokumen ini berisi informasi lengkap <?php echo e(count($kois)); ?> transaksi koi termasuk detail koi, informasi penjualan, dan harga.
            </p>
        </div>

        <div class="page-break"></div>

        <?php $__empty_1 = true; $__currentLoopData = $kois; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $koi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php if($index > 0 && $index % 2 == 0): ?>
                <div class="page-break"></div>
            <?php endif; ?>

            <div class="koi-item">
                <!-- Header -->
                <div class="koi-header">
                    <div class="koi-code"><?php echo e($koi->code); ?></div>
                    <div class="koi-name"><?php echo e($koi->name); ?></div>
                </div>

                <!-- Content -->
                <div class="koi-content">
                    <!-- Images -->
                    <div class="koi-images">
                        <div class="koi-image">
                            <div class="image-placeholder">Gambar tidak ditampilkan dalam PDF</div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="koi-details">
                        <!-- Info Dasar Koi -->
                        <div class="details-section">
                            <div class="section-title">📌 Informasi Koi</div>
                            <div class="detail-row">
                                <div class="detail-label">Jenis:</div>
                                <div class="detail-value"><?php echo e($koi->jenisKoi?->nama_jenis ?? '-'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Breeder:</div>
                                <div class="detail-value"><?php echo e($koi->breeder?->name_breeder ?? '-'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Umur:</div>
                                <div class="detail-value"><?php echo e($koi->age ? $koi->age . ' bulan' : '-'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Ukuran:</div>
                                <div class="detail-value"><?php echo e($koi->size ? $koi->size . ' cm' : '-'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Berat:</div>
                                <div class="detail-value"><?php echo e($koi->weight ? $koi->weight . ' kg' : '-'); ?></div>
                            </div>
                        </div>

                        <!-- Info Penjualan -->
                        <div class="details-section">
                            <div class="section-title">🛒 Informasi Penjualan</div>
                            <div class="detail-row">
                                <div class="detail-label">Invoice:</div>
                                <div class="detail-value highlight"><?php echo e($koi->sale_invoice ?? '-'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Tanggal:</div>
                                <div class="detail-value"><?php echo e($koi->sale_tanggal?->format('d-m-Y') ?? '-'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Status:</div>
                                <div class="detail-value">
                                    <?php
                                        $statusMap = [
                                            'pending' => 'badge-pending',
                                            'shipped' => 'badge-shipped',
                                            'azukari' => 'badge-azukari',
                                            'dead' => 'badge-dead',
                                            'cancel' => 'badge-cancel',
                                            'refund' => 'badge-refund',
                                            'dead_refund' => 'badge-dead-refund',
                                            'cancel_refund' => 'badge-cancel-refund',
                                        ];
                                        $statusLabel = ucfirst(str_replace('_', ' ', $koi->sale_status));
                                    ?>
                                    <span class="badge <?php echo e($statusMap[$koi->sale_status] ?? ''); ?>"><?php echo e($statusLabel); ?></span>
                                </div>
                            </div>
                            <?php if($isAdmin && $koi->customer): ?>
                                <div class="detail-row">
                                    <div class="detail-label">Pelanggan:</div>
                                    <div class="detail-value"><?php echo e($koi->customer->name); ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <div class="detail-label">Qty:</div>
                                <div class="detail-value"><?php echo e($koi->sale_qty ?? '-'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Box Qty:</div>
                                <div class="detail-value"><?php echo e($koi->box_qty ?? '-'); ?></div>
                            </div>
                        </div>

                        <!-- Info Harga -->
                        <div class="details-section">
                            <div class="section-title">💰 Informasi Harga</div>
                            <div class="price-highlight">
                                <div class="detail-row">
                                    <div class="detail-label">Harga JPY:</div>
                                    <div class="detail-value">¥ <?php echo e($koi->sell_jpy ? number_format($koi->sell_jpy, 0, ',', '.') : '-'); ?></div>
                                </div>
                                <?php if($koi->kurs && $koi->kurs > 0): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Kurs:</div>
                                        <div class="detail-value">Rp <?php echo e(number_format($koi->kurs, 0, ',', '.')); ?></div>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-row">
                                    <div class="detail-label">Harga IDR:</div>
                                    <div class="detail-value">Rp <?php echo e($koi->sell_idr ? number_format($koi->sell_idr, 0, ',', '.') : '-'); ?></div>
                                </div>
                                <?php if($isAdmin && $koi->fee): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Fee:</div>
                                        <div class="detail-value">Rp <?php echo e(number_format($koi->fee, 0, ',', '.')); ?></div>
                                    </div>
                                    <div class="detail-row" style="border-top: 1px solid #0066cc; padding-top: 8px; margin-top: 8px;">
                                        <div class="detail-label" style="font-weight: bold;">Profit:</div>
                                        <div class="detail-value" style="font-weight: bold; color: #0066cc;">Rp <?php echo e(number_format($koi->sell_idr - $koi->fee, 0, ',', '.')); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                Tidak ada data transaksi untuk diunduh.
            </div>
        <?php endif; ?>

        <div class="repeating-footer-content">
            <table style="width: 100%; margin-top: 20px; font-size: 11px; color: #666;">
                <tr>
                    <td style="text-align: left;">Koi Umeda Data Management System</td>
                    <td style="text-align: center;">Halaman: <span class="page"></span></td>
                    <td style="text-align: right;"><?php echo e(now()->format('d/m/Y H:i')); ?></td>
                </tr>
            </table>
        </div>
        <div class="footer">
            <p style="margin-top: 10px;">Dokumen ini dihasilkan secara otomatis dari Sistem Manajemen Data Koi Umeda</p>
            <p>© <?php echo e(now()->year); ?> - Koi Umeda Data Management System</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\laragon\www\koi-umeda-data-management\resources\views/pdf/rekap-transaksi.blade.php ENDPATH**/ ?>