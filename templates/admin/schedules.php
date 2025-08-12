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

// スケジュール一覧を取得（本日以降のスケジュールのみ、日付順）
$schedules = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}wpsr_schedules 
    WHERE DATE(date) >= %s
    ORDER BY date ASC
", $today));

// デバッグ用：SQLクエリと結果を表示
echo '<!-- Debug: SQL = SELECT * FROM ' . $wpdb->prefix . 'wpsr_schedules WHERE date >= ' . $today . ' -->';
echo '<!-- Debug: Found ' . count($schedules) . ' schedules -->';

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
                                            $time_slots = json_decode($schedule->time_slots, true);
                                            if ($time_slots) {
                                                foreach ($time_slots as $slot) {
                                                    echo '<span class="wpsr-time-slot-badge">' . esc_html($slot['time']) . '</span>';
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
                                    $time_slots = json_decode($schedule->time_slots, true);
                                    if ($time_slots) {
                                        foreach ($time_slots as $slot) {
                                            echo '<span class="wpsr-time-slot-badge wpsr-past-time-slot">' . esc_html($slot['time']) . '</span>';
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
        
        <!-- 設定タブ -->
        <div class="wpsr-tab-content" id="schedule-settings">
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
                    <input type="submit" name="wpsr_save_deadline_settings" class="button-primary" value="設定を保存">
                </p>
            </form>
            
            <hr>
            
            <h3>設定例</h3>
            <div class="wpsr-setting-examples">
                <div class="wpsr-example">
                    <h4>当日予約不可の場合</h4>
                    <p>予約締切日：<strong>1日前まで</strong><br>
                    予約締切時間：<strong>0時間前まで</strong></p>
                    <p class="description">前日までに予約を完了する必要があります。</p>
                </div>
                
                <div class="wpsr-example">
                    <h4>時間を指定する場合</h4>
                    <p>予約締切日：<strong>0日前まで</strong><br>
                    予約締切時間：<strong>2時間前まで</strong></p>
                    <p class="description">当日でも予約時間の2時間前まで予約できます。</p>
                </div>
                
                <div class="wpsr-example">
                    <h4>直前予約も可能の場合</h4>
                    <p>予約締切日：<strong>0日前まで</strong><br>
                    予約締切時間：<strong>0時間前まで</strong></p>
                    <p class="description">予約時間直前まで予約を受け付けます。</p>
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
    position: relative;
}

.fc-day-available::after {
    content: '';
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 8px;
    height: 8px;
    background-color: #4caf50;
    border-radius: 50%;
}

.fc-day-unavailable::after {
    content: '';
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 8px;
    height: 8px;
    background-color: #f44336;
    border-radius: 50%;
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
