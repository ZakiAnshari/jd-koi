<table>
    
    <tr>
        <td style="background-color:#1a1a1a; vertical-align:middle; padding:4px;" colspan="1">
        </td>
        <td style="background-color:#1a1a1a; color:#ffffff; font-size:20pt; font-weight:bold; vertical-align:middle;" colspan="3">
            JAPAN DIRECT KOI CENTER
        </td>
        <td style="background-color:#1a1a1a; color:#c0392b; font-size:22pt; font-weight:bold; text-align:right; vertical-align:middle;" colspan="3">
            INVOICE
        </td>
    </tr>
    
    <tr>
        <td style="background-color:#1a1a1a; color:#aaaaaa; font-size:10pt; text-align:center;" colspan="4">
            Citeko, Cisarua, Puncak · +6287868882200
        </td>
        <td style="background-color:#1a1a1a; color:#ffffff; font-size:14pt; font-weight:bold; text-align:right;" colspan="3">
            <?php echo e($invoiceNumber); ?>

        </td>
    </tr>
    
    <tr><td colspan="7" style="background-color:#c0392b; height:4px;"></td></tr>
    
    <tr>
        <td style="font-weight:bold; font-size:11pt; padding:6px;" colspan="4">
            Invoice to: <?php echo e($customerName); ?>

        </td>
        <td style="font-size:11pt; text-align:right; text-decoration:underline; color:#c0392b; font-weight:bold;" colspan="3">
            <?php echo e(\Carbon\Carbon::now()->format('d F Y')); ?>

        </td>
    </tr>
    
    <tr><td colspan="7"></td></tr>
    
    <tr>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:30px;">No.</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:300px;" colspan="2">Item Description</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:40px;">Qty</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:100px;">Price JPY</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:120px;">Price IDR</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:120px;">Total</td>
    </tr>
    
    <?php $__currentLoopData = $kois; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $koi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $bg      = $index % 2 === 0 ? '#ffffff' : '#f9f9f9';
        $qty     = max(1, intval($koi->sale_qty ?? 1));
        $unitJpy = floatval($koi->unit_price ?? 0);
        $kurs    = floatval($koi->kurs ?? 0);
        $unitIdr = $kurs > 0 && $unitJpy > 0 ? round($unitJpy * $kurs) : round(floatval($koi->sell_idr ?? 0) / $qty);
        $totalIdr = $unitIdr * $qty;
        $fee     = floatval($koi->fee ?? 0);
        $feePct  = $totalIdr > 0 && $fee > 0 ? round($fee / $totalIdr * 100) : 0;

        $stockJpy   = floatval($koi->stock_price ?? 0);
        $modalIdr   = $kurs > 0 && $stockJpy > 0 ? round($stockJpy * $kurs) : 0;
        $shippingPer = 0;
        if ($koi->shipping_cost && $koi->shipping_qty) {
            $shippingPer = round(floatval($koi->shipping_cost) / intval($koi->shipping_qty));
        }
        $profit = ($totalIdr - ($modalIdr * $qty) - $shippingPer) + $fee;
    ?>
    <tr>
        
        <td style="background-color:<?php echo e($bg); ?>; text-align:center; vertical-align:middle; border:1px solid #ddd; font-size:10pt; color:#666;"><?php echo e($index + 1); ?></td>
        
        <td style="background-color:<?php echo e($bg); ?>; vertical-align:top; border:1px solid #ddd; padding:6px;" colspan="2">
            <span style="font-weight:bold; font-size:10pt;"><?php echo e($koi->name); ?></span><br/>
            <span style="font-size:9pt; color:#888888;"><?php echo e($koi->code); ?></span><br/>
            <?php if($koi->azukari_period): ?>
                <span style="font-size:9pt; color:#aaaaaa;"><?php echo e($koi->azukari_period); ?></span><br/>
            <?php elseif($koi->sale_tanggal): ?>
                <span style="font-size:9pt; color:#aaaaaa;"><?php echo e(\Carbon\Carbon::parse($koi->sale_tanggal)->format('d M Y')); ?></span><br/>
            <?php endif; ?>
            <?php if($feePct > 0): ?>
                <span style="font-size:8pt; color:#c0392b;">Fee <?php echo e($feePct); ?>%</span>
            <?php endif; ?>
            <?php if($koi->shipping_note): ?>
                &nbsp;<span style="font-size:8pt; color:#0c5460;"><?php echo e($koi->shipping_note); ?></span>
            <?php endif; ?>
        </td>
        
        <td style="background-color:<?php echo e($bg); ?>; text-align:center; vertical-align:middle; border:1px solid #ddd; font-size:10pt;"><?php echo e($qty); ?></td>
        
        <td style="background-color:<?php echo e($bg); ?>; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; padding:6px;">
            <?php if($unitJpy > 0): ?>
                ¥<?php echo e(number_format($unitJpy)); ?>

                <?php if($feePct > 0): ?><br/><span style="font-size:8pt;color:#888;">Fee <?php echo e($feePct); ?>%</span><?php endif; ?>
            <?php else: ?> –<?php endif; ?>
        </td>
        
        <td style="background-color:<?php echo e($bg); ?>; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; padding:6px;">
            <?php if($unitIdr > 0): ?>
                <?php echo e(number_format($unitIdr, 0, ',', '.')); ?>

                <?php if($fee > 0): ?><br/><span style="font-size:8pt;color:#888;"><?php echo e(number_format($fee / $qty, 0, ',', '.')); ?></span><?php endif; ?>
            <?php else: ?> –<?php endif; ?>
        </td>
        
        <td style="background-color:<?php echo e($bg); ?>; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; font-weight:bold; padding:6px;">
            <?php if($totalIdr > 0): ?>
                <?php echo e(number_format($totalIdr, 0, ',', '.')); ?>

                <?php if($fee > 0): ?><br/><span style="font-size:8pt;color:#888;"><?php echo e(number_format($fee, 0, ',', '.')); ?></span><?php endif; ?>
            <?php else: ?> –<?php endif; ?>
        </td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    
    <tr><td colspan="7"></td></tr>
    
    <tr>
        <td colspan="4" style="font-size:10pt; font-weight:bold; color:#333; padding:4px;">
            Thank you for your purchased
        </td>
        <td style="background-color:#eeeeee; font-size:10pt; font-weight:bold; text-align:right; padding:4px; border:1px solid #ddd;">Subtotal</td>
        <td style="background-color:#eeeeee; font-size:10pt; text-align:right; padding:4px; border:1px solid #ddd;" colspan="2">IDR <?php echo e(number_format($grandTotal, 0, ',', '.')); ?></td>
    </tr>
    <?php if($totalFee > 0): ?>
    <tr>
        <td colspan="4"></td>
        <td style="background-color:#eeeeee; font-size:10pt; font-weight:bold; text-align:right; padding:4px; border:1px solid #ddd;">Total Fee</td>
        <td style="background-color:#eeeeee; font-size:10pt; text-align:right; padding:4px; border:1px solid #ddd;" colspan="2">IDR <?php echo e(number_format($totalFee, 0, ',', '.')); ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td colspan="4"></td>
        <td style="background-color:#c0392b; color:#ffffff; font-size:12pt; font-weight:bold; text-align:right; padding:6px; border:1px solid #a93226;">Total</td>
        <td style="background-color:#c0392b; color:#ffffff; font-size:12pt; font-weight:bold; text-align:right; padding:6px; border:1px solid #a93226;" colspan="2">IDR<?php echo e(number_format($grandTotal, 0, ',', '.')); ?></td>
    </tr>
    
    <tr><td colspan="7"></td></tr>
    <tr>
        <td style="font-size:9pt; color:#888; font-weight:bold;" colspan="7">Payment info:</td>
    </tr>
    <tr>
        <td style="font-size:9pt; color:#888;">Name</td>
        <td style="font-size:9pt; font-weight:bold;" colspan="6">: <?php echo e($paymentName); ?></td>
    </tr>
    <tr>
        <td style="font-size:9pt; color:#888;">Account</td>
        <td style="font-size:9pt; font-weight:bold;" colspan="6">: <?php echo e($paymentBank); ?></td>
    </tr>
    <tr>
        <td style="font-size:9pt; color:#888;">No. Rek</td>
        <td style="font-size:9pt; font-weight:bold;" colspan="6">: <?php echo e($paymentAccount); ?></td>
    </tr>
</table>
<?php /**PATH C:\laragon\www\koi-umeda-data-management\resources\views/excel/invoice.blade.php ENDPATH**/ ?>