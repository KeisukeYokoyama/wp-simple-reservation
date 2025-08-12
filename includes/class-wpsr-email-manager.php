<?php
/**
 * WP Simple Reservation Email Manager
 * メール送信管理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSR_Email_Manager {
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        // メール送信時のヘッダーをカスタマイズ
        add_filter('wp_mail_from', array($this, 'custom_from_email'));
        add_filter('wp_mail_from_name', array($this, 'custom_from_name'));
        
        // SMTP設定を追加
        add_action('phpmailer_init', array($this, 'setup_smtp'));
    }
    
    /**
     * SMTP設定
     */
    public function setup_smtp($phpmailer) {
        // ローカルテスト用設定（SMTP無効）
        // 本番環境では以下のコメントを外してGmail SMTPを使用
        /*
        $phpmailer->isSMTP();
        $phpmailer->Host = 'smtp.gmail.com';
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = 587;
        $phpmailer->SMTPSecure = 'tls';
        $phpmailer->Username = 'your-email@gmail.com';
        $phpmailer->Password = 'your-app-password';
        */
        
        // デバッグ設定
        $phpmailer->SMTPDebug = 0;
    }
    
    /**
     * 送信元メールアドレスをカスタマイズ
     */
    public function custom_from_email($email) {
        $from_email = get_option('wpsr_from_email', get_option('admin_email'));
        return $from_email;
    }
    
    /**
     * 送信者名をカスタマイズ
     */
    public function custom_from_name($name) {
        $from_name = get_option('wpsr_from_name', get_bloginfo('name'));
        return $from_name;
    }
    
    /**
     * 顧客向け予約確認メールを送信
     */
    public function send_customer_confirmation($reservation_data) {
        $to = $reservation_data['email'];
        $subject = $this->replace_placeholders(
            get_option('wpsr_email_subject', '予約確認メール'),
            $reservation_data
        );
        $message = $this->replace_placeholders(
            get_option('wpsr_email_body', 'ご予約ありがとうございます。'),
            $reservation_data
        );
        
        // HTMLテンプレートを使用
        $message = $this->get_html_template($message);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            error_log('WPSR: 顧客向けメール送信成功 - ' . $to);
        } else {
            error_log('WPSR: 顧客向けメール送信失敗 - ' . $to);
        }
        
        return $sent;
    }
    
    /**
     * 管理者向け予約通知メールを送信
     */
    public function send_admin_notification($reservation_data) {
        $to = get_option('wpsr_admin_email', get_option('admin_email'));
        $subject = $this->replace_placeholders(
            get_option('wpsr_admin_email_subject', '新しい予約がありました'),
            $reservation_data
        );
        $message = $this->replace_placeholders(
            get_option('wpsr_admin_email_body', '新しい予約が入りました。'),
            $reservation_data
        );
        
        // HTMLテンプレートを使用
        $message = $this->get_html_template($message);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            error_log('WPSR: 管理者向けメール送信成功 - ' . $to);
        } else {
            error_log('WPSR: 管理者向けメール送信失敗 - ' . $to);
        }
        
        return $sent;
    }
    
    /**
     * プレースホルダーを置換
     */
    private function replace_placeholders($text, $data) {
        $placeholders = array(
            '{name}' => isset($data['name']) ? $data['name'] : '',
            '{date}' => isset($data['date']) ? $data['date'] : '',
            '{time}' => isset($data['time']) ? $data['time'] : '',
            '{phone}' => isset($data['phone']) ? $data['phone'] : '',
            '{email}' => isset($data['email']) ? $data['email'] : '',
            '{message}' => isset($data['message']) ? $data['message'] : ''
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
    
    /**
     * HTMLメールテンプレートを取得
     */
    private function get_html_template($content) {
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>予約確認</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background-color: #f9f9f9; padding: 20px; border-radius: 5px;">
                ' . nl2br($content) . '
            </div>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                <p>このメールは自動送信されています。返信はできません。</p>
            </div>
        </body>
        </html>';
        
        return $template;
    }
}
