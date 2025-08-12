<?php
/**
 * WP Simple Reservation Google Calendar Manager
 * Googleカレンダー連携管理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSR_Google_Calendar_Manager {
    
    private $api_key;
    private $calendar_id;
    private $service_account_file;
    private $is_enabled;
    
    public function __construct() {
        $this->api_key = get_option('wpsr_google_calendar_api_key', '');
        $this->calendar_id = get_option('wpsr_google_calendar_id', '');
        
        // JSONファイルから読み込み
        $json_file_path = get_option('wpsr_google_calendar_service_account_file', '');
        $this->service_account_file = !empty($json_file_path) && file_exists($json_file_path) ? file_get_contents($json_file_path) : '';
        
        $this->is_enabled = get_option('wpsr_google_calendar_enabled', false);
        
        // 予約保存時のフック
        add_action('wpsr_reservation_created', array($this, 'create_calendar_event'), 10, 2);
    }
    
    /**
     * Googleカレンダー連携が有効かチェック
     */
    public function is_enabled() {
        return $this->is_enabled && !empty($this->service_account_file) && !empty($this->calendar_id);
    }
    
    /**
     * 予約からGoogleカレンダーイベントを作成（軽量版）
     */
    public function create_calendar_event($reservation_id, $reservation_data) {
        if (!$this->is_enabled()) {
            return false;
        }
        
        try {
            // Google Calendar APIクライアントを初期化
            $client_data = $this->get_google_client();
            if (!$client_data) {
                error_log('WPSR Google Calendar: Failed to initialize Google client');
                return false;
            }
            
            // アクセストークンを取得
            $access_token = $this->get_access_token($client_data['access_token']);
            if (!$access_token) {
                error_log('WPSR Google Calendar: Failed to get access token');
                return false;
            }
            
            // イベントデータを準備
            $event_data = $this->prepare_event_data($reservation_data);
            
            // イベントデータの準備が失敗した場合
            if ($event_data === false) {
                error_log('WPSR Google Calendar: Failed to prepare event data');
                return false;
            }
            
            // イベントを作成
            error_log('WPSR Debug - Creating calendar event with data: ' . print_r($event_data, true));
            $event_id = $this->create_calendar_event_via_api($access_token, $event_data);
            error_log('WPSR Debug - Calendar event creation result: ' . ($event_id ? $event_id : 'failed'));
            
            if ($event_id) {
                // 作成されたイベントのIDを予約データに保存
                $this->save_calendar_event_id($reservation_id, $event_id);
                error_log('WPSR Google Calendar: Event created successfully. Event ID: ' . $event_id);
                return $event_id;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('WPSR Google Calendar Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Google APIクライアントを初期化（軽量版）
     */
    private function get_google_client() {
        $debug_info = array();
        
        if (empty($this->service_account_file)) {
            $debug_info[] = 'サービスアカウントJSONが空';
            error_log('WPSR Google Calendar: Service account JSON is empty');
            return array('debug' => $debug_info);
        }
        
        try {
            $debug_info[] = 'JSONデコード開始';
            // サービスアカウントJSONをデコード
            $service_account_data = json_decode($this->service_account_file, true);
            if (!$service_account_data) {
                $debug_info[] = 'JSONデコード失敗';
                $debug_info[] = 'JSONエラー: ' . json_last_error_msg();
                $debug_info[] = 'JSON長さ: ' . strlen($this->service_account_file);
                $debug_info[] = 'JSON先頭50文字: ' . substr($this->service_account_file, 0, 50);
                error_log('WPSR Google Calendar: Invalid service account JSON - JSON decode failed: ' . json_last_error_msg());
                return array('debug' => $debug_info);
            }
            $debug_info[] = 'JSONデコード成功';
            
            // 必要なフィールドが存在するかチェック
            $required_fields = array('client_email', 'private_key', 'project_id');
            foreach ($required_fields as $field) {
                if (!isset($service_account_data[$field]) || empty($service_account_data[$field])) {
                    $debug_info[] = '必須フィールド不足: ' . $field;
                    error_log('WPSR Google Calendar: Missing required field: ' . $field);
                    return array('debug' => $debug_info);
                }
            }
            $debug_info[] = '必須フィールド確認完了';
            
            // JWTトークンを生成
            $debug_info[] = 'JWTトークン生成開始';
            $jwt_token = $this->generate_jwt_token($service_account_data);
            if (!$jwt_token) {
                $debug_info[] = 'JWTトークン生成失敗';
                error_log('WPSR Google Calendar: Failed to generate JWT token');
                return array('debug' => $debug_info);
            }
            $debug_info[] = 'JWTトークン生成成功';
            
            error_log('WPSR Google Calendar: Client initialized successfully');
            return array(
                'access_token' => $jwt_token,
                'service_account' => $service_account_data,
                'debug' => $debug_info
            );
            
        } catch (Exception $e) {
            $debug_info[] = 'Exception: ' . $e->getMessage();
            error_log('WPSR Google Calendar: Failed to initialize client: ' . $e->getMessage());
            return array('debug' => $debug_info);
        }
    }
    
    /**
     * JWTトークンを生成
     */
    private function generate_jwt_token($service_account_data) {
        try {
            $header = array(
                'alg' => 'RS256',
                'typ' => 'JWT'
            );
            
            $time = time();
            $payload = array(
                'iss' => $service_account_data['client_email'],
                'scope' => 'https://www.googleapis.com/auth/calendar',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $time + 3600,
                'iat' => $time
            );
            
            $header_encoded = $this->base64url_encode(json_encode($header));
            $payload_encoded = $this->base64url_encode(json_encode($payload));
            
            // プライベートキーの処理
            $private_key = $service_account_data['private_key'];
            
            // プライベートキーが正しい形式かチェック
            if (strpos($private_key, '-----BEGIN PRIVATE KEY-----') === false) {
                error_log('WPSR Google Calendar: Invalid private key format');
                return false;
            }
            
            $signature = '';
            $sign_result = openssl_sign(
                $header_encoded . '.' . $payload_encoded,
                $signature,
                $private_key,
                'SHA256'
            );
            
            if ($sign_result) {
                $signature_encoded = $this->base64url_encode($signature);
                $jwt_token = $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
                error_log('WPSR Google Calendar: JWT token generated successfully');
                return $jwt_token;
            } else {
                error_log('WPSR Google Calendar: Failed to sign JWT token');
                return false;
            }
            
        } catch (Exception $e) {
            error_log('WPSR Google Calendar: Failed to generate JWT token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Base64URLエンコード
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * アクセストークンを取得
     */
    private function get_access_token($jwt_token) {
        $url = 'https://oauth2.googleapis.com/token';
        $data = array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt_token
        );
        
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('WPSR Google Calendar: Failed to get access token: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $token_data = json_decode($body, true);
        
        if (isset($token_data['access_token'])) {
            return $token_data['access_token'];
        }
        
        error_log('WPSR Google Calendar: Invalid token response: ' . $body);
        return false;
    }
    
    /**
     * API経由でカレンダーイベントを作成
     */
    private function create_calendar_event_via_api($access_token, $event_data) {
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($this->calendar_id) . '/events';
        
        error_log('WPSR Debug - API URL: ' . $url);
        error_log('WPSR Debug - Request body: ' . json_encode($event_data));
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($event_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('WPSR Google Calendar: Failed to create event: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('WPSR Debug - Response status: ' . $status_code);
        error_log('WPSR Debug - Response body: ' . $body);
        
        $event_response = json_decode($body, true);
        
        if (isset($event_response['id'])) {
            error_log('WPSR Debug - Event created successfully with ID: ' . $event_response['id']);
            return $event_response['id'];
        }
        
        error_log('WPSR Google Calendar: Failed to create event: ' . $body);
        return false;
    }
    
    /**
     * イベントデータを準備（軽量版）
     */
    private function prepare_event_data($reservation_data) {
        // 開始時刻のフォーマット修正（秒を除去してから追加）
        $time_without_seconds = substr($reservation_data['schedule_time'], 0, 5);
        $start_time = $reservation_data['schedule_date'] . 'T' . $time_without_seconds . ':00';
        $end_time = $this->calculate_end_time($reservation_data['schedule_date'], $reservation_data['schedule_time']);
        
        // 終了時刻の計算が失敗した場合
        if ($end_time === false) {
            error_log('WPSR Google Calendar: Failed to calculate end time');
            return false;
        }
        
        $event_data = array(
            'summary' => '予約: ' . $reservation_data['name'],
            'description' => $this->format_event_description($reservation_data),
            'start' => array(
                'dateTime' => $start_time,
                'timeZone' => 'Asia/Tokyo'
            ),
            'end' => array(
                'dateTime' => $end_time,
                'timeZone' => 'Asia/Tokyo'
            )
        );
        
        return $event_data;
    }
    
    /**
     * 終了時刻を計算（デフォルト1時間後）
     */
    private function calculate_end_time($date, $time) {
        $duration_hours = get_option('wpsr_default_booking_duration', 1);
        
        // 時間から秒を除去（HH:MM:SS → HH:MM）
        $time_without_seconds = substr($time, 0, 5);
        
        $start_datetime = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time_without_seconds);
        
        // DateTime作成が失敗した場合のエラーハンドリング
        if ($start_datetime === false) {
            error_log('WPSR Google Calendar: Failed to create DateTime from: ' . $date . ' ' . $time_without_seconds);
            return false;
        }
        
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . $duration_hours . 'H'));
        
        return $end_datetime->format('Y-m-d\TH:i:s');
    }
    
    /**
     * イベントの説明文をフォーマット
     */
    private function format_event_description($reservation_data) {
        $description = "予約者: " . $reservation_data['name'] . "\n";
        $description .= "メール: " . $reservation_data['email'] . "\n";
        
        if (!empty($reservation_data['phone'])) {
            $description .= "電話: " . $reservation_data['phone'] . "\n";
        }
        
        if (!empty($reservation_data['message'])) {
            $description .= "メッセージ: " . $reservation_data['message'] . "\n";
        }
        
        $description .= "予約日時: " . $reservation_data['schedule_date'] . " " . $reservation_data['schedule_time'];
        
        return $description;
    }
    
    /**
     * カレンダーイベントIDを予約データに保存
     */
    private function save_calendar_event_id($reservation_id, $event_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpsr_reservations';
        $wpdb->update(
            $table_name,
            array('google_calendar_event_id' => $event_id),
            array('id' => $reservation_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * 設定を保存
     */
    public function save_settings($settings) {
        update_option('wpsr_google_calendar_enabled', isset($settings['enabled']) ? true : false);
        
        // JSONフィールドは特別な処理（base64デコード）
        $service_account_json = isset($settings['service_account']) ? $settings['service_account'] : '';
        
        // base64デコード
        if (!empty($service_account_json)) {
            $service_account_json = base64_decode($service_account_json);
            if ($service_account_json === false) {
                error_log('WPSR Google Calendar: Failed to decode base64 JSON');
                return false;
            }
        }
        
        // 最小限のサニタイズ（HTMLタグのみ除去）
        $service_account_json = preg_replace('/<[^>]*>/', '', $service_account_json);
        $service_account_json = trim($service_account_json);
        
        // JSONの構文チェック
        if (!empty($service_account_json)) {
            $decoded = json_decode($service_account_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('WPSR Google Calendar: Invalid JSON after processing: ' . json_last_error_msg());
                error_log('WPSR Google Calendar: JSON content (first 200 chars): ' . substr($service_account_json, 0, 200));
                return false;
            }
        }
        
        // JSONをファイルとして保存（エスケープ問題を完全回避）
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/wp-simple-reservation/';
        
        // ディレクトリが存在しない場合は作成
        if (!file_exists($plugin_dir)) {
            wp_mkdir_p($plugin_dir);
        }
        
        $json_file_path = $plugin_dir . 'google-service-account.json';
        
        // JSONファイルを保存
        $result = file_put_contents($json_file_path, $service_account_json);
        if ($result === false) {
            error_log('WPSR Google Calendar: Failed to save JSON file');
            return false;
        }
        
        // ファイルパスを保存
        update_option('wpsr_google_calendar_service_account_file', $json_file_path);
        
        update_option('wpsr_google_calendar_id', sanitize_text_field($settings['calendar_id']));
        update_option('wpsr_default_booking_duration', intval($settings['default_duration']));
        
        // 設定を再読み込み
        $this->is_enabled = get_option('wpsr_google_calendar_enabled', false);
        
        // JSONファイルから再読み込み
        $json_file_path = get_option('wpsr_google_calendar_service_account_file', '');
        $this->service_account_file = !empty($json_file_path) && file_exists($json_file_path) ? file_get_contents($json_file_path) : '';
        
        $this->calendar_id = get_option('wpsr_google_calendar_id', '');
    }
    
    /**
     * 設定を取得
     */
    public function get_settings() {
        // JSONファイルから読み込み
        $json_file_path = get_option('wpsr_google_calendar_service_account_file', '');
        $json_content = !empty($json_file_path) && file_exists($json_file_path) ? file_get_contents($json_file_path) : '';
        
        return array(
            'enabled' => get_option('wpsr_google_calendar_enabled', false),
            'service_account' => $json_content,
            'calendar_id' => get_option('wpsr_google_calendar_id', ''),
            'default_duration' => get_option('wpsr_default_booking_duration', 1)
        );
    }
    
    /**
     * 接続テスト（軽量版）
     */
    public function test_connection() {
        $debug_info = array();
        
        if (!$this->is_enabled()) {
            $debug_info[] = 'Googleカレンダー連携が無効';
            return array('success' => false, 'message' => 'Googleカレンダー連携が有効になっていません。', 'debug' => $debug_info);
        }
        
        try {
            // ステップ1: クライアント初期化
            $debug_info[] = 'Step 1: クライアント初期化開始';
            $client_data = $this->get_google_client();
            if (!isset($client_data['access_token'])) {
                $debug_info = array_merge($debug_info, isset($client_data['debug']) ? $client_data['debug'] : array());
                $debug_info[] = 'Step 1: クライアント初期化失敗';
                return array('success' => false, 'message' => 'Google APIクライアントの初期化に失敗しました。詳細はログを確認してください。', 'debug' => $debug_info);
            }
            $debug_info = array_merge($debug_info, isset($client_data['debug']) ? $client_data['debug'] : array());
            $debug_info[] = 'Step 1: クライアント初期化成功';
            
            // ステップ2: アクセストークン取得
            $debug_info[] = 'Step 2: アクセストークン取得開始';
            $access_token = $this->get_access_token($client_data['access_token']);
            if (!$access_token) {
                $debug_info[] = 'Step 2: アクセストークン取得失敗';
                return array('success' => false, 'message' => 'アクセストークンの取得に失敗しました。詳細はログを確認してください。', 'debug' => $debug_info);
            }
            $debug_info[] = 'Step 2: アクセストークン取得成功';
            
            // ステップ3: カレンダー情報取得
            $debug_info[] = 'Step 3: カレンダー情報取得開始';
            $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($this->calendar_id);
            
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token
                ),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                $debug_info[] = 'Step 3: カレンダー情報取得失敗 - ' . $response->get_error_message();
                return array('success' => false, 'message' => '接続エラー: ' . $response->get_error_message(), 'debug' => $debug_info);
            }
            
            $body = wp_remote_retrieve_body($response);
            $calendar_data = json_decode($body, true);
            
            if (isset($calendar_data['summary'])) {
                $debug_info[] = 'Step 3: カレンダー情報取得成功';
                return array(
                    'success' => true, 
                    'message' => '接続成功: ' . $calendar_data['summary'],
                    'debug' => $debug_info
                );
            } else {
                $debug_info[] = 'Step 3: カレンダー情報取得失敗 - ' . $body;
                return array('success' => false, 'message' => 'カレンダー情報の取得に失敗しました。カレンダーIDを確認してください。', 'debug' => $debug_info);
            }
            
        } catch (Exception $e) {
            $debug_info[] = 'Exception: ' . $e->getMessage();
            return array('success' => false, 'message' => '接続エラー: ' . $e->getMessage(), 'debug' => $debug_info);
        }
    }
}
