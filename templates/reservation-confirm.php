<?php
// グローバル変数からフォームデータを取得
global $wpsr_form_data;

if (!$wpsr_form_data) {
    echo '<div class="wpsr-error-message">データが見つかりません。予約フォームから再度お試しください。</div>';
    return;
}

// フォームマネージャーを取得
if (!class_exists('WPSR_Form_Manager')) {
    require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
}
$form_manager = new WPSR_Form_Manager();

// 設定を取得
$confirm_title = get_option('wpsr_confirm_title', '予約確認');
$confirm_button_text = get_option('wpsr_confirm_button_text', '予約を確定する');
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
    <div class="wpsr-reservation-form" id="wpsr-confirm-form">
        <div class="wpsr-section">
            <h3 class="wpsr-section-title"><?php echo esc_html($confirm_title); ?></h3>
            
            <div class="wpsr-form-group">
                <label class="wpsr-label">予約日時</label>
                <?php
                $schedule_date = $wpsr_form_data['schedule_date'];
                $schedule_time = $wpsr_form_data['schedule_time'];
                
                // 日付をフォーマット
                $date_obj = new DateTime($schedule_date);
                $weekdays = array('日', '月', '火', '水', '木', '金', '土');
                $formatted_date = $date_obj->format('Y年n月j日') . '（' . $weekdays[$date_obj->format('w')] . '）';
                
                echo '<div class="wpsr-schedule-info">' . esc_html($formatted_date . ' ' . $schedule_time . '〜') . '</div>';
                ?>
            </div>
            
            <div class="wpsr-form-group">
                <label class="wpsr-label">入力情報</label>
                <div class="wpsr-form-data">
                    <?php
                    $visible_fields = $form_manager->get_visible_fields();
                    foreach ($visible_fields as $field) {
                        $field_key = $field['field_key'];
                        $field_label = $field['field_label'];
                        
                        if (isset($wpsr_form_data[$field_key])) {
                            $field_value = $wpsr_form_data[$field_key];
                            
                            // ラジオボタンやセレクトボックスの場合は表示値を取得
                            if ($field['field_type'] === 'radio' || $field['field_type'] === 'select') {
                                // JSONのエスケープ文字を処理
                                $field_options = stripslashes($field['field_options']);
                                $options = json_decode($field_options, true);
                                
                                if ($options && isset($options[$field_value])) {
                                    $field_value = $options[$field_value];
                                }
                            }
                            
                            // チェックボックスの場合は選択された値を表示
                            if ($field['field_type'] === 'checkbox') {
                                if (empty($field_value)) {
                                    $field_value = '選択なし';
                                } else {
                                    // カンマ区切りの値を配列に変換
                                    $selected_values = explode(',', $field_value);
                                    $field_options = stripslashes($field['field_options']);
                                    $options = json_decode($field_options, true);
                                    
                                    $display_values = array();
                                    foreach ($selected_values as $value) {
                                        if ($options && isset($options[$value])) {
                                            $display_values[] = $options[$value];
                                        } else {
                                            $display_values[] = $value;
                                        }
                                    }
                                    $field_value = implode(', ', $display_values);
                                }
                            }
                            
                            // 性別フィールドの場合は日本語に変換
                            if ($field['field_type'] === 'gender') {
                                switch ($field_value) {
                                    case 'male':
                                        $field_value = '男性';
                                        break;
                                    case 'female':
                                        $field_value = '女性';
                                        break;
                                    default:
                                        $field_value = $field_value; // そのまま表示
                                }
                            }
                            
                            echo '<div class="wpsr-field-data">';
                            echo '<span class="wpsr-field-label">' . esc_html($field_label) . '</span>';
                            echo '<span class="wpsr-field-value">' . esc_html($field_value) . '</span>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <div class="wpsr-form-group">
                <form id="wpsr-confirm-form" method="post">
                    <?php wp_nonce_field('wpsr_nonce', 'wpsr_nonce'); ?>
                    <input type="hidden" name="action" value="wpsr_save_reservation">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpsr_nonce'); ?>">
                    
                    <?php
                    // フォームデータを隠しフィールドとして送信
                    foreach ($wpsr_form_data as $key => $value) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                    ?>
                </form>
                
                <div class="wpsr-button-container">
                    <button type="submit" form="wpsr-confirm-form" class="wpsr-submit-btn" id="wpsr-confirm-submit-btn">
                        <span class="wpsr-btn-text"><?php echo esc_html($confirm_button_text); ?></span>
                        <span class="wpsr-btn-spinner" style="display: none;">
                            <svg class="wpsr-spinner" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                    <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </span>
                    </button>
                    <button type="button" class="wpsr-btn wpsr-btn-outline" onclick="history.back()">修正する</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 確認画面専用スタイル */
.wpsr-schedule-info {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 10px 0;
    padding: 15px 20px;
    background: #f8f9fa;
    border: 1px solid #e1e8ed;
    border-radius: 8px;
    text-align: center;
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

/* スピナー付きボタンのスタイル */
.wpsr-submit-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.wpsr-btn-text {
    transition: opacity 0.2s ease;
}

.wpsr-btn-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
}

.wpsr-spinner {
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.wpsr-submit-btn:disabled .wpsr-btn-text {
    opacity: 0.7;
}

.wpsr-btn-outline {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 18px 20px !important;
    background: transparent !important;
    color: #6c757d !important;
    border: 2px solid #6c757d !important;
    border-radius: 8px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    display: block !important;
    text-align: center !important;
    box-sizing: border-box !important;
    height: 60px !important;
    line-height: 1.2 !important;
}

.wpsr-btn-outline:hover {
    background: #6c757d !important;
    color: #fff !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3) !important;
}

.wpsr-error-message {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: center;
}

/* ボタン配置の調整 */
.wpsr-form-group:last-child {
    text-align: center;
    margin-top: 30px;
    width: 100%;
}

/* ボタンコンテナの統一されたレイアウト */
.wpsr-button-container {
    width: 90%;
    max-width: 90%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

/* 確定ボタンのサイズ統一 */
.wpsr-button-container .wpsr-submit-btn {
    width: 100%;
    height: 60px;
    line-height: 1.2;
    margin: 0;
    font-size: 16px;
    padding: 18px 20px;
    box-sizing: border-box;
}

/* 修正ボタンのサイズ統一 - 最高優先度で設定 */
.wpsr-button-container .wpsr-btn-outline {
    width: 90% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 18px 20px !important;
    background: transparent !important;
    color: #6c757d !important;
    border: 2px solid #6c757d !important;
    border-radius: 8px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    display: block !important;
    text-align: center !important;
    box-sizing: border-box !important;
    height: 60px !important;
    line-height: 1.2 !important;
}

/* ラッパーの影響を排除 */
.wpsr-form-wrapper .wpsr-reservation-form .wpsr-form-group:last-child {
    width: 100% !important;
    max-width: 100% !important;
}

.wpsr-form-wrapper .wpsr-reservation-form .wpsr-button-container {
    width: 90% !important;
    max-width: 90% !important;
}

/* 修正ボタンのホバー効果も最高優先度で設定 */
.wpsr-button-container .wpsr-btn-outline:hover {
    background: #6c757d !important;
    color: #fff !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3) !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // フォームのsubmitイベントを無効化
    $('#wpsr-confirm-form').on('submit', function(e) {
        e.preventDefault();
    });
    
    // 予約確定ボタンのクリックイベント
    $('.wpsr-submit-btn').on('click', function(e) {
        e.preventDefault();
        
        // 重複送信を防ぐため、ボタンを無効化
        var $button = $(this);
        if ($button.prop('disabled')) {
            return;
        }
        
        // スピナーを表示
        $button.find('.wpsr-btn-text').hide();
        $button.find('.wpsr-btn-spinner').show();
        $button.prop('disabled', true);
        
        // フォームデータを手動で構築
        var formData = new FormData();
        
        // フォーム内の隠しフィールドを取得
        $('#wpsr-confirm-form input[type="hidden"]').each(function() {
            var name = $(this).attr('name');
            var value = $(this).attr('value');
            if (name && value !== undefined) {
                formData.append(name, value);
            }
        });
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    alert('エラーが発生しました: ' + response.data);
                    // スピナーを非表示にしてボタンを有効化
                    $button.find('.wpsr-btn-text').show();
                    $button.find('.wpsr-btn-spinner').hide();
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
                // スピナーを非表示にしてボタンを有効化
                $button.find('.wpsr-btn-text').show();
                $button.find('.wpsr-btn-spinner').hide();
                $button.prop('disabled', false);
            }
        });
    });
});
</script>
