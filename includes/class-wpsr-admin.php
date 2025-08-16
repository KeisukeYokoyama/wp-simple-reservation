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
        add_action('wp_ajax_wpsr_save_session_data', array($this, 'save_session_data'));
        add_action('wp_ajax_nopriv_wpsr_save_session_data', array($this, 'save_session_data'));
        add_action('wp_ajax_wpsr_get_schedules', array($this, 'get_schedules'));
        add_action('wp_ajax_nopriv_wpsr_get_schedules', array($this, 'get_schedules'));
        add_action('wp_ajax_wpsr_save_schedule', array($this, 'save_schedule'));
        add_action('wp_ajax_wpsr_delete_schedule', array($this, 'delete_schedule'));
        add_action('wp_ajax_wpsr_get_schedule', array($this, 'get_schedule'));
        add_action('wp_ajax_wpsr_get_schedule_by_date', array($this, 'get_schedule_by_date'));
        add_action('wp_ajax_wpsr_get_schedules_by_month', array($this, 'get_schedules_by_month'));
        add_action('wp_ajax_wpsr_get_available_times_by_date', array($this, 'get_available_times_by_date'));
        add_action('wp_ajax_wpsr_get_reservation', array($this, 'get_reservation'));
        add_action('wp_ajax_wpsr_update_reservation', array($this, 'update_reservation'));
        add_action('wp_ajax_wpsr_cancel_reservation', array($this, 'cancel_reservation'));
        add_action('wp_ajax_wpsr_delete_reservation', array($this, 'delete_reservation'));
        add_action('wp_ajax_wpsr_add_field', array($this, 'add_field'));
        add_action('wp_ajax_wpsr_update_field', array($this, 'update_field'));
        add_action('wp_ajax_wpsr_delete_field', array($this, 'delete_field'));
        add_action('wp_ajax_wpsr_get_field', array($this, 'get_field'));
        add_action('wp_ajax_wpsr_update_field_order', array($this, 'update_field_order'));
        add_action('wp_ajax_wpsr_update_reservations_table', array($this, 'update_reservations_table_ajax'));
        add_action('wp_ajax_wpsr_create_reservation', array($this, 'create_reservation'));
    }
    
    public function add_admin_menu() {
        // メインメニュー - 予約管理
        add_menu_page(
            __('予約管理', 'wp-simple-reservation'),
            __('予約管理', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-schedules', // デフォルトページをスケジュール管理に変更
            array($this, 'render_schedules_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // サブメニュー
        add_submenu_page(
            'wpsr-schedules', // 親メニューをwpsr-schedulesに変更
            __('スケジュール管理', 'wp-simple-reservation'),
            __('スケジュール管理', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-schedules',
            array($this, 'render_schedules_page')
        );
        
        add_submenu_page(
            'wpsr-schedules', // 親メニューをwpsr-schedulesに変更
            __('予約一覧', 'wp-simple-reservation'),
            __('予約一覧', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-reservations',
            array($this, 'render_reservations_page')
        );
        
        add_submenu_page(
            'wpsr-schedules', // 親メニューをwpsr-schedulesに変更
            __('フォーム設定', 'wp-simple-reservation'),
            __('フォーム設定', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-form-settings',
            array($this, 'render_form_settings_page')
        );
        
        add_submenu_page(
            'wpsr-schedules', // 親メニューをwpsr-schedulesに変更
            __('設定', 'wp-simple-reservation'),
            __('設定', 'wp-simple-reservation'),
            'manage_options',
            'wpsr-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'wpsr-schedules', // 親メニューをwpsr-schedulesに変更
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
    
    public function save_session_data() {
        // ノンス検証
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // デバッグログ
        error_log('WPSR Debug - save_session_data called');
        error_log('WPSR Debug - $_POST data: ' . print_r($_POST, true));
        
        // フォームマネージャーを取得
        if (!class_exists('WPSR_Form_Manager')) {
            require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
        }
        $form_manager = new WPSR_Form_Manager();
        
        // スケジュール日時の検証
        $schedule_date = isset($_POST['schedule_date']) ? sanitize_text_field($_POST['schedule_date']) : '';
        $schedule_time = isset($_POST['schedule_time']) ? sanitize_text_field($_POST['schedule_time']) : '';
        
        if (empty($schedule_date) || empty($schedule_time)) {
            wp_send_json_error('Schedule date and time are required');
        }
        
        // フォームデータを収集
        $form_data = array();
        
        // スケジュール情報
        $form_data['schedule_date'] = $schedule_date;
        $form_data['schedule_time'] = $schedule_time;
        
        // 動的フィールドのデータを収集
        $visible_fields = $form_manager->get_visible_fields();
        foreach ($visible_fields as $field) {
            $field_key = $field['field_key'];
            if (isset($_POST[$field_key])) {
                $form_data[$field_key] = sanitize_text_field($_POST[$field_key]);
            }
        }
        
        // 確認画面のURLを生成（データをURLパラメータとして渡す）
        $confirm_url = get_option('wpsr_confirm_page_url', home_url('/booking/confirm/'));
        $confirm_url = add_query_arg('data', base64_encode(json_encode($form_data)), $confirm_url);
        
        // デバッグログ
        error_log('WPSR Debug - Form data encoded: ' . base64_encode(json_encode($form_data)));
        error_log('WPSR Debug - Redirect URL: ' . $confirm_url);
        
        wp_send_json_success(array('redirect_url' => $confirm_url));
    }
    
    public function save_reservation() {
        // デバッグログ
        error_log('WPSR Debug - save_reservation called');
        error_log('WPSR Debug - $_POST data: ' . print_r($_POST, true));
        
        // ノンス検証
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            error_log('WPSR Error - Nonce verification failed');
            error_log('WPSR Debug - Expected nonce: wpsr_nonce');
            error_log('WPSR Debug - Received nonce: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'not set'));
            wp_send_json_error(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // フォームマネージャーを取得
        if (!class_exists('WPSR_Form_Manager')) {
            require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
        }
        $form_manager = new WPSR_Form_Manager();
        
        // 基本データのサニタイズ
        $schedule_date = sanitize_text_field($_POST['schedule_date']);
        $schedule_time = sanitize_text_field($_POST['schedule_time']);
        
        // バリデーション
        if (empty($schedule_date) || empty($schedule_time)) {
            wp_send_json_error(__('予約日時が選択されていません。', 'wp-simple-reservation'));
        }
        
        // 動的フィールドデータを収集
        $fields = $form_manager->get_all_fields();
        $form_data = array(
            'schedule_date' => $schedule_date,
            'schedule_time' => $schedule_time
        );
        
        foreach ($fields as $field) {
            if (isset($field['field_key']) && isset($_POST[$field['field_key']])) {
                if ($field['field_type'] === 'checkbox') {
                    // チェックボックスの場合は配列として保存
                    $form_data[$field['field_key']] = is_array($_POST[$field['field_key']]) ? implode(',', $_POST[$field['field_key']]) : $_POST[$field['field_key']];
                } else {
                    $form_data[$field['field_key']] = sanitize_text_field($_POST[$field['field_key']]);
                }
            }
        }
        
        // データベースに保存
        global $wpdb;
        $table_reservations = $wpdb->prefix . 'wpsr_reservations';
        
        // 既存の予約があるかチェック（同じ日時でキャンセル済みのもの）
        $existing_reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_reservations} 
             WHERE schedule_date = %s AND schedule_time = %s 
             AND status = 'cancelled'",
            $schedule_date, $schedule_time
        ));
        
        if ($existing_reservation) {
            // 既存のキャンセル済み予約を更新
            error_log('WPSR Debug - Updating existing cancelled reservation: ' . $existing_reservation->id);
            
            $result = $wpdb->update(
                $table_reservations,
                array_merge($form_data, array('status' => 'pending', 'updated_at' => current_time('mysql'))),
                array('id' => $existing_reservation->id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                error_log('WPSR Error - Database update failed: ' . $wpdb->last_error);
                wp_send_json_error(__('データベースエラーが発生しました。', 'wp-simple-reservation'));
            }
            
            $reservation_id = $existing_reservation->id;
        } else {
            // 新規予約を作成
            $result = $wpdb->insert(
                $table_reservations,
                $form_data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                error_log('WPSR Error - Database insert failed: ' . $wpdb->last_error);
                wp_send_json_error(__('データベースエラーが発生しました。', 'wp-simple-reservation'));
            }
            
            $reservation_id = $wpdb->insert_id;
        }
        
        // デバッグログ
        error_log('WPSR Debug - Reservation saved/updated with ID: ' . $reservation_id);
        
        // 在庫を更新
        $stock_result = $this->check_and_update_stock($schedule_date, $schedule_time);
        if (!$stock_result['success']) {
            error_log('WPSR Error - Stock update failed: ' . $stock_result['message']);
            // 予約を削除してロールバック
            $wpdb->delete($table_reservations, array('id' => $reservation_id), array('%d'));
            wp_send_json_error($stock_result['message']);
        }
        
        // デバッグログ
        error_log('WPSR Debug - Stock updated successfully');
        
        // 完了画面のURLを生成
        $complete_url = get_option('wpsr_complete_page_url', home_url('/booking/complete/'));
        $complete_url = add_query_arg(array(
            'status' => 'success',
            'reservation_id' => $reservation_id
        ), $complete_url);
        
        // デバッグログ
        error_log('WPSR Debug - Redirect URL: ' . $complete_url);
        
        wp_send_json_success(array('redirect_url' => $complete_url));
    }
    
    /**
     * 在庫チェックと更新
     */
    private function check_and_update_stock($schedule_date, $schedule_time) {
        global $wpdb;
        
        // 指定日のスケジュールを取得
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s",
            $schedule_date
        ));
        
        if (!$schedule) {
            return array(
                'success' => false,
                'message' => __('指定された日付のスケジュールが見つかりません。', 'wp-simple-reservation')
            );
        }
        
        // 時間枠データを解析
        $time_slots = json_decode($schedule->time_slots_with_stock, true);
        if (!$time_slots) {
            return array(
                'success' => false,
                'message' => __('スケジュールデータが正しくありません。', 'wp-simple-reservation')
            );
        }
        
        // 指定時間のスロットを検索
        $target_slot = null;
        $slot_index = -1;
        // 時間形式を統一（HH:MM形式に変換）
        $schedule_time_formatted = substr($schedule_time, 0, 5);
        
        error_log('WPSR Debug - Looking for time slot: ' . $schedule_time_formatted);
        error_log('WPSR Debug - Available time slots: ' . print_r($time_slots, true));
        
        foreach ($time_slots as $index => $slot) {
            if ($slot['time'] === $schedule_time_formatted) {
                $target_slot = $slot;
                $slot_index = $index;
                error_log('WPSR Debug - Found matching slot at index: ' . $index);
                break;
            }
        }
        
        if (!$target_slot) {
            return array(
                'success' => false,
                'message' => __('指定された時間のスケジュールが見つかりません。', 'wp-simple-reservation')
            );
        }
        
        // 在庫チェック（既存の予約がある場合はチェックをスキップ）
        $existing_reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_reservations 
             WHERE schedule_date = %s AND schedule_time = %s 
             AND status != 'cancelled'",
            $schedule_date, $schedule_time
        ));
        
        if (!$existing_reservation && $target_slot['current_stock'] <= 0) {
            return array(
                'success' => false,
                'message' => __('申し訳ございませんが、この時間は満席です。', 'wp-simple-reservation')
            );
        }
        
        // 在庫を減らす（既存の予約がない場合のみ）
        if (!$existing_reservation) {
            $time_slots[$slot_index]['current_stock'] = $target_slot['current_stock'] - 1;
            error_log('WPSR Debug - Decreasing stock from ' . $target_slot['current_stock'] . ' to ' . $time_slots[$slot_index]['current_stock']);
        } else {
            error_log('WPSR Debug - Existing reservation found, keeping stock at ' . $target_slot['current_stock']);
        }
        
        // データベースを更新
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_schedules',
            array('time_slots_with_stock' => json_encode($time_slots)),
            array('id' => $schedule->id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('在庫の更新に失敗しました。', 'wp-simple-reservation')
            );
        }
        
        return array('success' => true);
    }
    
    /**
     * 在庫を戻す
     */
    private function restore_stock($schedule_date, $schedule_time) {
        global $wpdb;
        
        // 指定日のスケジュールを取得
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s",
            $schedule_date
        ));
        
        if (!$schedule) {
            return array(
                'success' => false,
                'message' => __('指定された日付のスケジュールが見つかりません。', 'wp-simple-reservation')
            );
        }
        
        // 時間枠データを解析
        $time_slots = json_decode($schedule->time_slots_with_stock, true);
        if (!$time_slots) {
            return array(
                'success' => false,
                'message' => __('スケジュールデータが正しくありません。', 'wp-simple-reservation')
            );
        }
        
        // 指定時間のスロットを検索
        $slot_index = -1;
        // 時間形式を統一（HH:MM形式に変換）
        $schedule_time_formatted = substr($schedule_time, 0, 5);
        
        foreach ($time_slots as $index => $slot) {
            if ($slot['time'] === $schedule_time_formatted) {
                $slot_index = $index;
                break;
            }
        }
        
        if ($slot_index === -1) {
            return array(
                'success' => false,
                'message' => __('指定された時間のスケジュールが見つかりません。', 'wp-simple-reservation')
            );
        }
        
        // 在庫を戻す（最大在庫数を超えないように）
        $max_stock = $time_slots[$slot_index]['max_stock'];
        $current_stock = $time_slots[$slot_index]['current_stock'];
        $new_stock = min($max_stock, $current_stock + 1);
        
        // デバッグログ
        error_log("WPSR Restore Stock Debug - Date: {$schedule_date}, Time: {$schedule_time}");
        error_log("WPSR Restore Stock Debug - Max Stock: {$max_stock}, Current Stock: {$current_stock}, New Stock: {$new_stock}");
        
        $time_slots[$slot_index]['current_stock'] = $new_stock;
        
        // データベースを更新
        $json_data = json_encode($time_slots);
        error_log("WPSR Restore Stock Debug - JSON Data: {$json_data}");
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_schedules',
            array('time_slots_with_stock' => $json_data),
            array('id' => $schedule->id),
            array('%s'),
            array('%d')
        );
        
        error_log("WPSR Restore Stock Debug - Update Result: " . ($result !== false ? 'success' : 'failed'));
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('在庫の更新に失敗しました。', 'wp-simple-reservation')
            );
        }
        
        return array('success' => true);
    }
    
    /**
     * 指定日の在庫を再計算する
     */
    private function recalculate_stock($schedule_date) {
        global $wpdb;
        
        // 指定日のスケジュールを取得
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s",
            $schedule_date
        ));
        
        if (!$schedule) {
            return array(
                'success' => false,
                'message' => __('指定された日付のスケジュールが見つかりません。', 'wp-simple-reservation')
            );
        }
        
        // 時間枠データを解析
        $time_slots = json_decode($schedule->time_slots_with_stock, true);
        if (!$time_slots) {
            return array(
                'success' => false,
                'message' => __('スケジュールデータが正しくありません。', 'wp-simple-reservation')
            );
        }
        
        // キャンセルされていない予約を取得
        $existing_reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT schedule_time, status FROM {$wpdb->prefix}wpsr_reservations 
             WHERE schedule_date = %s AND status != 'cancelled'",
            $schedule_date
        ));
        
        $booked_times = array();
        foreach ($existing_reservations as $reservation) {
            $booked_times[] = $reservation->schedule_time;
        }
        
        error_log("WPSR Recalculate Stock Debug - Found " . count($existing_reservations) . " active reservations for date: {$schedule_date}");
        error_log("WPSR Recalculate Stock Debug - Booked times: " . implode(', ', $booked_times));
        
        // 各時間枠の在庫を再計算
        foreach ($time_slots as $index => $slot) {
            $booked_count = 0;
            foreach ($booked_times as $booked_time) {
                // 時間の比較（秒を除去して比較）
                $slot_time_short = substr($slot['time'], 0, 5); // "10:00"
                $booked_time_short = substr($booked_time, 0, 5); // "10:00"
                
                if ($booked_time_short === $slot_time_short) {
                    $booked_count++;
                    error_log("WPSR Recalculate Stock Debug - Time match found: {$booked_time_short} === {$slot_time_short}");
                }
            }
            
            $max_stock = $slot['max_stock'];
            $current_stock = max(0, $max_stock - $booked_count);
            $time_slots[$index]['current_stock'] = $current_stock;
            
            error_log("WPSR Recalculate Stock Debug - Time: {$slot['time']}, Max Stock: {$max_stock}, Booked: {$booked_count}, Current Stock: {$current_stock}");
        }
        
        // データベースを更新
        $json_data = json_encode($time_slots);
        error_log("WPSR Recalculate Stock Debug - JSON Data: {$json_data}");
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_schedules',
            array('time_slots_with_stock' => $json_data),
            array('id' => $schedule->id),
            array('%s'),
            array('%d')
        );
        
        error_log("WPSR Recalculate Stock Debug - Update Result: " . ($result !== false ? 'success' : 'failed'));
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('在庫の更新に失敗しました。', 'wp-simple-reservation')
            );
        }
        
        return array('success' => true);
    }
    
    public function get_schedules() {
        $date = sanitize_text_field($_POST['date']);
        
        if (empty($date)) {
            wp_send_json_error(__('日付が指定されていません。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        
        // 指定日のスケジュールを取得（利用可能なもののみ）
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s AND is_available = 1",
            $date
        ));
        
        if (!$schedule) {
            wp_send_json_error(__('指定された日付のスケジュールが見つかりません。', 'wp-simple-reservation'));
        }
        
        $time_slots = json_decode($schedule->time_slots_with_stock, true);
        
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
        
        // 利用可能な時間枠をフィルタリング（在庫管理対応）
        $available_slots = array();
        foreach ($time_slots as $slot) {
            // 既に予約されている時間は除外
            if (in_array($slot['time'], $booked_times)) {
                continue;
            }
            
            // 利用可能な時間枠として追加（在庫0も含める）
            $available_slots[] = array(
                'time' => $slot['time'],
                'current_stock' => $slot['current_stock']
            );
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
        $is_available = (isset($_POST['is_available']) && $_POST['is_available'] == 1) ? 1 : 0;
        
        // デバッグ用：is_availableの値をログに記録
        error_log('WPSR Debug - is_available raw value: ' . (isset($_POST['is_available']) ? $_POST['is_available'] : 'not set'));
        error_log('WPSR Debug - is_available processed value: ' . $is_available);
        
        // 時間枠と在庫数のデータを収集
        $time_slots = array();
        $max_stocks = array();
        
        // 方法1: time_slots[0], time_slots[1] 形式のデータを取得
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'time_slots[') === 0) {
                $time_slots[] = sanitize_text_field($value);
            }
            if (strpos($key, 'max_stock[') === 0) {
                $max_stocks[] = intval($value);
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
        
        if (empty($max_stocks) && isset($_POST['max_stock']) && is_array($_POST['max_stock'])) {
            foreach ($_POST['max_stock'] as $stock) {
                $max_stocks[] = intval($stock);
            }
        }
        
        // デバッグ用：収集したデータをログに記録
        error_log('WPSR Debug - Collected time_slots: ' . print_r($time_slots, true));
        error_log('WPSR Debug - Collected max_stocks: ' . print_r($max_stocks, true));
        error_log('WPSR Debug - $_POST data: ' . print_r($_POST, true));
        
        // バリデーション
        if (empty($date)) {
            wp_send_json_error(__('日付が入力されていません。', 'wp-simple-reservation'));
        }
        
        if (empty($time_slots)) {
            wp_send_json_error(__('時間枠が入力されていません。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        
        // 時間枠と在庫数のデータを整理
        $formatted_time_slots = array();
        
        // 既存のスケジュールデータを取得（更新時）
        $existing_schedule = null;
        if ($schedule_id > 0) {
            $existing_schedule = $wpdb->get_row($wpdb->prepare(
                "SELECT time_slots_with_stock FROM {$wpdb->prefix}wpsr_schedules WHERE id = %d",
                $schedule_id
            ));
        }
        
        // 既存の予約数を取得
        $existing_reservations = array();
        if ($schedule_id > 0) {
            $reservations = $wpdb->get_results($wpdb->prepare(
                "SELECT schedule_time, COUNT(*) as count FROM {$wpdb->prefix}wpsr_reservations 
                 WHERE schedule_date = %s AND status != 'cancelled' 
                 GROUP BY schedule_time",
                $date
            ));
            foreach ($reservations as $reservation) {
                $existing_reservations[$reservation->schedule_time] = $reservation->count;
            }
        }
        
        for ($i = 0; $i < count($time_slots); $i++) {
            if (!empty($time_slots[$i]) && trim($time_slots[$i]) !== '') {
                $time = $time_slots[$i];
                $max_stock = isset($max_stocks[$i]) ? max(0, min(10, $max_stocks[$i])) : 1;
                
                // 既存の予約数を取得
                $booked_count = isset($existing_reservations[$time]) ? $existing_reservations[$time] : 0;
                $current_stock = max(0, $max_stock - $booked_count);
                
                $formatted_time_slots[] = array(
                    'time' => $time,
                    'max_stock' => $max_stock,
                    'current_stock' => $current_stock
                );
            }
        }
        
        if (empty($formatted_time_slots)) {
            wp_send_json_error(__('有効な時間枠が入力されていません。', 'wp-simple-reservation'));
        }
        
        $data = array(
            'date' => $date,
            'time_slots_with_stock' => json_encode($formatted_time_slots),
            'is_available' => $is_available
        );
        
        if ($schedule_id > 0) {
            // 更新
            error_log('WPSR Debug - Updating schedule ID: ' . $schedule_id);
            error_log('WPSR Debug - Update data: ' . print_r($data, true));
            
            $result = $wpdb->update(
                $wpdb->prefix . 'wpsr_schedules',
                $data,
                array('id' => $schedule_id),
                array('%s', '%s', '%d'),
                array('%d')
            );
            
            error_log('WPSR Debug - Update result: ' . $result);
            error_log('WPSR Debug - Last SQL query: ' . $wpdb->last_query);
            error_log('WPSR Debug - Last SQL error: ' . $wpdb->last_error);
        } else {
            // 新規作成
            error_log('WPSR Debug - Creating new schedule');
            error_log('WPSR Debug - Insert data: ' . print_r($data, true));
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'wpsr_schedules',
                $data,
                array('%s', '%s', '%d')
            );
            
            error_log('WPSR Debug - Insert result: ' . $result);
            error_log('WPSR Debug - Last SQL query: ' . $wpdb->last_query);
            error_log('WPSR Debug - Last SQL error: ' . $wpdb->last_error);
        }
        
        if ($result === false) {
            error_log('WPSR Debug - Database operation failed');
            wp_send_json_error(__('スケジュールの保存に失敗しました。', 'wp-simple-reservation'));
        }
        
        // 更新後のデータを確認
        if ($schedule_id > 0) {
            $updated_schedule = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE id = %d",
                    $schedule_id
                )
            );
            error_log('WPSR Debug - Updated schedule data: ' . print_r($updated_schedule, true));
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
        
        // デバッグ用：リクエストされた日付をログに記録
        error_log('WPSR Debug - Requested date: ' . $date);
        
        global $wpdb;
        $schedule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s",
                $date
            )
        );
        
        // デバッグ用：SQLクエリと結果をログに記録
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s", $date);
        error_log('WPSR Debug - SQL Query: ' . $sql);
        error_log('WPSR Debug - Schedule result: ' . print_r($schedule, true));
        
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
        error_log('WPSR Debug - update_reservation method called');
        
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // データのサニタイズ
        $reservation_id = intval($_POST['reservation_id']);
        $schedule_date = sanitize_text_field($_POST['schedule_date']);
        $schedule_time = sanitize_text_field($_POST['schedule_time']);
        $status = sanitize_text_field($_POST['status']);
        $message = sanitize_textarea_field($_POST['message']);
        
        // 現在の予約データを取得（ステータス変更前）
        global $wpdb;
        $current_reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_reservations WHERE id = %d",
            $reservation_id
        ));
        
        if (!$current_reservation) {
            wp_send_json_error(__('予約が見つかりません。', 'wp-simple-reservation'));
        }
        
        $old_status = $current_reservation->status;
        $status_changed = ($old_status !== $status);
        
        error_log("WPSR Update Reservation Debug - Reservation ID: {$reservation_id}");
        error_log("WPSR Update Reservation Debug - Old Status: {$old_status}, New Status: {$status}, Status Changed: " . ($status_changed ? 'yes' : 'no'));
        error_log("WPSR Update Reservation Debug - Status comparison: '{$old_status}' !== '{$status}' = " . ($status_changed ? 'true' : 'false'));
        
        // バリデーション
        if (empty($schedule_date) || empty($schedule_time)) {
            wp_send_json_error(__('必須項目を入力してください。', 'wp-simple-reservation'));
        }
        
        if ($reservation_id <= 0) {
            wp_send_json_error(__('無効な予約IDです。', 'wp-simple-reservation'));
        }
        
        // 動的フィールドのデータを収集
        $form_manager = new WPSR_Form_Manager();
        $visible_fields = $form_manager->get_visible_fields();
        
        $data = array(
            'schedule_date' => $schedule_date,
            'schedule_time' => $schedule_time,
            'status' => $status,
            'message' => $message,
            'updated_at' => current_time('mysql')
        );
        
        // 動的フィールドの値を追加
        foreach ($visible_fields as $field) {
            $field_key = $field['field_key'];
            if (isset($_POST[$field_key])) {
                // フィールドタイプに応じてサニタイズ
                switch ($field['field_type']) {
                    case 'email':
                        $data[$field_key] = sanitize_email($_POST[$field_key]);
                        break;
                    case 'textarea':
                        $data[$field_key] = sanitize_textarea_field($_POST[$field_key]);
                        break;
                    default:
                        $data[$field_key] = sanitize_text_field($_POST[$field_key]);
                        break;
                }
            }
        }
        
        // データベースのカラムを取得
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}wpsr_reservations");
        error_log('WPSR Debug - Available columns: ' . print_r($columns, true));
        
        // 存在するカラムのみをフィルタリング
        $filtered_data = array();
        $format_placeholders = array();
        
        foreach ($data as $key => $value) {
            if (in_array($key, $columns)) {
                $filtered_data[$key] = $value;
                $format_placeholders[] = '%s';
            }
        }
        
        error_log('WPSR Debug - Filtered data: ' . print_r($filtered_data, true));
        error_log('WPSR Debug - Format placeholders: ' . print_r($format_placeholders, true));
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wpsr_reservations',
            $filtered_data,
            array('id' => $reservation_id),
            $format_placeholders,
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('予約の更新に失敗しました。', 'wp-simple-reservation'));
        }
        
        // ステータスが変更された場合、在庫を再計算
        if ($status_changed) {
            error_log("WPSR Update Reservation Debug - Status changed from '{$old_status}' to '{$status}', recalculating stock for date: {$schedule_date}");
            
            // ステータス変更の方向性をログ出力
            if ($old_status === 'cancelled' && in_array($status, ['confirmed', 'pending'])) {
                error_log("WPSR Update Reservation Debug - Cancelled reservation being reactivated, stock will decrease");
            } elseif (in_array($old_status, ['confirmed', 'pending']) && $status === 'cancelled') {
                error_log("WPSR Update Reservation Debug - Active reservation being cancelled, stock will increase");
            }
            
            $stock_result = $this->recalculate_stock($schedule_date);
            if (!$stock_result['success']) {
                wp_send_json_error($stock_result['message']);
            }
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
        
        error_log("WPSR Cancel Reservation Debug - Reservation ID: {$reservation_id}");
        
        if ($reservation_id <= 0) {
            wp_send_json_error(__('無効な予約IDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        
        // 予約データを取得（在庫戻し用）
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_reservations WHERE id = %d",
            $reservation_id
        ));
        
        if (!$reservation) {
            wp_send_json_error(__('予約が見つかりません。', 'wp-simple-reservation'));
        }
        
        error_log("WPSR Cancel Reservation Debug - Found reservation: Date: {$reservation->schedule_date}, Time: {$reservation->schedule_time}, Status: {$reservation->status}");
        
        // 既にキャンセルされている場合はエラー
        if ($reservation->status === 'cancelled') {
            wp_send_json_error(__('この予約は既にキャンセルされています。', 'wp-simple-reservation'));
        }
        
        // 予約ステータスをキャンセルに更新（先にステータスを変更）
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
        
        // 在庫を再計算（キャンセル後の状態で）
        $stock_result = $this->recalculate_stock($reservation->schedule_date);
        if (!$stock_result['success']) {
            wp_send_json_error($stock_result['message']);
        }
        
        wp_send_json_success(__('予約がキャンセルされました。', 'wp-simple-reservation'));
    }
    
    /**
     * 予約を削除する
     */
    public function delete_reservation() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $reservation_id = intval($_POST['reservation_id']);
        
        error_log("WPSR Delete Reservation Debug - Reservation ID: {$reservation_id}");
        
        if ($reservation_id <= 0) {
            wp_send_json_error(__('無効な予約IDです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        
        // 予約データを取得（在庫戻し用）
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpsr_reservations WHERE id = %d",
            $reservation_id
        ));
        
        if (!$reservation) {
            wp_send_json_error(__('予約が見つかりません。', 'wp-simple-reservation'));
        }
        
        // 予約を削除
        $result = $wpdb->delete(
            $wpdb->prefix . 'wpsr_reservations',
            array('id' => $reservation_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('予約の削除に失敗しました。', 'wp-simple-reservation'));
        }
        
        // 在庫を再計算（削除後の状態で）
        if ($reservation->status !== 'cancelled') {
            $stock_result = $this->recalculate_stock($reservation->schedule_date);
            if (!$stock_result['success']) {
                wp_send_json_error($stock_result['message']);
            }
        }
        
        wp_send_json_success(__('予約が削除されました。', 'wp-simple-reservation'));
    }
    
    /**
     * フィールドを追加
     */
    public function add_field() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // デバッグ用：受信データをログに記録
        error_log('WPSR Debug - Add field POST data: ' . print_r($_POST, true));
        
        // データのサニタイズ
        $field_key = sanitize_key($_POST['field_key']);
        $field_type = sanitize_text_field($_POST['field_type']);
        $field_label = sanitize_text_field($_POST['field_label']);
        $field_placeholder = sanitize_text_field($_POST['field_placeholder']);
        $field_options = sanitize_textarea_field($_POST['field_options']);
        // チェックボックスの値を確実に処理
        $required = (isset($_POST['required']) && $_POST['required'] == 1) ? 1 : 0;
        $visible = (isset($_POST['visible']) && $_POST['visible'] == 1) ? 1 : 0;
        
        // デバッグ用：処理後の値をログに記録
        error_log('WPSR Debug - Processed values - required: ' . $required . ', visible: ' . $visible);
        
        // バリデーション
        if (empty($field_key) || empty($field_label)) {
            wp_send_json_error(__('必須項目を入力してください。', 'wp-simple-reservation'));
        }
        
        // フォームマネージャーを使用してフィールド追加時の型チェックを実行
        $form_manager = new WPSR_Form_Manager();
        
        $field_data = array(
            'field_label' => $field_label,
            'field_placeholder' => $field_placeholder,
            'field_options' => $field_options,
            'required' => $required,
            'visible' => $visible
        );
        
        $result = $form_manager->check_field_addition($field_key, $field_type, $field_data);
        
        if (!$result['success']) {
            wp_send_json_error($result['error']);
        }
        
        // デバッグ用：結果をログに記録
        error_log('WPSR Debug - Field addition result: ' . print_r($result, true));
        
        // テーブル更新を実行
        do_action('wpsr_field_added');
        
        // 結果に応じてメッセージを送信
        if ($result['action'] === 'reactivated') {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_success(__('フィールドが追加されました。', 'wp-simple-reservation'));
        }
    }
    
    /**
     * フィールドを更新
     */
    public function update_field() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // デバッグ用：受信データをログに記録
        error_log('WPSR Debug - Update field POST data: ' . print_r($_POST, true));
        
        // データのサニタイズ
        $field_id = intval($_POST['field_id']);
        $field_key = sanitize_key($_POST['field_key']);
        $field_type = sanitize_text_field($_POST['field_type']);
        $field_label = sanitize_text_field($_POST['field_label']);
        $field_placeholder = sanitize_text_field($_POST['field_placeholder']);
        $field_options = sanitize_textarea_field($_POST['field_options']);
        // チェックボックスの値を確実に処理
        $required = (isset($_POST['required']) && $_POST['required'] == 1) ? 1 : 0;
        $visible = (isset($_POST['visible']) && $_POST['visible'] == 1) ? 1 : 0;
        
        // デバッグ用：処理後の値をログに記録
        error_log('WPSR Debug - Processed values - required: ' . $required . ', visible: ' . $visible);
        
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
        
        // デバッグ用：データベースに保存するデータをログに記録
        error_log('WPSR Debug - Database update data: ' . print_r($data, true));
        
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
     * フィールドを論理削除
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
        
        // フォームマネージャーを使用して論理削除を実行
        $form_manager = new WPSR_Form_Manager();
        $result = $form_manager->delete_field($field_id);
        
        if (!$result) {
            wp_send_json_error(__('フィールドの削除に失敗しました。', 'wp-simple-reservation'));
        }
        
        // テーブル更新を実行
        do_action('wpsr_field_deleted');
        
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
        
        // デバッグ用：取得したフィールドデータをログに記録
        error_log('WPSR Debug - Get field data: ' . print_r($field, true));
        
        // field_optionsの処理を改善
        if (!empty($field['field_options'])) {
            error_log('WPSR Debug - Raw field_options: ' . $field['field_options']);
            
            // 二重エスケープの可能性をチェック
            $decoded = json_decode($field['field_options'], true);
            if ($decoded === null) {
                // JSON解析に失敗した場合、エスケープを解除して再試行
                $unescaped = stripslashes($field['field_options']);
                error_log('WPSR Debug - Unescaped field_options: ' . $unescaped);
                $decoded = json_decode($unescaped, true);
                if ($decoded !== null) {
                    $field['field_options'] = $unescaped;
                    error_log('WPSR Debug - Successfully unescaped field_options');
                }
            }
        }
        
        wp_send_json_success($field);
    }
    
    /**
     * フィールドの並び順を更新
     */
    public function update_field_order() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // デバッグ用：受信データをログに記録
        error_log('WPSR Debug - Update field order POST data: ' . print_r($_POST, true));
        
        if (!isset($_POST['field_order'])) {
            error_log('WPSR Debug - field_order not found in POST data');
            wp_send_json_error(__('並び順データが見つかりません。', 'wp-simple-reservation'));
        }
        
        // JSONデータはsanitize_text_fieldを使用せずに直接取得
        $field_order_json = $_POST['field_order'];
        error_log('WPSR Debug - Raw field_order_json: ' . $field_order_json);
        error_log('WPSR Debug - field_order_json length: ' . strlen($field_order_json));
        
        // 二重エスケープを解除
        $field_order_json = stripslashes($field_order_json);
        error_log('WPSR Debug - Unescaped field_order_json: ' . $field_order_json);
        
        $field_order = json_decode($field_order_json, true);
        error_log('WPSR Debug - Decoded field_order: ' . print_r($field_order, true));
        error_log('WPSR Debug - JSON decode error: ' . json_last_error_msg());
        error_log('WPSR Debug - field_order type: ' . gettype($field_order));
        error_log('WPSR Debug - field_order is array: ' . (is_array($field_order) ? 'true' : 'false'));
        
        if (!$field_order || !is_array($field_order)) {
            error_log('WPSR Debug - Invalid field_order data');
            error_log('WPSR Debug - field_order is empty: ' . (empty($field_order) ? 'true' : 'false'));
            wp_send_json_error(__('無効な並び順データです。', 'wp-simple-reservation'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpsr_form_fields';
        $updated_count = 0;
        
        foreach ($field_order as $item) {
            $field_id = intval($item['field_id']);
            $sort_order = intval($item['sort_order']);
            
            if ($field_id > 0 && $sort_order > 0) {
                $result = $wpdb->update(
                    $table_name,
                    array('sort_order' => $sort_order),
                    array('id' => $field_id),
                    array('%d'),
                    array('%d')
                );
                
                if ($result !== false) {
                    $updated_count++;
                }
            }
        }
        
        // デバッグ用：更新結果をログに記録
        error_log('WPSR Debug - Updated ' . $updated_count . ' fields out of ' . count($field_order));
        
        if ($updated_count > 0) {
            wp_send_json_success(sprintf(__('%d個のフィールドの並び順を更新しました。', 'wp-simple-reservation'), $updated_count));
        } else {
            wp_send_json_error(__('並び順の更新に失敗しました。', 'wp-simple-reservation'));
        }
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
    
    /**
     * 予約テーブル更新のAjaxハンドラー
     */
    public function update_reservations_table_ajax() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // フォームマネージャーを取得
        if (!class_exists('WPSR_Form_Manager')) {
            require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
        }
        $form_manager = new WPSR_Form_Manager();
        
        // テーブル更新を実行
        $form_manager->update_reservations_table();
        
        wp_send_json_success(__('データベーステーブルが更新されました。', 'wp-simple-reservation'));
    }
    
    /**
     * 新規予約を作成する
     */
    public function create_reservation() {
        // デバッグ用：受信したデータをログに記録
        error_log('WPSR Debug - create_reservation $_POST: ' . print_r($_POST, true));
        
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        // データのサニタイズ
        $schedule_date = sanitize_text_field($_POST['schedule_date']);
        $schedule_time = sanitize_text_field($_POST['schedule_time']);
        $status = sanitize_text_field($_POST['status']);
        $message = sanitize_textarea_field($_POST['message']);
        
        // バリデーション
        if (empty($schedule_date) || empty($schedule_time)) {
            wp_send_json_error(__('必須項目を入力してください。', 'wp-simple-reservation'));
        }
        
        // 動的フィールドのデータを収集
        $form_manager = new WPSR_Form_Manager();
        $visible_fields = $form_manager->get_visible_fields();
        
        $data = array(
            'schedule_date' => $schedule_date,
            'schedule_time' => $schedule_time,
            'status' => $status,
            'message' => $message,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // 動的フィールドの値を追加
        foreach ($visible_fields as $field) {
            $field_key = $field['field_key'];
            if (isset($_POST[$field_key])) {
                // フィールドタイプに応じてサニタイズ
                switch ($field['field_type']) {
                    case 'email':
                        $data[$field_key] = sanitize_email($_POST[$field_key]);
                        break;
                    case 'textarea':
                        $data[$field_key] = sanitize_textarea_field($_POST[$field_key]);
                        break;
                    default:
                        $data[$field_key] = sanitize_text_field($_POST[$field_key]);
                        break;
                }
            }
        }
        
        // データベースのカラムを取得
        global $wpdb;
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}wpsr_reservations");
        error_log('WPSR Debug - Available columns: ' . print_r($columns, true));
        
        // 存在するカラムのみをフィルタリング
        $filtered_data = array();
        $format_placeholders = array();
        
        foreach ($data as $key => $value) {
            if (in_array($key, $columns)) {
                $filtered_data[$key] = $value;
                $format_placeholders[] = '%s';
            }
        }
        
        error_log('WPSR Debug - Filtered data: ' . print_r($filtered_data, true));
        error_log('WPSR Debug - Format placeholders: ' . print_r($format_placeholders, true));
        
        // 予約を挿入
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpsr_reservations',
            $filtered_data,
            $format_placeholders
        );
        
        if ($result === false) {
            wp_send_json_error(__('予約の作成に失敗しました。', 'wp-simple-reservation'));
        }
        
        $reservation_id = $wpdb->insert_id;
        
        // 在庫を再計算
        $stock_result = $this->recalculate_stock($schedule_date);
        if (!$stock_result['success']) {
            wp_send_json_error($stock_result['message']);
        }
        
        wp_send_json_success(__('新規予約を作成しました。', 'wp-simple-reservation'));
    }
    
    /**
     * 指定日の利用可能時間を取得する
     */
    public function get_available_times_by_date() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'wpsr_nonce')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'wp-simple-reservation'));
        }
        
        $date = sanitize_text_field($_POST['date']);
        
        if (empty($date)) {
            wp_send_json_error(__('日付が指定されていません。', 'wp-simple-reservation'));
        }
        
        // デバッグ用：リクエストされた日付をログに記録
        error_log('WPSR Debug - get_available_times_by_date requested date: ' . $date);
        
        global $wpdb;
        
        // 指定日のスケジュールを取得
        $schedule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpsr_schedules WHERE date = %s",
                $date
            )
        );
        
        if (!$schedule) {
            wp_send_json_success(array()); // スケジュールが存在しない場合は空配列
        }
        
        // 時間枠を解析
        $time_slots = json_decode($schedule->time_slots_with_stock, true);
        if (!$time_slots) {
            wp_send_json_success(array()); // 時間枠が不正な場合は空配列
        }
        
        // 各時間枠の利用可能数を計算
        $available_times = array();
        foreach ($time_slots as $time_slot) {
            $time = $time_slot['time'];
            $max_slots = intval($time_slot['max_stock']);
            $current_stock = intval($time_slot['current_stock']);
            
            // current_stockが利用可能数（1 = 利用可能、0 = 満席）
            if ($current_stock > 0) {
                $available_times[] = array(
                    'time_slot' => $time,
                    'available_slots' => $current_stock,
                    'max_slots' => $max_slots
                );
            }
        }
        
        // デバッグ用：結果をログに記録
        error_log('WPSR Debug - Available times: ' . print_r($available_times, true));
        
        wp_send_json_success($available_times);
    }
}
