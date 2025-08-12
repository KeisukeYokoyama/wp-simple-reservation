<?php
/**
 * Plugin Name: WP Simple Reservation
 * Plugin URI: https://pejite.com/wp-simple-reservation
 * Description: シンプルな予約管理プラグイン。オンラインで空きスケジュールを確認し、予約できる。
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
            PRIMARY KEY (id),
            UNIQUE KEY field_key (field_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_reservations);
        dbDelta($sql_schedules);
        dbDelta($sql_form_fields);
        
        // 既存テーブルにmessageカラムを追加（存在しない場合のみ）
        $this->update_tables();
    }
    
    /**
     * 既存テーブルのアップデート
     */
    public function update_tables() {
        global $wpdb;
        
        $table_reservations = $wpdb->prefix . 'wpsr_reservations';
        
        // 必要なカラムの定義
        $required_columns = array(
            'message' => 'text AFTER status',
            'birthdate' => 'date AFTER phone',
            'box_test' => 'varchar(255) AFTER birthdate',
            'text_area_test' => 'text AFTER box_test',
            'radio_test' => 'varchar(50) AFTER text_area_test',
            'gender' => 'varchar(20) AFTER radio_test'
        );
        
        // 各カラムが存在するかチェックして、存在しない場合は追加
        foreach ($required_columns as $column_name => $column_definition) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_reservations LIKE '$column_name'");
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_reservations ADD COLUMN $column_name $column_definition");
                error_log("WPSR: Added column $column_name to reservations table");
            }
        }
    }
    
    public function render_reservation_form($atts) {
        // ショートコードの属性を解析
        $atts = shortcode_atts(array(
            'title' => __('予約フォーム', 'wp-simple-reservation'),
            'show_calendar' => 'true'
        ), $atts);
        
        // テンプレートファイルを読み込み
        ob_start();
        include WPSR_PLUGIN_PATH . 'templates/reservation-form.php';
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
    return WP_Simple_Reservation::get_instance();
}

// プラグイン開始
wpsr_init();
