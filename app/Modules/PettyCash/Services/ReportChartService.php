<?php

namespace App\Modules\PettyCash\Services;

class ReportChartService
{
    // Returns base64 PNG or null if GD missing
    public function barChartBase64(array $totalsByBucket): ?string
    {
        if (!extension_loaded('gd')) return null;
        if (!$totalsByBucket) return null;

        $items = array_slice($totalsByBucket, 0, 8, true); // top 8
        $w = 900; $h = 420;
        $img = imagecreatetruecolor($w, $h);

        $bg = imagecolorallocate($img, 255, 255, 255);
        $text = imagecolorallocate($img, 17, 24, 40);
        $muted = imagecolorallocate($img, 102, 112, 133);
        $bar = imagecolorallocate($img, 127, 86, 217);

        imagefilledrectangle($img, 0, 0, $w, $h, $bg);

        $max = max($items);
        $max = $max > 0 ? $max : 1;

        $left = 220; $right = 40; $top = 40; $bottom = 40;
        $plotW = $w - $left - $right;
        $plotH = $h - $top - $bottom;

        imagestring($img, 5, 20, 10, "NET Spend by Category (Amount + Fee)", $text);

        $i = 0; $n = count($items);
        $barH = (int)($plotH / max($n, 1) * 0.6);
        $gap = (int)($plotH / max($n, 1) * 0.4);

        foreach ($items as $label => $val) {
            $y = $top + $i * ($barH + $gap);
            $len = (int)($plotW * ($val / $max));

            imagestring($img, 3, 20, $y + 2, strtoupper($label), $muted);
            imagefilledrectangle($img, $left, $y, $left + $len, $y + $barH, $bar);
            imagestring($img, 3, $left + $len + 10, $y + 2, number_format($val, 2), $text);

            $i++;
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return base64_encode($png);
    }
}
