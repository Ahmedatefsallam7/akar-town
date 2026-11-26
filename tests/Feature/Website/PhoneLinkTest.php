<?php

namespace Tests\Feature\Website;

use App\Support\Website\Phone;
use Tests\TestCase;

class PhoneLinkTest extends TestCase
{
    public function test_call_links_are_international_so_they_work_from_abroad(): void
    {
        $this->assertSame('tel:+201001234567', Phone::tel('01001234567'));
        $this->assertSame('tel:+201001234567', Phone::tel('0100 123-4567'));
        $this->assertSame('tel:+201001234567', Phone::tel('+20 100 123 4567'));
        $this->assertSame('tel:+20223456789', Phone::tel('02 2345 6789'));
        // A short number (the hotline) stays as it is.
        $this->assertSame('tel:19000', Phone::tel('19000'));
        $this->assertSame('tel:', Phone::tel(null));
    }
}
