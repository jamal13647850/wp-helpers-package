<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Controllers;

defined('ABSPATH') || exit();

class BlogRatingController
{
    public function __construct()
    {
        add_action('wp_ajax_submit_blog_rating', [$this, 'handle_submit_rating']);
        add_action('wp_ajax_nopriv_submit_blog_rating', [$this, 'handle_submit_rating']);
    }

    public function handle_submit_rating()
    {
        check_ajax_referer('blog_rating_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

        if (!$post_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => ' امتیاز نامعتبر است.']);
            wp_die();
        }

        $user_id = get_current_user_id();
        $meta_key = 'blog_user_rating_' . $post_id;

        // بررسی برای کاربران لاGIN‌شده
        if ($user_id > 0) {
            $user_rating = get_user_meta($user_id, $meta_key, true);
            if ($user_rating) {
                wp_send_json_error(['message' => 'شما قبلاً برای این پست امتیاز ثبت کرده‌اید.']);
                wp_die();
            }
            // ذخیره در متادیتای کاربر
            update_user_meta($user_id, $meta_key, '1'); // فقط وضعیت ثبت رو ذخیره می‌کنیم
        } else {
            // بررسی برای کاربران لاGIN‌نشده (مهمان‌ها) با استفاده از کوکی
            $cookie_name = 'blog_rating_' . $post_id;
            if (isset($_COOKIE[$cookie_name])) {
                wp_send_json_error(['message' => 'شما قبلاً برای این پست امتیاز ثبت کرده‌اید.']);
                wp_die();
            }
            // تنظیم کوکی برای 30 روز
            setcookie($cookie_name, '1', time() + (86400 * 30), '/');
        }

        // کلیدهای متا برای مجموع امتیازات و تعداد امتیازات
        $total_ratings_key = 'blog_rating_total';
        $count_ratings_key = 'blog_rating_count';

        // گرفتن مقادیر فعلی
        $current_total = (int) get_post_meta($post_id, $total_ratings_key, true);
        $current_count = (int) get_post_meta($post_id, $count_ratings_key, true);

        // به‌روزرسانی مجموع و تعداد امتیازات
        $new_total = $current_total + $rating;
        $new_count = $current_count + 1;

        update_post_meta($post_id, $total_ratings_key, $new_total);
        update_post_meta($post_id, $count_ratings_key, $new_count);

        // محاسبه میانگین
        $average_rating = $this->calculateRating($post_id);

        wp_send_json_success([
            'message' => ' امتیاز شما با موفقیت ثبت شد!',
            'average_rating' => round($average_rating, 1)
        ]);
        wp_die();
    }

    public function calculateRating($post_id)
    {
        $total_ratings_key = 'blog_rating_total';
        $count_ratings_key = 'blog_rating_count';
        $current_total = (int) get_post_meta($post_id, $total_ratings_key, true);
        $current_count = (int) get_post_meta($post_id, $count_ratings_key, true);

        return $current_count > 0 ? $current_total / $current_count : 0;
    }
    public function hasUserRated($post_id)
    {
        $user_id = get_current_user_id();
        $has_user_rated = false;
        $meta_key = 'blog_user_rating_' . $post_id;

        if ($user_id > 0) {
            $user_rating = get_user_meta($user_id, $meta_key, true);
            $has_user_rated = !empty($user_rating);
        } else {
            $cookie_name = 'blog_rating_' . $post_id;
            $has_user_rated = isset($_COOKIE[$cookie_name]);
        }

        return $has_user_rated;
    }
}
