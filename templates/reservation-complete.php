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

<div class="wpsr-reservation-complete" id="wpsr-reservation-complete">
    <?php if ($status === 'success'): ?>
        <!-- 成功時の表示 -->
        <div class="wpsr-success-content">
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
            
            <?php if ($reservation_data): ?>
            <div class="wpsr-reservation-details">
                <h3>予約詳細</h3>
                <div class="wpsr-detail-row">
                    <span class="wpsr-detail-label">予約番号：</span>
                    <span class="wpsr-detail-value"><?php echo esc_html($reservation_data['id']); ?></span>
                </div>
                <div class="wpsr-detail-row">
                    <span class="wpsr-detail-label">予約日時：</span>
                    <span class="wpsr-detail-value">
                        <?php 
                        $date_obj = new DateTime($reservation_data['schedule_date']);
                        $weekday = array('日', '月', '火', '水', '木', '金', '土');
                        echo esc_html($date_obj->format('Y年n月j日') . '（' . $weekday[$date_obj->format('w')] . '） ' . $reservation_data['schedule_time']);
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            $next_action = get_option('wpsr_next_action', '');
            if (!empty(trim($next_action))): 
            ?>
            <div class="wpsr-next-action">
                <?php echo wp_kses_post($next_action); ?>
            </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <!-- エラー時の表示 -->
        <div class="wpsr-error-content">
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
            
            <div class="wpsr-error-actions">
                <a href="<?php echo esc_url(home_url('/booking/')); ?>" class="wpsr-back-link">
                    予約フォームに戻る
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.wpsr-reservation-complete {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    text-align: center;
}

.wpsr-success-content,
.wpsr-error-content {
    background: #fff;
    border-radius: 8px;
    padding: 40px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.wpsr-success-icon,
.wpsr-error-icon {
    font-size: 60px;
    margin-bottom: 20px;
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
}

.wpsr-complete-message,
.wpsr-error-message {
    margin-bottom: 30px;
    color: #666;
    line-height: 1.6;
    font-size: 16px;
}

.wpsr-reservation-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: left;
}

.wpsr-reservation-details h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 18px;
}

.wpsr-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.wpsr-detail-row:last-child {
    border-bottom: none;
}

.wpsr-detail-label {
    font-weight: bold;
    color: #333;
}

.wpsr-detail-value {
    color: #666;
}

.wpsr-next-action {
    margin-top: 30px;
    padding: 20px;
    background: #e7f3ff;
    border-radius: 8px;
    border-left: 4px solid #007cba;
}

.wpsr-error-actions {
    margin-top: 30px;
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

@media (max-width: 768px) {
    .wpsr-reservation-complete {
        padding: 15px;
    }
    
    .wpsr-success-content,
    .wpsr-error-content {
        padding: 30px 15px;
    }
    
    .wpsr-detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>
