<?php
/**
 * 予約一覧管理画面
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// 検索条件を取得
$schedule_date_from = $_GET['schedule_date_from'] ?? '';
$schedule_date_to = $_GET['schedule_date_to'] ?? '';
$created_date_from = $_GET['created_date_from'] ?? '';
$created_date_to = $_GET['created_date_to'] ?? '';
$email_search = $_GET['email_search'] ?? '';

// WHERE句を構築
$where_conditions = array();
$where_values = array();

if (!empty($schedule_date_from)) {
    $where_conditions[] = "schedule_date >= %s";
    $where_values[] = $schedule_date_from;
}

if (!empty($schedule_date_to)) {
    $where_conditions[] = "schedule_date <= %s";
    $where_values[] = $schedule_date_to;
}

if (!empty($created_date_from)) {
    $where_conditions[] = "DATE(created_at) >= %s";
    $where_values[] = $created_date_from;
}

if (!empty($created_date_to)) {
    $where_conditions[] = "DATE(created_at) <= %s";
    $where_values[] = $created_date_to;
}

if (!empty($email_search)) {
    $where_conditions[] = "email LIKE %s";
    $where_values[] = '%' . $wpdb->esc_like($email_search) . '%';
}

// SQLクエリを構築
$sql = "SELECT * FROM {$wpdb->prefix}wpsr_reservations";
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}
$sql .= " ORDER BY created_at DESC";

// 予約一覧を取得
if (!empty($where_values)) {
    $reservations = $wpdb->get_results($wpdb->prepare($sql, $where_values));
} else {
    $reservations = $wpdb->get_results($sql);
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">予約一覧</h1>
    
    <!-- 検索フォーム -->
    <div class="wpsr-search-form">
        <form method="get" action="">
            <input type="hidden" name="page" value="wpsr-reservations">
            
            <div class="wpsr-search-row">
                <div class="wpsr-search-group">
                    <label>予約日時:</label>
                    <input type="date" name="schedule_date_from" value="<?php echo esc_attr($_GET['schedule_date_from'] ?? ''); ?>" placeholder="開始日">
                    <span>〜</span>
                    <input type="date" name="schedule_date_to" value="<?php echo esc_attr($_GET['schedule_date_to'] ?? ''); ?>" placeholder="終了日">
                </div>
                
                <div class="wpsr-search-group">
                    <label>登録日時:</label>
                    <input type="date" name="created_date_from" value="<?php echo esc_attr($_GET['created_date_from'] ?? ''); ?>" placeholder="開始日">
                    <span>〜</span>
                    <input type="date" name="created_date_to" value="<?php echo esc_attr($_GET['created_date_to'] ?? ''); ?>" placeholder="終了日">
                </div>
                
                <div class="wpsr-search-group">
                    <label>メールアドレス:</label>
                    <input type="text" name="email_search" value="<?php echo esc_attr($_GET['email_search'] ?? ''); ?>" placeholder="メールアドレスを入力">
                </div>
            </div>
            
            <div class="wpsr-search-actions">
                <button type="submit" class="button button-primary">検索</button>
                <a href="?page=wpsr-reservations" class="button">リセット</a>
            </div>
        </form>
    </div>
    
    <div class="wpsr-admin-content">
        <?php 
        $total_count = count($reservations);
        $has_search = !empty($schedule_date_from) || !empty($schedule_date_to) || 
                     !empty($created_date_from) || !empty($created_date_to) || 
                     !empty($email_search);
        ?>
        
        <?php if ($has_search): ?>
            <div class="wpsr-search-results">
                <p>検索結果: <strong><?php echo $total_count; ?></strong>件の予約が見つかりました。</p>
            </div>
        <?php endif; ?>
        
        <?php if (empty($reservations)): ?>
            <div class="wpsr-no-reservations">
                <p><?php echo $has_search ? '検索条件に一致する予約がありません。' : 'まだ予約がありません。'; ?></p>
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
                                <a href="#" class="button button-small wpsr-delete-reservation" 
                                   data-id="<?php echo esc_attr($reservation->id); ?>"
                                   data-name="<?php echo esc_attr($reservation->name); ?>">
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
/* 検索フォーム */
.wpsr-search-form {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.wpsr-search-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 15px;
}

.wpsr-search-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 300px;
}

.wpsr-search-group label {
    font-weight: bold;
    min-width: 120px;
    white-space: nowrap;
}

.wpsr-search-group input[type="date"],
.wpsr-search-group input[type="text"] {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.wpsr-search-group input[type="text"] {
    flex: 1;
    min-width: 200px;
}

.wpsr-search-actions {
    text-align: right;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.wpsr-search-actions .button {
    margin-left: 10px;
}

.wpsr-search-results {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    padding: 10px 15px;
    margin-bottom: 20px;
}

.wpsr-search-results p {
    margin: 0;
    color: #0066cc;
}

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
.wpsr-delete-reservation {
    margin-right: 5px;
}

.wpsr-delete-reservation {
    background: transparent !important;
    border-color: #dc3545 !important;
    color: #dc3545 !important;
}

.wpsr-delete-reservation:hover {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
    color: #fff !important;
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
