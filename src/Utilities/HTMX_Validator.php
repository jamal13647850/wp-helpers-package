<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Utilities;

defined('ABSPATH') || exit();

use jamal13647850\wphelpers\Language\LanguageManager;

/**
 * Class HTMX_Validator
 *
 * Validates HTMX requests with multilingual messages.
 */
class HTMX_Validator
{
    /**
     * @var View
     */
    private View $view;

    /**
     * @var array
     */
    private array $errors = [];

    /**
     * @var array
     */
    private array $data = [];

    /**
     * @var array
     */
    private array $rules = [];

    /**
     * @var array
     */
    private array $messages = [];

    /**
     * HTMX_Validator constructor.
     *
     * @param View|null $view View instance
     */
    public function __construct(?View $view = null)
    {
        $this->view = $view ?? new View();
    }

    /**
     * Validate request data.
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @return bool True if validation passes, false otherwise
     */
    public function validate(array $data, array $rules, array $messages = []): bool
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->errors = [];

        foreach ($rules as $field => $field_rules) {
            $field_rules = explode('|', $field_rules);

            foreach ($field_rules as $rule) {
                $rule_parts = explode(':', $rule);
                $rule_name = $rule_parts[0];
                $rule_params = isset($rule_parts[1]) ? explode(',', $rule_parts[1]) : [];

                $method = 'validate' . ucfirst($rule_name);

                if (method_exists($this, $method)) {
                    $value = $data[$field] ?? null;

                    if (!$this->$method($field, $value, $rule_params)) {
                        $this->addError($field, $rule_name, $rule_params);
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Add a validation error.
     *
     * @param string $field Field name
     * @param string $rule Rule name
     * @param array $params Rule parameters
     * @return void
     */
    private function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->messages[$field . '.' . $rule]
            ?? $this->messages[$field]
            ?? $this->getDefaultMessage($field, $rule, $params);

        $message = $this->replacePlaceholders($message, $field, $params);

        $this->errors[$field][] = $message;
    }

    /**
     * Get a default error message.
     *
     * @param string $field Field name
     * @param string $rule Rule name
     * @param array $params Rule parameters
     * @return string Error message
     */
    private function getDefaultMessage(string $field, string $rule, array $params = []): string
    {
        $lang = LanguageManager::getInstance();

        switch ($rule) {
            case 'required':
                return $lang->trans('validator_required', null, 'The :field field is required.');
            case 'email':
                return $lang->trans('validator_email', null, 'The :field field must be a valid email address.');
            case 'url':
                return $lang->trans('validator_url', null, 'The :field field must be a valid URL.');
            case 'numeric':
                return $lang->trans('validator_numeric', null, 'The :field field must be a number.');
            case 'integer':
                return $lang->trans('validator_integer', null, 'The :field field must be an integer.');
            case 'min':
                return $lang->trans('validator_min', null, 'The :field field must be at least :min characters.');
            case 'max':
                return $lang->trans('validator_max', null, 'The :field field must not exceed :max characters.');
            case 'between':
                return $lang->trans('validator_between', null, 'The :field field must be between :min and :max characters.');
            case 'in':
                return $lang->trans('validator_in', null, 'The selected :field is invalid.');
            case 'not_in':
                return $lang->trans('validator_not_in', null, 'The selected :field is invalid.');
            case 'regex':
                return $lang->trans('validator_regex', null, 'The :field format is invalid.');
            case 'date':
                return $lang->trans('validator_date', null, 'The :field field must be a valid date.');
            case 'date_format':
                return $lang->trans('validator_date_format', null, 'The :field field must match the format :format.');
            case 'before':
                return $lang->trans('validator_before', null, 'The :field field must be a date before :date.');
            case 'after':
                return $lang->trans('validator_after', null, 'The :field field must be a date after :date.');
            case 'same':
                return $lang->trans('validator_same', null, 'The :field field must match the :other field.');
            case 'different':
                return $lang->trans('validator_different', null, 'The :field field must be different from the :other field.');
            case 'unique':
                return $lang->trans('validator_unique', null, 'The :field has already been taken.');
            case 'exists':
                return $lang->trans('validator_exists', null, 'The selected :field is invalid.');
            case 'wp_nonce':
                return $lang->trans('validator_wp_nonce', null, 'Invalid WordPress nonce.');
            case 'wp_cap':
                return $lang->trans('validator_wp_cap', null, 'You do not have permission.');
            case 'file_type':
                return $lang->trans('validator_file_type', null, 'Invalid file type.');
            case 'file_size':
                return $lang->trans('validator_file_size', null, 'File size exceeds the allowed maximum.');
            case 'file_image':
                return $lang->trans('validator_file_image', null, 'Only image files are allowed.');
            case 'recaptcha':
                return $lang->trans('validator_recaptcha', null, 'Recaptcha validation failed.');
            case 'honeypot':
                return $lang->trans('validator_honeypot', null, 'Form submission failed.');
            default:
                return $lang->trans('validator_default', null, 'The :field field is invalid.');
        }
    }

    /**
     * Replace message placeholders.
     *
     * @param string $message Message with placeholders
     * @param string $field Field name
     * @param array $params Rule parameters
     * @return string Message with replaced placeholders
     */
    private function replacePlaceholders(string $message, string $field, array $params = []): string
    {
        $field_name = ucfirst(str_replace('_', ' ', $field));

        $replacements = [
            ':field' => $field_name,
        ];

        if (isset($params[0])) {
            $replacements[':min'] = $params[0];
            $replacements[':max'] = isset($params[1]) ? $params[1] : $params[0];
            $replacements[':size'] = $params[0];
            $replacements[':date'] = $params[0];
            $replacements[':format'] = $params[0];
            $replacements[':other'] = ucfirst(str_replace('_', ' ', $params[0]));
        }

        if (isset($params[1])) {
            $replacements[':max'] = $params[1];
        }

        return strtr($message, $replacements);
    }

    /**
     * Get all validation errors.
     *
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the first error for a field.
     *
     * @param string $field Field name
     * @return string|null First error message or null if no errors
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Check if a field has errors.
     *
     * @param string $field Field name
     * @return bool True if field has errors, false otherwise
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get all errors as a flat array.
     *
     * @return array Flat array of errors
     */
    public function getFlatErrors(): array
    {
        $flat = [];

        foreach ($this->errors as $field => $errors) {
            foreach ($errors as $error) {
                $flat[] = $error;
            }
        }

        return $flat;
    }

    /**
     * Render validation errors.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return string Rendered errors
     */
    public function renderErrors(string $template = 'validation/errors.twig', array $data = []): string
    {
        $data = array_merge([
            'errors' => $this->errors,
            'flat_errors' => $this->getFlatErrors(),
        ], $data);

        return $this->view->render($template, $data);
    }

    private function validateRequired(string $field, $value, array $params = []): bool
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_array($value) && count($value) < 1) {
            return false;
        }
        return true;
    }

    private function validateEmail(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateUrl(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateNumeric(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        return is_numeric($value);
    }

    private function validateInteger(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateMin(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        $min = (int)($params[0] ?? 0);

        if (is_numeric($value)) {
            return $value >= $min;
        }

        return mb_strlen($value) >= $min;
    }

    private function validateMax(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }

        $max = (int)($params[0] ?? 0);

        if (is_numeric($value)) {
            return $value <= $max;
        }
        return mb_strlen($value) <= $max;
    }

    private function validateBetween(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }

        $min = (int)($params[0] ?? 0);
        $max = (int)($params[1] ?? 0);

        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }

        $length = mb_strlen($value);
        return $length >= $min && $length <= $max;
    }

    private function validateIn(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        return in_array($value, $params);
    }

    private function validateNotIn(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        return !in_array($value, $params);
    }

    private function validateRegex(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        $pattern = $params[0] ?? '';
        return preg_match($pattern, $value) > 0;
    }

    private function validateDate(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        $date = date_create($value);
        return $date !== false;
    }

    private function validateDateFormat(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        $format = $params[0] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        return $date !== false && $date->format($format) === $value;
    }

    private function validateBefore(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }

        $date = date_create($value);
        $before = date_create($params[0] ?? 'now');
        return $date !== false && $before !== false && $date < $before;
    }

    private function validateAfter(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }

        $date = date_create($value);
        $after = date_create($params[0] ?? 'now');
        return $date !== false && $after !== false && $date > $after;
    }

    private function validateSame(string $field, $value, array $params = []): bool
    {
        $other_field = $params[0] ?? '';
        $other_value = $this->data[$other_field] ?? null;
        return $value === $other_value;
    }

    private function validateDifferent(string $field, $value, array $params = []): bool
    {
        $other_field = $params[0] ?? '';
        $other_value = $this->data[$other_field] ?? null;
        return $value !== $other_value;
    }

    private function validateUnique(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        global $wpdb;
        $table = $wpdb->prefix . ($params[0] ?? '');
        $column = $params[1] ?? $field;
        $except_column = $params[2] ?? 'id';
        $except_value = $params[3] ?? null;
        $query = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = %s", $value);
        if ($except_value !== null) {
            $query .= $wpdb->prepare(" AND {$except_column} != %s", $except_value);
        }
        $count = $wpdb->get_var($query);
        return $count == 0;
    }

    private function validateExists(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return true;
        }
        global $wpdb;
        $table = $wpdb->prefix . ($params[0] ?? '');
        $column = $params[1] ?? $field;
        $query = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = %s", $value);
        $count = $wpdb->get_var($query);
        return $count > 0;
    }

    private function validateWpNonce(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return false;
        }
        $action = $params[0] ?? -1;
        return wp_verify_nonce($value, $action);
    }

    private function validateWpCap(string $field, $value, array $params = []): bool
    {
        $capability = $params[0] ?? '';
        return current_user_can($capability);
    }

    private function validateFileType(string $field, $value, array $params = []): bool
    {
        if (empty($value) || !isset($_FILES[$field])) {
            return true;
        }
        $file = $_FILES[$field];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        return in_array($extension, $params);
    }

    private function validateFileSize(string $field, $value, array $params = []): bool
    {
        if (empty($value) || !isset($_FILES[$field])) {
            return true;
        }
        $file = $_FILES[$field];
        $max_size = (int)($params[0] ?? 0) * 1024 * 1024; // Convert MB to bytes
        return $file['size'] <= $max_size;
    }

    private function validateFileImage(string $field, $value, array $params = []): bool
    {
        if (empty($value) || !isset($_FILES[$field])) {
            return true;
        }
        $file = $_FILES[$field];
        $type = $file['type'];
        return strpos($type, 'image/') === 0;
    }

    private function validateRecaptcha(string $field, $value, array $params = []): bool
    {
        if (empty($value)) {
            return false;
        }
        $secret = $params[0] ?? (defined('Config') ? Config::get('recaptcha.secret_key', '') : '');
        if (empty($secret)) {
            return false;
        }
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $secret,
                'response' => $value,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
        ]);
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        return isset($data['success']) && $data['success'] === true;
    }

    private function validateHoneypot(string $field, $value, array $params = []): bool
    {
        return empty($value);
    }

    private function validateCustom(string $field, $value, array $params = []): bool
    {
        $callback = $params[0] ?? '';
        if (is_callable($callback)) {
            return call_user_func($callback, $value, $field, $this->data);
        }
        return false;
    }

    /**
     * Get validated data.
     *
     * @return array Validated data
     */
    public function getValidatedData(): array
    {
        $validated = [];

        foreach ($this->rules as $field => $rules) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }

    /**
     * Get only the specified fields from the validated data.
     *
     * @param array $fields Fields to get
     * @return array Filtered validated data
     */
    public function only(array $fields): array
    {
        $validated = $this->getValidatedData();
        $filtered = [];

        foreach ($fields as $field) {
            if (isset($validated[$field])) {
                $filtered[$field] = $validated[$field];
            }
        }

        return $filtered;
    }

    /**
     * Get all fields except the specified ones from the validated data.
     *
     * @param array $fields Fields to exclude
     * @return array Filtered validated data
     */
    public function except(array $fields): array
    {
        $validated = $this->getValidatedData();

        foreach ($fields as $field) {
            unset($validated[$field]);
        }

        return $validated;
    }

    /**
     * Send HTMX validation response.
     *
     * @param string $target Target element
     * @param string $template Template name
     * @param array $data Template data
     * @return void
     */
    public function sendHtmxResponse(string $target, string $template = 'validation/errors.twig', array $data = []): void
    {
        header('HX-Retarget: ' . $target);
        echo $this->renderErrors($template, $data);
        exit;
    }

    /**
     * Send HTMX validation error response.
     *
     * @param string $message Error message
     * @param int $status HTTP status code
     * @return void
     */
    public function sendHtmxError(string $message, int $status = 422): void
    {
        header('HX-Reswap: none');
        header('HX-Trigger: {"showMessage": {"message": "' . esc_js($message) . '", "type": "error"}}');
        http_response_code($status);
        exit;
    }

    /**
     * Send HTMX validation success response.
     *
     * @param string $message Success message
     * @param array $trigger Additional trigger data
     * @return void
     */
    public function sendHtmxSuccess(string $message, array $trigger = []): void
    {
        $trigger_data = array_merge([
            'showMessage' => [
                'message' => $message,
                'type' => 'success',
            ],
        ], $trigger);

        header('HX-Trigger: ' . json_encode($trigger_data));
        exit;
    }
}
