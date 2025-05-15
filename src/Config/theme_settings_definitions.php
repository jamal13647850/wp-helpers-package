<?php
// File: config/theme_settings_definitions.php

return [
    'general' => [
        'menu_slug'   => 'theme-settings-general-settings',
        'menu_order'  => 0,
        'title'       => 'تنظیمات عمومی',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'اطلاعات پایه',
                'name' => 'tab_basic_info'
            ],
            [
                'type' => 'image',
                'label' => 'لوگوی اصلی سایت',
                'name' => 'logo_main',
                'instructions' => 'لوگو باید حداقل 100×30 پیکسل باشد. فرمت مجاز: webp، svg، avif',
                'required' => true,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'min_width' => 100,
                'min_height' => 30,
                'mime_types' => 'webp,svg,avif'
            ],
            [
                'type' => 'image',
                'label' => 'لوگوی موبایل',
                'name' => 'logo_mobile',
                'instructions' => 'اختیاری',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'mime_types' => 'webp,svg,avif'
            ],
            [
                'type' => 'image',
                'label' => 'فاوآیکون',
                'name' => 'favicon',
                'instructions' => 'سایز 32×32 پیکسل، فرمت: webp، ico',
                'required' => true,
                'return_format' => 'array',
                'preview_size' => 'thumbnail',
                'min_width' => 32,
                'min_height' => 32,
                'mime_types' => 'webp,ico'
            ],
            [
                'type' => 'tab',
                'label' => 'اطلاعات تماس',
                'name' => 'tab_contact_info'
            ],
            [
                'type' => 'text',
                'label' => 'شماره تماس',
                'name' => 'phone',
                'required' => true,
                'instructions' => 'شماره تلفن اصلی سایت',
                'default_value' => ''
            ],
            [
                'type' => 'text',
                'label' => 'شماره موبایل',
                'name' => 'mobile'
            ],
            [
                'type' => 'email',
                'label' => 'ایمیل',
                'name' => 'email',
                'required' => true,
                'instructions' => 'ایمیل رسمی پشتیبانی'
            ],
            [
                'type' => 'textarea',
                'label' => 'آدرس',
                'name' => 'address',
                'rows' => 3,
            ],
        ]
    ],

    'header' => [
        'menu_slug'   => 'theme-settings-header-settings',
        'menu_order'  => 1,
        'title'       => 'تنظیمات هدر',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'تنظیمات کلی هدر',
                'name' => 'tab_general'
            ],
            [
                'type' => 'true_false',
                'label' => 'هدر چسبان',
                'name' => 'sticky_header',
                'instructions' => 'فعال کردن هدر چسبان در بالای صفحه',
                'default_value' => 1,
            ],
            [
                'type' => 'tab',
                'label' => 'نوار بالای هدر',
                'name' => 'tab_topbar'
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش نوار بالای هدر',
                'name' => 'topbar_show',
                'default_value' => 1,
            ],
            [
                'type' => 'text',
                'label' => 'متن نوار بالا',
                'name' => 'topbar_text',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'header_topbar_show',
                            'operator' => '==',
                            'value' => '1'
                        ]
                    ]
                ],
            ],
            [
                'type' => 'tab',
                'label' => 'منوها و آیکون‌ها',
                'name' => 'tab_menus'
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش جستجو',
                'name' => 'show_search',
                'default_value' => 1
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش سبد خرید',
                'name' => 'show_cart',
                'default_value' => 1
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش علاقه‌مندی‌ها',
                'name' => 'show_wishlist',
                'default_value' => 1
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش حساب کاربری',
                'name' => 'show_account',
                'default_value' => 1
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش مقایسه محصولات',
                'name' => 'show_compare',
                'default_value' => 1
            ],
        ]
    ],

    'footer' => [
        'menu_slug'   => 'theme-settings-footer-settings',
        'menu_order'  => 2,
        'title'       => 'تنظیمات فوتر',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'تنظیمات کلی فوتر',
                'name' => 'tab_general'
            ],
            [
                'type' => 'select',
                'label' => 'استایل فوتر',
                'name' => 'footer_style',
                'choices' => [
                    'style1' => 'استایل 1 (4 ستون)',
                    'style2' => 'استایل 2 (3 ستون)',
                    'style3' => 'استایل 3 (2 ستون)'
                ],
                'default_value' => 'style1',
            ],
            // بقیه ستون‌ها (مثال ستون اول:)
            [
                'type' => 'tab',
                'label' => 'ستون اول',
                'name' => 'tab_column1'
            ],
            [
                'type' => 'text',
                'label' => 'عنوان ستون اول',
                'name' => 'col1_title'
            ],
            [
                'type' => 'select',
                'label' => 'نوع محتوا',
                'name' => 'col1_content_type',
                'choices' => [
                    'custom' => 'محتوای سفارشی',
                    'menu'   => 'منو'
                ],
                'default_value' => 'custom'
            ],
            [
                'type' => 'wysiwyg',
                'label' => 'محتوای سفارشی',
                'name'  => 'col1_custom_content',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'footer_col1_content_type',
                            'operator' => '==',
                            'value' => 'custom'
                        ]
                    ]
                ]
            ],
            [
                'type' => 'nav_menu',
                'label' => 'انتخاب منو',
                'name'  => 'col1_menu',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'footer_col1_content_type',
                            'operator' => '==',
                            'value' => 'menu'
                        ]
                    ]
                ]
            ],
            // ستون‌های بعدی، تب فوتر پایین، کپی‌رایت و گالری آیکون پرداخت دقیقاً مانند کلاس، به همین صورت قابل افزایش است.
        ]
    ],

    // ادامه theme_settings_definitions.php

    'homepage' => [
        'menu_slug'   => 'theme-settings-homepage-settings',
        'menu_order'  => 3,
        'title'       => 'تنظیمات صفحه اصلی',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'اسلایدر',
                'name' => 'tab_slider',
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش اسلایدر',
                'name' => 'slider_show',
                'default_value' => 1,
                'ui' => 1
            ],
            [
                'type' => 'repeater',
                'label' => 'اسلایدها',
                'name' => 'slider_slides',
                'instructions' => 'افزودن اسلاید جدید به اسلایدر',
                'required' => 1,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'homepage_slider_show',
                            'operator' => '==',
                            'value' => '1'
                        ]
                    ]
                ],
                'min' => 0,
                'layout' => 'block',
                'button_label' => 'افزودن اسلاید',
                'sub_fields' => [
                    [
                        'type' => 'image',
                        'label' => 'تصویر اسلاید',
                        'name' => 'image',
                        'required' => 1,
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'min_width' => 1200,
                        'min_height' => 500,
                        'mime_types' => 'webp,svg,avif,jpg,jpeg,png',
                        'instructions' => 'حداقل سایز تصویر: 1200×500 پیکسل'
                    ],
                    [
                        'type' => 'text',
                        'label' => 'عنوان',
                        'name' => 'title'
                    ],
                    [
                        'type' => 'text',
                        'label' => 'زیرعنوان',
                        'name' => 'subtitle'
                    ],
                    [
                        'type' => 'text',
                        'label' => 'متن دکمه',
                        'name' => 'button_text'
                    ],
                    [
                        'type' => 'url',
                        'label' => 'لینک دکمه',
                        'name' => 'button_link'
                    ],
                    [
                        'type' => 'select',
                        'label' => 'موقعیت متن',
                        'name' => 'text_position',
                        'choices' => [
                            'right' => 'راست',
                            'left'  => 'چپ',
                            'center' => 'وسط'
                        ],
                        'default_value' => 'right'
                    ]
                ]
            ],
            // سایر تب‌ها و فیلدهای محصولات ویژه، بنرها و ... دقیقاً مطابق کلاس 
            // توصیه: برای readability، فقط نماینده برخی فیلدهای اصلی آورده شده
        ]
    ],

    'shop' => [
        'menu_slug'   => 'theme-settings-shop-settings',
        'menu_order'  => 4,
        'title'       => 'تنظیمات فروشگاه',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'تنظیمات نمایش محصولات',
                'name' => 'tab_products_display'
            ],
            [
                'type' => 'number',
                'label' => 'تعداد محصولات در هر صفحه',
                'name' => 'products_per_page',
                'default_value' => 12,
                'min' => 1
            ],
            [
                'type' => 'select',
                'label' => 'نوع نمایش پیش‌فرض',
                'name' => 'default_display_type',
                'choices' => [
                    'grid' => 'شبکه‌ای',
                    'list' => 'لیستی'
                ],
                'default_value' => 'grid'
            ],
            [
                'type' => 'select',
                'label' => 'تعداد ستون‌ها در حالت دسکتاپ',
                'name' => 'columns_desktop',
                'choices' => [2 => '2', 3 => '3', 4 => '4', 5 => '5'],
                'default_value' => 4
            ],
            // ... سایر فیلدهای Shop مطابق کلاس
        ]
    ],

    'inner' => [
        'menu_slug'   => 'theme-settings-inner-pages-settings',
        'menu_order'  => 5,
        'title'       => 'تنظیمات صفحات داخلی',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'تنظیمات کلی صفحات',
                'name' => 'tab_general'
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش عنوان صفحه',
                'name' => 'show_page_title',
                'default_value' => 1,
                'ui' => 1
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش مسیر (breadcrumb)',
                'name' => 'show_breadcrumb',
                'default_value' => 1,
                'ui' => 1
            ],
            [
                'type' => 'image',
                'label' => 'تصویر هدر پیش‌فرض',
                'name' => 'default_header_image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'mime_types' => 'webp,svg,avif,jpg,jpeg,png'
            ],
            [
                'type' => 'number',
                'label' => 'ارتفاع هدر صفحات (پیکسل)',
                'name' => 'header_height',
                'default_value' => 300,
                'min' => 100,
                'max' => 1000,
            ],
        ]
    ],

    'contact' => [
        'menu_slug'   => 'theme-settings-contact-settings',
        'menu_order'  => 6,
        'title'       => 'تنظیمات تماس با ما',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'اطلاعات تماس',
                'name' => 'tab_contact_info'
            ],
            [
                'type' => 'text',
                'label' => 'عنوان صفحه',
                'name' => 'page_title',
                'default_value' => 'تماس با ما'
            ],
            [
                'type' => 'wysiwyg',
                'label' => 'توضیحات صفحه',
                'name' => 'page_description'
            ],
            [
                'type' => 'email',
                'label' => 'ایمیل تماس',
                'name' => 'email'
            ],
            [
                'type' => 'text',
                'label' => 'شماره تماس',
                'name' => 'phone'
            ],
            // بقیه فیلدهای تماس طبق کلاس ...
        ]
    ],
    'about' => [
        'menu_slug'   => 'theme-settings-about-settings',
        'menu_order'  => 7,
        'title'       => 'تنظیمات درباره ما',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'محتوای اصلی',
                'name' => 'tab_main_content'
            ],
            [
                'type' => 'text',
                'label' => 'عنوان صفحه',
                'name' => 'page_title',
                'default_value' => 'درباره ما',
            ],
            [
                'type' => 'textarea',
                'label' => 'توضیحات کوتاه',
                'name' => 'short_description',
                'rows' => 3
            ],
            [
                'type' => 'wysiwyg',
                'label' => 'محتوای اصلی',
                'name' => 'main_content'
            ],
            [
                'type' => 'image',
                'label' => 'تصویر اصلی',
                'name' => 'main_image',
                'mime_types' => 'webp,svg,avif,jpg,jpeg,png',
                'return_format' => 'array',
                'preview_size' => 'medium'
            ],
            [
                'type' => 'tab',
                'label' => 'تیم ما',
                'name' => 'tab_team'
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش بخش تیم',
                'name' => 'show_team_section',
                'default_value' => 1,
                'ui' => 1
            ],
            [
                'type' => 'text',
                'label' => 'عنوان بخش',
                'name' => 'team_section_title',
                'default_value' => 'تیم ما'
            ],
            [
                'type' => 'text',
                'label' => 'توضیحات بخش',
                'name' => 'team_section_description'
            ],
            [
                'type' => 'repeater',
                'label' => 'اعضای تیم',
                'name' => 'team_members',
                'button_label' => 'افزودن عضو',
                'sub_fields' => [
                    [
                        'type' => 'image',
                        'label' => 'تصویر',
                        'name' => 'image',
                        'mime_types' => 'webp,svg,avif,jpg,jpeg,png',
                        'return_format' => 'array',
                        'preview_size' => 'thumbnail',
                    ],
                    [
                        'type' => 'text',
                        'label' => 'نام',
                        'name' => 'name'
                    ],
                    [
                        'type' => 'text',
                        'label' => 'سمت',
                        'name' => 'position'
                    ],
                    [
                        'type' => 'text',
                        'label' => 'توضیحات',
                        'name' => 'description'
                    ],
                    [
                        'type' => 'repeater',
                        'label' => 'شبکه‌های اجتماعی',
                        'name' => 'socials',
                        'button_label' => 'افزودن شبکه',
                        'sub_fields' => [
                            [
                                'type' => 'select',
                                'label' => 'نوع شبکه',
                                'name' => 'type',
                                'choices' => [
                                    'instagram' => 'اینستاگرام',
                                    'telegram'  => 'تلگرام',
                                    'linkedin'  => 'لینکدین',
                                    'twitter'   => 'توییتر',
                                ],
                            ],
                            [
                                'type' => 'url',
                                'label' => 'لینک',
                                'name' => 'link'
                            ]
                        ]
                    ]
                ]
            ],
            // تب مشتریان ما و سایر فیلدها در همین سبک
        ]
    ],

    'social' => [
        'menu_slug'   => 'theme-settings-social-settings',
        'menu_order'  => 8,
        'title'       => 'تنظیمات شبکه‌های اجتماعی',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'شبکه‌های اجتماعی',
                'name' => 'tab_social_links'
            ],
            [
                'type' => 'url',
                'label' => 'اینستاگرام',
                'name'  => 'instagram'
            ],
            [
                'type' => 'url',
                'label' => 'تلگرام',
                'name'  => 'telegram'
            ],
            [
                'type' => 'url',
                'label' => 'واتس‌اپ',
                'name'  => 'whatsapp'
            ],
            [
                'type' => 'url',
                'label' => 'لینکدین',
                'name'  => 'linkedin'
            ],
            [
                'type' => 'url',
                'label' => 'توییتر',
                'name'  => 'twitter'
            ],
            [
                'type' => 'url',
                'label' => 'یوتیوب',
                'name'  => 'youtube'
            ],
            [
                'type' => 'url',
                'label' => 'آپارات',
                'name'  => 'aparat'
            ],
            [
                'type' => 'url',
                'label' => 'پینترست',
                'name'  => 'pinterest'
            ],
            [
                'type' => 'tab',
                'label' => 'تنظیمات نمایش',
                'name' => 'tab_display_settings'
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش در هدر',
                'name'  => 'show_in_header',
                'default_value' => 1,
                'ui' => 1,
            ],
            [
                'type' => 'true_false',
                'label' => 'نمایش در فوتر',
                'name'  => 'show_in_footer',
                'default_value' => 1,
                'ui' => 1,
            ],
            [
                'type' => 'select',
                'label' => 'سایز آیکون‌ها',
                'name'  => 'icon_size',
                'choices' => [
                    'small'  => 'کوچک',
                    'medium' => 'متوسط',
                    'large'  => 'بزرگ',
                ],
                'default_value' => 'medium',
            ],
            [
                'type' => 'select',
                'label' => 'رنگ آیکون‌ها',
                'name'  => 'icon_color',
                'choices' => [
                    'color'  => 'رنگی',
                    'bw'     => 'سیاه و سفید',
                    'custom' => 'رنگ سفارشی',
                ],
                'default_value' => 'color',
            ],
            [
                'type' => 'color_picker',
                'label' => 'رنگ سفارشی',
                'name'  => 'custom_color',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'social_icon_color',
                            'operator' => '==',
                            'value' => 'custom',
                        ]
                    ]
                ]
            ]
        ]
    ],

    'scripts' => [
        'menu_slug'   => 'theme-settings-scripts-settings',
        'menu_order'  => 9,
        'title'       => 'تنظیمات اسکریپت‌ها',
        'fields' => [
            [
                'type' => 'tab',
                'label' => 'کدهای سفارشی',
                'name' => 'tab_custom_code'
            ],
            [
                'type' => 'code',
                'label' => 'کد سفارشی CSS',
                'name'  => 'custom_css',
                'language' => 'css',
                'rows' => 6,
            ],
            [
                'type' => 'code',
                'label' => 'کد سفارشی JavaScript',
                'name'  => 'custom_js',
                'language' => 'javascript',
                'rows' => 6,
            ],
            [
                'type' => 'tab',
                'label' => 'کدهای تحلیلی',
                'name' => 'tab_analytics'
            ],
            [
                'type' => 'textarea',
                'label' => 'کد گوگل آنالیتیکس',
                'name'  => 'google_analytics',
                'rows' => 4
            ],
            [
                'type' => 'textarea',
                'label' => 'کد گوگل تگ منیجر',
                'name'  => 'google_tag_manager',
                'rows' => 4
            ],
            [
                'type' => 'textarea',
                'label' => 'سایر کدهای تحلیلی',
                'name'  => 'other_analytics',
                'rows' => 4
            ],
            [
                'type' => 'tab',
                'label' => 'کدهای سربرگ و پاورقی',
                'name' => 'tab_head_footer'
            ],
            [
                'type' => 'textarea',
                'label' => 'کدهای اضافی سربرگ',
                'name'  => 'head_extra_code',
                'rows' => 5,
                'instructions' => 'کدهایی که در داخل تگ head قرار می‌گیرند'
            ],
            [
                'type' => 'textarea',
                'label' => 'کدهای اضافی پاورقی',
                'name'  => 'footer_extra_code',
                'rows' => 5,
                'instructions' => 'کدهایی که قبل از بسته شدن تگ body قرار می‌گیرند'
            ]
        ]
    ],
];
