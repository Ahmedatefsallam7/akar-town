<?php

/*
| Every lookup type and its values (the Locations type is a tree: country › city › region › area › sub-area).
| Exported from Dastan's database on 2026-10-05. Dastan's "propertytype" is "unittype" here (the same list; the team lead
| chose Unit Type).
| LookupsDatabaseSeeder creates what's missing and never overwrites values edited in the admin.
*/

return [
    [
        'slug' => 'unittype',
        'name_en' => 'Unit Type',
        'name_ar' => 'نوع الوحدة',
        'items' => [
            [
                'slug' => 'apartment',
                'name_en' => 'Apartment',
                'name_ar' => 'شقة',
                'attributes' => ['floor_number' => 1],
            ],
            [
                'slug' => 'villa',
                'name_en' => 'Villa',
                'name_ar' => 'فيلا',
                'attributes' => ['floor_number' => 2],
            ],
            [
                'slug' => 'duplex',
                'name_en' => 'Duplex',
                'name_ar' => 'دوبلكس',
                'attributes' => ['floor_number' => 3],
            ],
            [
                'slug' => 'townhouse',
                'name_en' => 'Townhouse',
                'name_ar' => 'تاون هاوس',
                'attributes' => ['floor_number' => 4],
            ],
            [
                'slug' => 'penthouse',
                'name_en' => 'Penthouse',
                'name_ar' => 'بنتهاوس',
                'attributes' => ['floor_number' => 5],
            ],
            [
                'slug' => 'land',
                'name_en' => 'Land',
                'name_ar' => 'أرض',
                'attributes' => ['floor_number' => 6],
            ],
            [
                'slug' => 'twin-house',
                'name_en' => 'Twin House',
                'name_ar' => 'توين هاوس',
                'attributes' => ['floor_number' => 7],
            ],
            [
                'slug' => 'standalone-villa',
                'name_en' => 'Standalone Villa',
                'name_ar' => 'فيلا مستقلة',
                'attributes' => ['floor_number' => 8],
            ],
            [
                'slug' => 'rooftop',
                'name_en' => 'Rooftop',
                'name_ar' => 'على السطح',
                'attributes' => ['floor_number' => 9],
            ],
            [
                'slug' => 'studio',
                'name_en' => 'Studio',
                'name_ar' => 'ستوديو',
                'attributes' => ['floor_number' => 10],
            ],
            [
                'slug' => 'basement',
                'name_en' => 'Basement',
                'name_ar' => 'قبو',
                'attributes' => ['floor_number' => 11],
            ],
            [
                'slug' => 'shop',
                'name_en' => 'Shop',
                'name_ar' => 'محل',
                'attributes' => ['floor_number' => 12],
            ],
            [
                'slug' => 'store',
                'name_en' => 'Store',
                'name_ar' => 'مخزن',
                'attributes' => ['floor_number' => 13],
            ],
            [
                'slug' => 'exhibition',
                'name_en' => 'Exhibition',
                'name_ar' => 'معرض',
                'attributes' => ['floor_number' => 14],
            ],
            [
                'slug' => 'caferestaurant',
                'name_en' => 'Cafe/Restaurant',
                'name_ar' => 'مقهى / مطعم',
                'attributes' => ['floor_number' => 15],
            ],
            [
                'slug' => 'retail',
                'name_en' => 'Retail',
                'name_ar' => 'قطاعي',
                'attributes' => ['floor_number' => 16],
            ],
            [
                'slug' => 'retail-showroom',
                'name_en' => 'Retail (Showroom)',
                'name_ar' => 'البيع بالتجزئة (صالة العرض)',
                'attributes' => ['floor_number' => 17],
            ],
            [
                'slug' => 'bank',
                'name_en' => 'Bank',
                'name_ar' => 'مصرف',
                'attributes' => ['floor_number' => 18],
            ],
            [
                'slug' => 'factory',
                'name_en' => 'Factory',
                'name_ar' => 'مصنع',
                'attributes' => ['floor_number' => 19],
            ],
            [
                'slug' => 'storage',
                'name_en' => 'Storage',
                'name_ar' => 'تخزين',
                'attributes' => ['floor_number' => 20],
            ],
            [
                'slug' => 'chalet',
                'name_en' => 'Chalet',
                'name_ar' => 'شاليه',
                'attributes' => ['floor_number' => 21],
            ],
        ],
    ],
    [
        'slug' => 'amenities',
        'name_en' => 'Amenities',
        'name_ar' => 'المرافق',
        'items' => [
            [
                'slug' => 'shared-pool',
                'name_en' => 'Shared Pool',
                'name_ar' => 'حمام سباحة مشترك',
            ],
            [
                'slug' => 'air-conditioner',
                'name_en' => 'Air Conditioner',
                'name_ar' => 'مكيف هواء',
            ],
            [
                'slug' => 'garages',
                'name_en' => 'Garages',
                'name_ar' => 'موقف سيارات',
            ],
            [
                'slug' => 'amenity-bathrooms',
                'name_en' => 'Bathrooms',
                'name_ar' => 'حمامات',
            ],
            [
                'slug' => 'nanny-room',
                'name_en' => 'Nanny Room',
                'name_ar' => 'غرفة مربية',
            ],
            [
                'slug' => 'garden-view',
                'name_en' => 'Garden View',
                'name_ar' => 'إطلالة على حديقة',
            ],
            [
                'slug' => 'balcony',
                'name_en' => 'Balcony',
                'name_ar' => 'شرفة',
            ],
            [
                'slug' => 'shared-spa',
                'name_en' => 'Shared Spa',
                'name_ar' => 'منتجع صحي مشترك',
            ],
            [
                'slug' => 'shared-gym',
                'name_en' => 'Shared Gym',
                'name_ar' => 'صالة رياضية مشتركة',
            ],
            [
                'slug' => 'water-view',
                'name_en' => 'Water View',
                'name_ar' => 'إطلالة على مياه',
            ],
            [
                'slug' => 'security',
                'name_en' => 'Security',
                'name_ar' => 'امان',
            ],
            [
                'slug' => 'childrens-play-area',
                'name_en' => 'Children\'s Play Area',
                'name_ar' => 'منطقه لعب للاطفال',
            ],
            [
                'slug' => 'landmark-view',
                'name_en' => 'Landmark View',
                'name_ar' => 'إطلالة على معالم',
            ],
            [
                'slug' => 'private-garden',
                'name_en' => 'Private Garden',
                'name_ar' => 'حديقة خاصة',
            ],
            [
                'slug' => 'childrens-pool',
                'name_en' => 'Children\'s Pool',
                'name_ar' => 'حمام سباحة للأطفال',
            ],
            [
                'slug' => 'concierge',
                'name_en' => 'Concierge',
                'name_ar' => 'خدمات الاستقبال والإرشاد',
            ],
            [
                'slug' => 'maid-service',
                'name_en' => 'Maid Service',
                'name_ar' => 'خدمة تدبير المنازل',
            ],
            [
                'slug' => 'lobby-in-building',
                'name_en' => 'Lobby in Building',
                'name_ar' => 'ردهة داخل المبنى',
            ],
            [
                'slug' => 'walk-in-closet',
                'name_en' => 'Walk-in Closet',
                'name_ar' => 'خزانة ملابس',
            ],
            [
                'slug' => 'networked',
                'name_en' => 'Networked',
                'name_ar' => 'يوجد بها شبكة',
            ],
            [
                'slug' => 'pets-allowed',
                'name_en' => 'Pets Allowed',
                'name_ar' => 'مسموح بدخول الحيوانات الأليفة',
            ],
            [
                'slug' => 'barbecue-area',
                'name_en' => 'Barbecue Area',
                'name_ar' => 'منطقة شواء',
            ],
            [
                'slug' => 'covered-parking',
                'name_en' => 'Covered Parking',
                'name_ar' => 'مواقف مغطاة للسيارات',
            ],
            [
                'slug' => 'maids-room',
                'name_en' => 'Maids Room',
                'name_ar' => 'غرفة للخادمة',
            ],
            [
                'slug' => 'study',
                'name_en' => 'Study',
                'name_ar' => 'دراسة',
            ],
            [
                'slug' => 'private-pool',
                'name_en' => 'Private Pool',
                'name_ar' => 'مسبح خاص',
            ],
            [
                'slug' => 'kitchen-appliances',
                'name_en' => 'Kitchen Appliances',
                'name_ar' => 'أدوات مطبخ',
            ],
            [
                'slug' => 'central-ac',
                'name_en' => 'Central A/C',
                'name_ar' => 'تكييف مركزي',
            ],
            [
                'slug' => 'private-jacuzzi',
                'name_en' => 'Private Jacuzzi',
                'name_ar' => 'جاكوزي خاص',
            ],
            [
                'slug' => 'shared-jacuzzi',
                'name_en' => 'Shared Jacuzzi',
                'name_ar' => 'جاكوزي مشترك',
            ],
            [
                'slug' => 'built-in-wardrobes',
                'name_en' => 'Built-in Wardrobes',
                'name_ar' => 'خزائن ملابس مدمجة',
            ],
        ],
    ],
    [
        'slug' => 'areaUnit',
        'name_en' => 'Area Unit',
        'name_ar' => 'وحدات المساحة',
        'items' => [
            [
                'slug' => 'm2',
                'name_en' => 'm2',
                'name_ar' => 'م2',
            ],
            [
                'slug' => 'square-foot',
                'name_en' => 'Square Foot',
                'name_ar' => 'قدم مربع',
            ],
            [
                'slug' => 'acre',
                'name_en' => 'Acre',
                'name_ar' => 'فدان',
            ],
        ],
    ],
    [
        'slug' => 'views',
        'name_en' => 'Views',
        'name_ar' => 'الإطلالات',
        'items' => [
            [
                'slug' => 'sea-view',
                'name_en' => 'Sea View',
                'name_ar' => 'إطلالة على البحر',
            ],
            [
                'slug' => 'city-view',
                'name_en' => 'City View',
                'name_ar' => 'إطلالة على المدينة',
            ],
            [
                'slug' => 'garden-view',
                'name_en' => 'Garden View',
                'name_ar' => 'إطلالة على الحديقة',
            ],
            [
                'slug' => 'mountain-view',
                'name_en' => 'Mountain View',
                'name_ar' => 'إطلالة على الجبل',
            ],
            [
                'slug' => 'lake-view',
                'name_en' => 'Lake View',
                'name_ar' => 'إطلالة على البحيرة',
            ],
        ],
    ],
    [
        'slug' => 'services',
        'name_en' => 'Services',
        'name_ar' => 'الخدمات',
        'items' => [
            [
                'slug' => 'security',
                'name_en' => 'Security',
                'name_ar' => 'الأمن',
            ],
            [
                'slug' => 'maintenance',
                'name_en' => 'Maintenance',
                'name_ar' => 'الصيانة',
            ],
            [
                'slug' => 'cleaning',
                'name_en' => 'Cleaning',
                'name_ar' => 'التنظيف',
            ],
            [
                'slug' => 'parking',
                'name_en' => 'Parking',
                'name_ar' => 'موقف السيارات',
            ],
            [
                'slug' => 'wifi',
                'name_en' => 'Wi-Fi',
                'name_ar' => 'واي فاي',
            ],
            [
                'slug' => 'gym',
                'name_en' => 'Gym',
                'name_ar' => 'صالة رياضية',
            ],
            [
                'slug' => 'swimming-pool',
                'name_en' => 'Swimming Pool',
                'name_ar' => 'مسبح',
            ],
        ],
    ],
    [
        'slug' => 'currencies',
        'name_en' => 'Currencies',
        'name_ar' => 'العملات',
        'items' => [
            [
                'slug' => 'egp',
                'name_en' => 'Egyptian Pound',
                'name_ar' => 'جنيه مصري',
            ],
            [
                'slug' => 'usd',
                'name_en' => 'US Dollar',
                'name_ar' => 'دولار أمريكي',
            ],
        ],
    ],
    [
        'slug' => 'bathrooms',
        'name_en' => 'Bathrooms',
        'name_ar' => 'عدد الحمامات',
        'items' => [
            [
                'slug' => '1-bathroom',
                'name_en' => '1 Bathroom',
                'name_ar' => 'حمام واحد',
            ],
            [
                'slug' => '2-bathrooms',
                'name_en' => '2 Bathrooms',
                'name_ar' => 'حمامان',
            ],
            [
                'slug' => '3-bathrooms',
                'name_en' => '3 Bathrooms',
                'name_ar' => 'ثلاثة حمامات',
            ],
            [
                'slug' => '4-bathrooms',
                'name_en' => '4 Bathrooms',
                'name_ar' => 'اربعة حمامات',
            ],
            [
                'slug' => '5-bathrooms',
                'name_en' => '5 Bathrooms',
                'name_ar' => 'خمسة حمامات',
            ],
        ],
    ],
    [
        'slug' => 'bedrooms',
        'name_en' => 'Bedrooms',
        'name_ar' => 'عدد الغرف',
        'items' => [
            [
                'slug' => 'studio',
                'name_en' => 'Studio',
                'name_ar' => 'ستوديو',
            ],
            [
                'slug' => '1-bedroom',
                'name_en' => '1 Bedroom',
                'name_ar' => 'غرفة نوم واحدة',
            ],
            [
                'slug' => '2-bedrooms',
                'name_en' => '2 Bedrooms',
                'name_ar' => 'غرفتان نوم',
            ],
            [
                'slug' => '3-bedrooms',
                'name_en' => '3 Bedrooms',
                'name_ar' => 'ثلاثة غرف نوم',
            ],
            [
                'slug' => '4-bedrooms',
                'name_en' => '4 Bedrooms',
                'name_ar' => 'اربعة غرف نوم',
            ],
            [
                'slug' => '5-bedrooms',
                'name_en' => '5 Bedrooms',
                'name_ar' => 'خمسة غرف نوم',
            ],
            [
                'slug' => '6-bedrooms',
                'name_en' => '6 Bedrooms',
                'name_ar' => 'ست غرف نوم',
            ],
            [
                'slug' => '7-bedrooms',
                'name_en' => '7 Bedrooms',
                'name_ar' => 'سبع غرف نوم',
            ],
        ],
    ],
    [
        'slug' => 'facility',
        'name_en' => 'Facility',
        'name_ar' => 'التسهيلات',
        'items' => [
            [
                'slug' => 'parking-space',
                'name_en' => 'Parking Space',
                'name_ar' => 'أماكن لوقوف السيارات',
            ],
            [
                'slug' => 'power-generator',
                'name_en' => 'Power Generator',
                'name_ar' => 'مولد طاقة',
            ],
            [
                'slug' => 'satellite-connection',
                'name_en' => 'Satellite Connection',
                'name_ar' => 'اتصال قمر صناعي',
            ],
            [
                'slug' => 'internet-access',
                'name_en' => 'Internet Access',
                'name_ar' => 'خدمة الإنترنت',
            ],
            [
                'slug' => 'closed-compound',
                'name_en' => 'Closed Compound',
                'name_ar' => 'مجمع مغلق',
            ],
        ],
    ],
    [
        'slug' => 'finishingtype',
        'name_en' => 'Finishing Type',
        'name_ar' => 'نوع التشطيب',
        'items' => [
            [
                'slug' => 'unfinished',
                'name_en' => 'Unfinished',
                'name_ar' => 'غير منتهى',
            ],
            [
                'slug' => 'fully-finished',
                'name_en' => 'Fully Finished',
                'name_ar' => 'انتهى بالكامل',
            ],
            [
                'slug' => 'super-lux',
                'name_en' => 'Super Lux',
                'name_ar' => 'سوبر لوكس',
            ],
            [
                'slug' => 'ultra-super-lux',
                'name_en' => 'Ultra Super Lux',
                'name_ar' => 'الترا سوبر لوكس',
            ],
            [
                'slug' => 'core-shell',
                'name_en' => 'Core Shell',
                'name_ar' => 'كور شل',
            ],
            [
                'slug' => 'semi-finished',
                'name_en' => 'Semi Finished',
                'name_ar' => 'نصف تشطيب',
            ],
        ],
    ],
    [
        'slug' => 'floornumber',
        'name_en' => 'Floor Number',
        'name_ar' => 'رقم الطابق',
        'items' => [
            [
                'slug' => 'basement',
                'name_en' => 'Basement',
                'name_ar' => 'القبو',
            ],
            [
                'slug' => 'ground',
                'name_en' => 'Ground',
                'name_ar' => 'ارضى',
            ],
            [
                'slug' => 'floor-1',
                'name_en' => 'Floor 1',
                'name_ar' => 'الدور الاول',
            ],
            [
                'slug' => 'floor-2',
                'name_en' => 'Floor 2',
                'name_ar' => 'الدور الثانى',
            ],
            [
                'slug' => 'floor-3',
                'name_en' => 'Floor 3',
                'name_ar' => 'الدور الثالث',
            ],
            [
                'slug' => 'floor-4',
                'name_en' => 'Floor 4',
                'name_ar' => 'الدور الرابع',
            ],
            [
                'slug' => 'floor-5',
                'name_en' => 'Floor 5',
                'name_ar' => 'الدور الخامس',
            ],
            [
                'slug' => 'floor-6',
                'name_en' => 'Floor 6',
                'name_ar' => 'الدور السادس',
            ],
            [
                'slug' => 'floor-7',
                'name_en' => 'Floor 7',
                'name_ar' => 'الدور السابع',
            ],
            [
                'slug' => 'floor-8',
                'name_en' => 'Floor 8',
                'name_ar' => 'الدور الثامن',
            ],
            [
                'slug' => 'floor-9',
                'name_en' => 'Floor 9',
                'name_ar' => 'الدور التاسع',
            ],
            [
                'slug' => 'floor-10',
                'name_en' => 'Floor 10',
                'name_ar' => 'الدور العاشر',
            ],
            [
                'slug' => 'floor-11',
                'name_en' => 'Floor 11',
                'name_ar' => 'الدور الحادى عشر',
            ],
            [
                'slug' => 'floor-12',
                'name_en' => 'Floor 12',
                'name_ar' => 'الدور الثانى عشر',
            ],
            [
                'slug' => 'floor-13',
                'name_en' => 'Floor 13',
                'name_ar' => 'الدور الثالث عشر',
            ],
            [
                'slug' => 'floor-14',
                'name_en' => 'Floor 14',
                'name_ar' => 'الدور الرابع عشر',
            ],
            [
                'slug' => 'floor-15',
                'name_en' => 'Floor 15',
                'name_ar' => 'الدور الخامس عشر',
            ],
            [
                'slug' => 'floor-16',
                'name_en' => 'Floor 16',
                'name_ar' => 'الدور السادس عشر',
            ],
            [
                'slug' => 'floor-17',
                'name_en' => 'Floor 17',
                'name_ar' => 'الدور السابع عشر',
            ],
            [
                'slug' => 'floor-18',
                'name_en' => 'Floor 18',
                'name_ar' => 'الدور الثامن عشر',
            ],
            [
                'slug' => 'floor-19',
                'name_en' => 'Floor 19',
                'name_ar' => 'الدور التاسع عشر',
            ],
            [
                'slug' => 'floor-20',
                'name_en' => 'Floor 20',
                'name_ar' => 'الدور العشرين',
            ],
            [
                'slug' => 'floor-21',
                'name_en' => 'Floor 21',
                'name_ar' => 'الدور الحادى والعشرين',
            ],
            [
                'slug' => 'floor-22',
                'name_en' => 'Floor 22',
                'name_ar' => 'الدور الثانى والعشرين',
            ],
            [
                'slug' => 'floor-23',
                'name_en' => 'Floor 23',
                'name_ar' => 'الدور الثالث والعشرين',
            ],
            [
                'slug' => 'floor-24',
                'name_en' => 'Floor 24',
                'name_ar' => 'الدور الرابع والعشرين',
            ],
            [
                'slug' => 'floor-25',
                'name_en' => 'Floor 25',
                'name_ar' => 'الدور الخامس والعشرين',
            ],
            [
                'slug' => 'floor-26',
                'name_en' => 'Floor 26',
                'name_ar' => 'الدور السادس والعشرين',
            ],
            [
                'slug' => 'floor-27',
                'name_en' => 'Floor 27',
                'name_ar' => 'الدور السابع والعشرين',
            ],
            [
                'slug' => 'floor-28',
                'name_en' => 'Floor 28',
                'name_ar' => 'الدور الثامن والعشرين',
            ],
            [
                'slug' => 'floor-29',
                'name_en' => 'Floor 29',
                'name_ar' => 'الدور التاسع والعشرين',
            ],
            [
                'slug' => 'floor-30',
                'name_en' => 'Floor 30',
                'name_ar' => 'الدور الثلاثين',
            ],
            [
                'slug' => 'roof',
                'name_en' => 'Roof',
                'name_ar' => 'السطح',
            ],
        ],
    ],
    [
        'slug' => 'furnishingstatus',
        'name_en' => 'Furnishing Status',
        'name_ar' => 'حالة الفرش',
        'items' => [
            [
                'slug' => 'furnished',
                'name_en' => 'Furnished',
                'name_ar' => 'مفروشة',
            ],
            [
                'slug' => 'unfurnished',
                'name_en' => 'Unfurnished',
                'name_ar' => 'غير مفروشة',
            ],
            [
                'slug' => 'partly-furnished',
                'name_en' => 'Partly Furnished',
                'name_ar' => 'مفروشة جزئيا',
            ],
        ],
    ],
    [
        'slug' => 'ownershiptype',
        'name_en' => 'Ownership Type',
        'name_ar' => 'نوع الملكية',
        'items' => [
            [
                'slug' => 'freehold',
                'name_en' => 'Freehold',
                'name_ar' => 'تمليك',
            ],
            [
                'slug' => 'leasehold',
                'name_en' => 'Leasehold',
                'name_ar' => 'حق انتفاع',
            ],
        ],
    ],
    [
        'slug' => 'offeringtype',
        'name_en' => 'Offering Type',
        'name_ar' => 'نوع العرض',
        'items' => [
            [
                'slug' => 'rent',
                'name_en' => 'Rent',
                'name_ar' => 'للإيجار',
            ],
            [
                'slug' => 'resale',
                'name_en' => 'Resale',
                'name_ar' => 'لإعادة بيعها',
            ],
            [
                'slug' => 'primary',
                'name_en' => 'Primary',
                'name_ar' => 'ابتدائي',
            ],
        ],
    ],
    [
        'slug' => 'paymentmethod',
        'name_en' => 'Payment Method',
        'name_ar' => 'طريقة الدفع',
        'items' => [
            [
                'slug' => 'cash',
                'name_en' => 'Cash',
                'name_ar' => 'نقدا',
            ],
            [
                'slug' => 'installments',
                'name_en' => 'Installments',
                'name_ar' => 'الأقساط',
            ],
        ],
    ],
    [
        'slug' => 'projectstatus',
        'name_en' => 'Project Status',
        'name_ar' => 'حالة المشروع',
        'items' => [
            [
                'slug' => 'under-construction',
                'name_en' => 'Under Construction',
                'name_ar' => 'قيد الإنشاء',
            ],
            [
                'slug' => 'ready-to-move',
                'name_en' => 'Ready to Move',
                'name_ar' => 'جاهز للسكن',
            ],
            [
                'slug' => 'off-plan',
                'name_en' => 'Off Plan',
                'name_ar' => 'على المخطط',
            ],
        ],
    ],
    [
        'slug' => 'saletype',
        'name_en' => 'Sale Type',
        'name_ar' => 'نوع البيع',
        'items' => [
            [
                'slug' => 'new-sale',
                'name_en' => 'New Sale',
                'name_ar' => 'بيع جديد',
            ],
            [
                'slug' => 're-sale',
                'name_en' => 'Re-Sale',
                'name_ar' => 'إعادة بيع',
            ],
        ],
    ],
    [
        'slug' => 'propertyusage',
        'name_en' => 'Property Usage',
        'name_ar' => 'استخدام العقار',
        'items' => [
            [
                'slug' => 'residential',
                'name_en' => 'Residential',
                'name_ar' => 'سكني',
            ],
            [
                'slug' => 'investment',
                'name_en' => 'Investment',
                'name_ar' => 'استثماري',
            ],
            [
                'slug' => 'commercial',
                'name_en' => 'Commercial',
                'name_ar' => 'تجاري',
            ],
        ],
    ],
    [
        'slug' => 'unitstatus',
        'name_en' => 'Unit Status',
        'name_ar' => 'حالة الوحدة',
        'items' => [
            [
                'slug' => 'us-ready-to-move',
                'name_en' => 'Ready to Move',
                'name_ar' => 'جاهز للسكن',
            ],
            [
                'slug' => 'us-available',
                'name_en' => 'Available',
                'name_ar' => 'متاح',
            ],
            [
                'slug' => 'us-limited-units',
                'name_en' => 'Limited Units',
                'name_ar' => 'وحدات محدودة',
            ],
            [
                'slug' => 'us-off-plan',
                'name_en' => 'Off-Plan',
                'name_ar' => 'على المخطط',
            ],
            [
                'slug' => 'us-under-construction',
                'name_en' => 'Under Construction',
                'name_ar' => 'قيد الإنشاء',
            ],
        ],
    ],
    [
        'slug' => 'department',
        'name_en' => 'Department',
        'name_ar' => 'القسم',
        'items' => [
            [
                'slug' => 'sales',
                'name_en' => 'Sales',
                'name_ar' => 'المبيعات',
            ],
            [
                'slug' => 'marketing',
                'name_en' => 'Marketing',
                'name_ar' => 'التسويق',
            ],
            [
                'slug' => 'customer-support',
                'name_en' => 'Customer Support',
                'name_ar' => 'خدمة العملاء',
            ],
            [
                'slug' => 'design',
                'name_en' => 'Design',
                'name_ar' => 'التصميم',
            ],
            [
                'slug' => 'engineering',
                'name_en' => 'Engineering',
                'name_ar' => 'الهندسة',
            ],
            [
                'slug' => 'finance',
                'name_en' => 'Finance',
                'name_ar' => 'المالية',
            ],
            [
                'slug' => 'human-resources',
                'name_en' => 'Human Resources',
                'name_ar' => 'الموارد البشرية',
            ],
        ],
    ],
    [
        'slug' => 'benefits',
        'name_en' => 'Benefits',
        'name_ar' => 'المزايا',
        'items' => [
            [
                'slug' => 'uncapped-commission',
                'name_en' => 'Uncapped Commission',
                'name_ar' => 'عمولة غير محدودة',
            ],
            [
                'slug' => 'premium-health-insurance',
                'name_en' => 'Premium Health Insurance',
                'name_ar' => 'تأمين صحي مميز',
            ],
            [
                'slug' => 'annual-flight-allowance',
                'name_en' => 'Annual Flight Allowance',
                'name_ar' => 'بدل سفر سنوي',
            ],
            [
                'slug' => 'latest-tech-marketing-support',
                'name_en' => 'Latest Tech & Marketing Support',
                'name_ar' => 'أحدث الدعم التقني والتسويقي',
            ],
            [
                'slug' => 'performance-bonuses',
                'name_en' => 'Performance Bonuses',
                'name_ar' => 'مكافآت الأداء',
            ],
            [
                'slug' => 'paid-time-off',
                'name_en' => 'Paid Time Off',
                'name_ar' => 'إجازات مدفوعة الأجر',
            ],
            [
                'slug' => 'professional-development',
                'name_en' => 'Professional Development',
                'name_ar' => 'التطوير المهني',
            ],
        ],
    ],
    [
        'slug' => 'locations',
        'name_en' => 'Locations',
        'name_ar' => 'المواقع',
        'items' => [
            [
                'slug' => 'egypt',
                'name_en' => 'Egypt',
                'name_ar' => 'مصر',
                'items' => [
                    [
                        'slug' => 'cairo',
                        'name_en' => 'Cairo',
                        'name_ar' => 'القاهرة',
                        'items' => [
                            [
                                'slug' => 'new-cairo',
                                'name_en' => 'New Cairo',
                                'name_ar' => 'القاهرة الجديدة',
                                'items' => [
                                    [
                                        'slug' => 'fifth-settlement',
                                        'name_en' => 'Fifth Settlement',
                                        'name_ar' => 'التجمع الخامس',
                                        'items' => [
                                            [
                                                'slug' => 'fifth-settlement-sub-1',
                                                'name_en' => 'Fifth Settlement Sub 1',
                                                'name_ar' => 'التجمع الخامس الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'fifth-settlement-sub-2',
                                                'name_en' => 'Fifth Settlement Sub 2',
                                                'name_ar' => 'التجمع الخامس الفرعية 2',
                                            ],
                                        ],
                                    ],
                                    [
                                        'slug' => 'golden-square',
                                        'name_en' => 'Golden Square',
                                        'name_ar' => 'الجولدن سكوير',
                                        'items' => [
                                            [
                                                'slug' => 'golden-square-sub-1',
                                                'name_en' => 'Golden Square Sub 1',
                                                'name_ar' => 'الجولدن سكوير الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'golden-square-sub-2',
                                                'name_en' => 'Golden Square Sub 2',
                                                'name_ar' => 'الجولدن سكوير الفرعية 2',
                                            ],
                                        ],
                                    ],
                                    [
                                        'slug' => 'first-settlement',
                                        'name_en' => 'First Settlement',
                                        'name_ar' => 'التجمع الأول',
                                        'items' => [
                                            [
                                                'slug' => 'first-settlement-sub-1',
                                                'name_en' => 'First Settlement Sub 1',
                                                'name_ar' => 'التجمع الأول الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'first-settlement-sub-2',
                                                'name_en' => 'First Settlement Sub 2',
                                                'name_ar' => 'التجمع الأول الفرعية 2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'slug' => 'nasr-city',
                                'name_en' => 'Nasr City',
                                'name_ar' => 'مدينة نصر',
                                'items' => [
                                    [
                                        'slug' => 'abbas-el-akkad',
                                        'name_en' => 'Abbas El Akkad',
                                        'name_ar' => 'عباس العقاد',
                                        'items' => [
                                            [
                                                'slug' => 'abbas-el-akkad-sub-1',
                                                'name_en' => 'Abbas El Akkad Sub 1',
                                                'name_ar' => 'عباس العقاد الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'abbas-el-akkad-sub-2',
                                                'name_en' => 'Abbas El Akkad Sub 2',
                                                'name_ar' => 'عباس العقاد الفرعية 2',
                                            ],
                                        ],
                                    ],
                                    [
                                        'slug' => 'el-hegaz-square',
                                        'name_en' => 'El Hegaz Square',
                                        'name_ar' => 'ميدان الحجاز',
                                        'items' => [
                                            [
                                                'slug' => 'el-hegaz-square-sub-1',
                                                'name_en' => 'El Hegaz Square Sub 1',
                                                'name_ar' => 'ميدان الحجاز الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'el-hegaz-square-sub-2',
                                                'name_en' => 'El Hegaz Square Sub 2',
                                                'name_ar' => 'ميدان الحجاز الفرعية 2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'slug' => 'heliopolis',
                                'name_en' => 'Heliopolis',
                                'name_ar' => 'مصر الجديدة',
                                'items' => [
                                    [
                                        'slug' => 'roxy',
                                        'name_en' => 'Roxy',
                                        'name_ar' => 'روكسي',
                                    ],
                                    [
                                        'slug' => 'korba',
                                        'name_en' => 'Korba',
                                        'name_ar' => 'الكوربة',
                                        'items' => [
                                            [
                                                'slug' => 'korba-sub-1',
                                                'name_en' => 'Korba Sub 1',
                                                'name_ar' => 'الكوربة الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'korba-sub-2',
                                                'name_en' => 'Korba Sub 2',
                                                'name_ar' => 'الكوربة الفرعية 2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'slug' => 'maadi',
                                'name_en' => 'Maadi',
                                'name_ar' => 'المعادي',
                                'items' => [
                                    [
                                        'slug' => 'old-maadi',
                                        'name_en' => 'Old Maadi',
                                        'name_ar' => 'المعادي القديمة',
                                        'items' => [
                                            [
                                                'slug' => 'old-maadi-sub-1',
                                                'name_en' => 'Old Maadi Sub 1',
                                                'name_ar' => 'المعادي القديمة الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'old-maadi-sub-2',
                                                'name_en' => 'Old Maadi Sub 2',
                                                'name_ar' => 'المعادي القديمة الفرعية 2',
                                            ],
                                        ],
                                    ],
                                    [
                                        'slug' => 'digla',
                                        'name_en' => 'Digla',
                                        'name_ar' => 'دجلة',
                                        'items' => [
                                            [
                                                'slug' => 'digla-sub-1',
                                                'name_en' => 'Digla Sub 1',
                                                'name_ar' => 'دجلة الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'digla-sub-2',
                                                'name_en' => 'Digla Sub 2',
                                                'name_ar' => 'دجلة الفرعية 2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'giza',
                        'name_en' => 'Giza',
                        'name_ar' => 'الجيزة',
                        'items' => [
                            [
                                'slug' => '6th-of-october',
                                'name_en' => '6th of October',
                                'name_ar' => '6 أكتوبر',
                                'items' => [
                                    [
                                        'slug' => 'sheikh-zayed',
                                        'name_en' => 'Sheikh Zayed',
                                        'name_ar' => 'الشيخ زايد',
                                    ],
                                    [
                                        'slug' => 'hadayek-october',
                                        'name_en' => 'Hadayek October',
                                        'name_ar' => 'حدائق أكتوبر',
                                        'items' => [
                                            [
                                                'slug' => 'hadayek-october-sub-1',
                                                'name_en' => 'Hadayek October Sub 1',
                                                'name_ar' => 'حدائق أكتوبر الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'hadayek-october-sub-2',
                                                'name_en' => 'Hadayek October Sub 2',
                                                'name_ar' => 'حدائق أكتوبر الفرعية 2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'slug' => 'sheikh-zayed-city',
                                'name_en' => 'Sheikh Zayed City',
                                'name_ar' => 'مدينة الشيخ زايد',
                                'items' => [
                                    [
                                        'slug' => 'beverly-hills',
                                        'name_en' => 'Beverly Hills',
                                        'name_ar' => 'بيفرلي هيلز',
                                        'items' => [
                                            [
                                                'slug' => 'beverly-hills-sub-1',
                                                'name_en' => 'Beverly Hills Sub 1',
                                                'name_ar' => 'بيفرلي هيلز الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'beverly-hills-sub-2',
                                                'name_en' => 'Beverly Hills Sub 2',
                                                'name_ar' => 'بيفرلي هيلز الفرعية 2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'north-coast',
                        'name_en' => 'North Coast',
                        'name_ar' => 'الساحل الشمالي',
                        'items' => [
                            [
                                'slug' => 'ras-el-hekma',
                                'name_en' => 'Ras El Hekma',
                                'name_ar' => 'رأس الحكمة',
                                'items' => [
                                    [
                                        'slug' => 'fouka-bay',
                                        'name_en' => 'Fouka Bay',
                                        'name_ar' => 'فوكة باي',
                                        'items' => [
                                            [
                                                'slug' => 'fouka-bay-sub-1',
                                                'name_en' => 'Fouka Bay Sub 1',
                                                'name_ar' => 'فوكة باي الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'fouka-bay-sub-2',
                                                'name_en' => 'Fouka Bay Sub 2',
                                                'name_ar' => 'فوكة باي الفرعية 2',
                                            ],
                                        ],
                                    ],
                                    [
                                        'slug' => 'marassi',
                                        'name_en' => 'Marassi',
                                        'name_ar' => 'مراسي',
                                        'items' => [
                                            [
                                                'slug' => 'marassi-sub-1',
                                                'name_en' => 'Marassi Sub 1',
                                                'name_ar' => 'مراسي الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'marassi-sub-2',
                                                'name_en' => 'Marassi Sub 2',
                                                'name_ar' => 'مراسي الفرعية 2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'slug' => 'marina',
                                'name_en' => 'Marina',
                                'name_ar' => 'مارينا',
                                'items' => [
                                    [
                                        'slug' => 'marina-1',
                                        'name_en' => 'Marina 1',
                                        'name_ar' => 'مارينا 1',
                                        'items' => [
                                            [
                                                'slug' => 'marina-1-sub-1',
                                                'name_en' => 'Marina 1 Sub 1',
                                                'name_ar' => 'مارينا 1 الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'marina-1-sub-2',
                                                'name_en' => 'Marina 1 Sub 2',
                                                'name_ar' => 'مارينا 1 الفرعية 2',
                                            ],
                                        ],
                                    ],
                                    [
                                        'slug' => 'marina-5',
                                        'name_en' => 'Marina 5',
                                        'name_ar' => 'مارينا 5',
                                        'items' => [
                                            [
                                                'slug' => 'marina-5-sub-1',
                                                'name_en' => 'Marina 5 Sub 1',
                                                'name_ar' => 'مارينا 5 الفرعية 1',
                                            ],
                                            [
                                                'slug' => 'marina-5-sub-2',
                                                'name_en' => 'Marina 5 Sub 2',
                                                'name_ar' => 'مارينا 5 الفرعية 2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'new-administrative-capital',
                        'name_en' => 'New Administrative Capital',
                        'name_ar' => 'العاصمة الإدارية الجديدة',
                        'items' => [
                            [
                                'slug' => 'r1-residential-district',
                                'name_en' => 'R1 Residential District',
                                'name_ar' => 'R1 الحي السكني',
                                'items' => [
                                    [
                                        'slug' => 'r1-villas-district',
                                        'name_en' => 'R1 Villas District',
                                        'name_ar' => 'R1 - منطقة الفيلات',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'slug' => 'blog_categories',
        'name_en' => 'Blog Categories',
        'name_ar' => 'تصنيفات المدونة',
        'items' => [
            [
                'slug' => 'market-news',
                'name_en' => 'Market News',
                'name_ar' => 'أخبار السوق',
            ],
            [
                'slug' => 'real-estate-tips',
                'name_en' => 'Real Estate Tips',
                'name_ar' => 'نصائح عقارية',
            ],
            [
                'slug' => 'new-projects',
                'name_en' => 'New Projects',
                'name_ar' => 'مشاريع جديدة',
            ],
            [
                'slug' => 'economic-insights',
                'name_en' => 'Economic Insights',
                'name_ar' => 'رؤى اقتصادية',
            ],
            [
                'slug' => 'lifestyle',
                'name_en' => 'Lifestyle',
                'name_ar' => 'أسلوب حياة',
            ],
        ],
    ],
];
