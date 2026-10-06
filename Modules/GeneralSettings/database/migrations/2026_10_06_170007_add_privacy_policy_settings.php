<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default content (from Dastan, rebranded to Akar Town); the admin edits it in General Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('privacy_policy.description_en', 'At Akar Town ("Akar Town", "we", "us", or "our"), your privacy matters to us. This Privacy Policy explains what information we collect when you use our website and services, how we use and protect it, and the choices you have. By using our website, you agree to the practices described below.');
        $this->migrator->add('privacy_policy.description_ar', 'توضح سياسة الخصوصية هذه كيفية جمع عقار تاون لمعلوماتك الشخصية واستخدامها وحمايتها عند استخدامك لموقعنا الإلكتروني وخدماتنا. باستخدام منصتنا، فإنك توافق على الممارسات الموضحة هنا.');
        $this->migrator->add('privacy_policy.entries', [
            [
                'header_en' => 'Information We Collect',
                'header_ar' => 'المعلومات التي نجمعها',
                'body_en' => 'We collect information you provide directly, such as your name, email address, phone number, and property preferences when you register, submit an inquiry, or contact us. We also automatically collect certain technical data including your IP address, browser type, and pages visited to improve our services.',
                'body_ar' => 'نجمع المعلومات التي تقدمها مباشرةً، مثل اسمك وعنوان بريدك الإلكتروني ورقم هاتفك وتفضيلاتك العقارية عند التسجيل أو تقديم استفسار أو التواصل معنا. كما نجمع تلقائياً بيانات تقنية معينة تشمل عنوان IP الخاص بك ونوع المتصفح والصفحات التي تزورها لتحسين خدماتنا.',
            ],
            [
                'header_en' => 'How We Use Your Information',
                'header_ar' => 'كيف نستخدم معلوماتك',
                'body_en' => 'Your information is used to provide and improve our services, respond to your inquiries, send you relevant property listings and updates (where you have consented), and comply with our legal obligations. We do not sell your personal data to third parties.',
                'body_ar' => 'تُستخدم معلوماتك لتقديم خدماتنا وتحسينها، والرد على استفساراتك، وإرسال قوائم العقارات والتحديثات ذات الصلة إليك (حيثما وافقت على ذلك)، والامتثال لالتزاماتنا القانونية. نحن لا نبيع بياناتك الشخصية لأطراف ثالثة.',
            ],
            [
                'header_en' => 'Information Sharing',
                'header_ar' => 'مشاركة المعلومات',
                'body_en' => 'We may share your information with property developers and agents when you express interest in a listing, with trusted service providers who assist us in operating our platform under confidentiality agreements, and with authorities when required by law. All third parties are obligated to keep your data secure.',
                'body_ar' => 'قد نشارك معلوماتك مع المطورين العقاريين والوكلاء عند إبدائك اهتماماً بإحدى القوائم، ومع مزودي الخدمات الموثوقين الذين يساعدوننا في تشغيل منصتنا وفق اتفاقيات سرية، ومع السلطات عند الاقتضاء القانوني. يلتزم جميع الأطراف الثالثة بالحفاظ على أمان بياناتك.',
            ],
            [
                'header_en' => 'Cookies & Tracking',
                'header_ar' => 'ملفات تعريف الارتباط والتتبع',
                'body_en' => 'We use cookies and similar technologies to enhance your browsing experience, remember your preferences, and analyse site traffic. You can control cookie settings through your browser. Disabling certain cookies may limit some features of our platform.',
                'body_ar' => 'نستخدم ملفات تعريف الارتباط وتقنيات مماثلة لتحسين تجربة التصفح لديك وتذكر تفضيلاتك وتحليل حركة الموقع. يمكنك التحكم في إعدادات ملفات تعريف الارتباط عبر متصفحك. قد يؤدي تعطيل بعض ملفات تعريف الارتباط إلى تقييد بعض ميزات منصتنا.',
            ],
            [
                'header_en' => 'Your Rights',
                'header_ar' => 'حقوقك',
                'body_en' => 'You have the right to access, correct, or request deletion of the personal data we hold about you. You may also withdraw consent for marketing communications at any time. To exercise these rights, please contact us at the details provided below.',
                'body_ar' => 'يحق لك الوصول إلى البيانات الشخصية التي نحتفظ بها عنك أو تصحيحها أو طلب حذفها. كما يمكنك سحب موافقتك على الاتصالات التسويقية في أي وقت. لممارسة هذه الحقوق، يرجى التواصل معنا عبر التفاصيل المقدمة أدناه.',
            ],
            [
                'header_en' => 'Contact Us',
                'header_ar' => 'تواصل معنا',
                'body_en' => 'If you have any questions about this Privacy Policy or how we handle your data, please contact our Data Protection team. We aim to respond to all inquiries within 5 business days.',
                'body_ar' => 'إذا كانت لديك أي أسئلة حول سياسة الخصوصية هذه أو كيفية تعاملنا مع بياناتك، يرجى التواصل مع فريق حماية البيانات لدينا. نسعى للرد على جميع الاستفسارات خلال 5 أيام عمل.',
            ],
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('privacy_policy.description_en');
        $this->migrator->deleteIfExists('privacy_policy.description_ar');
        $this->migrator->deleteIfExists('privacy_policy.entries');
    }
};
