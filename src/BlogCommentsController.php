<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

class BlogCommentsController
{
    use CommentValidationTrait;

    private View $view;
    private string $comment_form;
    private int $post_id;
    private CaptchaManager $captcha_manager;

    public function __construct(View $view, string $comment_form, int $post_id, CaptchaManager $captcha_manager)
    {
        $this->view = $view;
        $this->comment_form = $comment_form;
        $this->post_id = $post_id;
        $this->captcha_manager = $captcha_manager;

        add_action('wp_ajax_submit_blog_comment', [$this, 'handle_submit_comment']);
        add_action('wp_ajax_nopriv_submit_blog_comment', [$this, 'handle_submit_comment']);

        add_action('wp_ajax_filter_comments', [$this, 'filterComments']);
        add_action('wp_ajax_nopriv_filter_comments', [$this, 'filterComments']);
    }

    public function prepareCommentsData()
    {
        $comments = get_comments([
            'post_id' => $this->post_id,
            'status' => 'approve',
            'type' => 'comment',
        ]);
        return $this->formatComments($comments);
    }

    private function formatComments($comments)
    {
        $formatted_comments = [];
        $all_comments = [];

        foreach ($comments as $comment) {
            $all_comments[$comment->comment_ID] = [
                'data' => $this->formatSingleComment($comment),
                'replies' => [],
                'parent' => $comment->comment_parent,
            ];
        }

        foreach ($all_comments as $id => $comment) {
            if ($comment['parent'] == 0) {
                $formatted_comments[] = $this->buildCommentTree($id, $all_comments);
            }
        }

        return $formatted_comments;
    }

    private function buildCommentTree($comment_id, &$all_comments)
    {
        $comment = $all_comments[$comment_id];
        $comment_data = $comment['data'];

        foreach ($all_comments as $id => $potential_reply) {
            if ($potential_reply['parent'] == $comment_id) {
                $comment_data['replies'][] = $this->buildCommentTree($id, $all_comments);
            }
        }

        return $comment_data;
    }

    private function formatSingleComment($comment)
    {
        return [
            'id' => $comment->comment_ID,
            'author' => $comment->comment_author,
            'content' => $comment->comment_content,
            'date' => $this->getTimeAgo(strtotime($comment->comment_date)),
            'is_admin_reply' => $this->isAdminReply($comment),
            'parent_id' => $comment->comment_parent,
        ];
    }

    private function isAdminReply($comment)
    {
        $user = get_user_by('id', $comment->user_id);
        return $user && in_array('administrator', $user->roles);
    }

    private function getTimeAgo($time)
    {
        $diff = time() - $time;
        if ($diff < 60) return 'لحظاتی پیش';
        elseif ($diff < 3600) return floor($diff / 60) . ' دقیقه پیش';
        elseif ($diff < 86400) return floor($diff / 3600) . ' ساعت پیش';
        else return floor($diff / 86400) . ' روز پیش';
    }

    public function getCommentForm()
    {
        $is_logged_in = is_user_logged_in();
        $post = get_post($this->post_id);
        $captcha_field = $this->captcha_manager->render_captcha();

        return [
            'post' => $post,
            'is_logged_in' => $is_logged_in,
            'submit_comment_nonce' => wp_create_nonce('submit_blog_comment_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'captcha_field' => $captcha_field,
            'csrf_token' => wp_create_nonce('csrf_token')
        ];
    }

    public function submitComment()
    {
        // استفاده از توابع Trait
        $this->verifyNonce($_POST['nonce'], 'submit_blog_comment_nonce');
        $this->verifyHoneypot($_POST);
        $this->applyRateLimiting('comment');

        // اعتبارسنجی فیلدها
        $fields = [
            'comment_text' => [
                'value' => $_POST['comment_text'] ?? '',
                'rules' => ['required' => true]
            ]
        ];
        $this->validateFields($fields, is_user_logged_in());

        $comment_text = sanitize_textarea_field($_POST['comment_text']);
        $this->validateTextLength($comment_text);

        $this->verifyCaptcha($this->captcha_manager, $_POST);

        // ذخیره نظر
        $comment_data = [
            'comment_post_ID' => $_POST['post_id'],
            'comment_content' => $comment_text,
            'comment_type' => 'comment',
            'comment_parent' => 0,
            'user_id' => get_current_user_id(),
        ];

        if (!is_user_logged_in()) {
            $comment_data['comment_author'] = sanitize_text_field($_POST['author_name']);
            $comment_data['comment_author_email'] = sanitize_email($_POST['author_email']);
            if (get_option('require_name_email') && (!$comment_data['comment_author'] || !$comment_data['comment_author_email'])) {
                $this->view->render_with_exit('@views/components/blog/responses/missing-author-info.twig', [], 400);
            }
        }

        $require_moderation = get_option('comment_moderation') === '1';
        if ($require_moderation) {
            $comment_data['comment_approved'] = '0';
        } else {
            $approved_comment = get_comments([
                'author_email' => $comment_data['comment_author_email'],
                'status' => 'approve',
                'count' => true
            ]);
            if (get_option('comment_previously_approved') === '1' && empty($approved_comment)) {
                $comment_data['comment_approved'] = '0';
            } else {
                $comment_data['comment_approved'] = '1';
            }
        }

        $comment_id = wp_insert_comment($comment_data);
        if ($comment_id) {
            $this->view->render_with_exit(
                $comment_data['comment_approved'] === '0'
                    ? '@views/components/blog/responses/success-pending.twig'
                    : '@views/components/blog/responses/success.twig',
                [],
                200
            );
        } else {
            $this->view->render_with_exit('@views/components/blog/responses/error.twig', [], 500);
        }
    }

    public function handle_submit_comment()
    {
        $this->submitComment();
    }

    protected function getResponseTemplate(string $responseType): string
    {
        return "@views/components/blog/responses/{$responseType}.twig";
    }

    public function render_comment_form()
    {
        $template_data = $this->getCommentForm();
        return $this->view->render($this->comment_form, $template_data);
    }


    public function filterComments() {
        $post_id = intval($_GET['post_id']);
        $filter = sanitize_text_field($_GET['filter'] ?? 'latest');
    
        $args = [
            'post_id' => $post_id,
            'status' => 'approve',
            'type' => 'comment',
        ];
    
        switch ($filter) {
            case 'oldest':
                $args['orderby'] = 'comment_date';
                $args['order'] = 'ASC';
                break;
            case 'latest':
            default:
                $args['orderby'] = 'comment_date';
                $args['order'] = 'DESC';
                break;
        }
    
        $comments = get_comments($args);
        $formatted_comments = $this->formatComments($comments);
        $this->view->render_with_exit('@views/components/blog/comments-list.twig', ['comments' => $formatted_comments], 200);
    }
}
