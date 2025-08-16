<?php
/**
 * スケジュール管理画面
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// タイムゾーンを明示的に設定
date_default_timezone_set('Asia/Tokyo');

// 本日の日付を取得（日本時間）
$today = date('Y-m-d');

// WordPressの設定も試してみる
$wp_today = current_time('Y-m-d');

// デバッグ用：日付情報を表示
echo '<!-- Debug: PHP date() = ' . $today . ' -->';
echo '<!-- Debug: WordPress current_time() = ' . $wp_today . ' -->';
echo '<!-- Debug: PHP timezone = ' . date_default_timezone_get() . ' -->';
echo '<!-- Debug: WordPress timezone = ' . get_option('timezone_string') . ' -->';
echo '<!-- Debug: WordPress gmt_offset = ' . get_option('gmt_offset') . ' -->';
echo '<!-- Debug: Current timestamp = ' . time() . ' -->';
echo '<!-- Debug: WordPress timestamp = ' . current_time('timestamp') . ' -->';

// スケジュール一覧を取得（本日以降のスケジュールのみ、日付順）
$schedules = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}wpsr_schedules 
    WHERE DATE(date) >= %s
    ORDER BY date ASC
", $today));

// デバッグ用：SQLクエリと結果を表示
echo '<!-- Debug: SQL = SELECT * FROM ' . $wpdb->prefix . 'wpsr_schedules WHERE date >= ' . $today . ' -->';
echo '<!-- Debug: Found ' . count($schedules) . ' schedules -->';

// 表示設定の保存処理
if (isset($_POST['wpsr_save_display_settings'])) {
    if (wp_verify_nonce($_POST['wpsr_display_nonce'], 'wpsr_display_settings')) {
        // 基本設定項目を保存
        update_option('wpsr_personal_info_title', wp_kses_post($_POST['personal_info_title']));
        update_option('wpsr_booking_title', wp_kses_post($_POST['booking_title']));
        update_option('wpsr_booking_description', wp_kses_post($_POST['booking_description']));
        update_option('wpsr_submit_button_text', sanitize_text_field($_POST['submit_button_text']));
        
        // 確認画面設定項目を保存
        update_option('wpsr_confirm_page_url', esc_url_raw($_POST['confirm_page_url']));
        update_option('wpsr_confirm_title', sanitize_text_field($_POST['confirm_title']));
        update_option('wpsr_confirm_button_text', sanitize_text_field($_POST['confirm_button_text']));
        
        // 完了画面設定項目を保存
        update_option('wpsr_complete_page_url', esc_url_raw($_POST['complete_page_url']));
        update_option('wpsr_complete_title', sanitize_text_field($_POST['complete_title']));
        update_option('wpsr_complete_message', wp_kses_post($_POST['complete_message']));
        update_option('wpsr_next_action', wp_kses_post($_POST['next_action']));
        update_option('wpsr_error_title', sanitize_text_field($_POST['error_title']));
        update_option('wpsr_error_message', wp_kses_post($_POST['error_message']));
        
        // 詳細設定項目を保存
        update_option('wpsr_notice_text', wp_kses_post($_POST['notice_text']));
        update_option('wpsr_info_section_content', wp_kses_post($_POST['info_section_content']));
        
        echo '<div class="notice notice-success"><p>表示設定が保存されました。</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>セキュリティチェックに失敗しました。</p></div>';
    }
}

// 表示期間設定の保存処理
if (isset($_POST['wpsr_save_display_period'])) {
    if (wp_verify_nonce($_POST['wpsr_display_period_nonce'], 'wpsr_display_period')) {
        $display_days = intval($_POST['display_days']);
        
        // 値の検証
        if ($display_days >= 1 && $display_days <= 365) {
            update_option('wpsr_display_days', $display_days);
            echo '<div class="notice notice-success"><p>表示期間設定が保存されました。</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>入力値が正しくありません。1-365の範囲で入力してください。</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>セキュリティチェックに失敗しました。</p></div>';
    }
}

// 予約締切日設定の保存処理
if (isset($_POST['wpsr_save_deadline_settings'])) {
    if (wp_verify_nonce($_POST['wpsr_deadline_nonce'], 'wpsr_deadline_settings')) {
        $deadline_days = intval($_POST['booking_deadline_days']);
        $deadline_hours = intval($_POST['booking_deadline_hours']);
        
        // 値の検証
        if ($deadline_days >= 0 && $deadline_days <= 365 && $deadline_hours >= 0 && $deadline_hours <= 24) {
            update_option('wpsr_booking_deadline_days', $deadline_days);
            update_option('wpsr_booking_deadline_hours', $deadline_hours);
            echo '<div class="notice notice-success"><p>予約締切日設定が保存されました。</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>入力値が正しくありません。日数は0-365、時間は0-24の範囲で入力してください。</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>セキュリティチェックに失敗しました。</p></div>';
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">スケジュール管理</h1>
    <a href="#" class="page-title-action" id="wpsr-add-schedule">新規追加</a>
    
    <!-- タブナビゲーション -->
    <div class="wpsr-tabs">
        <button class="wpsr-tab-button active" data-tab="current">
            <span class="dashicons dashicons-calendar-alt"></span>
            スケジュール
        </button>
        <button class="wpsr-tab-button" data-tab="archive">
            <span class="dashicons dashicons-archive"></span>
            アーカイブ
        </button>
        <button class="wpsr-tab-button" data-tab="display">
            <span class="dashicons dashicons-admin-appearance"></span>
            表示設定
        </button>
        <button class="wpsr-tab-button" data-tab="settings">
            <span class="dashicons dashicons-admin-settings"></span>
            設定
        </button>
    </div>

    <div class="wpsr-admin-content">
        <!-- 今後のスケジュールタブ -->
        <div class="wpsr-tab-content active" id="current-schedules">
            <!-- カレンダー表示エリア -->
            <div class="wpsr-calendar-container">
                <div id="wpsr-calendar"></div>
            </div>
            
            <!-- スケジュールリスト -->
            <div class="wpsr-schedule-list">
                <h3>選択月のスケジュール</h3>
                <div id="wpsr-schedule-list-content">
                    <?php if (empty($schedules)): ?>
                        <div class="wpsr-no-schedules">
                            <p>まだスケジュールが登録されていません。</p>
                            <p>「新規追加」ボタンからスケジュールを登録してください。</p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>日付</th>
                                    <th>時間枠</th>
                                    <th>利用可能</th>
                                    <th>登録日</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo esc_html($schedule->id); ?></td>
                                        <td><?php echo esc_html($schedule->date); ?></td>
                                        <td>
                                            <?php 
                                            $time_slots = json_decode($schedule->time_slots_with_stock, true);
                                            if ($time_slots) {
                                                foreach ($time_slots as $slot) {
                                                    $stock_info = '';
                                                    $css_class = 'wpsr-time-slot-badge';
                                                    
                                                    if (isset($slot['max_stock']) && isset($slot['current_stock'])) {
                                                        if ($slot['current_stock'] <= 0) {
                                                            $stock_info = '（満席）';
                                                            $css_class .= ' wpsr-time-slot-full';
                                                        } else {
                                                            $stock_info = '（残り' . $slot['current_stock'] . '）';
                                                        }
                                                    }
                                                    echo '<span class="' . $css_class . '">' . esc_html($slot['time']) . $stock_info . '</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="wpsr-availability-<?php echo $schedule->is_available ? 'yes' : 'no'; ?>">
                                                <?php echo $schedule->is_available ? '利用可能' : '利用不可'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($schedule->created_at); ?></td>
                                        <td>
                                            <a href="#" class="button button-small wpsr-edit-schedule" 
                                               data-id="<?php echo esc_attr($schedule->id); ?>">
                                                編集
                                            </a>
                                            <a href="#" class="button button-small wpsr-delete-schedule" 
                                               data-id="<?php echo esc_attr($schedule->id); ?>">
                                                削除
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- アーカイブタブ -->
        <div class="wpsr-tab-content" id="archive-schedules">
            <?php
            // 過去のスケジュールを取得（日本時間）
            $past_schedules = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}wpsr_schedules 
                WHERE DATE(date) < %s
                ORDER BY date DESC
                LIMIT 50
            ", $today));
            
            if (empty($past_schedules)):
            ?>
                <div class="wpsr-no-schedules">
                    <p>過去のスケジュールはありません。</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>日付</th>
                            <th>時間枠</th>
                            <th>利用可能</th>
                            <th>登録日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_schedules as $schedule): ?>
                            <tr class="wpsr-past-schedule-row">
                                <td><?php echo esc_html($schedule->id); ?></td>
                                <td><?php echo esc_html($schedule->date); ?></td>
                                <td>
                                    <?php 
                                    $time_slots = json_decode($schedule->time_slots_with_stock, true);
                                    if ($time_slots) {
                                        foreach ($time_slots as $slot) {
                                            $stock_info = '';
                                            $css_class = 'wpsr-time-slot-badge wpsr-past-time-slot';
                                            
                                            if (isset($slot['max_stock']) && isset($slot['current_stock'])) {
                                                if ($slot['current_stock'] <= 0) {
                                                    $stock_info = '（満席）';
                                                    $css_class .= ' wpsr-time-slot-full';
                                                } else {
                                                    $stock_info = '（残り' . $slot['current_stock'] . '）';
                                                }
                                            }
                                            echo '<span class="' . $css_class . '">' . esc_html($slot['time']) . $stock_info . '</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="wpsr-availability-<?php echo $schedule->is_available ? 'yes' : 'no'; ?>">
                                        <?php echo $schedule->is_available ? '利用可能' : '利用不可'; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($schedule->created_at); ?></td>
                                <td>
                                    <a href="#" class="button button-small wpsr-edit-schedule" 
                                       data-id="<?php echo esc_attr($schedule->id); ?>">
                                        編集
                                    </a>
                                    <a href="#" class="button button-small wpsr-delete-schedule" 
                                       data-id="<?php echo esc_attr($schedule->id); ?>">
                                        削除
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 表示設定タブ -->
        <div class="wpsr-tab-content" id="display-settings">
            <h3>フロントエンド表示設定</h3>
            <p>フロントエンドの予約フォームの表示に関する設定を行います。</p>
            
            <form method="post" action="" id="wpsr-display-settings-form">
                <?php wp_nonce_field('wpsr_display_settings', 'wpsr_display_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="personal_info_title">個人情報入力セクションタイトル</label>
                        </th>
                        <td>
                            <input type="text" id="personal_info_title" name="personal_info_title" 
                                   value="<?php echo esc_attr(get_option('wpsr_personal_info_title', '個人情報入力')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                個人情報入力セクションのタイトルを設定します。<br>
                                HTMLタグも使用可能です（例：&lt;h3&gt;個人情報入力&lt;/h3&gt;）
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="booking_title">面談予約セクションタイトル</label>
                        </th>
                        <td>
                            <input type="text" id="booking_title" name="booking_title" 
                                   value="<?php echo esc_attr(get_option('wpsr_booking_title', '面談予約')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                面談予約セクションのタイトルを設定します。<br>
                                HTMLタグも使用可能です（例：&lt;h3&gt;面談予約&lt;/h3&gt;）
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="booking_description">面談予約セクション説明文</label>
                        </th>
                        <td>
                            <textarea id="booking_description" name="booking_description" rows="3" cols="50" class="large-text"><?php echo esc_textarea(get_option('wpsr_booking_description', '面談を行える日時を教えて下さい')); ?></textarea>
                            <p class="description">
                                面談予約セクションの説明文を設定します。<br>
                                HTMLタグも使用可能です。
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="submit_button_text">送信ボタンテキスト</label>
                        </th>
                        <td>
                            <input type="text" id="submit_button_text" name="submit_button_text" 
                                   value="<?php echo esc_attr(get_option('wpsr_submit_button_text', 'ご入力内容の確認')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                送信ボタンに表示するテキストを設定します。
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h3>確認画面設定</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="confirm_page_url">確認画面のURL</label>
                        </th>
                        <td>
                            <input type="url" id="confirm_page_url" name="confirm_page_url" 
                                   value="<?php echo esc_attr(get_option('wpsr_confirm_page_url', home_url('/booking/confirm/'))); ?>" 
                                   class="regular-text">
                            <p class="description">
                                確認画面の固定ページのURLを設定します。<br>
                                例：<?php echo home_url('/booking/confirm/'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="confirm_title">確認画面タイトル</label>
                        </th>
                        <td>
                            <input type="text" id="confirm_title" name="confirm_title" 
                                   value="<?php echo esc_attr(get_option('wpsr_confirm_title', '予約内容の確認')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                確認画面のタイトルを設定します。
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="confirm_button_text">確定ボタンテキスト</label>
                        </th>
                        <td>
                            <input type="text" id="confirm_button_text" name="confirm_button_text" 
                                   value="<?php echo esc_attr(get_option('wpsr_confirm_button_text', '予約を確定する')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                確認画面の確定ボタンに表示するテキストを設定します。
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h3>完了画面設定</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="complete_page_url">完了画面のURL</label>
                        </th>
                        <td>
                            <input type="url" id="complete_page_url" name="complete_page_url" 
                                   value="<?php echo esc_attr(get_option('wpsr_complete_page_url', home_url('/booking/complete/'))); ?>" 
                                   class="regular-text">
                            <p class="description">
                                完了画面の固定ページのURLを設定します。<br>
                                例：<?php echo home_url('/booking/complete/'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="complete_title">完了画面タイトル</label>
                        </th>
                        <td>
                            <input type="text" id="complete_title" name="complete_title" 
                                   value="<?php echo esc_attr(get_option('wpsr_complete_title', '予約が完了しました')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                完了画面のタイトルを設定します。
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="complete_message">完了メッセージ</label>
                        </th>
                        <td>
                            <textarea id="complete_message" name="complete_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('wpsr_complete_message', 'ご予約ありがとうございます。ご入力いただいたメールアドレスに確認メールをお送りしました。')); ?></textarea>
                            <p class="description">
                                完了画面に表示するメッセージを設定します。<br>
                                HTMLタグも使用可能です。
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="next_action">次のアクション案内</label>
                        </th>
                        <td>
                            <textarea id="next_action" name="next_action" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('wpsr_next_action', '')); ?></textarea>
                            <p class="description">
                                完了画面に表示する次のアクション案内を設定します。<br>
                                HTMLタグも使用可能です（例：&lt;a href="/contact/"&gt;お問い合わせ&lt;/a&gt;）
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="error_title">エラー画面タイトル</label>
                        </th>
                        <td>
                            <input type="text" id="error_title" name="error_title" 
                                   value="<?php echo esc_attr(get_option('wpsr_error_title', 'エラーが発生しました')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                エラー画面のタイトルを設定します。
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="error_message">エラーメッセージ</label>
                        </th>
                        <td>
                            <textarea id="error_message" name="error_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('wpsr_error_message', '予約の処理中にエラーが発生しました。もう一度お試しください。')); ?></textarea>
                            <p class="description">
                                エラー画面に表示するメッセージを設定します。<br>
                                HTMLタグも使用可能です。
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h3>詳細設定（HTMLエディタ）</h3>
                <p>デザインとレイアウトを完全にカスタマイズできます。HTMLとCSSを自由に使用してください。</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="notice_text">注意事項テキスト</label>
                        </th>
                        <td>
                            <textarea id="notice_text" name="notice_text" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('wpsr_notice_text', '※仮予約ではなく、選択したお時間で予約完了となりますので、確実にご参加いただける日程をご選択ください。')); ?></textarea>
                            <p class="description">
                                面談予約セクションの注意事項を設定します。<br>
                                デフォルトのスタイルは削除されているため、完全にカスタマイズ可能です。<br>
                                空白の場合は、このセクション全体が非表示になります。<br>
                                例：&lt;div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px;"&gt;&lt;span style="color: #ff0000; font-weight: bold;"&gt;※重要&lt;/span&gt;：内容&lt;/div&gt;
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="info_section_content">確認・補足情報セクション</label>
                        </th>
                        <td>
                            <textarea id="info_section_content" name="info_section_content" rows="8" cols="50" class="large-text"><?php echo esc_textarea(get_option('wpsr_info_section_content', '無料面談は入会のためのステップではなく、あなたの結婚の悩みを解消する場です。まずはお気軽にお問い合わせください。')); ?></textarea>
                            <p class="description">
                                予約ボタンの前に表示される確認・補足情報を設定します。<br>
                                デフォルトのスタイルは削除されているため、完全にカスタマイズ可能です。<br>
                                空白の場合は、このセクション全体が非表示になります。<br>
                                例：&lt;div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px;"&gt;&lt;p style="color: #155724; margin: 0;"&gt;&lt;strong&gt;重要&lt;/strong&gt;：内容&lt;/p&gt;&lt;/div&gt;
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="wpsr_save_display_settings" class="button-primary" value="表示設定を保存">
                </p>
            </form>
            
            <hr>
            
            <h3>フォーム表示設定例</h3>
            <div class="wpsr-setting-examples">
                <div class="wpsr-example">
                    <h4>基本設定の例</h4>
                    <p><strong>個人情報入力</strong>：お客様情報</p>
                    <p><strong>面談予約</strong>：ご希望日時</p>
                    <p><strong>送信ボタン</strong>：予約内容を確認する</p>
                    <p class="description">シンプルなテキストで設定できます。</p>
                </div>
                
                <div class="wpsr-example">
                    <h4>HTMLエディタの設定例</h4>
                    <p><strong>注意事項</strong>：&lt;span style="color: #ff0000; font-weight: bold;"&gt;※重要&lt;/span&gt;：ご都合の良い日時をお選びください</p>
                    <p><strong>補足情報</strong>：&lt;div style="background: #f0f8ff; padding: 15px; border-radius: 5px;"&gt;&lt;p&gt;無料面談は&lt;strong&gt;入会のためのステップ&lt;/strong&gt;ではなく...&lt;/p&gt;&lt;/div&gt;</p>
                    <p class="description">HTMLタグとCSSスタイルを使用して完全にカスタマイズ可能です。</p>
                </div>
            </div>
        </div>
        
        <!-- 設定タブ -->
        <div class="wpsr-tab-content" id="schedule-settings">
            <h3>表示期間設定</h3>
            <p>フロントエンドで表示する予約可能な期間を設定できます。</p>
            
            <form method="post" action="" id="wpsr-display-period-form">
                <?php wp_nonce_field('wpsr_display_period', 'wpsr_display_period_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="display_days">表示期間</label>
                        </th>
                        <td>
                            <input type="number" id="display_days" name="display_days" 
                                   value="<?php echo esc_attr(get_option('wpsr_display_days', 7)); ?>" 
                                   min="1" max="365" class="small-text">
                            <span>日先まで</span>
                            <p class="description">
                                フロントエンドで表示する予約可能な日数です。<br>
                                例：7と入力すると、今日から7日後まで予約可能な日が表示されます。
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="wpsr_save_display_period" class="button-primary" value="表示期間設定を保存">
                </p>
            </form>
            
            <hr>
            
            <h3>予約締切日設定</h3>
            <p>予約を受け付ける期限を設定できます。設定した期限を過ぎた日時は予約できなくなります。</p>
            
            <form method="post" action="" id="wpsr-deadline-settings-form">
                <?php wp_nonce_field('wpsr_deadline_settings', 'wpsr_deadline_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="booking_deadline_days">予約締切日（日数）</label>
                        </th>
                        <td>
                            <input type="number" id="booking_deadline_days" name="booking_deadline_days" 
                                   value="<?php echo esc_attr(get_option('wpsr_booking_deadline_days', 0)); ?>" 
                                   min="0" max="365" class="small-text">
                            <span>日前まで</span>
                            <p class="description">
                                例：1と入力すると、当日の予約は受け付けません。<br>
                                0と入力すると、当日の予約も受け付けます。
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="booking_deadline_hours">予約締切時間</label>
                        </th>
                        <td>
                            <input type="number" id="booking_deadline_hours" name="booking_deadline_hours" 
                                   value="<?php echo esc_attr(get_option('wpsr_booking_deadline_hours', 0)); ?>" 
                                   min="0" max="24" class="small-text">
                            <span>時間前まで</span>
                            <p class="description">
                                例：2と入力すると、予約時間の2時間前まで予約を受け付けます。<br>
                                0と入力すると、予約時間直前まで予約を受け付けます。
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="wpsr_save_deadline_settings" class="button-primary" value="予約締切日設定を保存">
                </p>
            </form>
            
            <hr>
            
            <h3>設定例</h3>
            <div class="wpsr-setting-examples">
                <div class="wpsr-example">
                    <h4>表示期間の設定例</h4>
                    <p><strong>1週間先まで表示</strong>：7日先まで</p>
                    <p><strong>1ヶ月先まで表示</strong>：30日先まで</p>
                    <p><strong>3ヶ月先まで表示</strong>：90日先まで</p>
                    <p class="description">今日から設定した日数後まで予約可能な日が表示されます。</p>
                </div>
                
                <div class="wpsr-example">
                    <h4>予約締切日の設定例</h4>
                    <p><strong>当日予約不可</strong>：1日前まで</p>
                    <p><strong>時間指定</strong>：2時間前まで</p>
                    <p><strong>直前予約可能</strong>：0時間前まで</p>
                    <p class="description">予約を受け付ける期限を設定できます。</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- スケジュール追加・編集モーダル -->
<div id="wpsr-schedule-modal" class="wpsr-modal" style="display: none;">
    <div class="wpsr-modal-content">
        <div class="wpsr-modal-header">
            <h2 id="wpsr-modal-title">スケジュール追加</h2>
            <span class="wpsr-modal-close">&times;</span>
        </div>
        <div class="wpsr-modal-body">
            <form id="wpsr-schedule-form">
                <input type="hidden" id="wpsr-schedule-id" name="schedule_id" value="">
                
                <div class="wpsr-form-group">
                    <label for="wpsr-schedule-date">日付 <span class="wpsr-required">*</span></label>
                    <input type="date" id="wpsr-schedule-date" name="date" required>
                </div>
                
                <div class="wpsr-form-group">
                    <label>時間枠 <span class="wpsr-required">*</span></label>
                    <div id="wpsr-time-slots-container">
                        <div class="wpsr-time-slot-input">
                            <input type="time" name="time_slots[]" required>
                            <label style="margin-left: 10px; font-size: 12px; color: #666;">予約可能数:</label>
                            <input type="number" name="max_stock[]" min="0" max="10" value="1" placeholder="在庫数" style="width: 80px; margin-left: 5px;">
                            <button type="button" class="button button-small wpsr-remove-time-slot">削除</button>
                        </div>
                    </div>
                    <button type="button" class="button button-secondary" id="wpsr-add-time-slot">時間枠を追加</button>
                </div>
                
                <div class="wpsr-form-group">
                    <label>
                        <input type="checkbox" id="wpsr-schedule-available" name="is_available" value="1" checked>
                        利用可能
                    </label>
                </div>
                
                <div class="wpsr-form-actions">
                    <button type="submit" class="button button-primary">保存</button>
                    <button type="button" class="button wpsr-modal-cancel">キャンセル</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.wpsr-admin-content {
    margin-top: 20px;
}

.wpsr-no-schedules {
    text-align: center;
    padding: 40px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wpsr-time-slot-badge {
    display: inline-block;
    background: #3498db;
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin: 2px;
}

.wpsr-time-slot-badge.wpsr-time-slot-full {
    background: #95a5a6;
    color: #fff;
    cursor: not-allowed;
}

.wpsr-availability-yes {
    color: #27ae60;
    font-weight: bold;
}

.wpsr-availability-no {
    color: #e74c3c;
    font-weight: bold;
}

/* タブスタイル */
.wpsr-tabs {
    margin: 20px 0;
    border-bottom: 1px solid #ddd;
}

.wpsr-tab-button {
    background: none;
    border: none;
    padding: 10px 20px;
    margin-right: 5px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    font-size: 14px;
    color: #666;
}

.wpsr-tab-button:hover {
    color: #0073aa;
}

.wpsr-tab-button.active {
    color: #0073aa;
    border-bottom-color: #0073aa;
    background-color: #f9f9f9;
}

.wpsr-tab-button .dashicons {
    margin-right: 5px;
}

.wpsr-tab-content {
    display: none;
    padding: 20px 0;
}

.wpsr-tab-content.active {
    display: block;
}

/* カレンダーコンテナ */
.wpsr-calendar-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

/* スケジュールリスト */
.wpsr-schedule-list {
    margin-top: 20px;
}

.wpsr-schedule-list h3 {
    margin-bottom: 15px;
    color: #333;
}

/* 過去のスケジュール */
.wpsr-past-schedule-row {
    opacity: 0.7;
    background-color: #f9f9f9;
}

.wpsr-past-schedule-row:hover {
    opacity: 1;
    background-color: #f0f0f0;
}

.wpsr-past-time-slot {
    background: #95a5a6 !important;
    opacity: 0.8;
}

/* FullCalendar.js カスタマイズ */
.fc-day-sat {
    background-color: #e3f2fd !important;
}

.fc-day-sun {
    background-color: #ffebee !important;
}

.fc-day-holiday {
    background-color: #fff3e0 !important;
}

/* カレンダーのサイズ調整 */
.fc .fc-daygrid-day {
    min-height: 35px !important;
}

.fc .fc-daygrid-day-frame {
    min-height: 35px !important;
}

.fc .fc-daygrid-day-events {
    min-height: 20px !important;
}

.fc .fc-daygrid-day-number {
    font-size: 13px !important;
    padding: 4px !important;
}

.fc .fc-toolbar {
    margin-bottom: 10px !important;
}

.fc .fc-toolbar-title {
    font-size: 18px !important;
}

.fc .fc-button {
    padding: 6px 12px !important;
    font-size: 13px !important;
}

.fc-day-available {
    position: relative !important;
}

.fc-day-available::after {
    content: '' !important;
    position: absolute !important;
    bottom: 5px !important;
    right: 5px !important;
    width: 8px !important;
    height: 8px !important;
    background-color: #4caf50 !important;
    border-radius: 50% !important;
    z-index: 10 !important;
}

.fc-day-unavailable {
    position: relative !important;
}

.fc-day-unavailable::after {
    content: '' !important;
    position: absolute !important;
    bottom: 5px !important;
    right: 5px !important;
    width: 8px !important;
    height: 8px !important;
    background-color: #f44336 !important;
    border-radius: 50% !important;
    z-index: 10 !important;
}

/* 設定例のスタイル */
.wpsr-setting-examples {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.wpsr-example {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.wpsr-example h4 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.wpsr-example p {
    margin: 10px 0;
}

.wpsr-example .description {
    color: #666;
    font-style: italic;
    font-size: 0.9em;
}

/* モーダルスタイル */
.wpsr-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wpsr-modal-content {
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90%;
    overflow-y: auto;
}

.wpsr-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wpsr-modal-header h2 {
    margin: 0;
}

.wpsr-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.wpsr-modal-close:hover {
    color: #000;
}

.wpsr-modal-body {
    padding: 20px;
}

.wpsr-form-group {
    margin-bottom: 20px;
}

.wpsr-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.wpsr-form-group input[type="text"],
.wpsr-form-group input[type="date"],
.wpsr-form-group input[type="time"],
.wpsr-form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wpsr-required {
    color: #e74c3c;
}

.wpsr-time-slot-input {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.wpsr-time-slot-input input {
    flex: 1;
    margin-right: 10px;
}

.wpsr-form-actions {
    margin-top: 20px;
    text-align: right;
}

.wpsr-form-actions .button {
    margin-left: 10px;
}
</style>

<!-- 管理画面用のJavaScriptは wpsr-admin-scripts.js で処理されます -->
