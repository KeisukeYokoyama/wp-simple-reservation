<?php
/**
 * 予約フォームテンプレート
 */

if (!defined('ABSPATH')) {
    exit;
}

// フォームマネージャーを取得
if (!class_exists('WPSR_Form_Manager')) {
    require_once WPSR_PLUGIN_PATH . 'includes/class-wpsr-form-manager.php';
}
$form_manager = new WPSR_Form_Manager();
?>

<div class="wpsr-reservation-form" id="wpsr-reservation-form">
    <form id="wpsr-form" method="post" action="<?php echo esc_url(home_url('/booking/confirm/')); ?>">
        <?php wp_nonce_field('wpsr_nonce', 'wpsr_nonce'); ?>
        
        <!-- 予約フォームセクション -->
        <div class="wpsr-section">
            <h3 class="wpsr-section-title"><?php echo wp_kses_post(get_option('wpsr_personal_info_title', '個人情報入力')); ?></h3>
            
            <!-- 動的に生成されたフィールド -->
            <?php echo $form_manager->generate_form_html(); ?>
            
            <h3 class="wpsr-section-title"><?php echo wp_kses_post(get_option('wpsr_booking_title', '面談予約')); ?></h3>
            <p class="wpsr-section-description"><?php echo wp_kses_post(get_option('wpsr_booking_description', '面談を行える日時を教えて下さい')); ?></p>
            
            <?php 
            $notice_text = get_option('wpsr_notice_text', '※仮予約ではなく、選択したお時間で予約完了となりますので、確実にご参加いただける日程をご選択ください。');
            if (!empty(trim($notice_text))): 
            ?>
            <div class="wpsr-notice">
                <p class="wpsr-notice-text"><?php echo wp_kses_post($notice_text); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- 日付選択 -->
            <div class="wpsr-form-group">
                <label class="wpsr-label">日付選択 <span class="wpsr-required">必須</span></label>
                <div class="wpsr-date-picker" id="wpsr-date-picker">
                    <!-- JavaScriptで動的に生成 -->
                </div>
            </div>
            
            <!-- 時間帯選択 -->
            <div class="wpsr-form-group">
                <label class="wpsr-label">時間帯選択 <span class="wpsr-required">必須</span></label>
                <div class="wpsr-time-slots" id="wpsr-time-slots">
                    <p class="wpsr-no-date">日付を選択してください</p>
                </div>
            </div>
        </div>
        
        <?php 
        $info_section_content = get_option('wpsr_info_section_content', '無料面談は入会のためのステップではなく、あなたの結婚の悩みを解消する場です。まずはお気軽にお問い合わせください。');
        if (!empty(trim($info_section_content))): 
        ?>
        <!-- 確認・補足情報セクション -->
        <div class="wpsr-section">
            <div class="wpsr-info-box">
                <div class="wpsr-info-content">
                    <div class="wpsr-info-text"><?php echo wp_kses_post($info_section_content); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 送信ボタン -->
        <div class="wpsr-form-group">
            <button type="submit" class="wpsr-submit-btn" id="wpsr-submit">
                <span class="wpsr-submit-text"><?php echo esc_html(get_option('wpsr_submit_button_text', 'ご入力内容の確認')); ?></span>
            </button>
        </div>
        
        <!-- 隠しフィールド -->
        <input type="hidden" name="schedule_date" id="wpsr-schedule-date">
        <input type="hidden" name="schedule_time" id="wpsr-schedule-time">
    </form>
    
    <!-- ローディング表示 -->
    <div class="wpsr-loading" id="wpsr-loading" style="display: none;">
        <div class="wpsr-loading-spinner"></div>
        <p class="wpsr-loading-text">読み込み中...</p>
    </div>
    
    <!-- 成功メッセージ -->
    <div class="wpsr-success" id="wpsr-success" style="display: none;">
        <div class="wpsr-success-icon">✓</div>
        <h3 class="wpsr-success-title">予約が完了しました</h3>
        <p class="wpsr-success-text">ご入力いただいたメールアドレスに確認メールをお送りしました。</p>
    </div>
    
    <!-- エラーメッセージ -->
    <div class="wpsr-error" id="wpsr-error" style="display: none;">
        <div class="wpsr-error-icon">✗</div>
        <h3 class="wpsr-error-title">エラーが発生しました</h3>
        <p class="wpsr-error-text" id="wpsr-error-message"></p>
    </div>
</div>

<!-- 設定値をJavaScriptに渡す -->
<script type="text/javascript">
var wpsrDisplaySettings = {
    displayDays: <?php echo intval(get_option('wpsr_display_days', 7)); ?>
};
</script>
