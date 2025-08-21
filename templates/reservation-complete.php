<?php
/**
 * 予約完了画面テンプレート
 */

if (!defined('ABSPATH')) {
    exit;
}

// URLパラメーターから状態を取得
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$reservation_id = isset($_GET['reservation_id']) ? intval($_GET['reservation_id']) : 0;

// 予約データを取得（成功の場合）
$reservation_data = null;
if ($status === 'success' && $reservation_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpsr_reservations';
    $reservation_data = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $reservation_id),
        ARRAY_A
    );
}
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
    <div class="wpsr-reservation-form" id="wpsr-reservation-complete">
        <?php if ($status === 'success'): ?>
            <!-- 成功時の表示 -->
            <div class="wpsr-section">
                <!-- <h3 class="wpsr-section-title">予約完了</h3> -->
                
                <div class="wpsr-form-group">
                    <div class="wpsr-success-icon">✓</div>
                    <h2 class="wpsr-complete-title"><?php echo esc_html(get_option('wpsr_complete_title', '予約が完了しました')); ?></h2>
                    
                    <?php 
                    $complete_message = get_option('wpsr_complete_message', 'ご予約ありがとうございます。ご入力いただいたメールアドレスに確認メールをお送りしました。');
                    if (!empty(trim($complete_message))): 
                    ?>
                    <div class="wpsr-complete-message">
                        <?php echo wp_kses_post($complete_message); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($reservation_data): ?>
                <div class="wpsr-form-group">
                    <!-- <label class="wpsr-label">予約詳細</label> -->
                    <div class="wpsr-form-data">
                        <div class="wpsr-field-data">
                            <span class="wpsr-field-label">予約番号</span>
                            <span class="wpsr-field-value"><?php echo esc_html($reservation_data['id']); ?></span>
                        </div>
                        <div class="wpsr-field-data">
                            <span class="wpsr-field-label">予約日時</span>
                            <span class="wpsr-field-value">
                                <?php 
                                $date_obj = new DateTime($reservation_data['schedule_date']);
                                $weekday = array('日', '月', '火', '水', '木', '金', '土');
                                echo esc_html($date_obj->format('Y年n月j日') . '（' . $weekday[$date_obj->format('w')] . '） ' . $reservation_data['schedule_time']);
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php 
                $next_action = get_option('wpsr_next_action', '');
                if (!empty(trim($next_action))): 
                ?>
                <div class="wpsr-form-group">
                    <div class="wpsr-next-action">
                        <?php echo wp_kses_post($next_action); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- エラー時の表示 -->
            <div class="wpsr-section">
                <h3 class="wpsr-section-title"><?php echo esc_html(get_option('wpsr_error_title', 'エラーが発生しました')); ?></h3>
                
                <div class="wpsr-form-group">
                    <div class="wpsr-error-icon">✗</div>
                    <h2 class="wpsr-complete-title"><?php echo esc_html(get_option('wpsr_error_title', 'エラーが発生しました')); ?></h2>
                    
                    <?php 
                    $error_message = get_option('wpsr_error_message', '予約の処理中にエラーが発生しました。もう一度お試しください。');
                    if (!empty(trim($error_message))): 
                    ?>
                    <div class="wpsr-error-message">
                        <?php echo wp_kses_post($error_message); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="wpsr-form-group">
                    <div class="wpsr-error-actions">
                        <a href="<?php echo esc_url(home_url('/booking/')); ?>" class="wpsr-back-link">
                            予約フォームに戻る
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* 完了画面専用スタイル - 確認画面と同じデザイン */
.wpsr-success-icon,
.wpsr-error-icon {
    font-size: 60px;
    margin-bottom: 20px;
    text-align: center;
}

.wpsr-success-icon {
    color: #28a745;
}

.wpsr-error-icon {
    color: #dc3545;
}

.wpsr-complete-title {
    margin-bottom: 20px;
    color: #333;
    font-size: 24px;
    text-align: center;
}

.wpsr-complete-message,
.wpsr-error-message {
    margin-bottom: 30px;
    color: #666;
    line-height: 1.6;
    font-size: 16px;
    text-align: center;
}

.wpsr-next-action {
    margin-top: 30px;
    padding: 20px;
    background: #e7f3ff;
    border-radius: 8px;
    border-left: 4px solid #007cba;
    text-align: center;
}

.wpsr-error-actions {
    margin-top: 30px;
    text-align: center;
}

.wpsr-back-link {
    display: inline-block;
    padding: 12px 30px;
    background: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.3s ease;
}

.wpsr-back-link:hover {
    background: #005a87;
    color: white;
}

/* 確認画面と同じスタイルを継承 */
.wpsr-form-wrapper {
    width: 100%;
    max-width: 100%;
    padding: 24px;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    box-sizing: border-box;
}

.wpsr-reservation-form {
    width: 450px;
    max-width: calc(100vw - 48px);
    min-width: 450px;
    background: transparent;
    border-radius: 0;
    box-shadow: none;
    padding: 0;
    margin: 0;
}

.wpsr-section {
    background: transparent;
    border-radius: 0;
    box-shadow: none;
    padding: 0;
    margin-bottom: 30px;
}

.wpsr-section-title {
    font-size: 22px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 20px;
    text-align: center;
    border-bottom: none;
}

.wpsr-form-group {
    margin-bottom: 20px;
    margin-left: 8px;
    margin-right: 8px;
}

.wpsr-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.wpsr-form-data {
    margin-top: 15px;
    padding: 20px;
    border: 1px solid #e1e8ed;
    border-radius: 8px;
    background: #fff;
}

.wpsr-field-data {
    display: flex;
    margin-bottom: 15px;
    padding: 8px 0;
    align-items: center;
    border-bottom: 1px solid #f1f3f4;
}

.wpsr-field-data:last-child {
    margin-bottom: 0;
    border-bottom: none;
}

.wpsr-field-label {
    width: 120px;
    color: #6c757d;
    font-size: 13px;
}

.wpsr-field-value {
    flex: 1;
    color: #333;
    font-size: 16px;
}

@media (max-width: 768px) {
    .wpsr-form-wrapper {
        padding: 15px;
    }
    
    .wpsr-reservation-form {
        min-width: auto;
    }
}
</style>
