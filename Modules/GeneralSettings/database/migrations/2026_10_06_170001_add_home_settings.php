<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default content (from Dastan, rebranded to Akar Town); the admin edits it in General Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('home.why_choose_subtitle_en', 'Why Choose Akar Town');
        $this->migrator->add('home.why_choose_subtitle_ar', 'لماذا تختار عقار تاون');
        $this->migrator->add('home.why_choose_header_en', 'Experience a New Standard of Living');
        $this->migrator->add('home.why_choose_header_ar', 'اختبر معياراً جديداً للحياة');
        $this->migrator->add('home.why_choose_body_en', 'We combine deep market knowledge with a passion for exceptional service, helping you find a home that truly reflects your lifestyle and aspirations.');
        $this->migrator->add('home.why_choose_body_ar', 'نجمع بين المعرفة العميقة بالسوق والشغف بالخدمة الاستثنائية لمساعدتك في العثور على منزل يعكس أسلوب حياتك وطموحاتك.');
        $this->migrator->add('home.why_choose_cta_text_en', 'Discover Our Story');
        $this->migrator->add('home.why_choose_cta_text_ar', 'اكتشف قصتنا');
        $this->migrator->add('home.why_choose_cta_url', null);
        $this->migrator->add('home.why_choose_cards', [
            [
                'header_en' => 'Find Your Future Home',
                'header_ar' => 'ابحث عن منزل مستقبلك',
                'body_en' => 'We help you discover the perfect property with a smart, curated real estate experience tailored to you.',
                'body_ar' => 'نساعدك على اكتشاف العقار المثالي من خلال تجربة عقارية ذكية ومنسقة خصيصاً لك.',
                'icon' => null,
            ],
            [
                'header_en' => 'Experienced Agents',
                'header_ar' => 'وكلاء ذوو خبرة',
                'body_en' => 'Our seasoned professionals know the market inside and out, ensuring you get the best deal possible.',
                'body_ar' => 'يعرف محترفونا المتمرسون السوق جيداً، مما يضمن حصولك على أفضل صفقة ممكنة.',
                'icon' => null,
            ],
            [
                'header_en' => 'Buy or Rent Homes',
                'header_ar' => 'شراء أو استئجار المنازل',
                'body_en' => 'Explore thousands of listings in your favourite cities, whether you\'re looking to buy or rent.',
                'body_ar' => 'استكشف آلاف القوائم في مدنك المفضلة، سواء كنت تبحث عن الشراء أو الإيجار.',
                'icon' => null,
            ],
            [
                'header_en' => 'List Your Property',
                'header_ar' => 'إدراج الممتلكات الخاصة بك',
                'body_en' => 'Sign up and effortlessly list your property to reach thousands of qualified buyers and renters.',
                'body_ar' => 'قم بالتسجيل وإدراج الممتلكات الخاصة بك بسهولة للوصول إلى آلاف المشترين والمستأجرين المؤهلين.',
                'icon' => null,
            ],
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('home.why_choose_subtitle_en');
        $this->migrator->deleteIfExists('home.why_choose_subtitle_ar');
        $this->migrator->deleteIfExists('home.why_choose_header_en');
        $this->migrator->deleteIfExists('home.why_choose_header_ar');
        $this->migrator->deleteIfExists('home.why_choose_body_en');
        $this->migrator->deleteIfExists('home.why_choose_body_ar');
        $this->migrator->deleteIfExists('home.why_choose_cta_text_en');
        $this->migrator->deleteIfExists('home.why_choose_cta_text_ar');
        $this->migrator->deleteIfExists('home.why_choose_cta_url');
        $this->migrator->deleteIfExists('home.why_choose_cards');
    }
};
