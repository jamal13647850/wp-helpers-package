<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Managers;


defined('ABSPATH') || exit();

/**
 * Class CaptchaManager
 * 
 * Manages CAPTCHA generation and verification.
 */
class CaptchaManager
{
    /**
     * @var View
     */
    private View $view;
    
    /**
     * @var string
     */
    private string $session_key;
    
    /**
     * @var bool
     */
    private bool $enabled;
    
    /**
     * @var string
     */
    private string $difficulty;
    
    /**
     * CaptchaManager constructor.
     *
     * @param View|null $view
     */
    public function __construct(?View $view = null)
    {
        $this->view = $view ?? new View();
        $this->enabled = Config::get('captcha.enabled', true);
        $this->difficulty = Config::get('captcha.difficulty', 'medium');
        $this->session_key = Config::get('captcha.session_key', 'captcha_answer');
        
        // Register AJAX handlers
        if ($this->enabled) {
            add_action('wp_ajax_generate_captcha', [$this, 'ajax_generate_captcha']);
            add_action('wp_ajax_nopriv_generate_captcha', [$this, 'ajax_generate_captcha']);
            add_action('wp_ajax_verify_captcha', [$this, 'ajax_verify_captcha']);
            add_action('wp_ajax_nopriv_verify_captcha', [$this, 'ajax_verify_captcha']);
        }
    }
    
    /**
     * Generate a new CAPTCHA.
     *
     * @return array CAPTCHA data
     */
    public function generate_captcha(): array
    {
        if (!$this->enabled) {
            return ['enabled' => false];
        }
        
        $question = '';
        $answer = 0;
        
        switch ($this->difficulty) {
            case 'easy':
                $num1 = rand(1, 10);
                $num2 = rand(1, 10);
                $answer = $num1 + $num2;
                $question = "حاصل جمع $num1 و $num2 چند می‌شود؟";
                break;
                
            case 'medium':
                $num1 = rand(10, 50);
                $num2 = rand(10, 50);
                $answer = $num1 + $num2;
                $question = "حاصل جمع $num1 و $num2 چند می‌شود؟";
                break;
                
            case 'hard':
                $num1 = rand(10, 100);
                $num2 = rand(10, 100);
                $operator = rand(0, 1) ? '+' : '*';
                $answer = $operator === '+' ? $num1 + $num2 : $num1 * $num2;
                $question = "حاصل $num1 " . ($operator === '+' ? 'به علاوه' : 'ضرب در') . " $num2 چند می‌شود؟";
                break;
        }
        
        $transient_key = 'captcha_' . wp_generate_uuid4();
        set_transient($transient_key, $answer, 300);
        
        if (Config::get('captcha.debug', false)) {
            error_log("Generated CAPTCHA - Answer: $answer, Transient Key: $transient_key");
        }
        
        return [
            'enabled' => true,
            'question' => $question,
            'nonce' => wp_create_nonce('captcha_' . $this->difficulty),
            'transient_key' => $transient_key,
        ];
    }
    
    /**
     * Verify a CAPTCHA answer.
     *
     * @param string $user_answer User's answer
     * @param string $nonce Security nonce
     * @param string $transient_key Transient key
     * @return bool True if correct, false otherwise
     */
    public function verify_captcha($user_answer, $nonce, $transient_key): bool
    {
        if (!$this->enabled) {
            return true;
        }
        
        if (Config::get('captcha.debug', false)) {
            error_log("Verifying CAPTCHA - User Answer: $user_answer (Type: " . gettype($user_answer) . "), Nonce: $nonce, Transient Key: $transient_key");
        }
        
        if (!wp_verify_nonce($nonce, 'captcha_' . $this->difficulty)) {
            if (Config::get('captcha.debug', false)) {
                error_log("Nonce verification failed");
            }
            return false;
        }
        
        $correct_answer = get_transient($transient_key);
        
        if (Config::get('captcha.debug', false)) {
            error_log("Correct Answer from Transient: " . ($correct_answer === false ? 'false' : $correct_answer) . " (Type: " . gettype($correct_answer) . ")");
        }
        
        delete_transient($transient_key);
        
        $user_answer_int = (int)$user_answer;
        $correct_answer_int = (int)$correct_answer;
        
        if (Config::get('captcha.debug', false)) {
            error_log("Converted - User Answer: $user_answer_int (Type: " . gettype($user_answer_int) . "), Correct Answer: $correct_answer_int (Type: " . gettype($correct_answer_int) . ")");
        }
        
        if ($correct_answer_int === false) {
            if (Config::get('captcha.debug', false)) {
                error_log("Transient returned false");
            }
            return false;
        }
        
        if ($user_answer_int === $correct_answer_int) {
            if (Config::get('captcha.debug', false)) {
                error_log("Answers match!");
            }
            return true;
        } else {
            if (Config::get('captcha.debug', false)) {
                error_log("Answers do not match - User: $user_answer_int, Correct: $correct_answer_int");
            }
            return false;
        }
    }
    
    /**
     * AJAX handler for generating a CAPTCHA.
     *
     * @return void
     */
    public function ajax_generate_captcha(): void
    {
        $captcha = $this->generate_captcha();
        $template = Config::get('captcha.template', '@views/components/captcha.twig');
        $html = $this->view->render($template, ['captcha' => $captcha]);
        echo $html;
        wp_die();
    }
    
    /**
     * AJAX handler for verifying a CAPTCHA.
     *
     * @return void
     */
    public function ajax_verify_captcha(): void
    {
        $user_answer = isset($_POST['captcha_answer']) ? sanitize_text_field($_POST['captcha_answer']) : '';
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $transient_key = isset($_POST['transient_key']) ? $_POST['transient_key'] : '';
        
        $result = $this->verify_captcha($user_answer, $nonce, $transient_key);
        wp_send_json(['success' => $result]);
        wp_die();
    }
    
    /**
     * Render a CAPTCHA.
     *
     * @param array $context Additional context for the template
     * @return string Rendered CAPTCHA HTML
     */
    public function render_captcha(array $context = []): string
    {
        if (!$this->enabled) {
            return '';
        }
        
        $captcha = $this->generate_captcha();
        $context['captcha'] = $captcha;
        $template = Config::get('captcha.template', '@views/components/captcha.twig');
        
        return $this->view->render($template, $context);
    }
    
    /**
     * Register settings for the admin panel.
     *
     * @return void
     */
    public static function register_settings(): void
    {
        add_action('admin_menu', function() {
            add_options_page(
                'تنظیمات کپچا',
                'کپچا',
                'manage_options',
                'wphelpers-captcha-settings',
                [__CLASS__, 'render_settings_page']
            );
        });
        
        register_setting('wphelpers_captcha_group', 'wphelpers_captcha_enabled');
        register_setting('wphelpers_captcha_group', 'wphelpers_captcha_difficulty');
        register_setting('wphelpers_captcha_group', 'wphelpers_captcha_debug');
        
        // Sync WordPress settings with Config
        add_action('admin_init', function() {
            $enabled = get_option('wphelpers_captcha_enabled', '1') === '1';
            $difficulty = get_option('wphelpers_captcha_difficulty', 'medium');
            $debug = get_option('wphelpers_captcha_debug', '0') === '1';
            
            Config::set('captcha.enabled', $enabled);
            Config::set('captcha.difficulty', $difficulty);
            Config::set('captcha.debug', $debug);
        });
    }
    
    /**
     * Render the settings page.
     *
     * @return void
     */
    public static function render_settings_page(): void
    {
        ?>
        <div class="wrap">
            <h1>تنظیمات کپچا</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wphelpers_captcha_group'); ?>
                <?php do_settings_sections('wphelpers_captcha_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>فعال کردن کپچا</th>
                        <td>
                            <input type="checkbox" name="wphelpers_captcha_enabled" value="1"
                                <?php checked(1, get_option('wphelpers_captcha_enabled', 1)); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th>سطح سختی کپچا</th>
                        <td>
                            <select name="wphelpers_captcha_difficulty">
                                <option value="easy" <?php selected(get_option('wphelpers_captcha_difficulty', 'medium'), 'easy'); ?>>آسان</option>
                                <option value="medium" <?php selected(get_option('wphelpers_captcha_difficulty', 'medium'), 'medium'); ?>>متوسط</option>
                                <option value="hard" <?php selected(get_option('wphelpers_captcha_difficulty', 'medium'), 'hard'); ?>>سخت</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>حالت دیباگ</th>
                        <td>
                            <input type="checkbox" name="wphelpers_captcha_debug" value="1"
                                <?php checked(1, get_option('wphelpers_captcha_debug', 0)); ?> />
                            <p class="description">در حالت دیباگ، اطلاعات کپچا در لاگ سیستم ثبت می‌شود.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Enable or disable the CAPTCHA.
     *
     * @param bool $enabled Whether to enable the CAPTCHA
     * @return void
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
    
    /**
     * Set the CAPTCHA difficulty.
     *
     * @param string $difficulty Difficulty level (easy, medium, hard)
     * @return void
     */
    public function setDifficulty(string $difficulty): void
    {
        $this->difficulty = $difficulty;
    }
}
