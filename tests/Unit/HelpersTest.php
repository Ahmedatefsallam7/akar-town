<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_localized_field_name_follows_the_current_locale(): void
    {
        app()->setLocale('en');
        $this->assertSame('name_en', getLocalizedFieldName('name'));

        app()->setLocale('ar');
        $this->assertSame('name_ar', getLocalizedFieldName('name'));
    }
}
