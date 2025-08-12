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
    <form id="wpsr-form" method="post">
        <?php wp_nonce_field('wpsr_nonce', 'wpsr_nonce'); ?>
        
        <!-- 個人情報入力セクション -->
        <div class="wpsr-section">
            <h3 class="wpsr-section-title">個人情報入力</h3>
            
            <!-- 動的に生成されたフィールド -->
            <?php echo $form_manager->generate_form_html(); ?>
        </div>
        
        <!-- 面談予約セクション -->
        <div class="wpsr-section">
            <h3 class="wpsr-section-title">面談予約</h3>
            <p class="wpsr-section-description">面談を行える日時を教えて下さい</p>
            
            <div class="wpsr-notice">
                <p class="wpsr-notice-text">※仮予約ではなく、選択したお時間で予約完了となりますので、確実にご参加いただける日程をご選択ください。</p>
            </div>
            
            <!-- 日付選択 -->
            <div class="wpsr-form-group">
                <label class="wpsr-label">日付選択 <span class="wpsr-required">*</span></label>
                <div class="wpsr-date-picker" id="wpsr-date-picker">
                    <!-- JavaScriptで動的に生成 -->
                </div>
            </div>
            
            <!-- 時間帯選択 -->
            <div class="wpsr-form-group">
                <label class="wpsr-label">時間帯選択 <span class="wpsr-required">*</span></label>
                <div class="wpsr-time-slots" id="wpsr-time-slots">
                    <p class="wpsr-no-date">日付を選択してください</p>
                </div>
            </div>
        </div>
        
        <!-- 確認・補足情報セクション -->
        <div class="wpsr-section">
            <div class="wpsr-info-box">
                <div class="wpsr-info-content">
                    <p class="wpsr-info-text">無料面談は入会のためのステップではなく、あなたの結婚の悩みを解消する場です。まずはお気軽にお問い合わせください。</p>
                    <div class="wpsr-info-icon">
                        <!-- アイコンはCSSで実装 -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 送信ボタン -->
        <div class="wpsr-form-group">
            <button type="submit" class="wpsr-submit-btn" id="wpsr-submit">
                <span class="wpsr-submit-text">ご入力内容の確認</span>
                <span class="wpsr-submit-icon">→</span>
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
