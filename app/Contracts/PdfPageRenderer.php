<?php

namespace App\Contracts;

use App\Models\DigitalBookAsset;

interface PdfPageRenderer
{
    public function render(DigitalBookAsset $asset): int;
}
