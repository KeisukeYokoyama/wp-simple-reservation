<?php
/**
 * WP Simple Reservation Form Manager
 * フォームフィールド管理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSR_Form_Manager {
    
    /**
     * テンプレートフィールド定義
     */
    private $template_fields = array(
        'email' => array(
            'type' => 'email',
            'label' => 'メールアドレス',
            'placeholder' => 'example@email.com',
            'required' => true
        ),
        'phone' => array(
            'type' => 'tel',
            'label' => '電話番号',
            'placeholder' => '090-1234-5678',
            'required' => false
        ),
        'gender' => array(
            'type' => 'radio',
            'label' => '性別',
            'options' => array(
                'male' => '男性',
                'female' => '女性',
                'other' => 'その他'
            ),
            'required' => false
        ),
        'birthdate' => array(
            'type' => 'date',
            'label' => '生年月日',
            'required' => false
        )
    );
    
    /**
     * カスタムフィールドタイプ定義
     */
    private $custom_field_types = array(
        'text' => 'テキストボックス',
        'radio' => 'ラジオボタン',
        'select' => 'プルダウン',
        'checkbox' => 'チェックボックス',
        'textarea' => 'テキストエリア'
    );
    
    /**
     * デフォルトフィールド定義
     */
    private $default_fields = array(
        'name' => array(
            'type' => 'text',
            'label' => 'お名前',
            'placeholder' => '山田太郎',
            'required' => true,
            'visible' => true,
            'sort_order' => 1
        ),
        'email' => array(
            'type' => 'email',
            'label' => 'メールアドレス',
            'placeholder' => 'example@email.com',
            'required' => true,
            'visible' => true,
            'sort_order' => 2
        ),
        'phone' => array(
            'type' => 'tel',
            'label' => '電話番号',
            'placeholder' => '090-1234-5678',
            'required' => false,
            'visible' => true,
            'sort_order' => 3
        ),
        'message' => array(
            'type' => 'textarea',
            'label' => 'メッセージ',
            'placeholder' => 'ご要望があればお聞かせください',
            'required' => false,
            'visible' => true,
            'sort_order' => 4
        )
    );
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        // 初期化時にデフォルトフィールドを設定
        add_action('init', array($this, 'init_default_fields'));
    }
    
    /**
     * デフォルトフィールドを初期化
     */
    public function init_default_fields() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        $existing_fields = $wpdb->get_results("SELECT field_key FROM $table_name");
        
        if (empty($existing_fields)) {
            foreach ($this->default_fields as $key => $field) {
                $this->add_field($key, $field);
            }
        }
    }
    
    /**
     * 全フィールドを取得
     */
    public function get_all_fields() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        $fields = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY sort_order ASC",
            ARRAY_A
        );
        
        return $fields;
    }
    
    /**
     * 表示可能なフィールドを取得
     */
    public function get_visible_fields() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        $fields = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE visible = 1 ORDER BY sort_order ASC",
            ARRAY_A
        );
        
        // デバッグログ
        error_log('WPSR Debug - get_visible_fields result: ' . print_r($fields, true));
        
        return $fields;
    }
    
    /**
     * フィールドを追加
     */
    public function add_field($field_key, $field_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        
        $data = array(
            'field_key' => $field_key,
            'field_type' => $field_data['type'],
            'field_label' => $field_data['label'],
            'field_placeholder' => isset($field_data['placeholder']) ? $field_data['placeholder'] : '',
            'field_options' => isset($field_data['options']) ? json_encode($field_data['options']) : '',
            'required' => isset($field_data['required']) ? $field_data['required'] : 0,
            'visible' => isset($field_data['visible']) ? $field_data['visible'] : 1,
            'sort_order' => isset($field_data['sort_order']) ? $field_data['sort_order'] : 0
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        return $result !== false;
    }
    
    /**
     * フィールドを更新
     */
    public function update_field($field_id, $field_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        
        $data = array(
            'field_type' => $field_data['type'],
            'field_label' => $field_data['label'],
            'field_placeholder' => isset($field_data['placeholder']) ? $field_data['placeholder'] : '',
            'field_options' => isset($field_data['options']) ? json_encode($field_data['options']) : '',
            'required' => isset($field_data['required']) ? $field_data['required'] : 0,
            'visible' => isset($field_data['visible']) ? $field_data['visible'] : 1,
            'sort_order' => isset($field_data['sort_order']) ? $field_data['sort_order'] : 0
        );
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $field_id),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * フィールドを削除
     */
    public function delete_field($field_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $field_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * フィールドを取得
     */
    public function get_field($field_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        
        $field = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $field_id),
            ARRAY_A
        );
        
        return $field;
    }
    
    /**
     * テンプレートフィールドを取得
     */
    public function get_template_fields() {
        return $this->template_fields;
    }
    
    /**
     * カスタムフィールドタイプを取得
     */
    public function get_custom_field_types() {
        return $this->custom_field_types;
    }
    
    /**
     * フォームHTMLを生成
     */
    public function generate_form_html() {
        $fields = $this->get_visible_fields();
        $html = '';
        
        foreach ($fields as $field) {
            $html .= $this->generate_field_html($field);
        }
        
        return $html;
    }
    
    /**
     * フィールドHTMLを生成
     */
    public function generate_field_html($field) {
        // デバッグログ
        error_log('WPSR Debug - generate_field_html for field: ' . $field['field_key']);
        error_log('WPSR Debug - field_type: ' . $field['field_type']);
        error_log('WPSR Debug - field_options: ' . $field['field_options']);
        
        $html = '<div class="wpsr-form-group">';
        $html .= '<label for="wpsr-' . esc_attr($field['field_key']) . '" class="wpsr-label">';
        $html .= esc_html($field['field_label']);
        if ($field['required']) {
            $html .= ' <span class="wpsr-required">*</span>';
        }
        $html .= '</label>';
        
        switch ($field['field_type']) {
            case 'text':
            case 'email':
            case 'tel':
            case 'date':
                $html .= '<input type="' . esc_attr($field['field_type']) . '" ';
                $html .= 'id="wpsr-' . esc_attr($field['field_key']) . '" ';
                $html .= 'name="' . esc_attr($field['field_key']) . '" ';
                if ($field['required']) {
                    $html .= 'required ';
                }
                if (!empty($field['field_placeholder'])) {
                    $html .= 'placeholder="' . esc_attr($field['field_placeholder']) . '" ';
                }
                $html .= 'class="wpsr-input">';
                break;
                
            case 'textarea':
                $html .= '<textarea ';
                $html .= 'id="wpsr-' . esc_attr($field['field_key']) . '" ';
                $html .= 'name="' . esc_attr($field['field_key']) . '" ';
                if ($field['required']) {
                    $html .= 'required ';
                }
                if (!empty($field['field_placeholder'])) {
                    $html .= 'placeholder="' . esc_attr($field['field_placeholder']) . '" ';
                }
                $html .= 'class="wpsr-input" rows="4"></textarea>';
                break;
                
            case 'select':
                $html .= '<select ';
                $html .= 'id="wpsr-' . esc_attr($field['field_key']) . '" ';
                $html .= 'name="' . esc_attr($field['field_key']) . '" ';
                if ($field['required']) {
                    $html .= 'required ';
                }
                $html .= 'class="wpsr-select">';
                $html .= '<option value="">選択してください</option>';
                
                if (!empty($field['field_options'])) {
                    // エスケープされた文字を処理してからJSON解析
                    $decoded_options = stripslashes($field['field_options']);
                    $options = json_decode($decoded_options, true);
                    error_log('WPSR Debug - select options decoded: ' . print_r($options, true));
                    if (is_array($options)) {
                        foreach ($options as $value => $label) {
                            $html .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                        }
                    }
                }
                
                $html .= '</select>';
                break;
                
            case 'radio':
                $html .= '<div class="wpsr-radio-group">';
                if (!empty($field['field_options'])) {
                    // エスケープされた文字を処理してからJSON解析
                    $decoded_options = stripslashes($field['field_options']);
                    $options = json_decode($decoded_options, true);
                    error_log('WPSR Debug - radio options decoded: ' . print_r($options, true));
                    if (is_array($options)) {
                        foreach ($options as $value => $label) {
                            $html .= '<label class="wpsr-radio">';
                            $html .= '<input type="radio" ';
                            $html .= 'name="' . esc_attr($field['field_key']) . '" ';
                            $html .= 'value="' . esc_attr($value) . '" ';
                            if ($field['required']) {
                                $html .= 'required ';
                            }
                            $html .= 'class="wpsr-radio-input">';
                            $html .= '<span class="wpsr-radio-text">' . esc_html($label) . '</span>';
                            $html .= '</label>';
                        }
                    }
                }
                $html .= '</div>';
                break;
                
            case 'checkbox':
                $html .= '<div class="wpsr-checkbox-group">';
                if (!empty($field['field_options'])) {
                    // エスケープされた文字を処理してからJSON解析
                    $decoded_options = stripslashes($field['field_options']);
                    $options = json_decode($decoded_options, true);
                    error_log('WPSR Debug - checkbox options decoded: ' . print_r($options, true));
                    if (is_array($options)) {
                        foreach ($options as $value => $label) {
                            $html .= '<label class="wpsr-checkbox">';
                            $html .= '<input type="checkbox" ';
                            $html .= 'name="' . esc_attr($field['field_key']) . '[]" ';
                            $html .= 'value="' . esc_attr($value) . '" ';
                            $html .= 'class="wpsr-checkbox-input">';
                            $html .= '<span class="wpsr-checkbox-text">' . esc_html($label) . '</span>';
                            $html .= '</label>';
                        }
                    }
                }
                $html .= '</div>';
                break;
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * フォームデータをバリデーション
     */
    public function validate_form_data($data) {
        $errors = array();
        $fields = $this->get_visible_fields();
        
        // 予約締切日のチェック
        if (isset($data['schedule_date']) && isset($data['schedule_time'])) {
            $deadline_error = $this->check_booking_deadline($data['schedule_date'], $data['schedule_time']);
            if ($deadline_error) {
                $errors[] = $deadline_error;
            }
        }
        
        foreach ($fields as $field) {
            $field_key = $field['field_key'];
            $field_value = isset($data[$field_key]) ? $data[$field_key] : '';
            
            // 必須チェック
            if ($field['required'] && empty($field_value)) {
                $errors[] = $field['field_label'] . 'は必須項目です。';
                continue;
            }
            
            // フィールドタイプ別バリデーション
            switch ($field['field_type']) {
                case 'email':
                    if (!empty($field_value) && !is_email($field_value)) {
                        $errors[] = $field['field_label'] . 'は有効なメールアドレスを入力してください。';
                    }
                    break;
                    
                case 'tel':
                    if (!empty($field_value) && !preg_match('/^[0-9\-\(\)\s]+$/', $field_value)) {
                        $errors[] = $field['field_label'] . 'は有効な電話番号を入力してください。';
                    }
                    break;
            }
        }
        
        return $errors;
    }
    
    /**
     * 予約締切日のチェック
     */
    public function check_booking_deadline($schedule_date, $schedule_time) {
        $deadline_days = intval(get_option('wpsr_booking_deadline_days', 0));
        $deadline_hours = intval(get_option('wpsr_booking_deadline_hours', 0));
        
        // 現在の日時を取得
        $current_datetime = current_time('Y-m-d H:i:s');
        $current_date = current_time('Y-m-d');
        
        // 予約日時をDateTimeオブジェクトに変換
        $booking_datetime = DateTime::createFromFormat('Y-m-d H:i', $schedule_date . ' ' . $schedule_time);
        if (!$booking_datetime) {
            return '予約日時の形式が正しくありません。';
        }
        
        // 日数制限のチェック
        if ($deadline_days > 0) {
            // 予約日が当日かどうかをチェック
            if ($schedule_date === $current_date) {
                // 当日の場合は時間制限をチェック
                if ($deadline_hours > 0) {
                    // 現在時刻から○時間後が予約時間を過ぎているかチェック
                    $current_datetime_obj = DateTime::createFromFormat('Y-m-d H:i:s', $current_datetime);
                    $deadline_time = clone $booking_datetime;
                    $deadline_time->sub(new DateInterval('PT' . $deadline_hours . 'H'));
                    
                    if ($current_datetime_obj > $deadline_time) {
                        return 'この日時は予約締切日（' . $deadline_days . '日前かつ' . $deadline_hours . '時間前まで）を過ぎているため、予約できません。';
                    }
                } else {
                    // 時間制限がない場合は当日は全て締切
                    return 'この日時は予約締切日（' . $deadline_days . '日前まで）を過ぎているため、予約できません。';
                }
            }
        } else if ($deadline_hours > 0) {
            // 日数制限がない場合は時間制限のみチェック
            $current_datetime_obj = DateTime::createFromFormat('Y-m-d H:i:s', $current_datetime);
            $deadline_time = clone $booking_datetime;
            $deadline_time->sub(new DateInterval('PT' . $deadline_hours . 'H'));
            
            if ($current_datetime_obj > $deadline_time) {
                return 'この日時は予約締切日（' . $deadline_hours . '時間前まで）を過ぎているため、予約できません。';
            }
        }
        
        return null;
    }
}
