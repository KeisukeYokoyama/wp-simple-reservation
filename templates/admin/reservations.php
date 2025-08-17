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
$email_search = $_GET['email_search'] ?? '';
$name_search = $_GET['name_search'] ?? '';
$status_filter = $_GET['status'] ?? array();

// ソート条件を取得
$sort_column = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'desc';

// ソート可能な列を定義
$sortable_columns = array(
    'id' => 'id',
    'name' => 'name',
    'email' => 'email',
    'schedule_date' => 'schedule_date',
    'status' => 'status',
    'created_at' => 'created_at'
);

// ソート列の検証
if (!array_key_exists($sort_column, $sortable_columns)) {
    $sort_column = 'created_at';
}

// ソート順の検証
if (!in_array($sort_order, array('asc', 'desc'))) {
    $sort_order = 'desc';
}

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

if (!empty($email_search)) {
    $where_conditions[] = "email LIKE %s";
    $where_values[] = '%' . $wpdb->esc_like($email_search) . '%';
}

if (!empty($name_search)) {
    $where_conditions[] = "name LIKE %s";
    $where_values[] = '%' . $wpdb->esc_like($name_search) . '%';
}

if (!empty($status_filter)) {
    $status_placeholders = array_fill(0, count($status_filter), '%s');
    $where_conditions[] = "status IN (" . implode(',', $status_placeholders) . ")";
    $where_values = array_merge($where_values, $status_filter);
}

// SQLクエリを構築
$sql = "SELECT * FROM {$wpdb->prefix}wpsr_reservations";
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// ソート条件を追加
$sql .= " ORDER BY {$sortable_columns[$sort_column]} {$sort_order}";

// 予約一覧を取得
if (!empty($where_values)) {
    $reservations = $wpdb->get_results($wpdb->prepare($sql, $where_values));
} else {
    $reservations = $wpdb->get_results($sql);
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">予約一覧</h1>
    <a href="#" class="page-title-action wpsr-add-reservation">予約を追加</a>
    
    <!-- 検索フォーム -->
    <div class="wpsr-search-form">
        <form method="get" action="">
            <input type="hidden" name="page" value="wpsr-reservations">
            
            <div class="wpsr-search-row">
                <div class="wpsr-search-group">
                    <label>名前:</label>
                    <input type="text" name="name_search" value="<?php echo esc_attr($_GET['name_search'] ?? ''); ?>" placeholder="名前を入力">
                </div>
                
                <div class="wpsr-search-group">
                    <label>メールアドレス:</label>
                    <input type="text" name="email_search" value="<?php echo esc_attr($_GET['email_search'] ?? ''); ?>" placeholder="メールアドレスを入力">
                </div>
                </div>
                
            <div class="wpsr-search-row">
                <div class="wpsr-search-group wpsr-date-range">
                    <label>予約日時:</label>
                    <div class="wpsr-date-inputs">
                        <input type="date" name="schedule_date_from" value="<?php echo esc_attr($_GET['schedule_date_from'] ?? ''); ?>" placeholder="開始日">
                        <span class="wpsr-date-separator">〜</span>
                        <input type="date" name="schedule_date_to" value="<?php echo esc_attr($_GET['schedule_date_to'] ?? ''); ?>" placeholder="終了日">
                </div>
            </div>
            
                <div class="wpsr-search-group">
                    <label>ステータス:</label>
                    <div class="wpsr-checkbox-group">
                        <label class="wpsr-checkbox">
                            <input type="checkbox" name="status[]" value="pending" <?php echo in_array('pending', $_GET['status'] ?? []) ? 'checked' : ''; ?>>
                            <span>保留中</span>
                        </label>
                        <label class="wpsr-checkbox">
                            <input type="checkbox" name="status[]" value="confirmed" <?php echo in_array('confirmed', $_GET['status'] ?? []) ? 'checked' : ''; ?>>
                            <span>確認済み</span>
                        </label>
                        <label class="wpsr-checkbox">
                            <input type="checkbox" name="status[]" value="cancelled" <?php echo in_array('cancelled', $_GET['status'] ?? []) ? 'checked' : ''; ?>>
                            <span>キャンセル</span>
                        </label>
                    </div>
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
                     !empty($email_search) || !empty($name_search) || !empty($status_filter);
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
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-id sortable <?php echo ($sort_column === 'id') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="ID">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'id', 'order' => ($sort_column === 'id' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>ID</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'id' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-name sortable <?php echo ($sort_column === 'name') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="名前">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'name', 'order' => ($sort_column === 'name' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>名前</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'name' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-email sortable <?php echo ($sort_column === 'email') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="メール">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'email', 'order' => ($sort_column === 'email' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>メール</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'email' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-schedule-date sortable <?php echo ($sort_column === 'schedule_date') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="予約日時">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'schedule_date', 'order' => ($sort_column === 'schedule_date' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>予約日時</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'schedule_date' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-status sortable <?php echo ($sort_column === 'status') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="ステータス">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'status', 'order' => ($sort_column === 'status' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>ステータス</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'status' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-created-at sortable <?php echo ($sort_column === 'created_at') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="登録日">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'created_at', 'order' => ($sort_column === 'created_at' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>登録日</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'created_at' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-actions">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo esc_html($reservation->id); ?></td>
                            <td><?php echo esc_html($reservation->name); ?></td>
                            <td><?php echo esc_html($reservation->email); ?></td>
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
                                   data-name="<?php echo esc_attr($reservation->id); ?>">
                                    削除
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-id sortable <?php echo ($sort_column === 'id') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="ID">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'id', 'order' => ($sort_column === 'id' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>ID</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'id' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-name sortable <?php echo ($sort_column === 'name') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="名前">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'name', 'order' => ($sort_column === 'name' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>名前</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'name' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-email sortable <?php echo ($sort_column === 'email') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="メール">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'email', 'order' => ($sort_column === 'email' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>メール</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'email' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-schedule-date sortable <?php echo ($sort_column === 'schedule_date') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="予約日時">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'schedule_date', 'order' => ($sort_column === 'schedule_date' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>予約日時</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'schedule_date' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-status sortable <?php echo ($sort_column === 'status') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="ステータス">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'status', 'order' => ($sort_column === 'status' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>ステータス</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'status' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-created-at sortable <?php echo ($sort_column === 'created_at') ? ($sort_order === 'asc' ? 'asc' : 'desc') : 'desc'; ?>" abbr="登録日">
                            <a href="<?php echo add_query_arg(array_merge($_GET, array('sort' => 'created_at', 'order' => ($sort_column === 'created_at' && $sort_order === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span>登録日</span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                                <span class="screen-reader-text"><?php echo ($sort_column === 'created_at' && $sort_order === 'asc') ? '降順で並べ替え。' : '昇順で並べ替え。'; ?></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-actions">操作</th>
                    </tr>
                </tfoot>
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
            
            <!-- 基本情報セクション -->
            <div class="wpsr-form-section">
                <h3>予約状況</h3>
            
            <div class="wpsr-form-group">
                    <label for="wpsr-reservation-date">予約日 *</label>
                    <input type="date" id="wpsr-reservation-date" name="schedule_date" required readonly>
                    <small class="wpsr-field-note">日付変更はできません</small>
            </div>
            
            <div class="wpsr-form-group">
                    <label for="wpsr-reservation-time">予約時間 *</label>
                    <input type="time" id="wpsr-reservation-time" name="schedule_time" required readonly>
                    <small class="wpsr-field-note">時間変更はできません</small>
            </div>
            
            <div class="wpsr-form-group">
                    <label for="wpsr-reservation-status">ステータス</label>
                    <select id="wpsr-reservation-status" name="status">
                        <option value="pending">保留中</option>
                        <option value="confirmed">確認済み</option>
                        <option value="cancelled">キャンセル</option>
                    </select>
            </div>
                
                <div class="wpsr-notice">
                    <p><strong>⚠️ 注意事項</strong></p>
                    <p>予約時間の変更は、他の予約との競合を避けるためこの画面では行えません。変更が必要な場合は、以下の手順で手動で対応してください。</p>
                    <ol>
                        <li>現在の予約を削除</li>
                        <li>新しい時間で予約を作成</li>
                    </ol>
                </div>
            </div>
            
            <!-- 動的フィールドセクション -->
            <div class="wpsr-form-section">
                <h3>予約者情報</h3>
                <div id="wpsr-dynamic-fields-container">
                    <!-- 動的フィールドがここに生成されます -->
                </div>
            </div>
            
            <div class="wpsr-modal-footer">
                <button type="submit" class="button button-primary">更新</button>
                <button type="button" class="button wpsr-modal-cancel">キャンセル</button>
            </div>
        </form>
    </div>
</div>



<!-- 新規予約作成モーダル -->
<div id="wpsr-add-reservation-modal" class="wpsr-modal" style="display: none;">
    <div class="wpsr-modal-content">
        <div class="wpsr-modal-header">
            <h2>新規予約作成</h2>
            <span class="wpsr-modal-close">&times;</span>
        </div>
        <form id="wpsr-add-reservation-form">
            <!-- 基本情報セクション -->
            <div class="wpsr-form-section">
                <h3>予約状況</h3>
            
            <div class="wpsr-form-group">
                    <label for="wpsr-add-reservation-date">予約日 *</label>
                    <input type="date" id="wpsr-add-reservation-date" name="schedule_date" required>
            </div>
            
            <div class="wpsr-form-group">
                    <label for="wpsr-add-reservation-time">予約時間 *</label>
                    <select id="wpsr-add-reservation-time" name="schedule_time" required>
                        <option value="">時間を選択してください</option>
                    </select>
            </div>
            
            <div class="wpsr-form-group">
                    <label for="wpsr-add-reservation-status">ステータス</label>
                    <select id="wpsr-add-reservation-status" name="status">
                    <option value="pending">保留中</option>
                    <option value="confirmed">確認済み</option>
                    <option value="cancelled">キャンセル</option>
                </select>
                </div>
            </div>
            
            <!-- 動的フィールドセクション -->
            <div class="wpsr-form-section">
                <h3>入力情報</h3>
                <div id="wpsr-add-dynamic-fields">
                    <!-- 動的にフィールドが生成されます -->
                </div>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-add-reservation-message">メッセージ</label>
                <textarea id="wpsr-add-reservation-message" name="message" rows="4"></textarea>
            </div>
            
            <div class="wpsr-modal-footer">
                <button type="submit" class="button button-primary">予約を作成</button>
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
    padding: 15px;
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
    flex-direction: column;
    gap: 6px;
    flex: 1;
    min-width: 250px;
}

.wpsr-search-group.wpsr-date-range {
    flex: 0 0 auto;
    min-width: 320px;
    max-width: 400px;
}

.wpsr-search-group label {
    font-weight: bold;
    color: #333;
    font-size: 14px;
    white-space: nowrap;
}

.wpsr-search-group input[type="date"],
.wpsr-search-group input[type="text"] {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}

.wpsr-date-inputs {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.wpsr-date-inputs input[type="date"] {
    flex: 1;
    min-width: 120px;
    max-width: 150px;
}

.wpsr-date-separator {
    color: #666;
    font-weight: bold;
    white-space: nowrap;
    margin: 0 5px;
}

.wpsr-checkbox-group {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.wpsr-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-weight: normal;
    font-size: 14px;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background-color 0.2s;
    white-space: nowrap;
}

.wpsr-checkbox:hover {
    background-color: #f5f5f5;
}

.wpsr-checkbox input[type="checkbox"] {
    margin: 0;
    width: 16px;
    height: 16px;
}



.wpsr-search-actions {
    text-align: right;
    padding-top: 12px;
    border-top: 1px solid #eee;
    margin-top: 8px;
}

.wpsr-search-actions .button {
    margin-left: 10px;
    padding: 6px 16px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 3px;
    transition: background-color 0.2s;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .wpsr-search-group {
        min-width: 100%;
    }
    
    .wpsr-date-inputs {
        flex-direction: column;
        align-items: stretch;
    }
    
    .wpsr-date-inputs input[type="date"] {
        min-width: auto;
        max-width: none;
    }
    
    .wpsr-checkbox-group {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .wpsr-search-actions {
        text-align: center;
    }
    
    .wpsr-search-actions .button {
        margin: 5px;
        width: 100%;
        max-width: 200px;
    }
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
    overflow-y: auto;
}

.wpsr-modal-content {
    background-color: #fefefe;
    margin: 2% auto;
    padding: 0;
    border: 1px solid #888;
    width: 90%;
    max-width: 600px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-height: 90vh;
    overflow-y: auto;
}

.wpsr-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
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

#wpsr-reservation-form,
#wpsr-add-reservation-form {
    padding: 20px;
    max-height: calc(90vh - 120px);
    overflow-y: auto;
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

.wpsr-form-section {
    margin-bottom: 25px;
    padding: 20px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    background-color: #fafafa;
}

.wpsr-form-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #007cba;
    padding-bottom: 10px;
    font-size: 16px;
}

.wpsr-radio-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wpsr-radio-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #fff;
    transition: background-color 0.2s;
}

.wpsr-radio-option:hover {
    background-color: #f5f5f5;
}

.wpsr-radio-option input[type="radio"] {
    margin: 0;
    width: 16px;
    height: 16px;
}

.wpsr-radio-option label {
    margin: 0;
    font-weight: normal;
    cursor: pointer;
    flex: 1;
}

.wpsr-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.wpsr-modal-footer .button {
    margin-left: 10px;
}

.wpsr-notice {
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
    color: #856404;
    font-size: 14px;
}

.wpsr-field-note {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.wpsr-form-group input[readonly],
.wpsr-form-group input[readonly]:focus {
    background-color: #f5f5f5;
    color: #666;
    cursor: not-allowed;
    border-color: #ddd;
}

.wpsr-form-group input[readonly] + .wpsr-field-note {
    color: #999;
    font-style: italic;
}

/* 新規予約作成ボタン */
.wpsr-add-reservation {
    margin-left: 10px;
    padding: 8px 16px;
    background: #007cba;
    color: #fff;
    text-decoration: none;
    border-radius: 3px;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.2s;
    display: inline-block;
    vertical-align: middle;
}

.wpsr-add-reservation:hover {
    background: #005a87;
    color: #fff;
    text-decoration: none;
}

/* ヘッダー部分のレイアウト調整 */
.wrap h1.wp-heading-inline {
    display: inline-block;
    margin-right: 0;
}

/* 新規予約作成モーダル */
#wpsr-add-reservation-modal .wpsr-modal-content {
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
}

#wpsr-add-reservation-modal .wpsr-form-section {
    background-color: #f8f9fa;
}

/* モーダル内のスクロールバーのスタイリング */
.wpsr-modal-content::-webkit-scrollbar {
    width: 8px;
}

.wpsr-modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.wpsr-modal-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.wpsr-modal-content::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* フォーム内のスクロールバーのスタイリング */
#wpsr-reservation-form::-webkit-scrollbar,
#wpsr-add-reservation-form::-webkit-scrollbar {
    width: 6px;
}

#wpsr-reservation-form::-webkit-scrollbar-track,
#wpsr-add-reservation-form::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#wpsr-reservation-form::-webkit-scrollbar-thumb,
#wpsr-add-reservation-form::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#wpsr-reservation-form::-webkit-scrollbar-thumb:hover,
#wpsr-add-reservation-form::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
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

// フィールド定義を取得
$form_fields = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}wpsr_form_fields 
    WHERE visible = 1 AND deleted_at IS NULL 
    ORDER BY sort_order ASC
");

// フィールド定義をJSONとして出力
$fields_json = json_encode($form_fields);
?>

<script>
// フィールド定義をグローバル変数として設定
var wpsrFormFields = <?php echo $fields_json; ?>;

// 動的フィールドを生成する関数
function generateDynamicFields(reservationData) {
    var container = document.getElementById('wpsr-dynamic-fields');
    container.innerHTML = '';
    
    wpsrFormFields.forEach(function(field) {
        var fieldDiv = document.createElement('div');
        fieldDiv.className = 'wpsr-form-group';
        
        var label = document.createElement('label');
        label.htmlFor = 'wpsr-reservation-' + field.field_key;
        label.textContent = field.field_label;
        if (field.required == 1) {
            label.innerHTML += ' *';
        }
        
        var input = createFormInput(field, reservationData);
        
        fieldDiv.appendChild(label);
        fieldDiv.appendChild(input);
        container.appendChild(fieldDiv);
    });
}

// フィールドタイプに応じて適切な入力要素を作成
function createFormInput(field, reservationData) {
    var input;
    var fieldValue = reservationData[field.field_key] || '';
    
    switch (field.field_type) {
        case 'text':
        case 'email':
        case 'tel':
        case 'date':
            input = document.createElement('input');
            input.type = field.field_type;
            input.id = 'wpsr-reservation-' + field.field_key;
            input.name = field.field_key;
            input.value = fieldValue;
            if (field.required == 1) {
                input.required = true;
            }
            if (field.field_placeholder) {
                input.placeholder = field.field_placeholder;
            }
            break;
            
        case 'textarea':
            input = document.createElement('textarea');
            input.id = 'wpsr-reservation-' + field.field_key;
            input.name = field.field_key;
            input.rows = 4;
            input.textContent = fieldValue;
            if (field.required == 1) {
                input.required = true;
            }
            if (field.field_placeholder) {
                input.placeholder = field.field_placeholder;
            }
            break;
            
        case 'radio':
            input = document.createElement('div');
            input.className = 'wpsr-radio-group';
            
            try {
                var options = JSON.parse(field.field_options.replace(/\\/g, ''));
                Object.keys(options).forEach(function(key) {
                    var radioDiv = document.createElement('div');
                    radioDiv.className = 'wpsr-radio-option';
                    
                    var radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.id = 'wpsr-reservation-' + field.field_key + '_' + key;
                    radio.name = field.field_key;
                    radio.value = key;
                    radio.checked = (fieldValue === key);
                    if (field.required == 1) {
                        radio.required = true;
                    }
                    
                    var radioLabel = document.createElement('label');
                    radioLabel.htmlFor = 'wpsr-reservation-' + field.field_key + '_' + key;
                    radioLabel.textContent = options[key];
                    
                    radioDiv.appendChild(radio);
                    radioDiv.appendChild(radioLabel);
                    input.appendChild(radioDiv);
                });
            } catch (e) {
                console.error('Error parsing radio options:', e);
            }
            break;
            
        case 'select':
            input = document.createElement('select');
            input.id = 'wpsr-reservation-' + field.field_key;
            input.name = field.field_key;
            if (field.required == 1) {
                input.required = true;
            }
            
            try {
                var options = JSON.parse(field.field_options.replace(/\\/g, ''));
                Object.keys(options).forEach(function(key) {
                    var option = document.createElement('option');
                    option.value = key;
                    option.textContent = options[key];
                    option.selected = (fieldValue === key);
                    input.appendChild(option);
                });
            } catch (e) {
                console.error('Error parsing select options:', e);
            }
            break;
            
        default:
            input = document.createElement('input');
            input.type = 'text';
            input.id = 'wpsr-reservation-' + field.field_key;
            input.name = field.field_key;
            input.value = fieldValue;
            break;
    }
    
    return input;
}

// 予約編集モーダルを開く
function openReservationModal(reservationId) {
    // 予約データを取得
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'wpsr_get_reservation',
            reservation_id: reservationId,
            nonce: '<?php echo wp_create_nonce('wpsr_nonce'); ?>'
        },
        success: function(response) {
            if (response.success) {
                var reservation = response.data;
                
                // 基本情報を設定
                document.getElementById('wpsr-reservation-id').value = reservation.id;
                document.getElementById('wpsr-reservation-date').value = reservation.schedule_date;
                document.getElementById('wpsr-reservation-time').value = reservation.schedule_time;
                document.getElementById('wpsr-reservation-status').value = reservation.status;
                document.getElementById('wpsr-reservation-message').value = reservation.message || '';
                
                // 日付と時間フィールドを読み取り専用に設定
                document.getElementById('wpsr-reservation-date').readOnly = true;
                document.getElementById('wpsr-reservation-time').readOnly = true;
                
                // 動的フィールドを生成
                generateDynamicFields(reservation);
                
                // モーダルを表示
                document.getElementById('wpsr-reservation-modal').style.display = 'block';
            } else {
                alert('予約データの取得に失敗しました: ' + response.data);
            }
        },
        error: function() {
            alert('通信エラーが発生しました。');
        }
    });
}

// モーダルを閉じる
function closeReservationModal() {
    document.getElementById('wpsr-reservation-modal').style.display = 'none';
}

// 新規予約作成モーダルを開く
function openAddReservationModal() {
    // 動的フィールドを生成（空のデータで）
    generateAddDynamicFields({});
    
    // モーダルを表示
    document.getElementById('wpsr-add-reservation-modal').style.display = 'block';
}

// 新規作成用の動的フィールドを生成
function generateAddDynamicFields(reservationData) {
    var container = document.getElementById('wpsr-add-dynamic-fields');
    container.innerHTML = '';
    
    wpsrFormFields.forEach(function(field) {
        var fieldDiv = document.createElement('div');
        fieldDiv.className = 'wpsr-form-group';
        
        var label = document.createElement('label');
        label.htmlFor = 'wpsr-add-reservation-' + field.field_key;
        label.textContent = field.field_label;
        if (field.required == 1) {
            label.innerHTML += ' *';
        }
        
        var input = createAddFormInput(field, reservationData);
        
        fieldDiv.appendChild(label);
        fieldDiv.appendChild(input);
        container.appendChild(fieldDiv);
    });
}

// 新規作成用のフィールドタイプに応じて適切な入力要素を作成
function createAddFormInput(field, reservationData) {
    var input;
    var fieldValue = reservationData[field.field_key] || '';
    
    switch (field.field_type) {
        case 'text':
        case 'email':
        case 'tel':
        case 'date':
            input = document.createElement('input');
            input.type = field.field_type;
            input.id = 'wpsr-add-reservation-' + field.field_key;
            input.name = field.field_key;
            input.value = fieldValue;
            if (field.required == 1) {
                input.required = true;
            }
            if (field.field_placeholder) {
                input.placeholder = field.field_placeholder;
            }
            break;
            
        case 'textarea':
            input = document.createElement('textarea');
            input.id = 'wpsr-add-reservation-' + field.field_key;
            input.name = field.field_key;
            input.rows = 4;
            input.textContent = fieldValue;
            if (field.required == 1) {
                input.required = true;
            }
            if (field.field_placeholder) {
                input.placeholder = field.field_placeholder;
            }
            break;
            
        case 'radio':
            input = document.createElement('div');
            input.className = 'wpsr-radio-group';
            
            try {
                var options = JSON.parse(field.field_options.replace(/\\/g, ''));
                Object.keys(options).forEach(function(key) {
                    var radioDiv = document.createElement('div');
                    radioDiv.className = 'wpsr-radio-option';
                    
                    var radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.id = 'wpsr-add-reservation-' + field.field_key + '_' + key;
                    radio.name = field.field_key;
                    radio.value = key;
                    radio.checked = (fieldValue === key);
                    if (field.required == 1) {
                        radio.required = true;
                    }
                    
                    var radioLabel = document.createElement('label');
                    radioLabel.htmlFor = 'wpsr-add-reservation-' + field.field_key + '_' + key;
                    radioLabel.textContent = options[key];
                    
                    radioDiv.appendChild(radio);
                    radioDiv.appendChild(radioLabel);
                    input.appendChild(radioDiv);
                });
            } catch (e) {
                console.error('Error parsing radio options:', e);
            }
            break;
            
        case 'select':
            input = document.createElement('select');
            input.id = 'wpsr-add-reservation-' + field.field_key;
            input.name = field.field_key;
            if (field.required == 1) {
                input.required = true;
            }
            
            try {
                var options = JSON.parse(field.field_options.replace(/\\/g, ''));
                Object.keys(options).forEach(function(key) {
                    var option = document.createElement('option');
                    option.value = key;
                    option.textContent = options[key];
                    option.selected = (fieldValue === key);
                    input.appendChild(option);
                });
            } catch (e) {
                console.error('Error parsing select options:', e);
            }
            break;
            
        default:
            input = document.createElement('input');
            input.type = 'text';
            input.id = 'wpsr-add-reservation-' + field.field_key;
            input.name = field.field_key;
            input.value = fieldValue;
            break;
    }
    
    return input;
}

// 新規予約作成モーダルを閉じる
function closeAddReservationModal() {
    document.getElementById('wpsr-add-reservation-modal').style.display = 'none';
}

// 日付変更時に利用可能時間を更新
function updateAvailableTimes() {
    var selectedDate = document.getElementById('wpsr-add-reservation-date').value;
    var timeSelect = document.getElementById('wpsr-add-reservation-time');
    
    console.log('updateAvailableTimes called with date:', selectedDate);
    
    if (!selectedDate) {
        timeSelect.innerHTML = '<option value="">時間を選択してください</option>';
        return;
    }
    
    // 利用可能時間を取得
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'wpsr_get_available_times_by_date',
            date: selectedDate,
            nonce: '<?php echo wp_create_nonce('wpsr_nonce'); ?>'
        },
        success: function(response) {
            console.log('Ajax response:', response);
            if (response.success) {
                var schedules = response.data;
                console.log('Schedules data:', schedules);
                timeSelect.innerHTML = '<option value="">時間を選択してください</option>';
                
                if (schedules && schedules.length > 0) {
                    schedules.forEach(function(schedule) {
                        console.log('Processing schedule:', schedule);
                        if (schedule.available_slots > 0) {
                            var option = document.createElement('option');
                            option.value = schedule.time_slot;
                            option.textContent = schedule.time_slot + ' (残り' + schedule.available_slots + ')';
                            timeSelect.appendChild(option);
                        }
                    });
                } else {
                    timeSelect.innerHTML = '<option value="">この日は予約可能な時間がありません</option>';
                }
            } else {
                console.log('Ajax error:', response.data);
                timeSelect.innerHTML = '<option value="">利用可能時間がありません</option>';
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', {xhr: xhr, status: status, error: error});
            timeSelect.innerHTML = '<option value="">エラーが発生しました</option>';
        }
    });
}

// イベントリスナーを設定
document.addEventListener('DOMContentLoaded', function() {
    // 編集ボタンのクリックイベント
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('wpsr-edit-reservation')) {
            e.preventDefault();
            var reservationId = e.target.getAttribute('data-id');
            openReservationModal(reservationId);
        }
    });
    
    // 新規予約作成ボタンのクリックイベント
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('wpsr-add-reservation')) {
            e.preventDefault();
            openAddReservationModal();
        }
    });

    // モーダルを閉じる
    document.querySelectorAll('.wpsr-modal-close').forEach(function(closeBtn) {
        closeBtn.addEventListener('click', function() {
            var modal = this.closest('.wpsr-modal');
            if (modal.id === 'wpsr-add-reservation-modal') {
                closeAddReservationModal();
            } else {
                closeReservationModal();
            }
        });
    });
    
    document.querySelectorAll('.wpsr-modal-cancel').forEach(function(cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            var modal = this.closest('.wpsr-modal');
            if (modal.id === 'wpsr-add-reservation-modal') {
                closeAddReservationModal();
            } else {
                closeReservationModal();
            }
        });
    });
    
    // モーダル外クリックで閉じる
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('wpsr-modal')) {
            closeReservationModal();
        }
        if (e.target.classList.contains('wpsr-add-reservation-modal')) {
            closeAddReservationModal();
        }
    });
    
    // フォーム送信は wpsr-admin-scripts.js で処理されるため、ここでは不要

    // 新規予約作成フォームの送信
    document.getElementById('wpsr-add-reservation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'wpsr_create_reservation');
        formData.append('nonce', '<?php echo wp_create_nonce('wpsr_nonce'); ?>');
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('新規予約を作成しました。');
                    closeAddReservationModal();
                    location.reload(); // ページを再読み込み
                } else {
                    alert('予約作成に失敗しました: ' + response.data);
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            }
        });
    });

    // 日付変更時に利用可能時間を更新
    document.getElementById('wpsr-add-reservation-date').addEventListener('change', updateAvailableTimes);
});
</script>
