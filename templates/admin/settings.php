<?php
/**
 * 設定画面
 */

if (!defined('ABSPATH')) {
    exit;
}

// 設定の保存処理
if (isset($_POST['wpsr_save_settings'])) {
    if (wp_verify_nonce($_POST['wpsr_settings_nonce'], 'wpsr_settings')) {
        // 顧客向けメール設定
        update_option('wpsr_email_subject', sanitize_text_field($_POST['email_subject']));
        update_option('wpsr_email_body', wp_kses_post($_POST['email_body']));
        
        // 管理者向けメール設定
        update_option('wpsr_admin_email_subject', sanitize_text_field($_POST['admin_email_subject']));
        update_option('wpsr_admin_email_body', wp_kses_post($_POST['admin_email_body']));
        
        // メールアドレス設定
        update_option('wpsr_admin_email', sanitize_email($_POST['admin_email']));
        update_option('wpsr_from_email', sanitize_email($_POST['from_email']));
        update_option('wpsr_from_name', sanitize_text_field($_POST['from_name']));
        
        echo '<div class="notice notice-success"><p>設定が保存されました。</p></div>';
    }
}

// データベース更新処理
if (isset($_POST['wpsr_update_database'])) {
    if (wp_verify_nonce($_POST['wpsr_db_update_nonce'], 'wpsr_db_update')) {
        // プラグインのメインクラスを取得してテーブル更新
        if (class_exists('WP_Simple_Reservation')) {
            $plugin = WP_Simple_Reservation::get_instance();
            $plugin->update_tables();
            echo '<div class="notice notice-success"><p>データベーステーブルが更新されました。</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>プラグインの初期化に失敗しました。</p></div>';
        }
    }
}

// 現在の設定を取得
$email_subject = get_option('wpsr_email_subject', '予約確認メール');
$email_body = get_option('wpsr_email_body', 'ご予約ありがとうございます。');
$admin_email_subject = get_option('wpsr_admin_email_subject', '新しい予約がありました');
$admin_email_body = get_option('wpsr_admin_email_body', '新しい予約が入りました。');
$admin_email = get_option('wpsr_admin_email', get_option('admin_email'));
$from_email = get_option('wpsr_from_email', get_option('admin_email'));
$from_name = get_option('wpsr_from_name', get_bloginfo('name'));
?>

<div class="wrap">
    <h1>設定</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wpsr_settings', 'wpsr_settings_nonce'); ?>
        
        <h2>メール設定</h2>
        
        <h3>送信元設定</h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="from_name">送信者名</label>
                </th>
                <td>
                    <input type="text" id="from_name" name="from_name" 
                           value="<?php echo esc_attr($from_name); ?>" class="regular-text">
                    <p class="description">メールの送信者名を設定します。</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="from_email">送信元メールアドレス</label>
                </th>
                <td>
                    <input type="email" id="from_email" name="from_email" 
                           value="<?php echo esc_attr($from_email); ?>" class="regular-text">
                    <p class="description">メールの送信元アドレスを設定します。</p>
                </td>
            </tr>
        </table>
        
        <h3>顧客向けメール設定</h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="email_subject">メール件名</label>
                </th>
                <td>
                    <input type="text" id="email_subject" name="email_subject" 
                           value="<?php echo esc_attr($email_subject); ?>" class="regular-text">
                    <p class="description">予約確認メールの件名を設定します。</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="email_body">メール本文</label>
                </th>
                <td>
                    <textarea id="email_body" name="email_body" rows="10" cols="50" class="large-text"><?php echo esc_textarea($email_body); ?></textarea>
                    <p class="description">
                        予約確認メールの本文を設定します。<br>
                        以下のプレースホルダーが使用できます：<br>
                        <code>{name}</code> - 予約者名<br>
                        <code>{date}</code> - 予約日<br>
                        <code>{time}</code> - 予約時間<br>
                        <code>{phone}</code> - 電話番号<br>
                        <code>{message}</code> - メッセージ
                    </p>
                </td>
            </tr>
        </table>
        
        <h3>管理者向けメール設定</h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="admin_email">管理者メールアドレス</label>
                </th>
                <td>
                    <input type="email" id="admin_email" name="admin_email" 
                           value="<?php echo esc_attr($admin_email); ?>" class="regular-text">
                    <p class="description">新しい予約があった際に通知を受け取るメールアドレスを設定します。</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="admin_email_subject">メール件名</label>
                </th>
                <td>
                    <input type="text" id="admin_email_subject" name="admin_email_subject" 
                           value="<?php echo esc_attr($admin_email_subject); ?>" class="regular-text">
                    <p class="description">管理者向け通知メールの件名を設定します。</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="admin_email_body">メール本文</label>
                </th>
                <td>
                    <textarea id="admin_email_body" name="admin_email_body" rows="10" cols="50" class="large-text"><?php echo esc_textarea($admin_email_body); ?></textarea>
                    <p class="description">
                        管理者向け通知メールの本文を設定します。<br>
                        以下のプレースホルダーが使用できます：<br>
                        <code>{name}</code> - 予約者名<br>
                        <code>{date}</code> - 予約日<br>
                        <code>{time}</code> - 予約時間<br>
                        <code>{phone}</code> - 電話番号<br>
                        <code>{email}</code> - メールアドレス<br>
                        <code>{message}</code> - メッセージ
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="wpsr_save_settings" class="button-primary" value="設定を保存">
        </p>
    </form>
    
    <hr>
    
    <h2>データベース管理</h2>
    <p>データベーステーブルの更新が必要な場合があります。</p>
    
    <form method="post" action="">
        <?php wp_nonce_field('wpsr_db_update', 'wpsr_db_update_nonce'); ?>
        <p class="submit">
            <input type="submit" name="wpsr_update_database" class="button-secondary" value="データベーステーブルを更新">
        </p>
    </form>
    
    <hr>
    
    <h2>ショートコード</h2>
    <p>以下のショートコードを使用して予約フォームを表示できます：</p>
    
    <div class="wpsr-shortcode-info">
        <code>[wp_simple_reservation_form]</code>
        <p class="description">基本的な予約フォームを表示します。</p>
    </div>
    
    <div class="wpsr-shortcode-info">
        <code>[wp_simple_reservation_form title="カスタムタイトル"]</code>
        <p class="description">カスタムタイトルで予約フォームを表示します。</p>
    </div>
    
    <div class="wpsr-shortcode-info">
        <code>[wp_simple_reservation_confirm]</code>
        <p class="description">予約確認画面を表示します。</p>
    </div>
    
    <div class="wpsr-shortcode-info">
        <code>[wp_simple_reservation_complete]</code>
        <p class="description">予約完了画面を表示します。</p>
    </div>
    
    <hr>
    
    <h2>データベース情報</h2>
    <p>プラグインで使用しているデータベーステーブル：</p>
    
    <ul>
        <li><code><?php echo $wpdb->prefix; ?>wpsr_reservations</code> - 予約データ</li>
        <li><code><?php echo $wpdb->prefix; ?>wpsr_schedules</code> - スケジュールデータ</li>
    </ul>
    
    <p class="description">
        プラグインを削除する際は、これらのテーブルも削除されます。
    </p>
</div>

<style>
.wpsr-shortcode-info {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin: 10px 0;
}

.wpsr-shortcode-info code {
    color: #d63638;
    font-weight: bold;
}

h3 {
    margin-top: 30px;
    margin-bottom: 15px;
    padding-bottom: 5px;
    border-bottom: 1px solid #ddd;
}
</style>
