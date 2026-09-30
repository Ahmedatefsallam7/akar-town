<?php

namespace Modules\Lookups\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Lookups\Models\Lookup;

class LookupsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Covers all lookup categories used across the application.
     */
    public function run(): void
    {
        $categories = [
            // ─── Amenities ────────────────────────────────────────────────
            'amenities' => [
                'en' => 'Amenities',
                'ar' => 'المميزات والخدمات',
                'items' => [
                    ['en' => 'Swimming Pool',   'ar' => 'حمام سباحة'],
                    ['en' => 'Gym',             'ar' => 'جيم'],
                    ['en' => 'Security',        'ar' => 'أمن وحراسة'],
                    ['en' => 'Private Garden',  'ar' => 'حديقة خاصة'],
                    ['en' => 'Clubhouse',       'ar' => 'كلوب هاوس'],
                    ['en' => 'Parking',         'ar' => 'موقف سيارات'],
                    ['en' => 'BBQ Area',        'ar' => 'منطقة شواء'],
                    ['en' => 'Kids Area',       'ar' => 'منطقة الأطفال'],
                    ['en' => 'Jogging Track',   'ar' => 'مضمار الجري'],
                    ['en' => 'Concierge',       'ar' => 'خدمة الاستقبال'],
                ],
            ],

            // ─── Facilities ───────────────────────────────────────────────
            'facility' => [
                'en' => 'Facilities',
                'ar' => 'المرافق',
                'items' => [
                    ['en' => 'Hospital',          'ar' => 'مستشفى'],
                    ['en' => 'School',            'ar' => 'مدرسة'],
                    ['en' => 'Mall',              'ar' => 'مول تجاري'],
                    ['en' => 'Mosque',            'ar' => 'مسجد'],
                    ['en' => 'Pharmacy',          'ar' => 'صيدلية'],
                    ['en' => 'Supermarket',       'ar' => 'سوبر ماركت'],
                    ['en' => 'Restaurant',        'ar' => 'مطعم'],
                    ['en' => 'Sports Club',       'ar' => 'نادي رياضي'],
                ],
            ],

            // ─── Views ────────────────────────────────────────────────────
            'views' => [
                'en' => 'Views',
                'ar' => 'الإطلالة',
                'items' => [
                    ['en' => 'Sea View',       'ar' => 'إطلالة على البحر'],
                    ['en' => 'Garden View',    'ar' => 'إطلالة على الحديقة'],
                    ['en' => 'Lake View',      'ar' => 'إطلالة على البحيرة'],
                    ['en' => 'Pool View',      'ar' => 'إطلالة على المسبح'],
                    ['en' => 'Main Street',    'ar' => 'شارع رئيسي'],
                    ['en' => 'Golf View',      'ar' => 'إطلالة على الجولف'],
                    ['en' => 'City View',      'ar' => 'إطلالة على المدينة'],
                ],
            ],

            // ─── Finishing Types ──────────────────────────────────────────
            'finishingtype' => [
                'en' => 'Finishing Types',
                'ar' => 'أنواع التشطيب',
                'items' => [
                    ['en' => 'Fully Finished',   'ar' => 'تشطيب كامل'],
                    ['en' => 'Semi Finished',    'ar' => 'نصف تشطيب'],
                    ['en' => 'Core & Shell',     'ar' => 'بدون تشطيب'],
                    ['en' => 'Fully Furnished',  'ar' => 'مفروش بالكامل'],
                    ['en' => 'Super Lux',        'ar' => 'سوبر لوكس'],
                    ['en' => 'Lux',              'ar' => 'لوكس'],
                ],
            ],

            // ─── Furnishing Status ────────────────────────────────────────
            'furnishingstatus' => [
                'en' => 'Furnishing Status',
                'ar' => 'حالة الفرش',
                'items' => [
                    ['en' => 'Furnished',         'ar' => 'مفروش'],
                    ['en' => 'Unfurnished',       'ar' => 'غير مفروش'],
                    ['en' => 'Semi-Furnished',    'ar' => 'نصف مفروش'],
                ],
            ],

            // ─── Offering Types ───────────────────────────────────────────
            'offeringtype' => [
                'en' => 'Offering Types',
                'ar' => 'أنواع العرض',
                'items' => [
                    ['en' => 'For Sale', 'ar' => 'للبيع'],
                    ['en' => 'For Rent', 'ar' => 'للإيجار'],
                ],
            ],

            // ─── Payment Methods ──────────────────────────────────────────
            'paymentmethod' => [
                'en' => 'Payment Methods',
                'ar' => 'طرق السداد',
                'items' => [
                    ['en' => 'Cash',         'ar' => 'كاش'],
                    ['en' => 'Installments', 'ar' => 'تقسيط'],
                ],
            ],

            // ─── Sale Types ───────────────────────────────────────────────
            'saletype' => [
                'en' => 'Sale Types',
                'ar' => 'أنواع البيع',
                'items' => [
                    ['en' => 'New Sale', 'ar' => 'بيع جديد'],
                    ['en' => 'Re-Sale',  'ar' => 'إعادة بيع'],
                ],
            ],

            // ─── Purpose Types (Property Types) ──────────────────────────
            'purposetype' => [
                'en' => 'Property Types',
                'ar' => 'أنواع العقارات',
                'items' => [
                    ['en' => 'Apartment',        'ar' => 'شقة'],
                    ['en' => 'Villa',             'ar' => 'فيلا'],
                    ['en' => 'Duplex',            'ar' => 'دوبلكس'],
                    ['en' => 'Townhouse',         'ar' => 'تاون هاوس'],
                    ['en' => 'Penthouse',         'ar' => 'بنتهاوس'],
                    ['en' => 'Studio',            'ar' => 'ستوديو'],
                    ['en' => 'Chalet',            'ar' => 'شاليه'],
                    ['en' => 'Twin House',        'ar' => 'توين هاوس'],
                    ['en' => 'Standalone Villa',  'ar' => 'فيلا مستقلة'],
                    ['en' => 'Land',              'ar' => 'أرض'],
                ],
            ],

            // ─── Project Status (Availability) ───────────────────────────
            'projectstatus' => [
                'en' => 'Project Status',
                'ar' => 'حالة المشروع',
                'items' => [
                    ['en' => 'Under Construction', 'ar' => 'قيد الإنشاء'],
                    ['en' => 'Ready to Move',      'ar' => 'جاهز للسكن'],
                    ['en' => 'Off Plan',           'ar' => 'على المخطط'],
                    ['en' => 'Completed',          'ar' => 'مكتمل'],
                ],
            ],

            // ─── Bedrooms ─────────────────────────────────────────────────
            'bedrooms' => [
                'en' => 'Bedrooms',
                'ar' => 'غرف النوم',
                'items' => [
                    ['en' => 'Studio',   'ar' => 'ستوديو'],
                    ['en' => '1 BR',     'ar' => '1 غرفة'],
                    ['en' => '2 BR',     'ar' => '2 غرفة'],
                    ['en' => '3 BR',     'ar' => '3 غرف'],
                    ['en' => '4 BR',     'ar' => '4 غرف'],
                    ['en' => '5+ BR',    'ar' => '5+ غرف'],
                ],
            ],

            // ─── Bathrooms ────────────────────────────────────────────────
            'bathrooms' => [
                'en' => 'Bathrooms',
                'ar' => 'دورات المياه',
                'items' => [
                    ['en' => '1',    'ar' => '1'],
                    ['en' => '2',    'ar' => '2'],
                    ['en' => '3',    'ar' => '3'],
                    ['en' => '4+',   'ar' => '4+'],
                ],
            ],

            // ─── Blog Categories ──────────────────────────────────────────
            'blog_categories' => [
                'en' => 'Blog Categories',
                'ar' => 'تصنيفات المدونة',
                'items' => [
                    ['en' => 'Market News',      'ar' => 'أخبار السوق'],
                    ['en' => 'Real Estate Tips',  'ar' => 'نصائح عقارية'],
                    ['en' => 'New Projects',      'ar' => 'مشاريع جديدة'],
                    ['en' => 'Economic Insights', 'ar' => 'رؤى اقتصادية'],
                    ['en' => 'Lifestyle',         'ar' => 'أسلوب حياة'],
                ],
            ],
        ];

        foreach ($categories as $slug => $category) {
            $parent = Lookup::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_en' => $category['en'],
                    'name_ar' => $category['ar'],
                    'is_active' => true,
                    'parent_id' => null,
                ]
            );

            foreach ($category['items'] as $index => $item) {
                $itemSlug = Str::slug($item['en']);
                Lookup::updateOrCreate(
                    ['slug' => $itemSlug, 'parent_id' => $parent->id],
                    [
                        'name_en' => $item['en'],
                        'name_ar' => $item['ar'],
                        'is_active' => true,
                        'order' => $index + 1,
                    ]
                );
            }
        }
    }
}
