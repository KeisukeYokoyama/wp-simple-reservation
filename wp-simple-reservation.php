<?php
/**
 * Plugin Name: WP Simple Reservation
 * Plugin URI: https://pejite.com/wp-simple-reservation
 * Description: WP Simple Reservationは、予約システムフォームを導入できるシンプルなプラグインです。
 * Version: 1.0.0
 * Author: Pejite
 * License: GPL v2 or later
 * Text Domain: wp-simple-reservation
 * Domain Path: /languages
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('WPSR_VERSION', '1.0.0');
define('WPSR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPSR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPSR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// メインクラス
class WP_Simple_Reservation {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // プラグイン初期化
        add_action('init', array($this, 'init_plugin'));
        
        // 管理画面の初期化
        if (is_admin()) {
            add_action('init', array($this, 'init_admin'));
        }
        
        // フロントエンドの初期化
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // ショートコード登録
        add_shortcode('wp_simple_reservation_form', array($this, 'render_reservation_form'));
        add_shortcode('wp_simple_reservation_confirm', array($this, 'render_reservation_confirm'));
        add_shortcode('wp_simple_reservation_complete', array($this, 'render_reservation_complete'));
        
        // アクティベーション・デアクティベーション
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init_plugin() {
        // カスタム投稿タイプの登録
        $this->register_post_types();
        
        // データベーステーブルの作成
        $this->create_tables();
        
        // フォームマネージャーの初期化
        require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
        new WPSR_Form_Manager();
        
        // メールマネージャーの初期化
        require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-email-manager.php';
        new WPSR_Email_Manager();
        
        // Googleカレンダーマネージャーの初期化
        require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-google-calendar.php';
        new WPSR_Google_Calendar_Manager();
    }
    
    public function init_admin() {
        // 管理画面の初期化
        require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-admin.php';
        new WPSR_Admin();
        
        // フォームマネージャーの初期化
        require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
        new WPSR_Form_Manager();
    }
    
    public function enqueue_scripts() {
        // CSS
        wp_enqueue_style(
            'wpsr-styles',
            WPSR_PLUGIN_URL . 'assets/css/wpsr-styles.css',
            array(),
            WPSR_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'wpsr-scripts',
            WPSR_PLUGIN_URL . 'assets/js/wpsr-scripts.js',
            array('jquery'),
            WPSR_VERSION,
            true
        );
        
        // Ajax用のnonce
        wp_localize_script('wpsr-scripts', 'wpsr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpsr_nonce'),
            'deadline_days' => intval(get_option('wpsr_booking_deadline_days', 0)),
            'deadline_hours' => intval(get_option('wpsr_booking_deadline_hours', 0)),
            'strings' => array(
                'loading' => __('読み込み中...', 'wp-simple-reservation'),
                'error' => __('エラーが発生しました。', 'wp-simple-reservation'),
                'success' => __('予約が完了しました。', 'wp-simple-reservation')
            )
        ));
    }
    
    public function enqueue_admin_scripts() {
        // FullCalendar.js
        wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
            array(),
            '6.1.10',
            true
        );
        
        // FullCalendar.js CSS
        wp_enqueue_style(
            'fullcalendar-css',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css',
            array(),
            '6.1.10'
        );
        
        // 管理画面用のスクリプト
        wp_enqueue_script(
            'wpsr-admin-scripts',
            WPSR_PLUGIN_URL . 'assets/js/wpsr-admin-scripts.js',
            array('jquery', 'fullcalendar'),
            WPSR_VERSION . '.' . time(), // 強制的にキャッシュを無効化
            true
        );
        
        wp_localize_script('wpsr-admin-scripts', 'wpsr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpsr_nonce')
        ));
    }
    
    public function register_post_types() {
        // 予約カスタム投稿タイプ（管理画面に表示しない）
        register_post_type('wpsr_reservation', array(
            'labels' => array(
                'name' => __('予約', 'wp-simple-reservation'),
                'singular_name' => __('予約', 'wp-simple-reservation'),
                'add_new' => __('新規追加', 'wp-simple-reservation'),
                'add_new_item' => __('新規予約を追加', 'wp-simple-reservation'),
                'edit_item' => __('予約を編集', 'wp-simple-reservation'),
                'new_item' => __('新しい予約', 'wp-simple-reservation'),
                'view_item' => __('予約を表示', 'wp-simple-reservation'),
                'search_items' => __('予約を検索', 'wp-simple-reservation'),
                'not_found' => __('予約が見つかりませんでした。', 'wp-simple-reservation'),
                'not_found_in_trash' => __('ゴミ箱に予約はありません。', 'wp-simple-reservation')
            ),
            'public' => false,
            'show_ui' => false, // 管理画面に表示しない
            'show_in_menu' => false, // メニューに表示しない
            'supports' => array('title'),
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false
            ),
            'map_meta_cap' => true
        ));
        
        // スケジュールカスタム投稿タイプ（管理画面に表示しない）
        register_post_type('wpsr_schedule', array(
            'labels' => array(
                'name' => __('スケジュール', 'wp-simple-reservation'),
                'singular_name' => __('スケジュール', 'wp-simple-reservation'),
                'add_new' => __('新規追加', 'wp-simple-reservation'),
                'add_new_item' => __('新規スケジュールを追加', 'wp-simple-reservation'),
                'edit_item' => __('スケジュールを編集', 'wp-simple-reservation'),
                'new_item' => __('新しいスケジュール', 'wp-simple-reservation'),
                'view_item' => __('スケジュールを表示', 'wp-simple-reservation'),
                'search_items' => __('スケジュールを検索', 'wp-simple-reservation'),
                'not_found' => __('スケジュールが見つかりませんでした。', 'wp-simple-reservation'),
                'not_found_in_trash' => __('ゴミ箱にスケジュールはありません。', 'wp-simple-reservation')
            ),
            'public' => false,
            'show_ui' => false, // 管理画面に表示しない
            'show_in_menu' => false, // メニューに表示しない
            'supports' => array('title'),
            'capability_type' => 'post'
        ));
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 予約テーブル
        $table_reservations = $wpdb->prefix . 'wpsr_reservations';
        $sql_reservations = "CREATE TABLE $table_reservations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            schedule_date date NOT NULL,
            schedule_time time NOT NULL,
            status varchar(20) DEFAULT 'pending',
            message text,
            google_calendar_event_id varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // スケジュールテーブル
        $table_schedules = $wpdb->prefix . 'wpsr_schedules';
        $sql_schedules = "CREATE TABLE $table_schedules (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            time_slots text NOT NULL,
            time_slots_with_stock text NOT NULL,
            is_available tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date (date)
        ) $charset_collate;";
        
        // フォームフィールドテーブル
        $table_form_fields = $wpdb->prefix . 'wpsr_form_fields';
        $sql_form_fields = "CREATE TABLE $table_form_fields (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            field_key varchar(50) NOT NULL,
            field_type varchar(20) NOT NULL,
            field_label varchar(100) NOT NULL,
            field_placeholder varchar(100),
            field_options text,
            required tinyint(1) DEFAULT 0,
            visible tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY field_key (field_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_reservations);
        dbDelta($sql_schedules);
        dbDelta($sql_form_fields);
        
        // 既存テーブルにmessageカラムを追加（存在しない場合のみ）
        $this->update_tables();
        
        // デフォルトフィールドを初期化
        $this->init_default_fields();
    }
    
    /**
     * 既存テーブルのアップデート
     */
    public function update_tables() {
        global $wpdb;
        
        $table_reservations = $wpdb->prefix . 'wpsr_reservations';
        $table_form_fields = $wpdb->prefix . 'wpsr_form_fields';
        $table_schedules = $wpdb->prefix . 'wpsr_schedules';
        
        // 予約テーブルの必要なカラムの定義
        $required_columns = array(
            'message' => 'text AFTER status',
            'google_calendar_event_id' => 'varchar(255) AFTER message'
        );
        
        // 各カラムが存在するかチェックして、存在しない場合は追加
        foreach ($required_columns as $column_name => $column_definition) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_reservations LIKE '$column_name'");
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_reservations ADD COLUMN $column_name $column_definition");
                error_log("WPSR: Added column $column_name to reservations table");
            }
        }
        
        // フォームフィールドテーブルにdeleted_atカラムを追加（存在しない場合のみ）
        $deleted_at_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_form_fields LIKE 'deleted_at'");
        if (empty($deleted_at_exists)) {
            $wpdb->query("ALTER TABLE $table_form_fields ADD COLUMN deleted_at datetime DEFAULT NULL AFTER updated_at");
            error_log("WPSR: Added deleted_at column to form_fields table");
        }
        
        // スケジュールテーブルにtime_slots_with_stockカラムを追加（存在しない場合のみ）
        $time_slots_with_stock_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_schedules LIKE 'time_slots_with_stock'");
        if (empty($time_slots_with_stock_exists)) {
            $wpdb->query("ALTER TABLE $table_schedules ADD COLUMN time_slots_with_stock text NOT NULL AFTER time_slots");
            error_log("WPSR: Added time_slots_with_stock column to schedules table");
        }
    }
    
    /**
     * デフォルトフィールドを初期化
     */
    public function init_default_fields() {
        global $wpdb;
        
        $table_form_fields = $wpdb->prefix . 'wpsr_form_fields';
        
        // 既存のフィールド数を確認
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_form_fields WHERE deleted_at IS NULL");
        
        if ($existing_count == 0) {
            // デフォルトフィールドを挿入
            $default_fields = array(
                array(
                    'field_key' => 'name',
                    'field_type' => 'text',
                    'field_label' => 'お名前',
                    'field_placeholder' => '例：山田太郎',
                    'field_options' => '',
                    'required' => 1,
                    'visible' => 1,
                    'sort_order' => 1
                ),
                array(
                    'field_key' => 'email',
                    'field_type' => 'email',
                    'field_label' => 'メールアドレス',
                    'field_placeholder' => '例：example@email.com',
                    'field_options' => '',
                    'required' => 1,
                    'visible' => 1,
                    'sort_order' => 2
                )
            );
            
            foreach ($default_fields as $field) {
                $wpdb->insert(
                    $table_form_fields,
                    array_merge($field, array(
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    )),
                    array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s')
                );
            }
            
            error_log("WPSR: Initialized default form fields (name, email)");
        }
    }
    
    public function render_reservation_form($atts) {
        // ショートコードの属性を解析
        $atts = shortcode_atts(array(
            'title' => __('予約フォーム', 'wp-simple-reservation'),
            'show_calendar' => 'true'
        ), $atts);
        
        // 独自ラッパーdivで囲んでテーマの影響を排除
        ob_start();
        ?>
        <div class="wpsr-form-wrapper" style="
            width: 100%;
            max-width: 100%;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            box-sizing: border-box;
        ">
            <?php include WPSR_PLUGIN_PATH . 'templates/reservation-form.php'; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_reservation_confirm($atts) {
        // ショートコードの属性を解析
        $atts = shortcode_atts(array(
            'title' => __('予約確認', 'wp-simple-reservation')
        ), $atts);
        
        // URLパラメータからデータを取得
        $encoded_data = isset($_GET['data']) ? sanitize_text_field($_GET['data']) : '';
        
        if (empty($encoded_data)) {
            return '<div class="wpsr-error-message">データが見つかりません。予約フォームから再度お試しください。</div>';
        }
        
        // データをデコード
        $decoded_data = json_decode(base64_decode($encoded_data), true);
        
        if (!$decoded_data || !isset($decoded_data['schedule_date']) || !isset($decoded_data['schedule_time'])) {
            return '<div class="wpsr-error-message">データの形式が正しくありません。予約フォームから再度お試しください。</div>';
        }
        
        // デバッグログ
        error_log('WPSR Debug - Confirm page - Decoded data: ' . print_r($decoded_data, true));
        
        // フォームデータをグローバル変数に設定（テンプレートで使用）
        global $wpsr_form_data;
        $wpsr_form_data = $decoded_data;
        
        // テンプレートファイルを読み込み
        ob_start();
        include WPSR_PLUGIN_PATH . 'templates/reservation-confirm.php';
        return ob_get_clean();
    }
    
    public function render_reservation_complete($atts) {
        // ショートコードの属性を解析
        $atts = shortcode_atts(array(
            'title' => __('予約完了', 'wp-simple-reservation')
        ), $atts);
        
        // URLパラメーターから状態を取得
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $reservation_id = isset($_GET['reservation_id']) ? intval($_GET['reservation_id']) : 0;
        
        // テンプレートファイルを読み込み
        ob_start();
        include WPSR_PLUGIN_PATH . 'templates/reservation-complete.php';
        return ob_get_clean();
    }
    
    public function activate() {
        // プラグイン有効化時の処理
        $this->create_tables();
        
        // デフォルト設定の追加
        add_option('wpsr_email_subject', __('予約確認メール', 'wp-simple-reservation'));
        add_option('wpsr_email_body', __('ご予約ありがとうございます。', 'wp-simple-reservation'));
        add_option('wpsr_admin_email_subject', __('新しい予約がありました', 'wp-simple-reservation'));
        add_option('wpsr_admin_email_body', __('新しい予約が入りました。', 'wp-simple-reservation'));
        add_option('wpsr_admin_email', get_option('admin_email'));
        add_option('wpsr_from_email', get_option('admin_email'));
        add_option('wpsr_from_name', get_bloginfo('name'));
        
        // リライトルールのフラッシュ
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // プラグイン無効化時の処理
        flush_rewrite_rules();
    }
}

// プラグインの初期化
function wpsr_init() {
    $instance = WP_Simple_Reservation::get_instance();
    
    // Googleカレンダーマネージャーを初期化
    if (!class_exists('WPSR_Google_Calendar_Manager')) {
        require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-google-calendar.php';
    }
    global $wpsr_google_calendar;
    $wpsr_google_calendar = new WPSR_Google_Calendar_Manager();
    
    return $instance;
}



// プラグイン開始
wpsr_init();
