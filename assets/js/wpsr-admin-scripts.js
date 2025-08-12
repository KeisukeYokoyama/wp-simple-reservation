/**
 * WP Simple Reservation Admin Scripts
 * 管理画面用のJavaScript
 */

(function($) {
    'use strict';
    
    // DOM読み込み完了時の初期化
    $(document).ready(function() {
        console.log('Admin scripts document ready'); // デバッグ用
        initAdminScripts();
    });
    
    /**
     * 管理画面スクリプトの初期化
     */
    function initAdminScripts() {
        // スケジュール管理画面の初期化
        if ($('#wpsr-schedule-modal').length > 0) {
            initScheduleManagement();
        }
        
        // 予約管理画面の初期化
        if ($('#wpsr-reservation-modal').length > 0) {
            initReservationManagement();
        }
        
        // フォーム設定画面の初期化
        if ($('#wpsr-field-modal').length > 0) {
            console.log('Initializing form settings...'); // デバッグ用
            initFormSettings();
        } else {
            console.log('Field modal not found, modal length:', $('#wpsr-field-modal').length); // デバッグ用
        }
    }
    
    /**
     * スケジュール管理の初期化
     */
    function initScheduleManagement() {
        // 新規追加ボタン
        $('#wpsr-add-schedule').on('click', function(e) {
            e.preventDefault();
            showScheduleModal();
        });
        
        // 編集ボタン
        $('.wpsr-edit-schedule').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            loadScheduleData(id);
        });
        
        // 削除ボタン
        $('.wpsr-delete-schedule').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            
            if (confirm('このスケジュールを削除しますか？')) {
                // Ajaxで削除
                $.ajax({
                    url: wpsr_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpsr_delete_schedule',
                        schedule_id: id,
                        nonce: wpsr_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data);
                            location.reload(); // ページを再読み込み
                        } else {
                            alert(response.data || 'エラーが発生しました。');
                        }
                    },
                    error: function() {
                        alert('通信エラーが発生しました。');
                    }
                });
            }
        });
        
        // モーダルを閉じる
        $('.wpsr-modal-close, .wpsr-modal-cancel').on('click', function() {
            hideScheduleModal();
        });
        
        // モーダル外クリックで閉じる
        $('#wpsr-schedule-modal').on('click', function(e) {
            if (e.target === this) {
                hideScheduleModal();
            }
        });
        
        // 時間枠追加
        $('#wpsr-add-time-slot').on('click', function() {
            addTimeSlotInput();
        });
        
        // タブ切り替え
        $('.wpsr-tab-button').on('click', function() {
            const tabName = $(this).data('tab');
            switchTab(tabName);
        });
        
        // FullCalendar.jsの初期化
        if ($('#wpsr-calendar').length > 0) {
            initCalendar();
        }
        
        // 時間枠削除
        $(document).on('click', '.wpsr-remove-time-slot', function() {
            // 最低1つの時間枠は残す
            if ($('.wpsr-time-slot-input').length > 1) {
                $(this).closest('.wpsr-time-slot-input').remove();
            } else {
                alert('最低1つの時間枠が必要です。');
            }
        });
        
        // フォーム送信
        $('#wpsr-schedule-form').on('submit', function(e) {
            e.preventDefault();
            
            // 時間枠のデータを収集
            const timeSlots = [];
            $('input[name="time_slots[]"]').each(function() {
                const value = $(this).val();
                if (value && value.trim() !== '') {
                    timeSlots.push(value);
                }
            });
            
            // バリデーション
            if (timeSlots.length === 0) {
                alert('時間枠を入力してください。');
                return;
            }
            
            // データを準備
            const scheduleId = $('#wpsr-schedule-id').val();
            const data = {
                action: 'wpsr_save_schedule',
                nonce: wpsr_ajax.nonce,
                date: $('#wpsr-schedule-date').val(),
                schedule_id: scheduleId,
                is_available: $('#wpsr-schedule-available').is(':checked') ? 1 : 0
            };
            
            // 時間枠データを追加
            timeSlots.forEach((slot, index) => {
                data['time_slots[' + index + ']'] = slot;
            });
            
            // デバッグ用：送信するデータをログに記録
            console.log('Sending data:', data);
            
            // Ajaxで送信
            $.ajax({
                url: wpsr_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('Response:', response); // デバッグ用
                    if (response.success) {
                        const message = scheduleId ? 'スケジュールが更新されました。' : 'スケジュールが追加されました。';
                        alert(message);
                        hideScheduleModal();
                        
                        // カレンダーを更新
                        if (window.wpsrCalendar) {
                            window.wpsrCalendar.refetchEvents();
                        }
                        
                        // スケジュールリストを更新
                        const currentDate = window.wpsrCalendar ? window.wpsrCalendar.getDate() : new Date();
                        updateScheduleList(
                            currentDate.toISOString().split('T')[0].substring(0, 7) + '-01',
                            new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).toISOString().split('T')[0]
                        );
                    } else {
                        alert(response.data || 'エラーが発生しました。');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr.responseText); // デバッグ用
                    alert('通信エラーが発生しました。');
                }
            });
        });
        
        function showScheduleModal() {
            $('#wpsr-modal-title').text('スケジュール追加');
            $('#wpsr-schedule-id').val('');
            $('#wpsr-schedule-form')[0].reset();
            // 時間枠を1つに戻す
            $('#wpsr-time-slots-container').empty();
            addTimeSlotInput();
            $('#wpsr-schedule-modal').show();
        }
        
        function hideScheduleModal() {
            $('#wpsr-schedule-modal').hide();
            // フォームをリセット
            $('#wpsr-schedule-form')[0].reset();
            $('#wpsr-schedule-id').val('');
            // 時間枠を1つに戻す
            $('#wpsr-time-slots-container').empty();
            addTimeSlotInput();
        }
        
        function addTimeSlotInput() {
            const timeSlotHtml = `
                <div class="wpsr-time-slot-input">
                    <input type="time" name="time_slots[]" required>
                    <button type="button" class="button button-small wpsr-remove-time-slot">削除</button>
                </div>
            `;
            $('#wpsr-time-slots-container').append(timeSlotHtml);
        }
        
        /**
         * タブを切り替える
         */
        function switchTab(tabName) {
            // すべてのタブボタンからactiveクラスを削除
            $('.wpsr-tab-button').removeClass('active');
            
            // すべてのタブコンテンツを非表示
            $('.wpsr-tab-content').removeClass('active');
            
            // 選択されたタブをアクティブにする
            $('[data-tab="' + tabName + '"]').addClass('active');
            
            // タブ名に応じてコンテンツを表示
            if (tabName === 'settings') {
                $('#schedule-settings').addClass('active');
            } else {
                $('#' + tabName + '-schedules').addClass('active');
            }
        }
        
        /**
         * FullCalendar.jsを初期化
         */
        function initCalendar() {
            const calendarEl = document.getElementById('wpsr-calendar');
            if (!calendarEl) return;
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ja',
                height: 'auto',
                contentHeight: 280,
                aspectRatio: 1.8,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: false
                },
                buttonText: {
                    today: '今日'
                },
                dayCellDidMount: function(arg) {
                    // 土日祝日の色分け
                    const date = arg.date;
                    const dayOfWeek = date.getDay();
                    
                    // 土曜日
                    if (dayOfWeek === 6) {
                        arg.el.classList.add('fc-day-sat');
                    }
                    // 日曜日
                    else if (dayOfWeek === 0) {
                        arg.el.classList.add('fc-day-sun');
                    }
                    
                    // 祝日判定（簡易版）
                    if (isHoliday(date)) {
                        arg.el.classList.add('fc-day-holiday');
                    }
                    
                    // 予約状況の表示
                    displayScheduleStatus(arg.el, date);
                },
                datesSet: function(info) {
                    // 月が変わった時の処理
                    updateScheduleList(info.startStr, info.endStr);
                },
                dateClick: function(info) {
                    // 日付クリック時の処理
                    handleDateClick(info.dateStr);
                }
            });
            
            calendar.render();
            
            // グローバル変数に保存
            window.wpsrCalendar = calendar;
        }
        
        /**
         * 祝日判定（簡易版）
         */
        function isHoliday(date) {
            // 日本の主要祝日（簡易版）
            const month = date.getMonth() + 1;
            const day = date.getDate();
            
            // 元日
            if (month === 1 && day === 1) return true;
            // 成人の日（1月第2月曜日）
            if (month === 1 && day >= 8 && day <= 14 && date.getDay() === 1) return true;
            // 建国記念の日
            if (month === 2 && day === 11) return true;
            // 天皇誕生日
            if (month === 2 && day === 23) return true;
            // 春分の日（簡易版）
            if (month === 3 && day === 21) return true;
            // 昭和の日
            if (month === 4 && day === 29) return true;
            // 憲法記念日
            if (month === 5 && day === 3) return true;
            // みどりの日
            if (month === 5 && day === 4) return true;
            // こどもの日
            if (month === 5 && day === 5) return true;
            // 海の日（7月第3月曜日）
            if (month === 7 && day >= 15 && day <= 21 && date.getDay() === 1) return true;
            // 山の日
            if (month === 8 && day === 11) return true;
            // 敬老の日（9月第3月曜日）
            if (month === 9 && day >= 15 && day <= 21 && date.getDay() === 1) return true;
            // 秋分の日（簡易版）
            if (month === 9 && day === 23) return true;
            // スポーツの日（10月第2月曜日）
            if (month === 10 && day >= 8 && day <= 14 && date.getDay() === 1) return true;
            // 文化の日
            if (month === 11 && day === 3) return true;
            // 勤労感謝の日
            if (month === 11 && day === 23) return true;
            
            return false;
        }
        
        /**
         * 予約状況を表示
         */
        function displayScheduleStatus(cellEl, date) {
            const dateStr = date.toISOString().split('T')[0];
            
            // Ajaxで予約状況を取得
            $.ajax({
                url: wpsr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpsr_get_schedule_by_date',
                    date: dateStr,
                    nonce: wpsr_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const schedule = response.data;
                        if (schedule.is_available == 1) {
                            cellEl.classList.add('fc-day-available');
                        } else {
                            cellEl.classList.add('fc-day-unavailable');
                        }
                    }
                },
                error: function() {
                    console.log('Schedule status fetch error for date:', dateStr);
                }
            });
        }
        
        /**
         * 月切り替え時のスケジュールリスト更新
         */
        function updateScheduleList(startDate, endDate) {
            $.ajax({
                url: wpsr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpsr_get_schedules_by_month',
                    start_date: startDate,
                    end_date: endDate,
                    nonce: wpsr_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateScheduleListContent(response.data);
                    }
                },
                error: function() {
                    console.log('Schedule list update error');
                }
            });
        }
        
        /**
         * 日付クリック時の処理
         */
        function handleDateClick(dateStr) {
            console.log('Date clicked:', dateStr);
            
            // 選択された日付のスケジュールを確認
            $.ajax({
                url: wpsr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpsr_get_schedule_by_date',
                    date: dateStr,
                    nonce: wpsr_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // スケジュールが存在する場合は編集モード
                        loadScheduleDataForEdit(response.data);
                    } else {
                        // スケジュールが存在しない場合は新規追加モード
                        showScheduleModalForDate(dateStr);
                    }
                },
                error: function() {
                    console.log('Schedule check error for date:', dateStr);
                    // エラーの場合は新規追加モードで表示
                    showScheduleModalForDate(dateStr);
                }
            });
        }
        
        /**
         * スケジュールリストの内容を更新
         */
        function updateScheduleListContent(schedules) {
            const container = $('#wpsr-schedule-list-content');
            
            // 本日以降のスケジュールのみをフィルタリング
            const today = new Date().toISOString().split('T')[0];
            const futureSchedules = schedules.filter(function(schedule) {
                return schedule.date >= today;
            });
            
            if (futureSchedules.length === 0) {
                container.html('<div class="wpsr-no-schedules"><p>選択された月に今後のスケジュールはありません。</p></div>');
                return;
            }
            
            let html = '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>ID</th><th>日付</th><th>時間枠</th><th>利用可能</th><th>登録日</th><th>操作</th></tr></thead><tbody>';
            
            futureSchedules.forEach(function(schedule) {
                html += '<tr>';
                html += '<td>' + schedule.id + '</td>';
                html += '<td>' + schedule.date + '</td>';
                html += '<td>';
                
                if (schedule.time_slots) {
                    const timeSlots = JSON.parse(schedule.time_slots);
                    timeSlots.forEach(function(slot) {
                        html += '<span class="wpsr-time-slot-badge">' + slot.time + '</span>';
                    });
                }
                
                html += '</td>';
                html += '<td><span class="wpsr-availability-' + (schedule.is_available == 1 ? 'yes' : 'no') + '">' + 
                       (schedule.is_available == 1 ? '利用可能' : '利用不可') + '</span></td>';
                html += '<td>' + schedule.created_at + '</td>';
                html += '<td>';
                html += '<a href="#" class="button button-small wpsr-edit-schedule" data-id="' + schedule.id + '">編集</a> ';
                html += '<a href="#" class="button button-small wpsr-delete-schedule" data-id="' + schedule.id + '">削除</a>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            container.html(html);
            
            // イベントハンドラーを再設定
            initScheduleEventHandlers();
        }
        
        /**
         * スケジュールイベントハンドラーを初期化
         */
        function initScheduleEventHandlers() {
            // 編集ボタン
            $('.wpsr-edit-schedule').off('click').on('click', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                loadScheduleData(id);
            });
            
            // 削除ボタン
            $('.wpsr-delete-schedule').off('click').on('click', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                
                if (confirm('このスケジュールを削除しますか？')) {
                    deleteSchedule(id);
                }
            });
        }
        
        /**
         * 指定日付でスケジュール追加モーダルを表示
         */
        function showScheduleModalForDate(dateStr) {
            // フォームをリセット
            $('#wpsr-schedule-form')[0].reset();
            $('#wpsr-schedule-id').val('');
            
            // 日付を設定
            $('#wpsr-schedule-date').val(dateStr);
            
            // 時間枠を1つに戻す
            $('#wpsr-time-slots-container').empty();
            addTimeSlotInput();
            
            // 利用可能フラグをチェック状態に
            $('#wpsr-schedule-available').prop('checked', true);
            
            // モーダルタイトルを設定
            $('#wpsr-modal-title').text('スケジュール追加 - ' + formatDateForDisplay(dateStr));
            
            // モーダルを表示
            $('#wpsr-schedule-modal').show();
        }
        
        /**
         * 日付を表示用にフォーマット
         */
        function formatDateForDisplay(dateStr) {
            const date = new Date(dateStr);
            const year = date.getFullYear();
            const month = date.getMonth() + 1;
            const day = date.getDate();
            const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
            const dayName = dayNames[date.getDay()];
            
            return `${year}年${month}月${day}日(${dayName})`;
        }
        
        /**
         * 編集用にスケジュールデータを読み込み
         */
        function loadScheduleDataForEdit(schedule) {
            // フォームにデータを設定
            $('#wpsr-schedule-id').val(schedule.id);
            $('#wpsr-schedule-date').val(schedule.date);
            $('#wpsr-schedule-available').prop('checked', schedule.is_available == 1);
            
            // 時間枠を設定
            $('#wpsr-time-slots-container').empty();
            if (schedule.time_slots) {
                const timeSlots = JSON.parse(schedule.time_slots);
                timeSlots.forEach(function(slot) {
                    const timeSlotHtml = `
                        <div class="wpsr-time-slot-input">
                            <input type="time" name="time_slots[]" value="${slot.time}" required>
                            <button type="button" class="button button-small wpsr-remove-time-slot">削除</button>
                        </div>
                    `;
                    $('#wpsr-time-slots-container').append(timeSlotHtml);
                });
            }
            
            // モーダルタイトルを設定
            $('#wpsr-modal-title').text('スケジュール編集 - ' + formatDateForDisplay(schedule.date));
            
            // モーダルを表示
            $('#wpsr-schedule-modal').show();
        }
        
        /**
         * スケジュールを削除
         */
        function deleteSchedule(id) {
            $.ajax({
                url: wpsr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpsr_delete_schedule',
                    schedule_id: id,
                    nonce: wpsr_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        // カレンダーを更新
                        if (window.wpsrCalendar) {
                            window.wpsrCalendar.refetchEvents();
                        }
                        // スケジュールリストを更新
                        const currentDate = window.wpsrCalendar ? window.wpsrCalendar.getDate() : new Date();
                        updateScheduleList(
                            currentDate.toISOString().split('T')[0].substring(0, 7) + '-01',
                            new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).toISOString().split('T')[0]
                        );
                    } else {
                        alert(response.data || 'エラーが発生しました。');
                    }
                },
                error: function() {
                    alert('通信エラーが発生しました。');
                }
            });
        }
        
        /**
         * スケジュールデータを読み込む（既存の編集ボタン用）
         */
        function loadScheduleData(id) {
            console.log('Loading schedule data for ID:', id);
            
            $.ajax({
                url: wpsr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpsr_get_schedule',
                    schedule_id: id,
                    nonce: wpsr_ajax.nonce
                },
                success: function(response) {
                    console.log('Get schedule response:', response);
                    if (response.success) {
                        const schedule = response.data;
                        loadScheduleDataForEdit(schedule);
                    } else {
                        alert(response.data || 'スケジュールデータの取得に失敗しました。');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Get schedule error:', xhr.responseText);
                    alert('通信エラーが発生しました。');
                }
            });
        }
    }
    
    /**
     * 予約管理の初期化
     */
    function initReservationManagement() {
        // 編集ボタン
        $('.wpsr-edit-reservation').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            loadReservationData(id);
        });
        
        // キャンセルボタン
        $('.wpsr-cancel-reservation').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            
            if (confirm('この予約をキャンセルしますか？')) {
                cancelReservation(id);
            }
        });
        
        // モーダルを閉じる
        $('.wpsr-modal-close, .wpsr-modal-cancel').on('click', function() {
            hideReservationModal();
        });
        
        // モーダル外クリックで閉じる
        $('#wpsr-reservation-modal').on('click', function(e) {
            if (e.target === this) {
                hideReservationModal();
            }
        });
        
        // フォーム送信
        $('#wpsr-reservation-form').on('submit', function(e) {
            e.preventDefault();
            saveReservation();
        });
    }
    
    /**
     * 予約データを読み込む
     */
    function loadReservationData(id) {
        console.log('Loading reservation data for ID:', id); // デバッグ用
        
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsr_get_reservation',
                reservation_id: id,
                nonce: wpsr_ajax.nonce
            },
            success: function(response) {
                console.log('Get reservation response:', response); // デバッグ用
                if (response.success) {
                    const reservation = response.data;
                    
                    // フォームにデータを設定
                    $('#wpsr-reservation-id').val(reservation.id);
                    $('#wpsr-reservation-name').val(reservation.name);
                    $('#wpsr-reservation-email').val(reservation.email);
                    $('#wpsr-reservation-phone').val(reservation.phone);
                    $('#wpsr-reservation-date').val(reservation.schedule_date);
                    $('#wpsr-reservation-time').val(reservation.schedule_time);
                    $('#wpsr-reservation-status').val(reservation.status);
                    $('#wpsr-reservation-message').val(reservation.message);
                    
                    // モーダルを表示
                    $('#wpsr-reservation-modal-title').text('予約編集');
                    $('#wpsr-reservation-modal').show();
                } else {
                    alert(response.data || '予約データの取得に失敗しました。');
                }
            },
            error: function(xhr, status, error) {
                console.log('Get reservation error:', xhr.responseText); // デバッグ用
                alert('通信エラーが発生しました。');
            }
        });
    }
    
    /**
     * 予約を保存する
     */
    function saveReservation() {
        const formData = {
            action: 'wpsr_update_reservation',
            nonce: wpsr_ajax.nonce,
            reservation_id: $('#wpsr-reservation-id').val(),
            name: $('#wpsr-reservation-name').val(),
            email: $('#wpsr-reservation-email').val(),
            phone: $('#wpsr-reservation-phone').val(),
            schedule_date: $('#wpsr-reservation-date').val(),
            schedule_time: $('#wpsr-reservation-time').val(),
            status: $('#wpsr-reservation-status').val(),
            message: $('#wpsr-reservation-message').val()
        };
        
        console.log('Saving reservation data:', formData); // デバッグ用
        
        // バリデーション
        if (!formData.name || !formData.email || !formData.schedule_date || !formData.schedule_time) {
            alert('必須項目を入力してください。');
            return;
        }
        
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Update reservation response:', response); // デバッグ用
                if (response.success) {
                    alert(response.data);
                    hideReservationModal();
                    location.reload(); // ページを再読み込み
                } else {
                    alert(response.data || 'エラーが発生しました。');
                }
            },
            error: function(xhr, status, error) {
                console.log('Update reservation error:', xhr.responseText); // デバッグ用
                alert('通信エラーが発生しました。');
            }
        });
    }
    
    /**
     * 予約をキャンセルする
     */
    function cancelReservation(id) {
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsr_cancel_reservation',
                reservation_id: id,
                nonce: wpsr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload(); // ページを再読み込み
                } else {
                    alert(response.data || 'エラーが発生しました。');
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            }
        });
    }
    
    /**
     * 予約編集モーダルを表示
     */
    function showReservationModal() {
        $('#wpsr-reservation-modal-title').text('予約編集');
        $('#wpsr-reservation-form')[0].reset();
        $('#wpsr-reservation-id').val('');
        $('#wpsr-reservation-modal').show();
    }
    
    /**
     * 予約編集モーダルを非表示
     */
    function hideReservationModal() {
        $('#wpsr-reservation-modal').hide();
    }
    
    /**
     * フォーム設定の初期化
     */
    function initFormSettings() {
        console.log('Form settings initialized'); // デバッグ用
        
        // テンプレートフィールド追加
        $('.wpsr-add-template-field').on('click', function(e) {
            console.log('Template field button clicked'); // デバッグ用
            e.preventDefault();
            const fieldKey = $(this).data('field-key');
            const fieldData = $(this).data('field-data');
            console.log('Field key:', fieldKey, 'Field data:', fieldData); // デバッグ用
            addTemplateField(fieldKey, fieldData);
        });
        
        // カスタムフィールド追加
        $('.wpsr-add-custom-field').on('click', function(e) {
            e.preventDefault();
            const fieldType = $(this).data('field-type');
            showFieldModal('add', fieldType);
        });
        
        // フィールド編集
        $('.wpsr-edit-field').on('click', function(e) {
            e.preventDefault();
            const fieldId = $(this).data('field-id');
            loadFieldData(fieldId);
        });
        
        // フィールド削除
        $('.wpsr-delete-field').on('click', function(e) {
            e.preventDefault();
            const fieldId = $(this).data('field-id');
            
            if (confirm('このフィールドを削除しますか？')) {
                deleteField(fieldId);
            }
        });
        
        // モーダルを閉じる
        $('.wpsr-modal-close, .wpsr-modal-cancel').on('click', function() {
            hideFieldModal();
        });
        
        // モーダル外クリックで閉じる
        $('#wpsr-field-modal').on('click', function(e) {
            if (e.target === this) {
                hideFieldModal();
            }
        });
        
        // フィールドタイプ変更時の処理
        $('#wpsr-field-type').on('change', function() {
            const fieldType = $(this).val();
            toggleOptionsGroup(fieldType);
        });
        
        // フォーム送信
        $('#wpsr-field-form').on('submit', function(e) {
            e.preventDefault();
            saveField();
        });
    }
    
    /**
     * テンプレートフィールドを追加
     */
    function addTemplateField(fieldKey, fieldData) {
        const data = {
            action: 'wpsr_add_field',
            nonce: wpsr_ajax.nonce,
            field_key: fieldKey,
            field_type: fieldData.type,
            field_label: fieldData.label,
            field_placeholder: fieldData.placeholder || '',
            field_options: fieldData.options ? JSON.stringify(fieldData.options) : '',
            required: fieldData.required ? 1 : 0,
            visible: 1
        };
        
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data || 'エラーが発生しました。');
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            }
        });
    }
    
    /**
     * フィールドデータを読み込む
     */
    function loadFieldData(fieldId) {
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsr_get_field',
                field_id: fieldId,
                nonce: wpsr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const field = response.data;
                    
                    // フォームにデータを設定
                    $('#wpsr-field-id').val(field.id);
                    $('#wpsr-field-key').val(field.field_key);
                    $('#wpsr-field-type').val(field.field_type);
                    $('#wpsr-field-label').val(field.field_label);
                    $('#wpsr-field-placeholder').val(field.field_placeholder);
                    $('#wpsr-field-required').prop('checked', field.required == 1);
                    $('#wpsr-field-visible').prop('checked', field.visible == 1);
                    
                    // 選択肢の設定
                    if (field.field_options) {
                        const options = JSON.parse(field.field_options);
                        if (typeof options === 'object') {
                            const optionsText = Object.values(options).join('\n');
                            $('#wpsr-field-options').val(optionsText);
                        }
                    }
                    
                    // オプショングループの表示/非表示
                    toggleOptionsGroup(field.field_type);
                    
                    // モーダルを表示
                    $('#wpsr-field-modal-title').text('フィールド編集');
                    $('#wpsr-field-modal').show();
                } else {
                    alert(response.data || 'フィールドデータの取得に失敗しました。');
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            }
        });
    }
    
    /**
     * フィールドを保存
     */
    function saveField() {
        const fieldId = $('#wpsr-field-id').val();
        const fieldType = $('#wpsr-field-type').val();
        
        // 選択肢の処理
        let fieldOptions = '';
        if (['select', 'radio', 'checkbox'].includes(fieldType)) {
            const optionsText = $('#wpsr-field-options').val();
            if (optionsText) {
                const optionsArray = optionsText.split('\n').filter(option => option.trim() !== '');
                const options = {};
                optionsArray.forEach((option, index) => {
                    options['option_' + index] = option.trim();
                });
                fieldOptions = JSON.stringify(options);
            }
        }
        
        const data = {
            action: fieldId ? 'wpsr_update_field' : 'wpsr_add_field',
            nonce: wpsr_ajax.nonce,
            field_id: fieldId,
            field_key: $('#wpsr-field-key').val(),
            field_type: fieldType,
            field_label: $('#wpsr-field-label').val(),
            field_placeholder: $('#wpsr-field-placeholder').val(),
            field_options: fieldOptions,
            required: $('#wpsr-field-required').is(':checked') ? 1 : 0,
            visible: $('#wpsr-field-visible').is(':checked') ? 1 : 0
        };
        
        // バリデーション
        if (!data.field_key || !data.field_label) {
            alert('必須項目を入力してください。');
            return;
        }
        
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    hideFieldModal();
                    location.reload();
                } else {
                    alert(response.data || 'エラーが発生しました。');
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            }
        });
    }
    
    /**
     * フィールドを削除
     */
    function deleteField(fieldId) {
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsr_delete_field',
                field_id: fieldId,
                nonce: wpsr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data || 'エラーが発生しました。');
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            }
        });
    }
    
    /**
     * フィールドモーダルを表示
     */
    function showFieldModal(mode, fieldType = '') {
        $('#wpsr-field-modal-title').text(mode === 'add' ? 'フィールド追加' : 'フィールド編集');
        $('#wpsr-field-form')[0].reset();
        $('#wpsr-field-id').val('');
        
        if (fieldType) {
            $('#wpsr-field-type').val(fieldType);
            toggleOptionsGroup(fieldType);
        }
        
        $('#wpsr-field-modal').show();
    }
    
    /**
     * フィールドモーダルを非表示
     */
    function hideFieldModal() {
        $('#wpsr-field-modal').hide();
    }
    
    /**
     * オプショングループの表示/非表示を切り替え
     */
    function toggleOptionsGroup(fieldType) {
        const optionsGroup = $('#wpsr-field-options-group');
        if (['select', 'radio', 'checkbox'].includes(fieldType)) {
            optionsGroup.show();
        } else {
            optionsGroup.hide();
        }
    }
    
})(jQuery);
