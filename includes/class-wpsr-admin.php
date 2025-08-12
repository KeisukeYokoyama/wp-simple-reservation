<?php
/**
 * 管理画面クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSR_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_admin'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wpsr_save_reservation', array($this, 'save_reservation'));
        add_action('wp_ajax_nopriv_wpsr_save_reservation', array($this, 'save_reservation'));
        add_action('wp_ajax_wpsr_get_schedules', array($this, 'get_schedules'));
        add_action('wp_ajax_nopriv_wpsr_get_schedules', array($this, 'get_schedules'));
        add_action('wp_ajax_wpsr_save_schedule', array($this, 'save_schedule'));
        add_action('wp_ajax_wpsr_delete_schedule', array($this, 'delete_schedule'));
        add_action('wp_ajax_wpsr_get_schedule', array($this, 'get_schedule'));
        add_action('wp_ajax_wpsr_get_schedule_by_date', array($this, 'get_schedule_by_date'));
        add_action('wp_ajax_wpsr_get_schedules_by_month', array($this, 'get_schedules_by_month'));
        add_action('wp_ajax_wpsr_get_reservation', array($this, 'get_reservation'));
        add_action('wp_ajax_wpsr_update_reservation', array($this, 'update_reservation'));
        add_action('wp_ajax_wpsr_cancel_reservation', array($this, 'cancel_reservation'));
        add_action('wp_ajax_wpsr_add_field', array($this, 'add_field'));
        add_action('wp_ajax_wpsr_update_field', array($this, 'update_field'));
        add_action('wp_ajax_wpsr_delete_field', array($this, 'delete_field'));
        add_action('wp_ajax_wpsr_get_field', array($this, 'get_field'));
    }
    
    public function add_admin_menu() {
        // メインメニュー - 予約管理
        add_menu_page(
            __('予約管理', 'wp-simple-reservation'),
            __('予約管理', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-reservations',
            array($this, 'render_reservations_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // サブメニュー
        add_submenu_page(
            'wpsr-reservations',
            __('予約一覧', 'wp-simple-reservation'),
            __('予約一覧', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-reservations',
            array($this, 'render_reservations_page')
        );
        
        add_submenu_page(
            'wpsr-reservations',
            __('スケジュール管理', 'wp-simple-reservation'),
            __('スケジュール管理', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-schedules',
            array($this, 'render_schedules_page')
        );
        
        add_submenu_page(
            'wpsr-reservations',
            __('フォーム設定', 'wp-simple-reservation'),
            __('フォーム設定', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-form-settings',
            array($this, 'render_form_settings_page')
        );
        
        add_submenu_page(
            'wpsr-reservations',
            __('設定', 'wp-simple-reservation'),
            __('設定', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'wpsr-reservations',
            __('Googleカレンダー連携', 'wp-simple-reservation'),
            __('Googleカレンダー連携', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-google-calendar',
            array($this, 'render_google_calendar_page')
        );
    }
    
    public function init_admin() {
        // 管理画面の初期化処理
    }
    
    public function enqueue_admin_scripts() {
        // 管理画面用のスクリプトを読み込み
        $wpsr = WP_Simple_Reservation::get_instance();
        $wpsr->enqueue_admin_scripts();
        
        // フォーム設定ページでもスクリプトを読み込む
        $screen = get_current_screen();
        if ($screen && $screen->id === 'wpsr-reservations_page_wpsr-form-settings') {
            wp_enqueue_script('jquery');
        }
    }
    
    public function render_reservations_page() {
        // エラーメッセージを表示
        if (isset($_SESSION['wpsr_error'])) {
            echo '<div class="notice notice-error"><p><strong>Googleカレンダー連携エラー:</strong></p>';
            echo '<p>' . esc_html($_SESSION['wpsr_error']) . '</p>';
            if (isset($_SESSION['wpsr_error_stack'])) {
                echo '<details><summary>詳細エラー情報</summary>';
                echo '<pre>' . esc_html($_SESSION['wpsr_error_stack']) . '</pre>';
                echo '</details>';
            }
            echo '</div>';
            
            // セッションからエラーを削除
            unset($_SESSION['wpsr_error']);
            unset($_SESSION['wpsr_error_stack']);
        }
        
        // 予約一覧ページ
        include WPSR_PLUGIN_PATH . 'templates/admin/reservations.php';
    }
    
    public function render_schedules_page() {
        // スケジュール管理ページ
        include WPSR_PLUGIN_PATH . 'templates/admin/schedules.php';
    }
    
    public function render_settings_page() {
        // 設定ページ
        include WPSR_PLUGIN_PATH . 'templates/admin/settings.php';
    }
    
    public function render_form_settings_page() {
        // フォーム設定ページ
        include WPSR_PLUGIN_PATH . 'templates/admin/form-settings.php';
    }
    
    public function save_reservation() {
        // デバッグログ
        error_log('WPSR Debug - save_reservation called');
        error_log('WPSR Debug - $_POST data: ' . print_r($_POST, true));
        
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            error_log('WPSR Error - Nonce verification failed');
            error_log('WPSR Debug - Expected nonce: wpsr_nonce');
            error_log('WPSR Debug - Received nonce: ' . $_POST['nonce']);
            wp_send_json_error(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // フォームマネージャーを取得
        if (!class_exists('WPSR_Form_Manager')) {
            require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
        }
        $form_manager = new WPSR_Form_Manager();
        
        // 動的フィールドのバリデーション
        error_log('WPSR Debug - Starting form validation');
        $validation_errors = $form_manager->validate_form_data($_POST);
        error_log('WPSR Debug - Validation errors: ' . print_r($validation_errors, true));
        
        if (!empty($validation_errors)) {
            error_log('WPSR Error - Validation failed: ' . implode(', ', $validation_errors));
            wp_send_json_error(implode('<br>', $validation_errors));
        }
        
        // 基本データのサニタイズ
        $schedule_date = sanitize_text_field($_POST['schedule_date']);
        $schedule_time = sanitize_text_field($_POST['schedule_time']);
        
        // バリデーション
        if (empty($schedule_date) || empty($schedule_time)) {
            wp_send_json_error(__('予約日時が選択されていません。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        
        // 重複チェック
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpsr_reservations 
             WHERE schedule_date = %s AND schedule_time = %s AND status != 'cancelled'",
            $schedule_date,
            $schedule_time
        ));
        
        if ($existing) {
            wp_send_json_error(__('選択された時間は既に予約されています。', 'wp-simple-reservation'));
        }
        
        // 動的フィールドデータを収集
        $fields = $form_manager->get_visible_fields();
        $field_data = array();
        
        foreach ($fields as $field) {
            $field_key = $field['field_key'];
            if (isset($_POST[$field_key])) {
                if ($field['field_type'] === 'checkbox') {
                    // チェックボックスの場合は配列として保存
                    $field_data[$field_key] = is_array($_POST[$field_key]) ? implode(',', $_POST[$field_key]) : $_POST[$field_key];
                } else {
                    $field_data[$field_key] = sanitize_text_field($_POST[$field_key]);
                }
            }
        }
        
        // 予約データを保存
        $insert_data = array(
            'schedule_date' => $schedule_date,
            'schedule_time' => $schedule_time,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        // 動的フィールドデータを追加
        foreach ($field_data as $key => $value) {
            $insert_data[$key] = $value;
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpsr_reservations',
            $insert_data,
            array_fill(0, count($insert_data), '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(__('予約の保存に失敗しました。', 'wp-simple-reservation'));
        }
        
        $reservation_id = $wpdb->insert_id;
        
        // 予約データを取得（メール送信用）
        error_log('WPSR Debug - Getting reservation data for ID: ' . $reservation_id);
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_reservations WHERE id = %d",
            $reservation_id
        ));
        
        error_log('WPSR Debug - Reservation data: ' . print_r($reservation, true));
        
        // メール送信（一時的に無効化）
        /*
        if ($reservation) {
            error_log('WPSR Debug - Starting email sending');
            try {
                $this->send_confirmation_emails($reservation);
                error_log('WPSR Debug - Email sending completed');
            } catch (Exception $e) {
                error_log('WPSR Email Error: ' . $e->getMessage());
                // メール送信でエラーが発生しても予約は成功とする
            }
        }
        */
        
        // Googleカレンダー連携
        if ($reservation) {
            try {
                error_log('WPSR Debug - Google Calendar integration starting');
                do_action('wpsr_reservation_created', $reservation_id, (array)$reservation);
                error_log('WPSR Debug - Google Calendar integration completed');
            } catch (Exception $e) {
                $error_message = 'WPSR Google Calendar Error: ' . $e->getMessage();
                $error_stack = 'WPSR Google Calendar Error Stack: ' . $e->getTraceAsString();
                
                error_log($error_message);
                error_log($error_stack);
                
                // エラーを画面に表示するため、セッションに保存
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['wpsr_error'] = $error_message;
                $_SESSION['wpsr_error_stack'] = $error_stack;
                
                // 直接的なエラー出力（デバッグ用）
                file_put_contents(WP_CONTENT_DIR . '/wpsr-error.log', 
                    date('Y-m-d H:i:s') . ' - ' . $error_message . "\n" . $error_stack . "\n\n", 
                    FILE_APPEND | LOCK_EX
                );
                
                // Googleカレンダー連携でエラーが発生しても予約は成功とする
            }
        }
        
        wp_send_json_success(__('予約が完了しました。', 'wp-simple-reservation'));
    }
    
    public function get_schedules() {
        $date = sanitize_text_field($_POST['date']);
        
        if (empty($date)) {
            wp_send_json_error(__('日付が指定されていません。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        
        // 指定日のスケジュールを取得
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s",
            $date
        ));
        
        if (!$schedule) {
            wp_send_json_error(__('指定された日付のスケジュールが見つかりません。', 'wp-simple-reservation'));
        }
        
        $time_slots = json_decode($schedule->time_slots, true);
        
        // 既存の予約を取得
        $existing_reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT schedule_time FROM {$wpdb->prefix}wpsr_reservations 
             WHERE schedule_date = %s AND status != 'cancelled'",
            $date
        ));
        
        $booked_times = array();
        foreach ($existing_reservations as $reservation) {
            $booked_times[] = $reservation->schedule_time;
        }
        
        // 利用可能な時間枠をフィルタリング
        $available_slots = array();
        foreach ($time_slots as $slot) {
            if (!in_array($slot['time'], $booked_times)) {
                $available_slots[] = $slot;
            }
        }
        
        wp_send_json_success(array(
            'time_slots' => $available_slots
        ));
    }
    
    private function send_confirmation_email($reservation_id) {
        global $wpdb;
        
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_reservations WHERE id = %d",
            $reservation_id
        ));
        
        if (!$reservation) {
            return false;
        }
        
        // メールマネージャーを使用してメール送信
        $this->send_confirmation_emails($reservation);
        
        return true;
    }
    
    /**
     * 予約確認メールを送信
     */
    private function send_confirmation_emails($reservation) {
        // メールマネージャーを取得
        if (!class_exists('WPSR_Email_Manager')) {
            require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-email-manager.php';
        }
        $email_manager = new WPSR_Email_Manager();
        
        // 予約データを整形
        $reservation_data = array(
            'name' => isset($reservation->name) ? $reservation->name : '',
            'email' => isset($reservation->email) ? $reservation->email : '',
            'phone' => isset($reservation->phone) ? $reservation->phone : '',
            'date' => $reservation->schedule_date,
            'time' => $reservation->schedule_time,
            'message' => isset($reservation->message) ? $reservation->message : ''
        );
        
        // 顧客向けメール送信
        $email_manager->send_customer_confirmation($reservation_data);
        
        // 管理者向けメール送信
        $email_manager->send_admin_notification($reservation_data);
    }
    
    /**
     * スケジュールを保存
     */
    public function save_schedule() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // デバッグ用：受信したデータをログに記録
        error_log('WPSR Debug - $_POST data: ' . print_r($_POST, true));
        error_log('WPSR Debug - $_POST keys: ' . print_r(array_keys($_POST), true));
        
        // データのサニタイズ
        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
        $date = sanitize_text_field($_POST['date']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // 時間枠のデータを収集
        $time_slots = array();
        
        // 方法1: time_slots[0], time_slots[1] 形式のデータを取得
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'time_slots[') === 0) {
                $time_slots[] = sanitize_text_field($value);
            }
        }
        
        // 方法2: 従来の配列形式もチェック
        if (empty($time_slots) && isset($_POST['time_slots']) && is_array($_POST['time_slots'])) {
            foreach ($_POST['time_slots'] as $slot) {
                if (!empty($slot)) {
                    $time_slots[] = sanitize_text_field($slot);
                }
            }
        }
        
        // デバッグ用：収集した時間枠データをログに記録
        error_log('WPSR Debug - Collected time_slots: ' . print_r($time_slots, true));
        
        // バリデーション
        if (empty($date)) {
            wp_send_json_error(__('日付が入力されていません。', 'wp-simple-reservation'));
        }
        
        if (empty($time_slots)) {
            wp_send_json_error(__('時間枠が入力されていません。', 'wp-simple-reservation'));
        }
        
        // 時間枠のデータを整理
        $formatted_time_slots = array();
        foreach ($time_slots as $slot) {
            if (!empty($slot) && trim($slot) !== '') {
                $formatted_time_slots[] = array('time' => $slot);
            }
        }
        
        if (empty($formatted_time_slots)) {
            wp_send_json_error(__('有効な時間枠が入力されていません。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        
        $data = array(
            'date' => $date,
            'time_slots' => json_encode($formatted_time_slots),
            'is_available' => $is_available
        );
        
        if ($schedule_id > 0) {
            // 更新
            $result = $wpdb->update(
                $wpdb->prefix . 'wpsr_schedules',
                $data,
                array('id' => $schedule_id),
                array('%s', '%s', '%d'),
                array('%d')
            );
        } else {
            // 新規作成
            $result = $wpdb->insert(
                $wpdb->prefix . 'wpsr_schedules',
                $data,
                array('%s', '%s', '%d')
            );
        }
        
        if ($result === false) {
            wp_send_json_error(__('スケジュールの保存に失敗しました。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success(__('スケジュールが保存されました。', 'wp-simple-reservation'));
    }
    
    /**
     * スケジュールを削除
     */
    public function delete_schedule() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $schedule_id = intval($_POST['schedule_id']);
        
        if ($schedule_id <= 0) {
            wp_send_json_error(__('無効なスケジュールIDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'wpsr_schedules',
            array('id' => $schedule_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('スケジュールの削除に失敗しました。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success(__('スケジュールが削除されました。', 'wp-simple-reservation'));
    }
    
    /**
     * スケジュールデータを取得する
     */
    public function get_schedule() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $schedule_id = intval($_POST['schedule_id']);
        
        if ($schedule_id <= 0) {
            wp_send_json_error(__('無効なスケジュールIDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $schedule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE id = %d",
                $schedule_id
            )
        );
        
        if (!$schedule) {
            wp_send_json_error(__('スケジュールが見つかりません。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success($schedule);
    }
    
    /**
     * 指定日付のスケジュールを取得する
     */
    public function get_schedule_by_date() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $date = sanitize_text_field($_POST['date']);
        
        if (empty($date)) {
            wp_send_json_error(__('日付が指定されていません。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $schedule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s",
                $date
            )
        );
        
        wp_send_json_success($schedule);
    }
    
    /**
     * 指定月のスケジュールを取得する
     */
    public function get_schedules_by_month() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        if (empty($start_date) || empty($end_date)) {
            wp_send_json_error(__('日付範囲が指定されていません。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $schedules = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpsr_schedules 
                 WHERE date >= %s AND date <= %s 
                 ORDER BY date ASC",
                $start_date,
                $end_date
            )
        );
        
        wp_send_json_success($schedules);
    }
    
    /**
     * 予約データを取得する
     */
    public function get_reservation() {
        // デバッグ用：受信したデータをログに記録
        error_log('WPSR Debug - get_reservation $_POST: ' . print_r($_POST, true));
        
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $reservation_id = intval($_POST['reservation_id']);
        
        if ($reservation_id <= 0) {
            wp_send_json_error(__('無効な予約IDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $reservation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpsr_reservations WHERE id = %d",
                $reservation_id
            )
        );
        
        if (!$reservation) {
            wp_send_json_error(__('予約が見つかりません。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success($reservation);
    }
    
    /**
     * 予約を更新する
     */
    public function update_reservation() {
        // デバッグ用：受信したデータをログに記録
        error_log('WPSR Debug - update_reservation $_POST: ' . print_r($_POST, true));
        
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // データのサニタイズ
        $reservation_id = intval($_POST['reservation_id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $schedule_date = sanitize_text_field($_POST['schedule_date']);
        $schedule_time = sanitize_text_field($_POST['schedule_time']);
        $status = sanitize_text_field($_POST['status']);
        $message = sanitize_textarea_field($_POST['message']);
        
        // バリデーション
        if (empty($name) || empty($email) || empty($schedule_date) || empty($schedule_time)) {
            wp_send_json_error(__('必須項目を入力してください。', 'wp-simple-reservation'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(__('有効なメールアドレスを入力してください。', 'wp-simple-reservation'));
        }
        
        if ($reservation_id <= 0) {
            wp_send_json_error(__('無効な予約IDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $data = array(
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'schedule_date' => $schedule_date,
            'schedule_time' => $schedule_time,
            'status' => $status,
            'message' => $message,
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_reservations',
            $data,
            array('id' => $reservation_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('予約の更新に失敗しました。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success(__('予約が更新されました。', 'wp-simple-reservation'));
    }
    
    /**
     * 予約をキャンセルする
     */
    public function cancel_reservation() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $reservation_id = intval($_POST['reservation_id']);
        
        if ($reservation_id <= 0) {
            wp_send_json_error(__('無効な予約IDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_reservations',
            array(
                'status' => 'cancelled',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $reservation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('予約のキャンセルに失敗しました。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success(__('予約がキャンセルされました。', 'wp-simple-reservation'));
    }
    
    /**
     * フィールドを追加
     */
    public function add_field() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // データのサニタイズ
        $field_key = sanitize_key($_POST['field_key']);
        $field_type = sanitize_text_field($_POST['field_type']);
        $field_label = sanitize_text_field($_POST['field_label']);
        $field_placeholder = sanitize_text_field($_POST['field_placeholder']);
        $field_options = sanitize_textarea_field($_POST['field_options']);
        $required = isset($_POST['required']) ? 1 : 0;
        $visible = isset($_POST['visible']) ? 1 : 0;
        
        // バリデーション
        if (empty($field_key) || empty($field_label)) {
            wp_send_json_error(__('必須項目を入力してください。', 'wp-simple-reservation'));
        }
        
        // フィールドキーの重複チェック
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpsr_form_fields WHERE field_key = %s",
            $field_key
        ));
        
        if ($existing) {
            wp_send_json_error(__('このフィールドキーは既に使用されています。', 'wp-simple-reservation'));
        }
        
        // 最大のsort_orderを取得
        $max_sort = $wpdb->get_var("SELECT MAX(sort_order) FROM {$wpdb->prefix}wpsr_form_fields");
        $sort_order = $max_sort ? $max_sort + 1 : 1;
        
        $data = array(
            'field_key' => $field_key,
            'field_type' => $field_type,
            'field_label' => $field_label,
            'field_placeholder' => $field_placeholder,
            'field_options' => $field_options,
            'required' => $required,
            'visible' => $visible,
            'sort_order' => $sort_order
        );
        
        $result = $wpdb->insert($wpdb->prefix . 'wpsr_form_fields', $data);
        
        if ($result === false) {
            wp_send_json_error(__('フィールドの追加に失敗しました。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success(__('フィールドが追加されました。', 'wp-simple-reservation'));
    }
    
    /**
     * フィールドを更新
     */
    public function update_field() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // データのサニタイズ
        $field_id = intval($_POST['field_id']);
        $field_key = sanitize_key($_POST['field_key']);
        $field_type = sanitize_text_field($_POST['field_type']);
        $field_label = sanitize_text_field($_POST['field_label']);
        $field_placeholder = sanitize_text_field($_POST['field_placeholder']);
        $field_options = sanitize_textarea_field($_POST['field_options']);
        $required = isset($_POST['required']) ? 1 : 0;
        $visible = isset($_POST['visible']) ? 1 : 0;
        
        // バリデーション
        if (empty($field_key) || empty($field_label)) {
            wp_send_json_error(__('必須項目を入力してください。', 'wp-simple-reservation'));
        }
        
        if ($field_id <= 0) {
            wp_send_json_error(__('無効なフィールドIDです。', 'wp-simple-reservation'));
        }
        
        // フィールドキーの重複チェック（自分以外）
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpsr_form_fields WHERE field_key = %s AND id != %d",
            $field_key,
            $field_id
        ));
        
        if ($existing) {
            wp_send_json_error(__('このフィールドキーは既に使用されています。', 'wp-simple-reservation'));
        }
        
        $data = array(
            'field_key' => $field_key,
            'field_type' => $field_type,
            'field_label' => $field_label,
            'field_placeholder' => $field_placeholder,
            'field_options' => $field_options,
            'required' => $required,
            'visible' => $visible
        );
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_form_fields',
            $data,
            array('id' => $field_id),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('フィールドの更新に失敗しました。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success(__('フィールドが更新されました。', 'wp-simple-reservation'));
    }
    
    /**
     * フィールドを削除
     */
    public function delete_field() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $field_id = intval($_POST['field_id']);
        
        if ($field_id <= 0) {
            wp_send_json_error(__('無効なフィールドIDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'wpsr_form_fields',
            array('id' => $field_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('フィールドの削除に失敗しました。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success(__('フィールドが削除されました。', 'wp-simple-reservation'));
    }
    
    /**
     * フィールドデータを取得
     */
    public function get_field() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $field_id = intval($_POST['field_id']);
        
        if ($field_id <= 0) {
            wp_send_json_error(__('無効なフィールドIDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $field = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpsr_form_fields WHERE id = %d",
                $field_id
            ),
            ARRAY_A
        );
        
        if (!$field) {
            wp_send_json_error(__('フィールドが見つかりません。', 'wp-simple-reservation'));
        }
        
        wp_send_json_success($field);
    }
    
    /**
     * Googleカレンダー設定ページをレンダリング
     */
    public function render_google_calendar_page() {
        // Googleカレンダーマネージャーを取得
        global $wpsr_google_calendar;
        if (!isset($wpsr_google_calendar)) {
            require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-google-calendar.php';
            $wpsr_google_calendar = new WPSR_Google_Calendar_Manager();
        }
        
        $settings = $wpsr_google_calendar->get_settings();
        
        // 設定保存処理
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['wpsr_google_calendar_nonce'], 'wpsr_google_calendar_settings')) {
            $save_result = $wpsr_google_calendar->save_settings($_POST['wpsr_google_calendar']);
            if ($save_result === false) {
                echo '<div class="notice notice-error"><p>' . __('設定の保存に失敗しました。JSONの形式を確認してください。', 'wp-simple-reservation') . '</p></div>';
                
                // デバッグ情報を表示（開発時のみ）
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $json_content = isset($_POST['wpsr_google_calendar']['service_account']) ? $_POST['wpsr_google_calendar']['service_account'] : '';
                    $json_error = json_last_error_msg();
                    echo '<div class="notice notice-info"><p><strong>デバッグ情報:</strong></p>';
                    echo '<p>JSONエラー: ' . esc_html($json_error) . '</p>';
                    echo '<p>JSON先頭100文字: ' . esc_html(substr($json_content, 0, 100)) . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="notice notice-success"><p>' . __('設定が保存されました。', 'wp-simple-reservation') . '</p></div>';
            }
            $settings = $wpsr_google_calendar->get_settings();
        }
        
        // 接続テスト処理
        if (isset($_POST['test_connection']) && wp_verify_nonce($_POST['wpsr_google_calendar_nonce'], 'wpsr_google_calendar_settings')) {
            $test_result = $wpsr_google_calendar->test_connection();
            $message_class = $test_result['success'] ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . $message_class . '"><p>' . esc_html($test_result['message']) . '</p></div>';
            
            // デバッグ情報を表示（開発時のみ）
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<div class="notice notice-info"><p><strong>デバッグ情報:</strong></p>';
                echo '<pre>' . esc_html(print_r($test_result, true)) . '</pre></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Googleカレンダー連携設定', 'wp-simple-reservation'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpsr_google_calendar_settings', 'wpsr_google_calendar_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpsr_google_calendar_enabled"><?php _e('Googleカレンダー連携を有効にする', 'wp-simple-reservation'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="wpsr_google_calendar_enabled" name="wpsr_google_calendar[enabled]" value="1" <?php checked($settings['enabled']); ?> />
                            <p class="description"><?php _e('チェックすると、新規予約が自動的にGoogleカレンダーに反映されます。', 'wp-simple-reservation'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wpsr_google_calendar_service_account"><?php _e('サービスアカウントJSON', 'wp-simple-reservation'); ?></label>
                        </th>
                        <td>
                            <textarea id="wpsr_google_calendar_service_account" name="wpsr_google_calendar[service_account_raw]" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($settings['service_account']); ?></textarea>
                            <input type="hidden" id="wpsr_google_calendar_service_account_encoded" name="wpsr_google_calendar[service_account]" value="">
                            <p class="description">
                                <?php _e('Google Cloud Consoleで作成したサービスアカウントのJSONキーを貼り付けてください。', 'wp-simple-reservation'); ?><br>
                                <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>でサービスアカウントを作成し、Calendar APIを有効にしてください。
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wpsr_google_calendar_calendar_id"><?php _e('カレンダーID', 'wp-simple-reservation'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wpsr_google_calendar_calendar_id" name="wpsr_google_calendar[calendar_id]" value="<?php echo esc_attr($settings['calendar_id']); ?>" class="regular-text" />
                            <p class="description">
                                <?php _e('予約を反映するGoogleカレンダーのIDを入力してください。', 'wp-simple-reservation'); ?><br>
                                <?php _e('例: example@gmail.com または example.com_abc123@group.calendar.google.com', 'wp-simple-reservation'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wpsr_google_calendar_default_duration"><?php _e('デフォルト予約時間（時間）', 'wp-simple-reservation'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wpsr_google_calendar_default_duration" name="wpsr_google_calendar[default_duration]" value="<?php echo esc_attr($settings['default_duration']); ?>" min="1" max="24" class="small-text" />
                            <p class="description"><?php _e('Googleカレンダーに作成されるイベントのデフォルト時間を設定します。', 'wp-simple-reservation'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('設定を保存', 'wp-simple-reservation'); ?>" />
                    <input type="submit" name="test_connection" id="test_connection" class="button button-secondary" value="<?php _e('接続テスト', 'wp-simple-reservation'); ?>" />
                </p>
            </form>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form');
                const textarea = document.getElementById('wpsr_google_calendar_service_account');
                const hiddenInput = document.getElementById('wpsr_google_calendar_service_account_encoded');
                
                form.addEventListener('submit', function(e) {
                    // JSONをbase64エンコードしてhidden inputに設定
                    const jsonContent = textarea.value;
                    if (jsonContent.trim()) {
                        try {
                            // JSONの構文チェック
                            JSON.parse(jsonContent);
                            // base64エンコード
                            const encoded = btoa(unescape(encodeURIComponent(jsonContent)));
                            hiddenInput.value = encoded;
                        } catch (error) {
                            alert('JSONの形式が正しくありません。確認してください。');
                            e.preventDefault();
                            return false;
                        }
                    }
                });
            });
            </script>
            
            <div class="card">
                <h2><?php _e('セットアップ手順', 'wp-simple-reservation'); ?></h2>
                <ol>
                    <li><?php _e('Google Cloud Consoleでプロジェクトを作成', 'wp-simple-reservation'); ?></li>
                    <li><?php _e('Calendar APIを有効化', 'wp-simple-reservation'); ?></li>
                    <li><?php _e('サービスアカウントを作成', 'wp-simple-reservation'); ?></li>
                    <li><?php _e('サービスアカウントキー（JSON）をダウンロード', 'wp-simple-reservation'); ?></li>
                    <li><?php _e('Googleカレンダーでサービスアカウントに権限を付与', 'wp-simple-reservation'); ?></li>
                    <li><?php _e('上記の設定を入力して保存', 'wp-simple-reservation'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
}
