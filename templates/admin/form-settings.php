<?php
/**
 * フォーム設定管理画面
 */

if (!defined('ABSPATH')) {
    exit;
}

// フォームマネージャーを取得
if (!class_exists('WPSR_Form_Manager')) {
    require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
}
$form_manager = new WPSR_Form_Manager();
$fields = $form_manager->get_all_fields();
$template_fields = $form_manager->get_template_fields();
$custom_field_types = $form_manager->get_custom_field_types();

// デバッグ用
error_log('WPSR Debug - Template fields: ' . print_r($template_fields, true));
error_log('WPSR Debug - Custom field types: ' . print_r($custom_field_types, true));
?>

<div class="wrap">
    <h1 class="wp-heading-inline">フォーム設定</h1>
    
    <div class="wpsr-admin-content">
        <div class="wpsr-form-settings-container">
            
            <!-- テンプレート項目セクション -->
            <div class="wpsr-settings-section">
                <h2>テンプレート項目</h2>
                <p>よく使用される項目を簡単に追加できます。</p>
                
                <div class="wpsr-template-fields">
                    <?php foreach ($template_fields as $key => $field): ?>
                        <div class="wpsr-template-field">
                            <span class="wpsr-field-label"><?php echo esc_html($field['label']); ?></span>
                            <button type="button" class="button wpsr-add-template-field" 
                                    data-field-key="<?php echo esc_attr($key); ?>"
                                    data-field-data='<?php echo json_encode($field); ?>'>
                                追加
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 自由項目セクション -->
            <div class="wpsr-settings-section">
                <h2>自由項目</h2>
                <p>カスタムフィールドを追加できます。</p>
                
                <div class="wpsr-custom-fields">
                    <?php foreach ($custom_field_types as $type => $label): ?>
                        <div class="wpsr-custom-field">
                            <span class="wpsr-field-label"><?php echo esc_html($label); ?></span>
                            <button type="button" class="button wpsr-add-custom-field" 
                                    data-field-type="<?php echo esc_attr($type); ?>">
                                追加
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- フィールド一覧セクション -->
            <div class="wpsr-settings-section">
                <h2>フィールド一覧</h2>
                <p>現在のフォームに含まれるフィールドです。</p>
                
                <?php if (empty($fields)): ?>
                    <div class="wpsr-no-fields">
                        <p>フィールドが設定されていません。上記からフィールドを追加してください。</p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>順序</th>
                                <th>ラベル</th>
                                <th>タイプ</th>
                                <th>必須</th>
                                <th>表示</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="wpsr-fields-list">
                            <?php foreach ($fields as $field): ?>
                                <tr data-field-id="<?php echo esc_attr($field['id']); ?>">
                                    <td>
                                        <span class="wpsr-sort-handle">↕</span>
                                        <?php echo esc_html($field['sort_order']); ?>
                                    </td>
                                    <td><?php echo esc_html($field['field_label']); ?></td>
                                    <td><?php echo esc_html($field['field_type']); ?></td>
                                    <td>
                                        <span class="wpsr-status-<?php echo $field['required'] ? 'required' : 'optional'; ?>">
                                            <?php echo $field['required'] ? '必須' : '任意'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="wpsr-status-<?php echo $field['visible'] ? 'visible' : 'hidden'; ?>">
                                            <?php echo $field['visible'] ? '表示' : '非表示'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small wpsr-edit-field" 
                                                data-field-id="<?php echo esc_attr($field['id']); ?>">
                                            編集
                                        </button>
                                        <button type="button" class="button button-small wpsr-delete-field" 
                                                data-field-id="<?php echo esc_attr($field['id']); ?>">
                                            削除
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<!-- フィールド編集モーダル -->
<div id="wpsr-field-modal" class="wpsr-modal" style="display: none;">
    <div class="wpsr-modal-content">
        <div class="wpsr-modal-header">
            <h2 id="wpsr-field-modal-title">フィールド編集</h2>
            <span class="wpsr-modal-close">&times;</span>
        </div>
        <form id="wpsr-field-form">
            <input type="hidden" id="wpsr-field-id" name="field_id" value="">
            
            <div class="wpsr-form-group">
                <label for="wpsr-field-key">フィールドキー *</label>
                <input type="text" id="wpsr-field-key" name="field_key" required>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-field-type">フィールドタイプ *</label>
                <select id="wpsr-field-type" name="field_type" required>
                    <option value="text">テキストボックス</option>
                    <option value="email">メールアドレス</option>
                    <option value="tel">電話番号</option>
                    <option value="date">日付</option>
                    <option value="textarea">テキストエリア</option>
                    <option value="select">プルダウン</option>
                    <option value="radio">ラジオボタン</option>
                    <option value="checkbox">チェックボックス</option>
                </select>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-field-label">ラベル *</label>
                <input type="text" id="wpsr-field-label" name="field_label" required>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-field-placeholder">プレースホルダー</label>
                <input type="text" id="wpsr-field-placeholder" name="field_placeholder">
            </div>
            
            <div class="wpsr-form-group" id="wpsr-field-options-group" style="display: none;">
                <label for="wpsr-field-options">選択肢（1行に1つ）</label>
                <textarea id="wpsr-field-options" name="field_options" rows="4" 
                          placeholder="選択肢1&#10;選択肢2&#10;選択肢3"></textarea>
            </div>
            
            <div class="wpsr-form-group">
                <label>
                    <input type="checkbox" id="wpsr-field-required" name="required" value="1">
                    必須項目にする
                </label>
            </div>
            
            <div class="wpsr-form-group">
                <label>
                    <input type="checkbox" id="wpsr-field-visible" name="visible" value="1" checked>
                    表示する
                </label>
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

.wpsr-settings-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.wpsr-settings-section h2 {
    margin-top: 0;
    color: #23282d;
}

.wpsr-template-fields,
.wpsr-custom-fields {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.wpsr-template-field,
.wpsr-custom-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
}

.wpsr-field-label {
    font-weight: bold;
}

.wpsr-no-fields {
    text-align: center;
    padding: 40px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wpsr-sort-handle {
    cursor: move;
    color: #666;
    font-weight: bold;
}

.wpsr-status-required {
    color: #e74c3c;
    font-weight: bold;
}

.wpsr-status-optional {
    color: #27ae60;
    font-weight: bold;
}

.wpsr-status-visible {
    color: #27ae60;
    font-weight: bold;
}

.wpsr-status-hidden {
    color: #f39c12;
    font-weight: bold;
}

/* モーダルスタイル */
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

#wpsr-field-form {
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

<script>
// デバッグ用スクリプト
jQuery(document).ready(function($) {
    console.log('Form settings page loaded');
    console.log('Template field buttons:', $('.wpsr-add-template-field').length);
    console.log('Custom field buttons:', $('.wpsr-add-custom-field').length);
    console.log('Field modal exists:', $('#wpsr-field-modal').length > 0);
    
    // フォーム設定の初期化を直接実行
    if (typeof initFormSettings === 'function') {
        console.log('Initializing form settings directly...');
        initFormSettings();
    } else {
        console.log('initFormSettings function not found');
    }
    
    // ボタンクリックテスト
    $('.wpsr-add-template-field').on('click', function() {
        console.log('Template field button clicked (direct)');
    });
    
    $('.wpsr-add-custom-field').on('click', function() {
        console.log('Custom field button clicked (direct)');
    });
});
</script>
