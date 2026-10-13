<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default content (from Dastan, rebranded to Akar Town); the admin edits it in General Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('about_us.hero_header_en', 'Redefining Real Estate');
        $this->migrator->add('about_us.hero_header_ar', 'إعادة تعريف قطاع العقارات');
        $this->migrator->add('about_us.hero_body_en', 'Discover the passion, expertise, and commitment behind Akar Town.');
        $this->migrator->add('about_us.hero_body_ar', 'اكتشف الشغف والخبرة والالتزام الذي تقوم عليه عقار تاون.');
        $this->migrator->add('about_us.story_header_en', 'Built on Trust and Vision');
        $this->migrator->add('about_us.story_header_ar', 'قائمة على الثقة والرؤية');
        $this->migrator->add('about_us.story_body_en', 'At Akar Town, we believe that finding a home is more than just a transaction; it\'s a life-changing decision. Established over a decade ago, our mission has always been to provide exceptional service rooted in integrity, transparency, and a profound understanding of the market.

Our curated portfolio of properties represents the pinnacle of luxury, comfort, and architectural excellence. We work tirelessly to match our clients with spaces that not only meet their needs but exceed their wildest dreams.');
        $this->migrator->add('about_us.story_body_ar', 'في عقار تاون، نؤمن بأن العثور على منزل هو أكثر من مجرد صفقة؛ إنه قرار يغيّر حياتك. منذ تأسيسنا قبل أكثر من عقد، كانت مهمتنا دائماً تقديم خدمة استثنائية قائمة على النزاهة والشفافية وفهم عميق للسوق.

تمثل مجموعتنا المنتقاة من العقارات قمة الفخامة والراحة والتميز المعماري. نعمل بلا كلل لنوفق بين عملائنا والمساحات التي لا تلبي احتياجاتهم فحسب، بل تفوق طموحاتهم.');
        $this->migrator->add('about_us.years_of_excellence', 0);
        $this->migrator->add('about_us.stats_happy_clients', 0);
        $this->migrator->add('about_us.stats_properties_sold', 0);
        $this->migrator->add('about_us.stats_awards_won', 0);
        $this->migrator->add('about_us.stats_commitment', 100);
        $this->migrator->add('about_us.core_values', [
            [
                'header_en' => 'Integrity',
                'header_ar' => 'النزاهة',
                'body_en' => 'We operate with absolute transparency and honesty, ensuring our clients feel confident and secure in every decision.',
                'body_ar' => 'نعمل بشفافية وصدق مطلقين، لضمان شعور عملائنا بالثقة والأمان في كل قرار.',
                'icon' => null,
            ],
            [
                'header_en' => 'Excellence',
                'header_ar' => 'التميز',
                'body_en' => 'From our property selection to our customer service, we strive for perfection and deliver premium experiences.',
                'body_ar' => 'من اختيار العقارات إلى خدمة العملاء، نسعى للكمال ونقدم تجارب متميزة.',
                'icon' => null,
            ],
            [
                'header_en' => 'Client-Centric',
                'header_ar' => 'التركيز على العميل',
                'body_en' => 'Your vision is our priority. We listen, adapt, and go above and beyond to fulfill your unique real estate needs.',
                'body_ar' => 'رؤيتك هي أولويتنا. نصغي ونتكيف ونبذل جهداً إضافياً لتلبية احتياجاتك العقارية الفريدة.',
                'icon' => null,
            ],
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('about_us.hero_header_en');
        $this->migrator->deleteIfExists('about_us.hero_header_ar');
        $this->migrator->deleteIfExists('about_us.hero_body_en');
        $this->migrator->deleteIfExists('about_us.hero_body_ar');
        $this->migrator->deleteIfExists('about_us.story_header_en');
        $this->migrator->deleteIfExists('about_us.story_header_ar');
        $this->migrator->deleteIfExists('about_us.story_body_en');
        $this->migrator->deleteIfExists('about_us.story_body_ar');
        $this->migrator->deleteIfExists('about_us.years_of_excellence');
        $this->migrator->deleteIfExists('about_us.stats_happy_clients');
        $this->migrator->deleteIfExists('about_us.stats_properties_sold');
        $this->migrator->deleteIfExists('about_us.stats_awards_won');
        $this->migrator->deleteIfExists('about_us.stats_commitment');
        $this->migrator->deleteIfExists('about_us.core_values');
    }
};
