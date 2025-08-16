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

<div class="wpsr-confirm-container">
    <h2><?php echo esc_html($confirm_title); ?></h2>
    
    <div class="wpsr-confirm-section">
        <h3>予約日時</h3>
        <?php
        $schedule_date = $wpsr_form_data['schedule_date'];
        $schedule_time = $wpsr_form_data['schedule_time'];
        
        // 日付をフォーマット
        $date_obj = new DateTime($schedule_date);
        $weekdays = array('日', '月', '火', '水', '木', '金', '土');
        $formatted_date = $date_obj->format('Y年n月j日') . '（' . $weekdays[$date_obj->format('w')] . '）';
        
        echo '<p class="wpsr-schedule-info">' . esc_html($formatted_date . ' ' . $schedule_time . '〜') . '</p>';
        ?>
    </div>
    
    <div class="wpsr-confirm-section">
        <h3>入力情報</h3>
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
                    
                    echo '<div class="wpsr-field-data">';
                    echo '<span class="wpsr-field-label">' . esc_html($field_label) . ':</span>';
                    echo '<span class="wpsr-field-value">' . esc_html($field_value) . '</span>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
    
    <div class="wpsr-confirm-actions">
        <button type="button" class="wpsr-btn wpsr-btn-secondary" onclick="history.back()">修正する</button>
        
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
            
            <button type="submit" class="wpsr-btn wpsr-btn-primary"><?php echo esc_html($confirm_button_text); ?></button>
        </form>
    </div>
</div>

<style>
.wpsr-confirm-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.wpsr-confirm-section {
    margin-bottom: 30px;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.wpsr-confirm-section h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #007cba;
    padding-bottom: 10px;
}

.wpsr-schedule-info {
    font-size: 18px;
    font-weight: bold;
    color: #007cba;
    margin: 10px 0;
}

.wpsr-form-data {
    margin-top: 15px;
}

.wpsr-field-data {
    display: flex;
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.wpsr-field-label {
    font-weight: bold;
    width: 120px;
    color: #555;
}

.wpsr-field-value {
    flex: 1;
    color: #333;
}

.wpsr-confirm-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

.wpsr-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.wpsr-btn-primary {
    background-color: #007cba;
    color: white;
}

.wpsr-btn-primary:hover {
    background-color: #005a87;
}

.wpsr-btn-secondary {
    background-color: #6c757d;
    color: white;
}

.wpsr-btn-secondary:hover {
    background-color: #545b62;
}

.wpsr-error-message {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#wpsr-confirm-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
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
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            }
        });
    });
});
</script>
