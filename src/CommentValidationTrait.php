<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

trait CommentValidationTrait
{
    /**
     * Verify nonce
     */
    private function verifyNonce(string $nonce, string $action): void
    {
        if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || !wp_verify_nonce($_SERVER['HTTP_X_CSRF_TOKEN'], 'csrf_token')) {
            $this->view->render_with_exit($this->getResponseTemplate('invalid-csrf'), [], 400);
        }
        if (!wp_verify_nonce($nonce, $action)) {
            $this->view->render_with_exit(
                $this->getResponseTemplate('invalid-nonce'),
                [],
                400
            );
        }
    }

    /**
     * Verify captcha
     */
    private function verifyCaptcha(CaptchaManager $captchaManager, array $postData): void
    {
        $captcha_answer = isset($postData['captcha_answer']) ? sanitize_text_field($postData['captcha_answer']) : '';
        $captcha_nonce = isset($postData['captcha_nonce']) ? sanitize_text_field($postData['captcha_nonce']) : '';
        $captcha_transient_key = isset($postData['captcha_transient_key']) ? sanitize_text_field($postData['captcha_transient_key']) : '';

        if (!$captchaManager->verify_captcha($captcha_answer, $captcha_nonce, $captcha_transient_key)) {
            $this->view->render_with_exit(
                $this->getResponseTemplate('captcha-failed'),
                [],
                400
            );
        }
    }

    /**
     * Verify honeypot
     */
    private function verifyHoneypot(array $postData): void
    {
        if (!empty($postData['hp_field'])) {
            $this->view->render_with_exit(
                $this->getResponseTemplate('honeypot-failed'),
                [],
                400
            );
        }
    }

    /**
     * Apply rate limiting
     */
    private function applyRateLimiting(string $transientPrefix): void
    {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = $transientPrefix . '_rate_limit_' . md5($user_ip);
        $attempts = get_transient($transient_key);

        if ($attempts === false) {
            set_transient($transient_key, 1, 300); // 5 دقیقه
        } elseif ($attempts >= 5) {
            $this->view->render_with_exit(
                $this->getResponseTemplate('rate-limit'),
                [],
                429 // Too Many Requests
            );
        } else {
            set_transient($transient_key, $attempts + 1, 300);
        }
    }

    /**
     * Validate form fields
     */
    private function validateFields(array $fields, bool $isLoggedIn): void
    {
        $validator = new HTMX_Validator('html');

        if (!$isLoggedIn) {
            $fields['author_name'] = [
                'value' => $_POST['author_name'] ?? '',
                'rules' => ['required' => true]
            ];
            $fields['author_email'] = [
                'value' => $_POST['author_email'] ?? '',
                'rules' => ['required' => true, 'type' => 'email']
            ];
        }

        $validator->validate_all_fields($fields);

        if (!empty($validator->get_errors())) {
            $this->view->render_with_exit(
                $this->getResponseTemplate('validation-errors'),
                ['errors' => $validator->get_errors()],
                400
            );
        }
    }

    /**
     * Validate text length
     */
    private function validateTextLength(string $text, int $minLength = 10, int $maxLength = 1000): void
    {
        $length = mb_strlen($text);
        if ($length < $minLength || $length > $maxLength) {
            $this->view->render_with_exit(
                $this->getResponseTemplate('invalid-length'),
                [],
                400
            );
        }
    }

    /**
     * Abstract method to be implemented by the using class to define response template paths
     */
    abstract protected function getResponseTemplate(string $responseType): string;
}