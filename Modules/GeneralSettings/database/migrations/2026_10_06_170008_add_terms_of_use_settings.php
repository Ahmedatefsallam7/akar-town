<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default content (from Dastan, rebranded to Akar Town); the admin edits it in General Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('terms_of_use.description_en', 'Welcome to Akar Town. These Terms of Use ("Terms") govern your access to and use of our website, content, and services. By accessing or using our website, you agree to be bound by these Terms. If you do not agree, please do not use the website.');
        $this->migrator->add('terms_of_use.description_ar', 'تحكم شروط الاستخدام هذه وصولك إلى منصة عقار تاون واستخدامها. يرجى قراءتها بعناية قبل استخدام خدماتنا. يُعدّ استمرارك في استخدام المنصة قبولاً منك لهذه الشروط.');
        $this->migrator->add('terms_of_use.entries', [
            [
                'header_en' => 'Acceptance of Terms',
                'header_ar' => 'قبول الشروط',
                'body_en' => 'By accessing or using the Akar Town platform, you confirm that you are at least 18 years of age and have the legal capacity to enter into binding agreements. If you are using the platform on behalf of an organisation, you warrant that you have authority to bind that organisation to these terms.',
                'body_ar' => 'باستخدامك منصة عقار تاون أو الوصول إليها، فإنك تؤكد أنك تجاوزت الثامنة عشرة من عمرك وتتمتع بالأهلية القانونية لإبرام اتفاقيات ملزمة. إذا كنت تستخدم المنصة نيابةً عن مؤسسة، فإنك تضمن امتلاكك الصلاحية لإلزام تلك المؤسسة بهذه الشروط.',
            ],
            [
                'header_en' => 'Use of the Platform',
                'header_ar' => 'استخدام المنصة',
                'body_en' => 'You agree to use the platform solely for lawful purposes and in a manner that does not infringe the rights of others. You must not misuse our services by introducing viruses or other malicious code, attempt to gain unauthorised access to any part of the platform, or use automated tools to scrape or harvest data without our written consent.',
                'body_ar' => 'توافق على استخدام المنصة لأغراض مشروعة فحسب، وبطريقة لا تنتهك حقوق الآخرين. يُحظر عليك إساءة استخدام خدماتنا بإدخال فيروسات أو أكواد ضارة أخرى، أو محاولة الوصول غير المصرح به إلى أي جزء من المنصة، أو استخدام أدوات آلية لاستخراج البيانات أو جمعها دون موافقة خطية منا.',
            ],
            [
                'header_en' => 'Intellectual Property',
                'header_ar' => 'الملكية الفكرية',
                'body_en' => 'All content on this platform, including text, images, logos, and software, is the property of Akar Town or its licensors and is protected by applicable intellectual property laws. You may not reproduce, distribute, or create derivative works without our prior written permission.',
                'body_ar' => 'جميع المحتويات على هذه المنصة، بما تشمل النصوص والصور والشعارات والبرامج، هي ملك لعقار تاون أو مرخصيها وتحميها قوانين الملكية الفكرية المعمول بها. لا يجوز لك إعادة إنتاج هذه المحتويات أو توزيعها أو إنشاء أعمال مشتقة منها دون إذن خطي مسبق منا.',
            ],
            [
                'header_en' => 'Limitation of Liability',
                'header_ar' => 'تحديد المسؤولية',
                'body_en' => 'The platform is provided on an "as is" basis. Akar Town makes no warranties regarding the accuracy or completeness of property listings. To the fullest extent permitted by law, we shall not be liable for any indirect, incidental, or consequential damages arising from your use of the platform.',
                'body_ar' => 'تُقدَّم المنصة على أساس "كما هي". لا تقدم عقار تاون أي ضمانات بشأن دقة قوائم العقارات أو اكتمالها. إلى أقصى حد يسمح به القانون، لن نكون مسؤولين عن أي أضرار غير مباشرة أو عرضية أو تبعية تنشأ عن استخدامك للمنصة.',
            ],
            [
                'header_en' => 'Governing Law',
                'header_ar' => 'القانون الحاكم',
                'body_en' => 'These Terms of Use are governed by the laws of the Arab Republic of Egypt. Any disputes arising from or relating to these terms shall be subject to the exclusive jurisdiction of the courts of Cairo, Egypt.',
                'body_ar' => 'تخضع شروط الاستخدام هذه لقوانين جمهورية مصر العربية. تخضع أي نزاعات تنشأ عن هذه الشروط أو تتعلق بها للاختصاص القضائي الحصري لمحاكم القاهرة، مصر.',
            ],
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('terms_of_use.description_en');
        $this->migrator->deleteIfExists('terms_of_use.description_ar');
        $this->migrator->deleteIfExists('terms_of_use.entries');
    }
};
