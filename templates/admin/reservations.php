<?php
/**
 * 予約一覧管理画面
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// 予約一覧を取得
$reservations = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}wpsr_reservations 
    ORDER BY created_at DESC
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">予約一覧</h1>
    
    <div class="wpsr-admin-content">
        <?php if (empty($reservations)): ?>
            <div class="wpsr-no-reservations">
                <p>まだ予約がありません。</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名前</th>
                        <th>メール</th>
                        <th>電話</th>
                        <th>予約日時</th>
                        <th>ステータス</th>
                        <th>登録日</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo esc_html($reservation->id); ?></td>
                            <td><?php echo esc_html($reservation->name); ?></td>
                            <td><?php echo esc_html($reservation->email); ?></td>
                            <td><?php echo esc_html($reservation->phone); ?></td>
                            <td>
                                <?php 
                                echo esc_html($reservation->schedule_date) . ' ' . 
                                     esc_html($reservation->schedule_time); 
                                ?>
                            </td>
                            <td>
                                <span class="wpsr-status-<?php echo esc_attr($reservation->status); ?>">
                                    <?php echo esc_html(get_status_label($reservation->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($reservation->created_at); ?></td>
                            <td>
                                <a href="#" class="button button-small wpsr-edit-reservation" 
                                   data-id="<?php echo esc_attr($reservation->id); ?>">
                                    編集
                                </a>
                                <a href="#" class="button button-small wpsr-cancel-reservation" 
                                   data-id="<?php echo esc_attr($reservation->id); ?>">
                                    キャンセル
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- 予約編集モーダル -->
<div id="wpsr-reservation-modal" class="wpsr-modal" style="display: none;">
    <div class="wpsr-modal-content">
        <div class="wpsr-modal-header">
            <h2 id="wpsr-reservation-modal-title">予約編集</h2>
            <span class="wpsr-modal-close">&times;</span>
        </div>
        <form id="wpsr-reservation-form">
            <input type="hidden" id="wpsr-reservation-id" name="reservation_id" value="">
            
            <div class="wpsr-form-group">
                <label for="wpsr-reservation-name">名前 *</label>
                <input type="text" id="wpsr-reservation-name" name="name" required>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-reservation-email">メールアドレス *</label>
                <input type="email" id="wpsr-reservation-email" name="email" required>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-reservation-phone">電話番号</label>
                <input type="tel" id="wpsr-reservation-phone" name="phone">
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-reservation-date">予約日 *</label>
                <input type="date" id="wpsr-reservation-date" name="schedule_date" required>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-reservation-time">予約時間 *</label>
                <input type="time" id="wpsr-reservation-time" name="schedule_time" required>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-reservation-status">ステータス</label>
                <select id="wpsr-reservation-status" name="status">
                    <option value="pending">保留中</option>
                    <option value="confirmed">確認済み</option>
                    <option value="cancelled">キャンセル</option>
                </select>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-reservation-message">メッセージ</label>
                <textarea id="wpsr-reservation-message" name="message" rows="4"></textarea>
            </div>
            
            <div class="wpsr-modal-footer">
                <button type="submit" class="button button-primary">保存</button>
                <button type="button" class="button wpsr-modal-cancel">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<style>
.wpsr-admin-content {
    margin-top: 20px;
}

.wpsr-no-reservations {
    text-align: center;
    padding: 40px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wpsr-status-pending {
    color: #f39c12;
    font-weight: bold;
}

.wpsr-status-confirmed {
    color: #27ae60;
    font-weight: bold;
}

.wpsr-status-cancelled {
    color: #e74c3c;
    font-weight: bold;
}

.wpsr-edit-reservation,
.wpsr-cancel-reservation {
    margin-right: 5px;
}

.wpsr-cancel-reservation {
    background: #e74c3c;
    border-color: #e74c3c;
    color: #fff;
}

.wpsr-cancel-reservation:hover {
    background: #c0392b;
    border-color: #c0392b;
}

/* 予約編集モーダル */
.wpsr-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.wpsr-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.wpsr-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wpsr-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.wpsr-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.wpsr-modal-close:hover {
    color: #000;
}

#wpsr-reservation-form {
    padding: 20px;
}

.wpsr-form-group {
    margin-bottom: 15px;
}

.wpsr-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.wpsr-form-group input,
.wpsr-form-group select,
.wpsr-form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.wpsr-form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.wpsr-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.wpsr-modal-footer .button {
    margin-left: 10px;
}
</style>

<!-- 管理画面用のJavaScriptは wpsr-admin-scripts.js で処理されます -->

<?php
/**
 * ステータスラベルを取得
 */
function get_status_label($status) {
    $labels = array(
        'pending' => '保留中',
        'confirmed' => '確認済み',
        'cancelled' => 'キャンセル'
    );
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}
?>
