/**
 * WP Simple Reservation Admin Scripts
 * 管理画面用のJavaScript
 */

// WordPress管理画面用のajaxurlを定義
var ajaxurl = typeof wpsr_ajax !== 'undefined' ? wpsr_ajax.ajax_url : '/wp-admin/admin-ajax.php';

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
            const isAvailable = $('#wpsr-schedule-available').is(':checked');
            
            // デバッグ用：チェックボックスの状態をログに記録
            console.log('Checkbox checked:', isAvailable);
            console.log('Checkbox element:', $('#wpsr-schedule-available'));
            
            const data = {
                action: 'wpsr_save_schedule',
                nonce: wpsr_ajax.nonce,
                date: $('#wpsr-schedule-date').val(),
                schedule_id: scheduleId,
                is_available: isAvailable ? 1 : 0
            };
            
            // 時間枠データを追加
            timeSlots.forEach((slot, index) => {
                data['time_slots[' + index + ']'] = slot;
            });
            
            // 在庫数データを追加
            const maxStocks = [];
            $('input[name="max_stock[]"]').each(function() {
                maxStocks.push($(this).val());
            });
            
            maxStocks.forEach((stock, index) => {
                data['max_stock[' + index + ']'] = stock;
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
                            // カレンダーの表示を更新
                            setTimeout(function() {
                                updateAllDayCells();
                            }, 200);
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
                    <label style="margin-left: 10px; font-size: 12px; color: #666;">予約可能数:</label>
                    <input type="number" name="max_stock[]" min="0" max="10" value="1" placeholder="在庫数" style="width: 80px; margin-left: 5px;">
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
            } else if (tabName === 'display') {
                $('#display-settings').addClass('active');
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
            
            // 既存のカレンダーインスタンスがあれば破棄
            if (window.wpsrCalendar) {
                window.wpsrCalendar.destroy();
            }
            
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
                    
                    // PHPから渡された今日の日付を使用（タイムゾーン問題を解決）
                    if (typeof wpsrToday !== 'undefined') {
                        // JavaScriptの日付をローカルタイムゾーンでYYYY-MM-DD形式に変換
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        const cellDateStr = `${year}-${month}-${day}`;
                        
                        if (cellDateStr === wpsrToday) {
                            arg.el.classList.add('fc-day-today');
                            console.log('Today cell found (PHP):', cellDateStr, 'PHP Today:', wpsrToday); // デバッグ用
                        }
                    }
                    
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
                    
                    // 予約状況の表示（遅延実行で確実に処理）
                    setTimeout(function() {
                        displayScheduleStatus(arg.el, date);
                    }, 50);
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
            
            // カレンダーの強制更新
            setTimeout(function() {
                calendar.refetchEvents();
                // 全ての日付セルの表示を更新
                updateAllDayCells();
            }, 100);
        }
        
        /**
         * 全ての日付セルの表示を更新
         */
        function updateAllDayCells() {
            const dayCells = document.querySelectorAll('.fc-daygrid-day');
            dayCells.forEach(function(cell) {
                // data-date属性から日付を取得（より確実）
                const dataDate = cell.getAttribute('data-date');
                if (dataDate) {
                    // その日のスケジュール状況を確認
                    $.ajax({
                        url: wpsr_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wpsr_get_schedule_by_date',
                            date: dataDate,
                            nonce: wpsr_ajax.nonce
                        },
                        success: function(response) {
                            // 既存のクラスをクリア
                            cell.classList.remove('fc-day-available', 'fc-day-unavailable');
                            
                            if (response.success && response.data) {
                                const schedule = response.data;
                                if (schedule.is_available == 1) {
                                    cell.classList.add('fc-day-available');
                                } else {
                                    cell.classList.add('fc-day-unavailable');
                                }
                            }
                        },
                        error: function() {
                            console.log('Error updating cell for date:', dataDate);
                        }
                    });
                }
            });
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
            // 日本時間で日付を取得
            const japanTime = new Date(date.getTime() + (9 * 60 * 60 * 1000));
            const dateStr = japanTime.toISOString().split('T')[0];
            
            // 既存のクラスをクリア
            cellEl.classList.remove('fc-day-available', 'fc-day-unavailable');
            
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
                        console.log('Schedule for', dateStr, ':', schedule);
                        
                        if (schedule.is_available == 1) {
                            cellEl.classList.add('fc-day-available');
                            console.log('Added available class for', dateStr);
                        } else {
                            cellEl.classList.add('fc-day-unavailable');
                            console.log('Added unavailable class for', dateStr);
                        }
                    } else {
                        console.log('No schedule data for', dateStr);
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
            // 日本時間で日付を取得
            const date = new Date(dateStr);
            const japanTime = new Date(date.getTime() + (9 * 60 * 60 * 1000));
            const japanDateStr = japanTime.toISOString().split('T')[0];
            
            console.log('Date clicked:', dateStr, '-> Japan time:', japanDateStr);
            
            // 選択された日付のスケジュールを確認
            $.ajax({
                url: wpsr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpsr_get_schedule_by_date',
                    date: japanDateStr,
                    nonce: wpsr_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // スケジュールが存在する場合は編集モード
                        loadScheduleDataForEdit(response.data);
                    } else {
                        // スケジュールが存在しない場合は新規追加モード
                        showScheduleModalForDate(japanDateStr);
                    }
                },
                error: function() {
                    console.log('Schedule check error for date:', japanDateStr);
                    // エラーの場合は新規追加モードで表示
                    showScheduleModalForDate(japanDateStr);
                }
            });
        }
        
        /**
         * スケジュールリストの内容を更新
         */
        function updateScheduleListContent(schedules) {
            const container = $('#wpsr-schedule-list-content');
            
            // 本日以降のスケジュールのみをフィルタリング（日本時間）
            const now = new Date();
            const japanTime = new Date(now.getTime() + (9 * 60 * 60 * 1000));
            const today = japanTime.toISOString().split('T')[0];
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
                
                if (schedule.time_slots_with_stock) {
                    const timeSlots = JSON.parse(schedule.time_slots_with_stock);
                    timeSlots.forEach(function(slot) {
                        let stockInfo = '';
                        let cssClass = 'wpsr-time-slot-badge';
                        
                        if (slot.max_stock !== undefined && slot.current_stock !== undefined) {
                            if (slot.current_stock <= 0) {
                                stockInfo = '（満席）';
                                cssClass += ' wpsr-time-slot-full';
                            } else {
                                stockInfo = '（残り' + slot.current_stock + '）';
                            }
                        }
                        html += '<span class="' + cssClass + '">' + slot.time + stockInfo + '</span>';
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
            // デバッグ用：スケジュールデータをログに記録
            console.log('Loading schedule data for edit:', schedule);
            
            // フォームにデータを設定
            $('#wpsr-schedule-id').val(schedule.id);
            $('#wpsr-schedule-date').val(schedule.date);
            $('#wpsr-schedule-available').prop('checked', schedule.is_available == 1);
            
            // デバッグ用：チェックボックスの状態をログに記録
            console.log('is_available value:', schedule.is_available);
            console.log('Checkbox checked after setting:', $('#wpsr-schedule-available').is(':checked'));
            
            // 時間枠を設定
            $('#wpsr-time-slots-container').empty();
            if (schedule.time_slots_with_stock) {
                const timeSlots = JSON.parse(schedule.time_slots_with_stock);
                timeSlots.forEach(function(slot) {
                    const maxStock = slot.max_stock || 1;
                    const timeSlotHtml = `
                        <div class="wpsr-time-slot-input">
                            <input type="time" name="time_slots[]" value="${slot.time}" required>
                            <label style="margin-left: 10px; font-size: 12px; color: #666;">予約可能数:</label>
                            <input type="number" name="max_stock[]" min="0" max="10" value="${maxStock}" placeholder="在庫数" style="width: 80px; margin-left: 5px;">
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
                            // カレンダーの表示を更新
                            setTimeout(function() {
                                updateAllDayCells();
                            }, 200);
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
        // イベントハンドラーを初期化
        initReservationEventHandlers();
    }
    
    /**
     * 予約イベントハンドラーを初期化
     */
    function initReservationEventHandlers() {
        // 編集ボタン
        $('.wpsr-edit-reservation').off('click').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            loadReservationData(id);
        });
        

        
        // 削除ボタン
        $('.wpsr-delete-reservation').off('click').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            if (confirm('予約者「' + name + '」の予約を完全に削除しますか？\n\nこの操作は取り消すことができません。')) {
                deleteReservation(id);
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
     * 予約を削除する
     */
    function deleteReservation(id) {
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsr_delete_reservation',
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
        
        // モーダルの初期状態を確認
        console.log('Modal initial state:', {
            exists: $('#wpsr-field-modal').length > 0,
            display: $('#wpsr-field-modal').css('display'),
            zIndex: $('#wpsr-field-modal').css('z-index')
        });
        
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
            console.log('Edit field button clicked'); // デバッグ用
            e.preventDefault();
            const fieldId = $(this).data('field-id');
            console.log('Field ID:', fieldId); // デバッグ用
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
            
            // 性別フィールドの場合はプレースホルダーを無効化
            if (fieldType === 'gender') {
                $('#wpsr-field-placeholder').prop('disabled', true).val('');
            } else {
                $('#wpsr-field-placeholder').prop('disabled', false);
            }
        });
        
        // フォーム送信
        $('#wpsr-field-form').on('submit', function(e) {
            e.preventDefault();
            saveField();
        });
        
        // テーブル更新ボタン
        $('#wpsr-update-table').on('click', function(e) {
            e.preventDefault();
            updateReservationsTable();
        });
        
        // フィールド並び替え機能の初期化
        initFieldSorting();
    }
    
    /**
     * フィールド並び替え機能を初期化
     */
    function initFieldSorting() {
        console.log('Initializing field sorting...'); // デバッグ用
        
        // jQuery UI Sortableが利用可能かチェック
        if (typeof $.fn.sortable === 'undefined') {
            console.log('jQuery UI Sortable not available, loading...'); // デバッグ用
            // jQuery UIを動的に読み込み
            loadJQueryUI();
            return;
        }
        
        // 並び替え機能を初期化
        $('#wpsr-fields-list').sortable({
            handle: '.wpsr-sort-handle',
            axis: 'y',
            cursor: 'move',
            opacity: 0.8,
            helper: function(e, tr) {
                // ヘルパー要素のスタイルを設定
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function(index) {
                    $(this).width($originals.eq(index).width());
                });
                return $helper;
            },
            start: function(event, ui) {
                console.log('Sorting started'); // デバッグ用
                ui.placeholder.height(ui.item.height());
            },
            update: function(event, ui) {
                console.log('Sorting updated'); // デバッグ用
                updateFieldOrder();
            }
        });
        
        console.log('Field sorting initialized successfully'); // デバッグ用
    }
    
    /**
     * jQuery UIを動的に読み込み
     */
    function loadJQueryUI() {
        // jQuery UI CSS
        if (!$('link[href*="jquery-ui"]').length) {
            $('head').append('<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">');
        }
        
        // jQuery UI JavaScript
        $.getScript('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js')
            .done(function() {
                console.log('jQuery UI loaded successfully'); // デバッグ用
                initFieldSorting();
            })
            .fail(function() {
                console.error('Failed to load jQuery UI'); // デバッグ用
                alert('並び替え機能の読み込みに失敗しました。');
            });
    }
    
    /**
     * フィールドの並び順を更新
     */
    function updateFieldOrder() {
        console.log('Updating field order...'); // デバッグ用
        
        const newOrder = [];
        $('#wpsr-fields-list tr').each(function(index) {
            const fieldId = $(this).data('field-id');
            console.log('Processing row', index, 'field_id:', fieldId); // デバッグ用
            if (fieldId) {
                newOrder.push({
                    field_id: fieldId,
                    sort_order: index + 1
                });
            }
        });
        
        console.log('New order:', newOrder); // デバッグ用
        console.log('New order JSON:', JSON.stringify(newOrder)); // デバッグ用
        
        const ajaxData = {
            action: 'wpsr_update_field_order',
            nonce: wpsr_ajax.nonce,
            field_order: JSON.stringify(newOrder)
        };
        console.log('Ajax data:', ajaxData); // デバッグ用
        console.log('Ajax URL:', wpsr_ajax.ajax_url); // デバッグ用
        console.log('Nonce:', wpsr_ajax.nonce); // デバッグ用
        
        // Ajaxで並び順を更新
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('Update order response:', response); // デバッグ用
                if (response.success) {
                    // 成功時は順序番号を更新
                    $('#wpsr-fields-list tr').each(function(index) {
                        $(this).find('td:first').text(index + 1);
                        $(this).attr('data-sort-order', index + 1);
                    });
                    console.log('Field order updated successfully'); // デバッグ用
                } else {
                    console.error('Failed to update field order:', response.data); // デバッグ用
                    alert('並び順の更新に失敗しました。');
                    location.reload(); // ページを再読み込み
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error updating field order:', xhr.responseText); // デバッグ用
                alert('並び順の更新中にエラーが発生しました。');
                location.reload(); // ページを再読み込み
            }
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
        console.log('loadFieldData function started for field ID:', fieldId); // デバッグ用
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsr_get_field',
                field_id: fieldId,
                nonce: wpsr_ajax.nonce
            },
            success: function(response) {
                console.log('Get field response:', response); // デバッグ用
                try {
                    if (response.success) {
                        const field = response.data;
                        console.log('Field data:', field); // デバッグ用
                    
                    // フォームにデータを設定
                    $('#wpsr-field-id').val(field.id);
                    $('#wpsr-field-key').val(field.field_key);
                    $('#wpsr-field-type').val(field.field_type);
                    $('#wpsr-field-label').val(field.field_label);
                    $('#wpsr-field-placeholder').val(field.field_placeholder);
                    $('#wpsr-field-required').prop('checked', field.required == 1);
                    $('#wpsr-field-visible').prop('checked', field.visible == 1);
                    
                    // 選択肢の設定
                    console.log('Starting field options processing...'); // デバッグ用
                    if (field.field_options) {
                        try {
                            console.log('Field options raw:', field.field_options);
                            
                            // 二重エスケープの可能性を考慮した処理
                            let optionsText = field.field_options;
                            
                            // まず通常のJSON.parseを試行
                            try {
                                const options = JSON.parse(optionsText);
                                console.log('Parsed options (first attempt):', options);
                                if (typeof options === 'object') {
                                    const values = Object.values(options);
                                    console.log('Options values:', values);
                                    $('#wpsr-field-options').val(values.join('\n'));
                                    console.log('Options set successfully'); // デバッグ用
                                }
                            } catch (firstError) {
                                console.log('First JSON.parse failed:', firstError.message);
                            }
                            
                            // 二重エスケープされている可能性がある場合の処理
                            try {
                                // エスケープされた文字列を復元
                                const unescaped = optionsText.replace(/\\"/g, '"').replace(/\\\\/g, '\\');
                                console.log('Unescaped options:', unescaped);
                                const options = JSON.parse(unescaped);
                                console.log('Parsed options (second attempt):', options);
                                if (typeof options === 'object') {
                                    const values = Object.values(options);
                                    console.log('Options values (second attempt):', values);
                                    $('#wpsr-field-options').val(values.join('\n'));
                                    console.log('Options set successfully (second attempt)'); // デバッグ用
                                }
                            } catch (secondError) {
                                console.log('Second JSON.parse failed:', secondError.message);
                            }
                            
                            // 最後の手段：手動でパース
                            try {
                                // 単純な文字列として処理
                                const cleanText = optionsText.replace(/[{}"]/g, '').replace(/\\/g, '');
                                const pairs = cleanText.split(',');
                                const values = [];
                                pairs.forEach(pair => {
                                    const colonIndex = pair.indexOf(':');
                                    if (colonIndex > 0) {
                                        const value = pair.substring(colonIndex + 1).trim();
                                        if (value) {
                                            values.push(value);
                                        }
                                    }
                                });
                                console.log('Manual parsed values:', values);
                                $('#wpsr-field-options').val(values.join('\n'));
                                console.log('Options set successfully (manual)'); // デバッグ用
                            } catch (manualError) {
                                console.error('Manual parsing failed:', manualError);
                                $('#wpsr-field-options').val('');
                            }
                            
                        } catch (error) {
                            console.error('Error parsing field options:', error);
                            // エラーが発生した場合は空文字を設定
                            $('#wpsr-field-options').val('');
                        }
                    } else {
                        console.log('No field options found');
                        $('#wpsr-field-options').val('');
                    }
                    console.log('Field options processing completed'); // デバッグ用
                    
                    // オプショングループの表示/非表示
                    console.log('Starting toggleOptionsGroup for field type:', field.field_type); // デバッグ用
                    toggleOptionsGroup(field.field_type);
                    console.log('toggleOptionsGroup completed'); // デバッグ用
                    
                    // システム必須フィールドの説明文制御
                    const isSystemRequired = field.field_key === 'name' || field.field_key === 'email';
                    toggleSystemFieldDescriptions(isSystemRequired);
                    
                    // システム必須フィールドの場合は入力フィールドを無効化
                    if (isSystemRequired) {
                        $('#wpsr-field-key').prop('disabled', true);
                        $('#wpsr-field-type').prop('disabled', true);
                        $('#wpsr-field-required').prop('disabled', true);
                        $('#wpsr-field-visible').prop('disabled', true);
                    } else {
                        $('#wpsr-field-key').prop('disabled', false);
                        $('#wpsr-field-type').prop('disabled', false);
                        $('#wpsr-field-required').prop('disabled', false);
                        $('#wpsr-field-visible').prop('disabled', false);
                    }
                    
                    // モーダルを表示
                    console.log('Starting modal display process...'); // デバッグ用
                    console.log('Setting modal title to: フィールド編集'); // デバッグ用
                    $('#wpsr-field-modal-title').text('フィールド編集');
                    console.log('Modal element exists:', $('#wpsr-field-modal').length > 0); // デバッグ用
                    console.log('Modal current display:', $('#wpsr-field-modal').css('display')); // デバッグ用
                    $('#wpsr-field-modal').show();
                    console.log('Modal display after show():', $('#wpsr-field-modal').css('display')); // デバッグ用
                    
                    // 強制的にモーダルを表示
                    setTimeout(function() {
                        console.log('Executing forced modal display...'); // デバッグ用
                        $('#wpsr-field-modal').css({
                            'display': 'block',
                            'z-index': '999999'
                        });
                        console.log('Modal forced display:', $('#wpsr-field-modal').css('display')); // デバッグ用
                    }, 100);
                    console.log('Modal display process completed'); // デバッグ用
                } else {
                    console.error('Get field failed:', response.data); // デバッグ用
                    alert(response.data || 'フィールドデータの取得に失敗しました。');
                }
                } catch (error) {
                    console.error('Error in loadFieldData success handler:', error); // デバッグ用
                    alert('フィールドデータの処理中にエラーが発生しました。');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', xhr.responseText); // デバッグ用
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
        
        // チェックボックスの値を確実に取得
        const requiredChecked = $('#wpsr-field-required').is(':checked');
        const visibleChecked = $('#wpsr-field-visible').is(':checked');
        
        const data = {
            action: fieldId ? 'wpsr_update_field' : 'wpsr_add_field',
            nonce: wpsr_ajax.nonce,
            field_id: fieldId,
            field_key: $('#wpsr-field-key').val(),
            field_type: fieldType,
            field_label: $('#wpsr-field-label').val(),
            field_placeholder: $('#wpsr-field-placeholder').val(),
            field_options: fieldOptions,
            required: requiredChecked ? 1 : 0,
            visible: visibleChecked ? 1 : 0
        };
        
        // チェックボックスがチェックされていない場合でも、明示的に0を送信
        if (!requiredChecked) {
            data.required = 0;
        }
        if (!visibleChecked) {
            data.visible = 0;
        }
        
        // デバッグ用：送信データをログに記録
        console.log('Sending field data:', data);
        console.log('Required checkbox checked:', $('#wpsr-field-required').is(':checked'));
        console.log('Visible checkbox checked:', $('#wpsr-field-visible').is(':checked'));
        
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
        
        // 新規追加時はフィールドキーを空にして、システム必須フィールドの判定を回避
        if (mode === 'add') {
            $('#wpsr-field-key').val('').prop('disabled', false);
            $('#wpsr-field-type').prop('disabled', false);
            $('#wpsr-field-required').prop('disabled', false);
            $('#wpsr-field-visible').prop('disabled', false);
            
            // フィールドタイプに応じてデフォルト値を設定
            if (fieldType) {
                setDefaultFieldValues(fieldType);
                // 性別フィールドの場合はプレースホルダーを無効化
                if (fieldType === 'gender') {
                    $('#wpsr-field-placeholder').prop('disabled', true).val('');
                } else {
                    $('#wpsr-field-placeholder').prop('disabled', false);
                }
            }
            
            // 新規追加時は説明文を非表示
            toggleSystemFieldDescriptions(false);
        }
        
        if (fieldType) {
            $('#wpsr-field-type').val(fieldType);
            toggleOptionsGroup(fieldType);
        }
        
        $('#wpsr-field-modal').show();
    }
    
    /**
     * フィールドタイプに応じてデフォルト値を設定
     */
    function setDefaultFieldValues(fieldType) {
        let fieldKey = '';
        let label = '';
        let placeholder = '';
        
        // フィールドキーをハイブリッド方式で生成
        fieldKey = generateFieldKey(fieldType);
        
        switch (fieldType) {
            case 'text':
                label = '';
                placeholder = '';
                break;
            case 'radio':
                label = '';
                placeholder = '';
                break;
            case 'select':
                label = '';
                placeholder = '';
                break;
            case 'checkbox':
                label = '';
                placeholder = '';
                break;
            case 'textarea':
                label = '';
                placeholder = '';
                break;
            case 'tel':
                label = '電話番号';
                placeholder = '03-0000-0000';
                break;
            case 'date':
                label = '';
                placeholder = '';
                break;
            case 'gender':
                label = '性別';
                placeholder = '';
                break;
            default:
                label = '';
                placeholder = '';
        }
        
        $('#wpsr-field-key').val(fieldKey);
        $('#wpsr-field-label').val(label);
        $('#wpsr-field-placeholder').val(placeholder);
    }
    
    /**
     * フィールドキーをハイブリッド方式で生成
     * 例: text-a7b3c9, radio-x2y8z1
     */
    function generateFieldKey(prefix) {
        const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let result = prefix + '-';
        for (let i = 0; i < 6; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
    
    /**
     * フィールドモーダルを非表示
     */
    function hideFieldModal() {
        $('#wpsr-field-modal').hide();
    }
    
    /**
     * システム必須フィールドの説明文の表示/非表示を制御
     */
    function toggleSystemFieldDescriptions(show) {
        // 説明文の要素を取得
        const descriptions = $('.wpsr-field-description');
        
        if (show) {
            // 説明文を表示
            descriptions.show();
        } else {
            // 説明文を非表示
            descriptions.hide();
        }
    }
    
    /**
     * オプショングループの表示/非表示を切り替え
     */
    function toggleOptionsGroup(fieldType) {
        console.log('Toggle options group for field type:', fieldType); // デバッグ用
        const optionsGroup = $('#wpsr-field-options-group');
        console.log('Options group element:', optionsGroup.length > 0 ? 'found' : 'not found'); // デバッグ用
        
        if (['select', 'radio', 'checkbox'].includes(fieldType)) {
            console.log('Showing options group'); // デバッグ用
            optionsGroup.show();
        } else if (fieldType === 'gender') {
            console.log('Hiding options group for gender field'); // デバッグ用
            optionsGroup.hide();
        } else {
            console.log('Hiding options group'); // デバッグ用
            optionsGroup.hide();
        }
    }
    
    /**
     * 予約テーブルを更新
     */
    function updateReservationsTable() {
        if (!confirm('データベーステーブルを更新しますか？\n\nこの操作により、フィールドの追加・削除に応じてテーブル構造が更新されます。')) {
            return;
        }
        
        console.log('Updating reservations table...'); // デバッグ用
        
        $.ajax({
            url: wpsr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsr_update_reservations_table',
                nonce: wpsr_ajax.nonce
            },
            success: function(response) {
                console.log('Update table response:', response); // デバッグ用
                if (response.success) {
                    alert(response.data);
                } else {
                    alert(response.data || 'テーブル更新に失敗しました。');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error updating table:', xhr.responseText); // デバッグ用
                alert('テーブル更新中にエラーが発生しました。');
            }
        });
    }
    
})(jQuery);
