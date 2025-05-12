<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

class ProductReviewsController
{
    use CommentValidationTrait;

    private CaptchaManager $captcha_manager;

    public function __construct(private View $view, private string $reviewform, private int $product_id, CaptchaManager $captcha_manager)
    {
        $this->view = $view;
        $this->reviewform = $reviewform;
        $this->product_id = $product_id;
        $this->captcha_manager = $captcha_manager;

        add_action('wp_ajax_submit_review', [$this, 'handle_ajax_submit']);
        add_action('wp_ajax_nopriv_submit_review', [$this, 'handle_ajax_submit']);

        add_action('wp_ajax_filter_reviews', [$this, 'filterReviews']);
        add_action('wp_ajax_nopriv_filter_reviews', [$this, 'filterReviews']);
    }

    public function prepareReviewsData()
    {
        $reviews = get_comments([
            'post_id' => $this->product_id,
            'status' => 'approve',
            'hierarchical' => true,
        ]);

        return $this->formatReviews($reviews);
    }

    private function formatReviews($reviews)
    {
        $formatted_reviews = [];
        $all_reviews = [];

        // اول همه دیدگاه‌ها را فرمت می‌کنیم و در آرایه ذخیره می‌کنیم
        foreach ($reviews as $review) {
            $all_reviews[$review->comment_ID] = [
                'data' => $this->formatSingleReview($review),
                'replies' => [],
                'parent' => $review->comment_parent
            ];
        }

        // حالا ساختار درختی را می‌سازیم
        foreach ($all_reviews as $id => $review) {
            if ($review['parent'] == 0) {
                // این یک دیدگاه اصلی است
                $formatted_reviews[] = $this->buildReviewTree($id, $all_reviews);
            }
        }

        return $formatted_reviews;
    }

    private function buildReviewTree($review_id, &$all_reviews)
    {
        $review = $all_reviews[$review_id];
        $review_data = $review['data'];

        // پیدا کردن همه پاسخ‌های مستقیم به این دیدگاه
        foreach ($all_reviews as $id => $potential_reply) {
            if ($potential_reply['parent'] == $review_id) {
                $review_data['replies'][] = $this->buildReviewTree($id, $all_reviews);
            }
        }

        return $review_data;
    }

    private function formatSingleReview($review)
    {
        return [
            'id' => $review->comment_ID,
            'author' => $review->comment_author,
            'content' => $review->comment_content,
            'date' => $this->getTimeAgo(strtotime($review->comment_date)),
            'rating' => get_comment_meta($review->comment_ID, 'rating', true),
            'is_admin_reply' => $this->isAdminReply($review),
            'parent_id' => $review->comment_parent,
            'replies' => []
        ];
    }

    private function isAdminReply($review)
    {
        $user = get_user_by('id', $review->user_id);
        return $user && in_array('administrator', $user->roles);
    }

    private function getTimeAgo($time)
    {
        $diff = time() - $time;

        if ($diff < 60) {
            return 'لحظاتی پیش';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' دقیقه پیش';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' ساعت پیش';
        } else {
            return floor($diff / 86400) . ' روز پیش';
        }
    }

    public function loadReplies()
    {
        $comment_id = intval($_GET['comment_id']);
        $replies = $this->getReplies($comment_id);
        $this->view->render_with_exit('@views/components/single-product/replies.twig', ['replies' => $replies], 200);
    }


    private function getReplies($parent_id)
    {
        $replies = get_comments([
            'parent' => $parent_id,
            'status' => 'approve'
        ]);

        $formatted_replies = [];
        foreach ($replies as $reply) {
            $formatted_replies[] = $this->formatSingleReview($reply);
        }

        return $formatted_replies;
    }






    public function getReviewForm()
    {
        $product = wc_get_product($this->product_id);
        $is_logged_in = is_user_logged_in();
        $captcha_field = $this->captcha_manager->render_captcha();
        $honeypot_field = '<input type="text" name="hp_field" value="" style="display:none;">';

        return [
            'product' => $product,
            'is_logged_in' => $is_logged_in,
            'captcha_field' => $captcha_field,
            'honeypot_field' => $honeypot_field,
            'submit_review_nonce' => wp_create_nonce('submit_review_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'csrf_token' => wp_create_nonce('csrf_token')
        ];
    }

    public function submitReview()
    {
        // استفاده از توابع Trait
        $this->verifyNonce($_POST['nonce'], 'submit_review_nonce');
        $this->verifyCaptcha($this->captcha_manager, $_POST);
        $this->verifyHoneypot($_POST);
        $this->applyRateLimiting('review');

        // اعتبارسنجی فیلدها
        $fields = [
            'review_text' => [
                'value' => $_POST['review_text'] ?? '',
                'rules' => ['required' => true]
            ],
            'rating' => [
                'value' => $_POST['rating'] ?? '',
                'rules' => ['required' => true, 'type' => 'integer', 'min' => 1, 'max' => 5]
            ]
        ];
        $this->validateFields($fields, is_user_logged_in());

        $review_text = sanitize_textarea_field($_POST['review_text']);
        $this->validateTextLength($review_text);

        // ذخیره دیدگاه
        $review_data = [
            'comment_post_ID' => $_POST['product_id'],
            'comment_content' => $review_text,
            'comment_type' => 'review',
            'comment_parent' => 0,
            'user_id' => get_current_user_id(),
            'comment_meta' => [
                'rating' => intval($_POST['rating'])
            ]
        ];

        if (!is_user_logged_in()) {
            $review_data['comment_author'] = sanitize_text_field($_POST['author_name']);
            $review_data['comment_author_email'] = sanitize_email($_POST['author_email']);
            if (get_option('require_name_email') && (!$review_data['comment_author'] || !$review_data['comment_author_email'])) {
                $this->view->render_with_exit('@views/components/single-product/review-responses/missing-author-info.twig', [], 400);
            }
        }

        $require_moderation = get_option('comment_moderation') === '1';
        if ($require_moderation) {
            $review_data['comment_approved'] = '0';
        } else {
            $approved_comment = get_comments([
                'author_email' => $review_data['comment_author_email'],
                'status' => 'approve',
                'count' => true
            ]);
            if (get_option('comment_previously_approved') === '1' && empty($approved_comment)) {
                $review_data['comment_approved'] = '0';
            } else {
                $review_data['comment_approved'] = '1';
            }
        }

        $comment_id = wp_insert_comment($review_data);
        if ($comment_id) {
            $this->view->render_with_exit(
                $review_data['comment_approved'] === '0'
                    ? '@views/components/single-product/review-responses/success-pending.twig'
                    : '@views/components/single-product/review-responses/success.twig',
                [],
                200
            );
        } else {
            $this->view->render_with_exit('@views/components/single-product/review-responses/error.twig', [], 500);
        }
    }

    public function handle_ajax_submit()
    {
        $this->submitReview();
    }

    protected function getResponseTemplate(string $responseType): string
    {
        return "@views/components/single-product/review-responses/{$responseType}.twig";
    }


    public function render_review_form()
    {

        $template_data = $this->getReviewForm($this->product_id);

        return $this->view->render($this->reviewform, $template_data);
    }

    public function submitReply()
    {
        $this->verifyNonce($_POST['nonce'], 'submit_review_nonce');
        $reply_text = sanitize_textarea_field($_POST['reply_text']);
        $parent_id = intval($_POST['parent_id']);

        $reply_data = [
            'comment_post_ID' => $this->product_id,
            'comment_content' => $reply_text,
            'comment_type' => 'review',
            'comment_parent' => $parent_id,
            'user_id' => get_current_user_id(),
        ];

        $comment_id = wp_insert_comment($reply_data);
        if ($comment_id) {
            $this->view->render_with_exit('@views/components/single-product/review-responses/success.twig', [], 200);
        } else {
            $this->view->render_with_exit('@views/components/single-product/review-responses/error.twig', [], 500);
        }
    }


    public function filterReviews() {
        $product_id = intval($_GET['product_id']);
        $filter = sanitize_text_field($_GET['filter'] ?? 'latest');
    
        $args = [
            'post_id' => $product_id,
            'status' => 'approve',
            'type' => 'review',
        ];
    
        switch ($filter) {
            case 'oldest':
                $args['orderby'] = 'comment_date';
                $args['order'] = 'ASC';
                break;
            case 'highest_rating':
                $args['meta_key'] = 'rating';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'lowest_rating':
                $args['meta_key'] = 'rating';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'latest':
            default:
                $args['orderby'] = 'comment_date';
                $args['order'] = 'DESC';
                break;
        }
    
        $reviews = get_comments($args);
        $formatted_reviews = $this->formatReviews($reviews);
        $this->view->render_with_exit('@views/components/single-product/reviews-list.twig', ['product' => ['reviews' => $formatted_reviews]], 200);
    }
    
   
}
