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
     * デフォルトフィールド定義
     */
    private $default_fields = array(
        'name' => array(
            'type' => 'text',
            'label' => 'お名前',
            'placeholder' => '山田太郎',
            'required' => true,
            'visible' => true,
            'sort_order' => 1,
            'system_required' => true
        ),
        'email' => array(
            'type' => 'email',
            'label' => 'メールアドレス',
            'placeholder' => 'example@email.com',
            'required' => true,
            'visible' => true,
            'sort_order' => 2,
            'system_required' => true
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
        'textarea' => 'テキストエリア',
        'tel' => '電話番号',
        'date' => '日付',
        'gender' => '性別'
    );
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        // 初期化時にデフォルトフィールドを設定
        add_action('init', array($this, 'init_default_fields'));
        
        // フィールド追加・削除時にテーブルを更新
        add_action('wpsr_field_added', array($this, 'update_reservations_table'));
        add_action('wpsr_field_deleted', array($this, 'update_reservations_table'));
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
     * 全フィールドを取得（論理削除されていないもの）
     */
    public function get_all_fields() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        $fields = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE deleted_at IS NULL ORDER BY sort_order ASC",
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
            "SELECT * FROM $table_name WHERE visible = 1 AND deleted_at IS NULL ORDER BY sort_order ASC",
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
        
        // フィールド情報を取得
        $field = $this->get_field($field_id);
        if (!$field) {
            return false;
        }
        
        // システム必須フィールドは削除できない
        if ($this->is_system_required_field($field['field_key'])) {
            return false;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_form_fields',
            array('deleted_at' => current_time('mysql')),
            array('id' => $field_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // 削除後の並び順を再調整
            $this->reorder_fields_after_delete($field['sort_order']);
            return true;
        }
        
        return false;
    }
    
    /**
     * フィールド削除後の並び順を再調整
     */
    private function reorder_fields_after_delete($deleted_sort_order) {
        global $wpdb;
        
        // 削除されたフィールドより後の並び順を1つ前にずらす
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}wpsr_form_fields 
             SET sort_order = sort_order - 1 
             WHERE sort_order > %d AND deleted_at IS NULL",
            $deleted_sort_order
        ));
    }
    
    /**
     * フィールドを物理削除（完全削除）
     */
    public function hard_delete_field($field_id) {
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
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND deleted_at IS NULL", $field_id),
            ARRAY_A
        );
        
        return $field;
    }
    
    /**
     * 論理削除されたフィールドを含めて取得
     */
    public function get_field_including_deleted($field_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        
        $field = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $field_id),
            ARRAY_A
        );
        
        return $field;
    }
    
    /**
     * フィールドキーで論理削除されたフィールドを検索
     */
    public function get_deleted_field_by_key($field_key) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        
        $field = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE field_key = %s AND deleted_at IS NOT NULL", $field_key),
            ARRAY_A
        );
        
        return $field;
    }
    
    /**
     * テンプレートフィールドを取得（廃止予定）
     * @deprecated テンプレート機能は廃止されました
     */
    public function get_template_fields() {
        return array();
    }
    
    /**
     * カスタムフィールドタイプを取得
     */
    public function get_custom_field_types() {
        return $this->custom_field_types;
    }
    
    /**
     * デフォルトフィールドを取得
     */
    public function get_default_fields() {
        return $this->default_fields;
    }
    
    /**
     * フィールドがシステム必須かチェック
     */
    public function is_system_required_field($field_key) {
        return isset($this->default_fields[$field_key]) && 
               isset($this->default_fields[$field_key]['system_required']) && 
               $this->default_fields[$field_key]['system_required'];
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
            $html .= ' <span class="wpsr-required">必須</span>';
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
                
            case 'gender':
                $html .= '<div class="wpsr-gender-group">';
                $html .= '<label class="wpsr-gender-option">';
                $html .= '<input type="radio" ';
                $html .= 'name="' . esc_attr($field['field_key']) . '" ';
                $html .= 'value="male" ';
                if ($field['required']) {
                    $html .= 'required ';
                }
                $html .= 'class="wpsr-gender-input">';
                $html .= '<span class="wpsr-gender-text">男性</span>';
                $html .= '</label>';
                
                $html .= '<label class="wpsr-gender-option">';
                $html .= '<input type="radio" ';
                $html .= 'name="' . esc_attr($field['field_key']) . '" ';
                $html .= 'value="female" ';
                if ($field['required']) {
                    $html .= 'required ';
                }
                $html .= 'class="wpsr-gender-input">';
                $html .= '<span class="wpsr-gender-text">女性</span>';
                $html .= '</label>';
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
    
    /**
     * 予約テーブルを更新（フィールド追加・削除時）
     */
    public function update_reservations_table() {
        global $wpdb;
        
        $reservations_table = $wpdb->prefix . 'wpsr_reservations';
        $form_fields_table = $wpdb->prefix . 'wpsr_form_fields';
        
        // 現在のフィールド定義を取得（論理削除されていないもののみ）
        $active_fields = $wpdb->get_results("SELECT field_key, field_type FROM $form_fields_table WHERE deleted_at IS NULL", ARRAY_A);
        
        // 論理削除されたフィールドも含めて全フィールドを取得（カラム削除の判断用）
        $all_fields = $wpdb->get_results("SELECT field_key, field_type FROM $form_fields_table", ARRAY_A);
        
        // 現在のテーブル構造を取得
        $table_structure = $wpdb->get_results("DESCRIBE $reservations_table", ARRAY_A);
        $existing_columns = array_column($table_structure, 'Field');
        
        // 必要なカラムを追加（アクティブなフィールドのみ）
        foreach ($active_fields as $field) {
            $field_key = $field['field_key'];
            $field_type = $field['field_type'];
            
            // 基本フィールドはスキップ
            if (in_array($field_key, array('id', 'name', 'email', 'phone', 'schedule_date', 'schedule_time', 'status', 'created_at', 'updated_at', 'message', 'google_calendar_event_id'))) {
                continue;
            }
            
            // カラムが存在しない場合は追加
            if (!in_array($field_key, $existing_columns)) {
                $column_type = $this->get_column_type($field_type);
                $sql = "ALTER TABLE $reservations_table ADD COLUMN `$field_key` $column_type";
                
                $result = $wpdb->query($sql);
                if ($result !== false) {
                    error_log("WPSR Debug - Added column: $field_key ($column_type)");
                } else {
                    error_log("WPSR Debug - Failed to add column: $field_key");
                }
            }
        }
        
        // 不要なカラムを削除（フィールド定義に全く存在しないカラムのみ）
        $all_field_keys = array_column($all_fields, 'field_key');
        foreach ($existing_columns as $column) {
            // 基本フィールドはスキップ
            if (in_array($column, array('id', 'name', 'email', 'phone', 'schedule_date', 'schedule_time', 'status', 'created_at', 'updated_at', 'message', 'google_calendar_event_id'))) {
                continue;
            }
            
            // フィールド定義に全く存在しないカラムのみ削除（論理削除されたフィールドのカラムは保持）
            if (!in_array($column, $all_field_keys)) {
                $sql = "ALTER TABLE $reservations_table DROP COLUMN `$column`";
                $result = $wpdb->query($sql);
                if ($result !== false) {
                    error_log("WPSR Debug - Dropped column: $column");
                } else {
                    error_log("WPSR Debug - Failed to drop column: $column");
                }
            }
        }
    }
    
    /**
     * フィールドタイプに応じたカラムタイプを取得
     */
    private function get_column_type($field_type) {
        switch ($field_type) {
            case 'textarea':
                return 'TEXT';
            case 'date':
                return 'DATE';
            case 'email':
            case 'tel':
            case 'text':
            case 'select':
            case 'radio':
            case 'checkbox':
            default:
                return 'VARCHAR(255)';
        }
    }
    
    /**
     * フィールド追加時の型チェック
     */
    public function check_field_addition($field_key, $field_type, $field_data) {
        // 論理削除されたフィールドをチェック
        $deleted_field = $this->get_deleted_field_by_key($field_key);
        
        if ($deleted_field) {
            if ($deleted_field['field_type'] === $field_type) {
                // 同一型 → 復活
                return $this->reactivate_field($deleted_field['id'], $field_data);
            } else {
                // 異なる型 → エラー
                return array(
                    'success' => false,
                    'error' => sprintf(
                        'フィールド「%s」は%s型で既に登録されています。同じフィールド名で異なる型を登録することはできません。',
                        $field_key,
                        $this->get_field_type_display_name($deleted_field['field_type'])
                    )
                );
            }
        }
        
        // 新規フィールドとして追加
        return $this->add_new_field($field_key, $field_type, $field_data);
    }
    
    /**
     * 無効化されたフィールドを復活
     */
    private function reactivate_field($field_id, $new_data) {
        global $wpdb;
        
        $update_data = array(
            'deleted_at' => null,
            'field_label' => $new_data['field_label'],
            'field_placeholder' => $new_data['field_placeholder'],
            'required' => $new_data['required'],
            'visible' => $new_data['visible'],
            'updated_at' => current_time('mysql')
        );
        
        // 選択肢がある場合は更新
        if (isset($new_data['field_options'])) {
            $update_data['field_options'] = $new_data['field_options'];
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_form_fields',
            $update_data,
            array('id' => $field_id)
        );
        
        if ($result !== false) {
            return array(
                'success' => true,
                'action' => 'reactivated',
                'field_id' => $field_id,
                'message' => 'フィールドが復活しました。'
            );
        }
        
        return array(
            'success' => false,
            'error' => 'フィールドの復活に失敗しました。'
        );
    }
    
    /**
     * 新規フィールドとして追加
     */
    private function add_new_field($field_key, $field_type, $field_data) {
        global $wpdb;
        
        // 最大のsort_orderを取得
        $max_sort = $wpdb->get_var("SELECT MAX(sort_order) FROM {$wpdb->prefix}wpsr_form_fields WHERE deleted_at IS NULL");
        $sort_order = $max_sort ? $max_sort + 1 : 1;
        
        $data = array(
            'field_key' => $field_key,
            'field_type' => $field_type,
            'field_label' => $field_data['field_label'],
            'field_placeholder' => $field_data['field_placeholder'],
            'field_options' => isset($field_data['field_options']) ? $field_data['field_options'] : '',
            'required' => $field_data['required'],
            'visible' => $field_data['visible'],
            'sort_order' => $sort_order
        );
        
        $result = $wpdb->insert($wpdb->prefix . 'wpsr_form_fields', $data);
        
        if ($result !== false) {
            return array(
                'success' => true,
                'action' => 'added',
                'field_id' => $wpdb->insert_id,
                'message' => 'フィールドが追加されました。'
            );
        }
        
        return array(
            'success' => false,
            'error' => 'フィールドの追加に失敗しました。'
        );
    }
    
    /**
     * フィールド型の表示名を取得
     */
    private function get_field_type_display_name($field_type) {
        $type_names = array(
            'text' => 'テキストボックス',
            'email' => 'メールアドレス',
            'tel' => '電話番号',
            'date' => '日付',
            'textarea' => 'テキストエリア',
            'select' => 'プルダウン',
            'radio' => 'ラジオボタン',
            'checkbox' => 'チェックボックス'
        );
        
        return isset($type_names[$field_type]) ? $type_names[$field_type] : $field_type;
    }
}
