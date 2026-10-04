<?php

namespace Modules\Lookups\Tests\Unit;

use Modules\Lookups\Models\Lookup;
use Tests\TestCase;

class LookupLocalizationTest extends TestCase
{
    public function test_localized_value_follows_the_current_locale(): void
    {
        $lookup = new Lookup(['name_en' => 'Villa', 'name_ar' => 'فيلا']);

        app()->setLocale('en');
        $this->assertSame('Villa', $lookup->getLocalized('name'));

        app()->setLocale('ar');
        $this->assertSame('فيلا', $lookup->getLocalized('name'));
    }

    public function test_localized_value_falls_back_to_english_when_arabic_is_empty(): void
    {
        app()->setLocale('ar');

        $this->assertSame('Villa', (new Lookup(['name_en' => 'Villa']))->getLocalized('name'));
    }

    public function test_localized_value_falls_back_to_arabic_when_english_is_empty(): void
    {
        app()->setLocale('en');

        $this->assertSame('فيلا', (new Lookup(['name_ar' => 'فيلا']))->getLocalized('name'));
    }

    public function test_base_field_name_returns_the_localized_value(): void
    {
        app()->setLocale('ar');
        $lookup = new Lookup(['name_en' => 'Villa', 'name_ar' => 'فيلا']);

        $this->assertSame('فيلا', $lookup->name);
        $this->assertTrue(isset($lookup->name));
        $this->assertSame(['name', 'description'], $lookup->localizedFields());
        $this->assertSame(['en' => 'Villa', 'ar' => 'فيلا'], $lookup->getLocalizedVariants('name'));
    }
}
