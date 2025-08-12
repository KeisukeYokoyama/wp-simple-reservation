/**
 * WP Simple Reservation Scripts
 * 予約フォームの動的機能
 */

(function($) {
    'use strict';
    
    // グローバル変数
    let selectedDate = null;
    let selectedTime = null;
    
    // DOM読み込み完了時の初期化
    $(document).ready(function() {
        initReservationForm();
    });
    
    /**
     * 予約フォームの初期化
     */
    function initReservationForm() {
        if ($('#wpsr-reservation-form').length === 0) {
            return;
        }
        
        // 日付選択の初期化
        initDatePicker();
        
        // フォーム送信の処理
        $('#wpsr-form').on('submit', handleFormSubmit);
        
        // 動的に生成されたフィールドのイベントハンドラーを設定
        setupDynamicFieldHandlers();
    }
    
    /**
     * 動的に生成されたフィールドのイベントハンドラーを設定
     */
    function setupDynamicFieldHandlers() {
        // ラジオボタンのスタイル調整
        $(document).on('change', '.wpsr-radio input[type="radio"]', function() {
            const radioGroup = $(this).closest('.wpsr-radio-group');
            radioGroup.find('.wpsr-radio').removeClass('selected');
            $(this).closest('.wpsr-radio').addClass('selected');
        });
        
        // ラジオボタンのクリックイベント（フォールバック）
        $(document).on('click', '.wpsr-radio', function(e) {
            if (e.target.type !== 'radio') {
                const radio = $(this).find('input[type="radio"]');
                radio.prop('checked', true).trigger('change');
            }
        });
        
        // チェックボックスのスタイル調整
        $(document).on('change', '.wpsr-checkbox input[type="checkbox"]', function() {
            const checkbox = $(this);
            const label = checkbox.closest('.wpsr-checkbox');
            if (checkbox.is(':checked')) {
                label.addClass('selected');
            } else {
                label.removeClass('selected');
            }
        });
        
        // 初期状態のラジオボタンスタイルを設定
        $('.wpsr-radio input[type="radio"]:checked').each(function() {
            $(this).closest('.wpsr-radio').addClass('selected');
        });
    }
    
    /**
     * 日付選択の初期化
     */
    function initDatePicker() {
        const datePicker = $('#wpsr-date-picker');
        if (datePicker.length === 0) {
            return;
        }
        
        // 現在日から1週間分の日付を生成
        const dates = generateWeekDates();
        
        // 日付カードを生成
        dates.forEach((date, index) => {
            const dateCard = createDateCard(date, index === 0);
            datePicker.append(dateCard);
        });
        
        // 最初の日付を選択
        if (dates.length > 0) {
            selectDate(dates[0]);
        }
    }
    
    /**
     * 1週間分の日付を生成
     */
    function generateWeekDates() {
        const dates = [];
        const today = new Date();
        
        for (let i = 0; i < 7; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);
            
            dates.push({
                date: date,
                day: date.getDate(),
                month: date.getMonth() + 1,
                year: date.getFullYear(),
                weekday: getWeekdayName(date.getDay()),
                isHoliday: isHoliday(date.getDay()),
                formatted: formatDate(date)
            });
        }
        
        return dates;
    }
    
    /**
     * 日付カードを作成
     */
    function createDateCard(dateInfo, isSelected = false) {
        const card = $('<div>', {
            class: `wpsr-date-card ${isSelected ? 'selected' : ''} ${dateInfo.isHoliday ? 'holiday' : ''}`,
            'data-date': dateInfo.formatted,
            tabindex: 0
        });
        
        card.html(`
            <div class="wpsr-date-day">${dateInfo.month}/${dateInfo.day}</div>
            <div class="wpsr-date-weekday">${dateInfo.weekday}</div>
        `);
        
        // クリックイベント
        card.on('click', function() {
            selectDate(dateInfo);
        });
        
        // キーボードイベント
        card.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectDate(dateInfo);
            }
        });
        
        return card;
    }
    
    /**
     * 日付を選択
     */
    function selectDate(dateInfo) {
        // 既存の選択を解除
        $('.wpsr-date-card').removeClass('selected');
        
        // 新しい日付を選択
        $(`.wpsr-date-card[data-date="${dateInfo.formatted}"]`).addClass('selected');
        
        selectedDate = dateInfo.formatted;
        $('#wpsr-schedule-date').val(selectedDate);
        
        // 時間枠を読み込み
        loadTimeSlots(dateInfo.formatted);
    }
    
    /**
     * 時間枠を読み込み
     */
    function loadTimeSlots(date) {
        const timeSlotsContainer = $('#wpsr-time-slots');
        
        // ローディング表示
        timeSlotsContainer.html('<p class="wpsr-loading-text">読み込み中...</p>');
        
        // Ajaxで時間枠を取得
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsr_get_schedules',
                date: date,
                nonce: wpsr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayTimeSlots(response.data.time_slots);
                } else {
                    displayNoTimeSlots();
                }
            },
            error: function() {
                displayNoTimeSlots();
            }
        });
    }
    
    /**
     * 時間枠を表示
     */
    function displayTimeSlots(timeSlots) {
        const container = $('#wpsr-time-slots');
        
        if (timeSlots.length === 0) {
            container.html('<p class="wpsr-no-date">この日は予約可能な時間がありません</p>');
            return;
        }
        
        let html = '';
        timeSlots.forEach((slot, index) => {
            const isRecommended = index === 0; // 最初の時間枠をおすすめとする
            
            // 予約締切日をチェック
            const isDeadlinePassed = checkBookingDeadline(selectedDate, slot.time);
            
            if (isDeadlinePassed) {
                // 締切日を過ぎている場合は「TEL」と表示
                html += `
                    <button type="button" class="wpsr-time-slot deadline-passed" 
                            data-time="${slot.time}" disabled>
                        <span class="wpsr-time-text">${slot.time}</span>
                        <span class="wpsr-deadline-label">TEL</span>
                    </button>
                `;
            } else {
                // 通常の時間枠
                html += `
                    <button type="button" class="wpsr-time-slot ${isRecommended ? 'recommended' : ''}" 
                            data-time="${slot.time}">
                        <span class="wpsr-time-text">${slot.time}</span>
                    </button>
                `;
            }
        });
        
        container.html(html);
        
        // 時間枠のクリックイベント（締切日を過ぎていないもののみ）
        $('.wpsr-time-slot:not(.deadline-passed)').on('click', function() {
            selectTimeSlot($(this));
        });
        
        // 締切日を過ぎた時間枠のツールチップ
        $('.wpsr-time-slot.deadline-passed').on('click', function(e) {
            e.preventDefault();
            showDeadlineTooltip($(this));
        });
    }
    
    /**
     * 時間枠がない場合の表示
     */
    function displayNoTimeSlots() {
        $('#wpsr-time-slots').html('<p class="wpsr-no-date">この日は予約可能な時間がありません</p>');
    }
    
    /**
     * 時間枠を選択
     */
    function selectTimeSlot(timeSlot) {
        $('.wpsr-time-slot').removeClass('selected');
        timeSlot.addClass('selected');
        
        selectedTime = timeSlot.data('time');
        $('#wpsr-schedule-time').val(selectedTime);
    }
    
    /**
     * フォーム送信の処理
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        // バリデーション
        if (!validateForm()) {
            return;
        }
        
        // ローディング表示
        showLoading();
        
        // フォームデータを取得
        const formData = new FormData($('#wpsr-form')[0]);
        formData.append('action', 'wpsr_save_reservation');
        formData.append('nonce', wpsr_ajax.nonce);
        
        // フォームデータのログ出力
        console.log('WPSR Form Data:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ':', value);
        }
        
        // Ajaxで送信
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                console.log('WPSR Ajax Success Response:', response);
                
                if (response.success) {
                    showSuccess();
                } else {
                    console.log('WPSR Ajax Error Response:', response);
                    showError(response.data || wpsr_ajax.strings.error);
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.log('WPSR Ajax Error Details:');
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('Response Text:', xhr.responseText);
                console.log('Status Code:', xhr.status);
                
                let errorMessage = wpsr_ajax.strings.error;
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data) {
                        errorMessage = response.data;
                    }
                } catch (e) {
                    console.log('Failed to parse error response:', e);
                }
                
                showError(errorMessage);
            }
        });
    }
    
    /**
     * フォームバリデーション
     */
    function validateForm() {
        let isValid = true;
        
        // 動的に生成されたフィールドのバリデーション
        $('.wpsr-form-group').each(function() {
            const fieldGroup = $(this);
            const label = fieldGroup.find('.wpsr-label');
            const required = label.find('.wpsr-required').length > 0;
            
            if (required) {
                const input = fieldGroup.find('input, select, textarea');
                if (input.length > 0) {
                    let value = '';
                    
                    if (input.attr('type') === 'radio') {
                        // ラジオボタンの場合
                        value = fieldGroup.find('input[type="radio"]:checked').val();
                    } else if (input.attr('type') === 'checkbox') {
                        // チェックボックスの場合
                        value = fieldGroup.find('input[type="checkbox"]:checked').val();
                    } else {
                        // その他の入力フィールド
                        value = input.val();
                    }
                    
                    if (!value || value.trim() === '') {
                        const fieldName = label.text().replace('*', '').trim();
                        showFieldError(input.attr('id'), `${fieldName}を入力してください`);
                        isValid = false;
                    } else {
                        clearFieldError(input.attr('id'));
                    }
                }
            }
        });
        
        // 日時選択のチェック
        if (!selectedDate || !selectedTime) {
            showError('日時を選択してください');
            isValid = false;
        }
        
        // 予約締切日のチェック
        if (selectedDate && selectedTime) {
            const deadlineError = checkBookingDeadline(selectedDate, selectedTime);
            if (deadlineError) {
                showError(deadlineError);
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    /**
     * フィールドエラーの表示
     */
    function showFieldError(fieldId, message) {
        if (!fieldId) return;
        
        const field = $(`#${fieldId}`);
        if (field.length === 0) return;
        
        field.addClass('wpsr-error-field');
        
        // エラーメッセージを表示
        let errorElement = field.siblings('.wpsr-field-error');
        if (errorElement.length === 0) {
            errorElement = $('<div class="wpsr-field-error"></div>');
            field.after(errorElement);
        }
        errorElement.text(message);
    }
    
    /**
     * フィールドエラーのクリア
     */
    function clearFieldError(fieldId) {
        if (!fieldId) return;
        
        const field = $(`#${fieldId}`);
        if (field.length === 0) return;
        
        field.removeClass('wpsr-error-field');
        field.siblings('.wpsr-field-error').remove();
    }
    
    /**
     * ローディング表示
     */
    function showLoading() {
        $('#wpsr-form').hide();
        $('#wpsr-loading').show();
        $('#wpsr-success').hide();
        $('#wpsr-error').hide();
    }
    
    /**
     * ローディング非表示
     */
    function hideLoading() {
        $('#wpsr-loading').hide();
    }
    
    /**
     * 成功メッセージ表示
     */
    function showSuccess() {
        $('#wpsr-form').hide();
        $('#wpsr-success').show();
        $('#wpsr-error').hide();
    }
    
    /**
     * エラーメッセージ表示
     */
    function showError(message) {
        $('#wpsr-form').hide();
        $('#wpsr-loading').hide();
        $('#wpsr-success').hide();
        $('#wpsr-error').show();
        
        // エラーメッセージを詳細化
        let detailedMessage = message;
        if (typeof message === 'string' && message.includes('エラーが発生しました')) {
            detailedMessage = message + '<br><br>詳細なエラー情報は、ブラウザの開発者ツール（F12）のコンソールタブで確認できます。';
        }
        
        $('#wpsr-error-message').html(detailedMessage);
    }
    
    /**
     * 曜日名を取得
     */
    function getWeekdayName(day) {
        const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        return weekdays[day];
    }
    
    /**
     * 予約締切日のチェック
     */
    function checkBookingDeadline(selectedDate, selectedTime) {
        // 設定値を取得（サーバーサイドから渡される必要があります）
        const deadlineDays = parseInt(wpsr_ajax.deadline_days || 0);
        const deadlineHours = parseInt(wpsr_ajax.deadline_hours || 0);
        
        if (deadlineDays === 0 && deadlineHours === 0) {
            return null; // 制限なし
        }
        
        // 現在の日時を取得
        const now = new Date();
        const currentDate = now.toISOString().split('T')[0];
        const currentTime = now.toTimeString().split(' ')[0];
        
        // 予約日時をDateオブジェクトに変換
        const bookingDateTime = new Date(selectedDate + 'T' + selectedTime);
        if (isNaN(bookingDateTime.getTime())) {
            return '予約日時の形式が正しくありません。';
        }
        
        // 日付のみの比較（時間は別途チェック）
        const bookingDate = selectedDate;
        const today = currentDate;
        
        // 日数制限のチェック
        if (deadlineDays > 0) {
            // 予約日が当日かどうかをチェック
            if (bookingDate === today) {
                // 当日の場合は時間制限をチェック
                if (deadlineHours > 0) {
                    // 現在時刻から○時間後が予約時間を過ぎているかチェック
                    const currentTimeObj = new Date();
                    const deadlineTime = new Date(bookingDateTime);
                    deadlineTime.setHours(deadlineTime.getHours() - deadlineHours);
                    
                    if (currentTimeObj > deadlineTime) {
                        return 'この日時は予約締切日（' + deadlineDays + '日前かつ' + deadlineHours + '時間前まで）を過ぎているため、予約できません。';
                    }
                } else {
                    // 時間制限がない場合は当日は全て締切
                    return 'この日時は予約締切日（' + deadlineDays + '日前まで）を過ぎているため、予約できません。';
                }
            }
        } else if (deadlineHours > 0) {
            // 日数制限がない場合は時間制限のみチェック
            const currentTimeObj = new Date();
            const deadlineTime = new Date(bookingDateTime);
            deadlineTime.setHours(deadlineTime.getHours() - deadlineHours);
            
            if (currentTimeObj > deadlineTime) {
                return 'この日時は予約締切日（' + deadlineHours + '時間前まで）を過ぎているため、予約できません。';
            }
        }
        
        return null;
    }
    
    /**
     * 締切日を過ぎた時間枠のツールチップを表示
     */
    function showDeadlineTooltip(timeSlot) {
        const deadlineDays = parseInt(wpsr_ajax.deadline_days || 0);
        const deadlineHours = parseInt(wpsr_ajax.deadline_hours || 0);
        
        let message = 'この時間は予約締切日を過ぎています。';
        if (deadlineDays > 0 || deadlineHours > 0) {
            let deadlineText = '';
            if (deadlineDays > 0) {
                deadlineText += deadlineDays + '日前';
            }
            if (deadlineHours > 0) {
                if (deadlineText) {
                    deadlineText += 'かつ';
                }
                deadlineText += deadlineHours + '時間前';
            }
            message += '（締切：' + deadlineText + 'まで）';
        }
        message += 'お電話でご確認ください。';
        
        // 既存のツールチップを削除
        $('.wpsr-deadline-tooltip').remove();
        
        // 新しいツールチップを作成
        const tooltip = $('<div class="wpsr-deadline-tooltip">' + message + '</div>');
        
        // ツールチップを表示
        timeSlot.append(tooltip);
        
        // 3秒後に自動で非表示
        setTimeout(function() {
            tooltip.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * 休日かどうかを判定
     */
    function isHoliday(day) {
        return day === 0; // 日曜日を休日とする
    }
    
    /**
     * 日付をフォーマット
     */
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
})(jQuery);
