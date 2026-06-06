<?php

namespace App\Contracts;

use App\Models\DigitalBookAsset;
use App\Models\Member;
use App\Models\ReadingSession;

interface PageWatermarker
{
    public function watermark(
        DigitalBookAsset $asset,
        Member $member,
        ReadingSession $session,
        int $page
    ): string;
}
