<?php
/**
 * フォーム設定管理画面
 */

if (!defined('ABSPATH')) {
    exit;
}

// WordPressの関数を使用するための確認
if (!function_exists('esc_html')) {
    require_once(ABSPATH . 'wp-includes/formatting.php');
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
    
    <div class="wpsr-admin-actions">
        <button type="button" class="button button-secondary" id="wpsr-update-table">
            データベーステーブルを更新
        </button>
        <span class="wpsr-help-text">フィールドの追加・削除・編集を行った後は、データベーステーブルを更新して変更を反映してください。</span>
    </div>
    
    <div class="wpsr-admin-content">
        <div class="wpsr-form-settings-container">

            <!-- 自由項目セクション -->
            <div class="wpsr-settings-section">
                <h2>カスタムフィールド</h2>
                <p>追加のフィールドをカスタマイズできます。</p>
                
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
                    <table class="wp-list-table widefat fixed striped wpsr-fields-table">
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
                        <tbody id="wpsr-fields-list" class="wpsr-fields-list">
                            <?php foreach ($fields as $field): ?>
                                <tr data-field-id="<?php echo esc_attr($field['id']); ?>" data-sort-order="<?php echo esc_attr($field['sort_order']); ?>">
                                    <td>
                                        <span class="wpsr-sort-handle" title="ドラッグして並び替え">↕</span>
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
                                        <?php if (!$form_manager->is_system_required_field($field['field_key'])): ?>
                                        <button type="button" class="button button-small wpsr-delete-field" 
                                                data-field-id="<?php echo esc_attr($field['id']); ?>">
                                            削除
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <p><small>メールアドレスと名前フィールドは予約システムの基本機能として必要で削除はできません</small></p>
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
                <input type="text" id="wpsr-field-key" name="field_key" 
                       value="<?php echo esc_attr($field['field_key'] ?? ''); ?>"
                       <?php echo (isset($field['field_key']) && !empty($field['field_key']) && $form_manager->is_system_required_field($field['field_key'])) ? 'disabled' : ''; ?>
                       required>
                <?php if (isset($field['field_key']) && !empty($field['field_key']) && $form_manager->is_system_required_field($field['field_key'])): ?>
                <p class="description wpsr-field-description">システム必須フィールドのため、フィールドキーは変更できません。</p>
                <?php endif; ?>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-field-type">フィールドタイプ *</label>
                <select id="wpsr-field-type" name="field_type" 
                        <?php echo (isset($field['field_key']) && !empty($field['field_key']) && $form_manager->is_system_required_field($field['field_key'])) ? 'disabled' : ''; ?>
                        required>
                    <option value="text" <?php echo (isset($field['field_type']) && $field['field_type'] === 'text') ? 'selected' : ''; ?>>テキストボックス</option>
                    <option value="email" <?php echo (isset($field['field_type']) && $field['field_type'] === 'email') ? 'selected' : ''; ?>>メールアドレス</option>
                    <option value="tel" <?php echo (isset($field['field_type']) && $field['field_type'] === 'tel') ? 'selected' : ''; ?>>電話番号</option>
                    <option value="date" <?php echo (isset($field['field_type']) && $field['field_type'] === 'date') ? 'selected' : ''; ?>>日付</option>
                    <option value="textarea" <?php echo (isset($field['field_type']) && $field['field_type'] === 'textarea') ? 'selected' : ''; ?>>テキストエリア</option>
                    <option value="select" <?php echo (isset($field['field_type']) && $field['field_type'] === 'select') ? 'selected' : ''; ?>>プルダウン</option>
                    <option value="radio" <?php echo (isset($field['field_type']) && $field['field_type'] === 'radio') ? 'selected' : ''; ?>>ラジオボタン</option>
                    <option value="checkbox" <?php echo (isset($field['field_type']) && $field['field_type'] === 'checkbox') ? 'selected' : ''; ?>>チェックボックス</option>
                    <option value="gender" <?php echo (isset($field['field_type']) && $field['field_type'] === 'gender') ? 'selected' : ''; ?>>性別</option>
                </select>
                <?php if (isset($field['field_key']) && !empty($field['field_key']) && $form_manager->is_system_required_field($field['field_key'])): ?>
                <p class="description wpsr-field-description">システム必須フィールドのため、フィールドタイプは変更できません。</p>
                <?php endif; ?>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-field-label">ラベル *</label>
                <input type="text" id="wpsr-field-label" name="field_label" 
                       value="<?php echo esc_attr($field['field_label'] ?? ''); ?>" required>
            </div>
            
            <div class="wpsr-form-group">
                <label for="wpsr-field-placeholder">プレースホルダー</label>
                <input type="text" id="wpsr-field-placeholder" name="field_placeholder" 
                       value="<?php echo esc_attr($field['field_placeholder'] ?? ''); ?>">
            </div>
            
            <div class="wpsr-form-group" id="wpsr-field-options-group" style="display: none;">
                <label for="wpsr-field-options">選択肢（1行に1つ）</label>
                <textarea id="wpsr-field-options" name="field_options" rows="4" 
                          placeholder="選択肢1&#10;選択肢2&#10;選択肢3"><?php echo esc_textarea($field['field_options'] ?? ''); ?></textarea>
            </div>
            
            <div class="wpsr-form-group">
                <label>
                    <input type="checkbox" id="wpsr-field-required" name="required" value="1"
                           <?php echo (isset($field['required']) && $field['required']) ? 'checked' : ''; ?>
                           <?php echo (isset($field['field_key']) && !empty($field['field_key']) && $form_manager->is_system_required_field($field['field_key'])) ? 'disabled' : ''; ?>>
                    必須項目にする
                </label>
                <?php if (isset($field['field_key']) && !empty($field['field_key']) && $form_manager->is_system_required_field($field['field_key'])): ?>
                <p class="description wpsr-field-description">システム必須フィールドのため、必須設定は変更できません。</p>
                <?php endif; ?>
            </div>
            
            <div class="wpsr-form-group">
                <label>
                    <input type="checkbox" id="wpsr-field-visible" name="visible" value="1"
                           <?php echo (!isset($field['visible']) || $field['visible']) ? 'checked' : ''; ?>
                           <?php echo (isset($field['field_key']) && !empty($field['field_key']) && $form_manager->is_system_required_field($field['field_key'])) ? 'disabled' : ''; ?>>
                    表示する
                </label>
                <?php if (isset($field['field_key']) && !empty($field['field_key']) && $form_manager->is_system_required_field($field['field_key'])): ?>
                <p class="description wpsr-field-description">システム必須フィールドのため、表示設定は変更できません。</p>
                <?php endif; ?>
            </div>
            
            <div class="wpsr-modal-footer">
                <button type="submit" class="button button-primary">保存</button>
                <button type="button" class="button wpsr-modal-cancel">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<style>
.wpsr-admin-actions {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wpsr-admin-actions .button {
    margin-right: 10px;
}

.wpsr-help-text {
    color: #666;
    font-size: 13px;
    font-style: italic;
}

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
    padding: 5px;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.wpsr-sort-handle:hover {
    background-color: #f0f0f0;
    color: #333;
}

/* 並び替え中のスタイル */
.wpsr-fields-list.ui-sortable-helper {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    border: 1px solid #ddd;
}

.wpsr-fields-list.ui-sortable-placeholder {
    background: #f9f9f9;
    border: 2px dashed #ccc;
    height: 50px;
}

/* 並び替え中の行のスタイル */
.wpsr-fields-list tr.ui-sortable-helper {
    background: #fff !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.wpsr-fields-list tr.ui-sortable-placeholder {
    background: #f9f9f9 !important;
    border: 2px dashed #ccc;
    height: 50px;
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

/* デフォルトフィールドのスタイル */
.wpsr-default-fields {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.wpsr-default-field {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.wpsr-default-field:last-child {
    border-bottom: none;
}

.wpsr-default-field .wpsr-field-label {
    font-weight: bold;
    color: #333;
    min-width: 150px;
}

.wpsr-default-field .wpsr-field-type {
    color: #666;
    min-width: 100px;
}

.wpsr-default-field .wpsr-field-status {
    background: #007cba;
    color: white;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

/* カスタムフィールドのスタイル */
.wpsr-custom-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.wpsr-custom-field {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    transition: all 0.2s;
}

.wpsr-custom-field:hover {
    border-color: #007cba;
    box-shadow: 0 2px 4px rgba(0, 124, 186, 0.1);
}

.wpsr-custom-field .wpsr-field-label {
    font-weight: bold;
    color: #333;
    text-align: center;
}

.wpsr-custom-field .button {
    width: 100%;
}

/* モーダルスタイル */
.wpsr-modal {
    display: none;
    position: fixed;
    z-index: 999999;
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

/* チェックボックスのスタイル調整 */
.wpsr-form-group input[type="checkbox"] {
    width: auto !important;
    margin-right: 8px !important;
    vertical-align: middle !important;
}

.wpsr-form-group label {
    display: flex !important;
    align-items: center !important;
    font-weight: normal !important;
    cursor: pointer !important;
}

.wpsr-form-group .description {
    margin-top: 5px !important;
    margin-left: 24px !important;
    color: #666 !important;
    font-style: italic !important;
}

/* システム必須フィールドの説明文はデフォルトで非表示 */
.wpsr-field-description {
    display: none;
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
