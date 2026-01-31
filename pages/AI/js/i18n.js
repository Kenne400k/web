// =================== INTERNATIONALIZATION (i18n) ===================
// Language system for KingCong TTS Tool

const i18n = {
    currentLang: localStorage.getItem('app_language') || 'vi',

    translations: {
        vi: {
            // Common
            dashboard: 'Trang chủ',
            text_to_speech: 'Chuyển đổi văn bản thành giọng nói',
            voice_cloning: 'Giọng nhân bản',
            pro_tool: 'Công cụ nâng cao',
            logout: 'Đăng xuất',
            credits: 'Credits',

            // Voice Cloning Page
            vc_banner: 'Nhân bản bất cứ giọng nói nào mà bạn muốn, không còn nỗi lo không tìm thấy giọng phù hợp. (sử dụng Minimax)',
            vc_voice_name: 'Tên Giọng Nói <span>*</span>',
            vc_voice_name_placeholder: 'VD: Giọng đọc tin tức',
            vc_upload_audio: 'Tải Lên File Âm Thanh <span>*</span>',
            vc_drag_drop: 'Kéo thả file hoặc nhấn để chọn',
            vc_duration_hint: 'Thời lượng 10s - 2 phút, tối đa 20MB',
            vc_format_hint: 'Hiện chỉ hỗ trợ: .mp3',
            vc_remove_file: 'Xóa file',
            vc_select_language: 'Chọn Ngôn Ngữ',
            vc_auto_detect: 'Tự xác định',
            vc_search: 'Tìm kiếm...',
            vc_select_gender: 'Chọn Giới Tính',
            vc_male: 'Nam',
            vc_female: 'Nữ',
            vc_preview_text: 'Văn Bản Nghe Trước (Tùy chọn)',
            vc_preview_placeholder: 'Nhập văn bản (Nếu để trống, hệ thống sẽ tự dùng mẫu câu mặc định)...',
            vc_chars: 'ký tự',
            vc_remove_noise: 'Loại Bỏ Tiếng Ồn',
            vc_clone_btn: 'Nhân Bản',
            vc_library_title: 'Thư viện giọng nhân bản',
            vc_voices: 'giọng',
            vc_refresh: 'Làm mới',
            vc_loading: 'Đang tải danh sách...',
            vc_confirm_delete: 'Xác nhận xóa giọng',
            vc_confirm_delete_msg: 'Bạn có chắc chắn muốn xóa giọng',
            vc_cannot_undo: 'Hành động này không thể hoàn tác.',
            vc_cancel: 'Hủy',
            vc_delete: 'Xóa',
            vc_processing: 'Đang xử lý...',
            vc_processing_hint: 'AI đang học giọng nói của bạn. Quá trình này có thể mất 1-2 phút.',
            vc_notification: 'Thông báo',
            vc_use_voice: 'Sử dụng',
            vc_play: 'Phát',
            vc_stop: 'Dừng',
            vc_no_voices: 'Chưa có giọng nào',
            vc_no_voices_hint: 'Tạo giọng nhân bản đầu tiên của bạn',
            vc_select_model: 'Chọn Mô Hình',
            vc_trim_audio: 'Cắt Audio',
            vc_trim_range: 'Thời gian bắt đầu - kết thúc',
            vc_trim_start: 'Bắt đầu (giây)',
            vc_trim_end: 'Kết thúc (giây)',
            vc_trim_duration: 'Thời lượng đoạn cắt:',
            vc_seconds: 'giây',
            vc_trim_use: 'Cắt & Sử dụng',

            // Dashboard
            db_welcome: 'Chào mừng trở lại,',
            db_overview: 'Tổng quan',
            db_total_tasks: 'Tổng số tác vụ',
            db_credits_remaining: 'Credits còn lại',
            db_pending_tasks: 'Tác vụ đang chờ',
            db_success_rate: 'Tỷ lệ thành công',
            db_recent_tasks: 'Tác vụ gần đây',
            db_view_all: 'Xem tất cả',
            db_new_task: 'Tác vụ mới',
            db_loading: 'Đang tải dashboard...',
            db_tagline: 'Đây là những gì đang xảy ra với các dự án lồng tiếng của bạn hôm nay.',
            db_tbl_id: 'ID Tác vụ',
            db_tbl_preview: 'Xem trước',
            db_tbl_voice: 'Giọng đọc',
            db_tbl_status: 'Trạng thái',
            db_tbl_cost: 'Chi phí',
            db_tbl_date: 'Ngày tạo',
            db_tbl_loading: 'Đang tải tác vụ...',

            // TTS Page
            tts_title: 'Chuyển đổi văn bản thành giọng nói',
            tts_enter_text: 'Nhập văn bản của bạn tại đây.',
            tts_no_limit: 'Không giới hạn ký tự văn bản.',
            tts_select_voice: 'Chọn giọng nói...',
            tts_generate: 'Tạo Giọng Nói',
            tts_settings: 'Cài đặt',
            tts_history: 'Lịch sử',
            tts_loading: 'Đang tải...',
            tts_wait_moment: 'Vui lòng đợi trong giây lát',
            tts_processing: 'Đang xử lý...',
            tts_tip_minimax: 'Tiếng Việt nên sử dụng <b style="color:#fff">Minimax</b>',
            tts_tip_break: '💡 <code class="es-code" style="background:#333;color:#4ade80;padding:2px 6px;border-radius:4px">&lt;break time="0.5s" /&gt;</code> để nghỉ 0.5 giây',
            tts_tip_drag: '📁 Kéo thả tệp <b style="color:#fff">.txt, .srt</b> vào đây',
            tts_upload: 'Tải lên',
            tts_upload_file: 'Tải lên tệp (.txt .srt .zip)',
            tts_upload_folder: 'Tải lên thư mục',
            tts_clear: 'Xóa',
            tts_clear_title: 'Xóa toàn bộ văn bản',
            tts_fee_info: 'Bao gồm phí xử lý',
            tts_fee_processing: '• Bao gồm phí xử lý: <span style="color: #fff; font-weight: 600;">x1.12</span>',
            tts_fee_srt: '• Định dạng SRT đắt hơn: <span style="color: #fff; font-weight: 600;">x1.2</span>',
            tts_provider_eleven_desc: 'Giọng đọc tự nhiên và nhiều người dùng hơn.',
            tts_provider_minimax_desc: 'Nói tốt tiếng Việt. Có thể sử dụng giọng nhân bản.',
            tts_provider_kingcong_desc: 'Giọng đọc KingCong chất lượng cao (voice clone).',
            tts_kingcong_model: 'Chọn Mô Hình KingCong',
            tts_punct_delay: 'Độ trễ dấu câu',
            tts_punct_delay_info: 'Tự động thêm khoảng dừng sau các dấu câu',
            tts_punct_beta_warn: 'Tính năng này đang trong giai đoạn thử nghiệm, có thể không hoạt động ổn định.',
            tts_punct_period: 'Dấu chấm',
            tts_punct_comma: 'Dấu phẩy',
            tts_punct_exclamation: 'Dấu chấm than',
            tts_punct_question: 'Dấu hỏi',
            tts_delay: 'Khoảng dừng',
            tts_quick_select: 'Chọn nhanh:',
            tts_or_enter: 'Hoặc nhập:',
            tts_add: 'Thêm',
            tts_refresh: 'Làm mới',
            tts_details: 'Chi tiết',
            tts_select_model: 'Chọn mô hình ngôn ngữ',
            tts_speed: 'Tốc độ',
            tts_pitch: 'Cao độ',
            tts_volume: 'Âm lượng',
            tts_stability: 'Độ ổn định',
            tts_similarity: 'Độ tương đồng',
            tts_style_exaggeration: 'Phóng đại phong cách',
            tts_voice_boost: 'Tăng cường giọng nói',
            tts_subtitles: 'Phụ đề',
            tts_subtitle_fee: '+15% phí',
            tts_reset: 'Đặt lại',
            tts_model_info: 'Thông tin Mô hình',
            tts_history_loading: 'Đang tải...',
            tts_history_end: 'Đã hiển thị toàn bộ lịch sử',
            tts_vmodal_default: 'Mặc định',
            tts_vmodal_cloned: 'Giọng nhân bản',
            tts_vmodal_library: 'Thư viện giọng nói',
            tts_vmodal_favorites: 'Yêu thích',
            tts_vmodal_search: 'Tìm kiếm giọng đọc...',
            tts_vmodal_sort_most_used: 'Dùng nhiều nhất',
            tts_vmodal_sort_trending: 'Xu hướng',
            tts_vmodal_sort_newest: 'Mới nhất',
            tts_vmodal_sort_chars: 'Ký tự được tạo',
            tts_vmodal_filter_lang: 'Ngôn ngữ',
            tts_vmodal_filter_gender: 'Giới tính',
            tts_vmodal_filter_age: 'Độ tuổi',
            tts_vmodal_all: 'Tất cả',
            tts_vmodal_male: 'Nam',
            tts_vmodal_female: 'Nữ',
            tts_vmodal_young: 'Trẻ',
            tts_vmodal_middle_aged: 'Trung niên',
            tts_vmodal_old: 'Lớn tuổi',
            tts_vmodal_loading: 'Đang tải danh sách giọng...',
            tts_vmodal_tab_default: 'Mặc định',
            tts_vmodal_tab_cloned: 'Giọng nhân bản',
            tts_vmodal_tab_library: 'Thư viện giọng nói',
            tts_vmodal_tab_favorites: 'Yêu thích',
            tts_vmodal_filter_provider: 'Nhà cung cấp',
            tts_vmodal_filter_style: 'Phong cách',
            tts_vmodal_provider_maziao: 'Maziao (Khuyên dùng)',
            tts_vmodal_style_narrative: 'Kể chuyện',
            tts_vmodal_style_conversational: 'Hội thoại',
            tts_vmodal_style_news: 'Tin tức',
            tts_vmodal_style_audiobook: 'Sách nói',
            tts_vmodal_style_education: 'Giáo dục',
            tts_vmodal_use: 'Dùng',
            tts_vmodal_preview: 'Nghe thử',
            tts_vmodal_favorite: 'Yêu thích',
            tts_vmodal_delete_voice: 'Xóa giọng',
            tts_vmodal_filter_accent: 'Giọng',
            tts_vmodal_reset: 'Đặt lại',
            tts_vmodal_clone_new: 'Nhân bản giọng mới',
            tts_vmodal_clone_desc: 'Tạo giọng nói của riêng bạn',
            tts_del_task_title: 'Xóa tác vụ',
            tts_del_task_warn: 'Bạn có chắc chắn muốn xóa tác vụ này? Hành động này không thể hoàn tác.',
            tts_del_task_note: 'Nếu tác vụ bị treo quá 24h sẽ được hoàn tín dụng.',
            tts_del_task_cancel: 'Hủy',
            tts_del_task_confirm: 'Xóa',
            tts_history_modal_title: 'Lịch sử',
            tts_history_modal_refresh: 'Làm mới',
            tts_history_modal_delete: 'Xóa',
            tts_history_modal_download_audio: 'Tải xuống Audio',
            tts_history_modal_download_srt: 'Tải xuống SRT',
            tts_history_modal_loading: 'Đang tải dữ liệu...',
            tts_drop_overlay_title: 'Thả tệp vào đây',
            tts_drop_overlay_desc: 'Hỗ trợ .txt, .srt, .zip',
            tts_creative: 'Sáng tạo',
            tts_natural: 'Tự nhiên',
            tts_robust: 'Mạnh mẽ',
            tts_text_placeholder: 'Nhập văn bản cần chuyển đổi...',
            tts_normalize: 'Chuẩn hóa',
            tts_normalize_vn: 'Chuẩn hóa Tiếng Việt',
            tts_normalize_title: 'Chuẩn hóa văn bản cho TTS',
            tts_normalize_vn_title: 'Chuyển đổi để AI đọc chuẩn (ai→aai, im→yim)',
            tts_pronunciation: 'Phát âm từ',
            tts_pronunciation_title: 'Thêm cách phát âm cho từ viết tắt',
            tts_pron_original: 'Từ gốc',
            tts_pron_pronunciation: 'Phát âm',
            tts_pron_language: 'Ngôn ngữ',
            tts_pron_list: 'Danh sách',
            tts_pron_filter_all: 'Tất cả',
            tts_pron_empty: 'Chưa có từ nào được thêm',
            tts_subtitles: 'Phụ đề',
            tts_cancel: 'Hủy',
            tts_close: 'Đóng',
            tts_voice_search_placeholder: 'Tìm kiếm giọng đọc...',
            tts_voice_loading: 'Đang tải danh sách giọng...',
            tts_clone_name_placeholder: 'Ví dụ: Giọng của tôi',
            tts_note: 'Lưu ý:',
            tts_clear_all: 'Xóa tất cả',
            tts_process_all: 'Xử lý tất cả',
            tts_delete_text: 'Xóa văn bản',
            tts_cancel_action: 'Hủy bỏ',
            tts_process_now: 'Xử lý ngay',
            tts_bulk_delete: 'Xóa',
            tts_bulk_download_audio: 'Tải xuống Audio',
            tts_bulk_download_srt: 'Tải xuống SRT',
            tts_bulk_download_json: 'Tải xuống JSON',
            tts_tn_whitespace: 'Chuẩn hóa khoảng trắng',
            tts_tn_punctuation: 'Xóa dấu câu lạc',
            tts_tn_content: 'Xử lý nội dung',
            tts_tn_use_pron: 'Dùng danh sách từ "Phát âm từ"',
            tts_apply_changes: 'Áp dụng thay đổi',

            // Pro Tool
            pt_title: 'Công cụ nâng cao',
            pt_ready: 'Sẵn sàng',
            pt_maintenance: 'Đang bảo trì',
            pt_maintenance_title: 'Đang bảo trì',
            pt_maintenance_message: 'Tính năng Pro Tool đang được bảo trì. Vui lòng quay lại sau.',
            pt_maintenance_status: 'Đang xử lý',
            pt_tasks: 'tác vụ',
            pt_provider: 'Nhà cung cấp',
            pt_voice: 'Giọng nói',
            pt_clone: 'Nhân bản',
            pt_my_clones: 'Giọng đã nhân bản',
            pt_load: 'Tải',
            pt_library: 'Thư viện',
            pt_voice_id: 'Voice ID',
            pt_voice_id_placeholder: 'Voice ID...',
            pt_add_to_library: 'Thêm vào thư viện',
            pt_model: 'Mô hình',
            pt_voice_settings: 'Cài đặt giọng',
            pt_reset: 'Đặt lại',
            pt_reset_defaults: 'Đặt lại mặc định',
            pt_speed: 'Tốc độ',
            pt_stability: 'Độ ổn định',
            pt_similarity: 'Độ tương đồng',
            pt_style: 'Phong cách',
            pt_creative: 'Sáng tạo',
            pt_natural: 'Tự nhiên',
            pt_robust: 'Mạnh mẽ',
            pt_speaker_boost: 'Tăng cường giọng nói',
            pt_pitch: 'Cao độ',
            pt_volume: 'Âm lượng',
            pt_auto_srt: 'Tự động SRT',
            pt_auto_srt_tip: 'Tự động tạo file phụ đề .srt',
            pt_advanced_settings: 'Cài đặt nâng cao',
            pt_delay_between_segments: 'Trễ giữa các đoạn',
            pt_delay_unit: '(s) khi ghép file',
            pt_silent_by_character: 'Im lặng theo ký tự',
            pt_silent_char_tip: 'Chèn <break time="0.5s"/> để nghỉ 0.5 giây cho Elevenlabs.\nChèn <#0.5#> để nghỉ 0.5 giây cho Minimax.',
            pt_character_label: 'Ký tự:',
            pt_options: 'Tùy chọn',
            pt_split_by_chars: 'Chia theo ký tự',
            pt_split_by_chars_tip: 'Chia kịch bản theo số lượng ký tự được nhập trong ô bên cạnh',
            pt_auto_split: 'Tự động chia',
            pt_auto_split_tip: 'Chia kịch bản theo ký tự được nhập trong ô bên cạnh',
            pt_auto_split_placeholder: 'Ký tự tách...',
            pt_one_line_one_file: '1 Dòng = 1 File',
            pt_one_line_tip: 'Mỗi dòng = 1 file audio riêng',
            pt_advanced: 'Nâng cao',
            pt_threads: 'Luồng (1-10)',
            pt_reset_all_settings: 'Reset toàn bộ cài đặt',
            pt_toolbar_file: 'Tệp',
            pt_toolbar_file_tip: 'Nhập .txt, .srt',
            pt_toolbar_folder: 'Thư mục',
            pt_toolbar_folder_tip: 'Nhập cả thư mục',
            pt_toolbar_mp3: 'MP3',
            pt_toolbar_mp3_tip: 'Nhập MP3 để nối',
            pt_start: 'Bắt đầu',
            pt_start_tip: 'Bắt đầu xử lý',
            pt_stop: 'Dừng',
            pt_stop_tip: 'Dừng xử lý',
            pt_join_mp3_srt: 'Nối MP3 & SRT',
            pt_join_mp3_srt_tip: 'Nối MP3 + tạo SRT',
            pt_backup: 'Sao lưu',
            pt_backup_tip: 'Lịch sử / Backup',
            pt_open_output_tip: 'Mở output',
            pt_clear_all_tip: 'Xóa tất cả',
            pt_output_name: 'Tên output',
            pt_content: 'Nội dung',
            pt_status: 'Trạng thái',
            pt_voice_library: 'Thư viện giọng nói',
            pt_voice_library_hint: 'Dùng <code>#1</code> <code>#2</code> <code>#3</code> đầu mỗi dòng để dùng voice tương ứng cho hội thoại nhiều người.',
            pt_project: 'Dự án',
            pt_name: 'Tên',
            pt_boost: 'Boost',
            pt_actions: 'Thao tác',
            pt_cancel: 'Hủy',
            pt_save: 'Lưu',
            pt_choose_voice: 'Chọn giọng nói',
            pt_default: 'Mặc định',
            pt_loading: 'Đang tải...',
            pt_close: 'Đóng',
            pt_saved_projects: 'Dự án đã lưu',
            pt_no_projects: 'Chưa có dự án',
            pt_cloned_voices_title: 'Giọng nhân bản của tôi (Minimax)',
            pt_clone_new_voice: 'Tạo giọng mới',
            
            // JS specific
            js_select_voice: 'Chọn giọng nói...',
            js_processing: 'Đang xử lý',
            js_done: 'Hoàn thành',
            js_failed: 'Thất bại',
            js_timeout: 'Timeout',
            js_queued: 'Hàng đợi',
            js_no_voices_found: 'Không tìm thấy giọng nói',
            js_clear_filters: 'Xóa bộ lọc',
            js_searching: 'Đang tìm kiếm...',
            js_not_found: 'Không tìm thấy',
            js_delete_confirm: 'Bạn có chắc chắn muốn xóa?',
            js_delete_success: 'Đã xóa thành công',
            js_select_voice_first: 'Vui lòng chọn giọng nói trước!',
            js_enter_text: 'Vui lòng nhập văn bản!',
            js_copied: 'Đã copy!',
            js_added_to_favorites: 'Đã thêm vào yêu thích',
            js_removed_from_favorites: 'Đã xóa khỏi yêu thích',

            // Status text (for history cards)
            js_status_processing_dots: 'Xử lý...',
            js_status_waiting: 'Đang chờ',
            js_status_done_short: 'Xong',
            js_status_completed: 'Hoàn thành',
            js_status_failed_text: 'Thất bại',
            js_status_error: 'Lỗi trạng thái',
            js_just_finished: 'Vừa xong',
            js_delete_task: 'Xóa task',
            js_delete_history: 'Xóa lịch sử',
            js_delete_completed_history: 'Xóa lịch sử đã hoàn thành',

            // Model Sidebar
            tts_model_info_title: 'Thông tin Mô hình',
            tts_kingcong_model_title: 'Chọn Mô Hình KingCong',

            // Voice Cloning sample text
            vc_sample_text_too_long: 'Văn bản mẫu quá dài (Tối đa 500 ký tự)',

            // Empty state tips
            tts_tip_minimax_delay: '💡 Chèn <b>&lt;#0.5#&gt;</b> để ngừng 0.5 giây.',
            tts_tip_drag_txt: '📁 Kéo thả tệp <b>.txt</b> vào đây.',
            tts_tip_kingcong_delay: 'Chèn <code class="es-code">[delay=0.5s]</code> để nghỉ 0.5 giây',
            tts_tip_max_chars: 'Tối đa <b>250.000 ký tự</b>/nhiệm vụ',
            tts_tip_vn_use_minimax: 'Tiếng Việt nên sử dụng <b>Minimax</b>',
            tts_tip_eleven_break: 'Chèn <code class="es-code">&lt;break time="0.5s" /&gt;</code> để nghỉ 0.5 giây',

            // Detail History status badges
            dh_status_done: 'Xong',
            dh_status_error: 'Lỗi',
            dh_status_processing: 'Đang xử lý',

            // Detail History credits labels
            dh_credits_used: 'Tín dụng sử dụng',
            dh_credits_refunded: 'Đã hoàn trả',
            dh_credits_frozen: 'Tín dụng đóng băng',

            // Detail History queue/processing
            dh_queue: 'Hàng đợi',
            dh_queue_position: 'Hàng đợi #',
            dh_processing_percent: 'Xử lý',
            dh_error_unknown: 'Lỗi không xác định',

            // Download
            dh_download: 'Tải xuống',
            dh_download_expires: 'Tải xuống (hết hạn sau 72 giờ)',
            dh_subtitle_srt: 'Phụ đề (SRT)',
            dh_quick_download: 'Tải nhanh',
            dh_no_download_link: 'Không có link tải xuống',

            // Model sidebar
            model_languages: 'Ngôn ngữ:',
            model_see_more: 'Xem thêm',

            // ElevenLabs Models
            model_eleven_v3_name: 'Eleven v3 (Alpha)',
            model_eleven_v3_badge: 'Mới nhất',
            model_eleven_v3_desc: 'Mô hình biểu đạt tốt nhất. Hỗ trợ hơn 70 ngôn ngữ. Cần nhiều kỹ thuật prompt engineering hơn so với các mô hình trước đây. Hiện đang ở giai đoạn alpha và độ ổn định sẽ được cải thiện theo thời gian.',

            model_eleven_multilingual_v2_name: 'Eleven Multilingual v2 (Không dùng cho Tiếng Việt)',
            model_eleven_multilingual_v2_badge: 'Chất lượng cao',
            model_eleven_multilingual_v2_desc: 'Chế độ giống người thật và giàu cảm xúc nhất, hỗ trợ 29 ngôn ngữ. Phù hợp cho lồng tiếng, sách nói.',

            model_eleven_turbo_v2_5_name: 'Eleven Turbo v2.5',
            model_eleven_turbo_v2_5_badge: '50% rẻ hơn',
            model_eleven_turbo_v2_5_desc: 'Mô hình chất lượng cao, độ trễ thấp, hỗ trợ 32 ngôn ngữ. Phù hợp cho ứng dụng cần tốc độ.',

            model_eleven_turbo_v2_name: 'Eleven Turbo v2',
            model_eleven_turbo_v2_badge: '50% rẻ hơn',
            model_eleven_turbo_v2_desc: 'Mô hình tiếng Anh với độ trễ thấp. Phù hợp cho các ứng dụng developer.',

            model_eleven_flash_v2_5_name: 'Eleven Flash v2.5',
            model_eleven_flash_v2_5_badge: '50% rẻ hơn',
            model_eleven_flash_v2_5_desc: 'Mô hình độ trễ siêu thấp, hỗ trợ 32 ngôn ngữ. Lý tưởng cho chatbot và hội thoại.',

            model_eleven_flash_v2_name: 'Eleven Flash v2',
            model_eleven_flash_v2_badge: '50% rẻ hơn',
            model_eleven_flash_v2_desc: 'Mô hình tiếng Anh với độ trễ siêu thấp. Lý tưởng cho hội thoại.',

            model_eleven_monolingual_v1_name: 'Eleven Monolingual v1',
            model_eleven_monolingual_v1_badge: 'Cơ bản',
            model_eleven_monolingual_v1_desc: 'Mô hình tiếng Anh cơ bản với chi phí thấp.',

            model_default_badge_saving: 'Tiết kiệm',
            model_default_badge_standard: 'Standard',
            model_default_desc: 'Mô hình text-to-speech chất lượng.',

            // KingCong Models
            model_kc_lingual_v1_name: 'KingCong Speech V1',
            model_kc_lingual_v1_badge: 'Voice Cloning',
            model_kc_lingual_v1_desc: 'Mô hình đa ngôn ngữ hỗ trợ clone giọng nói. Chất lượng cao, phù hợp cho nhiều ứng dụng.',
            model_kc_lingual_v1_langs: 'Việt Nam, Mỹ, Trung Quốc, Ấn Độ, Pháp, Phần Lan, Đức, Ý, Nga, Tây Ban Nha',

            model_kc_jeck_name: 'KingCong Speech',
            model_kc_jeck_badge: 'Voice Cloning',
            model_kc_jeck_desc: 'Mô hình chuyên dụng cho ngôn ngữ Đông Á với khả năng clone giọng.',
            model_kc_jeck_langs: 'Mỹ, Trung Quốc, Nhật Bản, Hàn Quốc',

            model_kc_base_name: 'KingCong Speech Base',
            model_kc_base_badge: 'No Cloning',
            model_kc_base_desc: 'Mô hình cơ bản không hỗ trợ clone giọng. Phù hợp cho TTS thông thường.',
            model_kc_base_langs: 'Mỹ, Nhật Bản, Trung Quốc, Tây Ban Nha, Ấn Độ, Ý, Bồ Đào Nha, Pháp',

            // Model dropdown short descriptions
            model_kc_lingual_v1_short: 'Hỗ trợ 10 ngôn ngữ: Việt Nam, Mỹ, Trung Quốc, Ấn Độ, Pháp, Phần Lan, Đức, Ý, Nga, Tây Ban Nha',
            model_kc_jeck_short: 'Hỗ trợ 4 ngôn ngữ: Mỹ, Trung Quốc, Nhật Bản, Hàn Quốc',
        },

        en: {
            // Common
            dashboard: 'Dashboard',
            text_to_speech: 'Text to Speech',
            voice_cloning: 'Voice Cloning',
            pro_tool: 'Pro Tool',
            logout: 'Logout',
            credits: 'Credits',

            // Voice Cloning Page
            vc_banner: 'Clone any voice you want, no more worrying about not finding the right voice. (using Minimax)',
            vc_voice_name: 'Voice Name <span>*</span>',
            vc_voice_name_placeholder: 'E.g.: News reader voice',
            vc_upload_audio: 'Upload Audio File <span>*</span>',
            vc_drag_drop: 'Drag & drop file or click to select',
            vc_duration_hint: 'Duration 10s - 2 minutes, max 20MB',
            vc_format_hint: 'Currently supports: .mp3',
            vc_remove_file: 'Remove file',
            vc_select_language: 'Select Language',
            vc_auto_detect: 'Auto detect',
            vc_search: 'Search...',
            vc_select_gender: 'Select Gender',
            vc_male: 'Male',
            vc_female: 'Female',
            vc_preview_text: 'Preview Text (Optional)',
            vc_preview_placeholder: 'Enter text (If empty, system will use default sample)...',
            vc_chars: 'characters',
            vc_remove_noise: 'Remove Noise',
            vc_clone_btn: 'Clone Voice',
            vc_library_title: 'Cloned Voice Library',
            vc_voices: 'voices',
            vc_refresh: 'Refresh',
            vc_loading: 'Loading list...',
            vc_confirm_delete: 'Confirm Delete Voice',
            vc_confirm_delete_msg: 'Are you sure you want to delete voice',
            vc_cannot_undo: 'This action cannot be undone.',
            vc_cancel: 'Cancel',
            vc_delete: 'Delete',
            vc_processing: 'Processing...',
            vc_processing_hint: 'AI is learning your voice. This process may take 1-2 minutes.',
            vc_notification: 'Notification',
            vc_use_voice: 'Use',
            vc_play: 'Play',
            vc_stop: 'Stop',
            vc_no_voices: 'No voices yet',
            vc_no_voices_hint: 'Create your first cloned voice',
            vc_select_model: 'Select Model',
            vc_trim_audio: 'Trim Audio',
            vc_trim_range: 'Start - End Time',
            vc_trim_start: 'Start (seconds)',
            vc_trim_end: 'End (seconds)',
            vc_trim_duration: 'Trimmed duration:',
            vc_seconds: 'seconds',
            vc_trim_use: 'Trim & Use',

            // Dashboard
            db_welcome: 'Welcome back,',
            db_overview: 'Overview',
            db_total_tasks: 'Total Tasks',
            db_credits_remaining: 'Credits Remaining',
            db_pending_tasks: 'Pending Tasks',
            db_success_rate: 'Success Rate',
            db_recent_tasks: 'Recent Tasks',
            db_view_all: 'View All',
            db_new_task: 'New Task',
            db_loading: 'Loading dashboard...',
            db_tagline: 'Here\'s what\'s happening with your voice projects today.',
            db_tbl_id: 'Task ID',
            db_tbl_preview: 'Text Preview',
            db_tbl_voice: 'Voice',
            db_tbl_status: 'Status',
            db_tbl_cost: 'Cost',
            db_tbl_date: 'Date',
            db_tbl_loading: 'Loading tasks...',

            // TTS Page
            tts_title: 'Text to Speech',
            tts_enter_text: 'Enter your text here.',
            tts_no_limit: 'No character limit.',
            tts_select_voice: 'Select voice...',
            tts_generate: 'Generate Voice',
            tts_settings: 'Settings',
            tts_history: 'History',
            tts_loading: 'Loading...',
            tts_wait_moment: 'Please wait a moment',
            tts_processing: 'Processing...',
            tts_tip_minimax: 'For Vietnamese, it\'s recommended to use <b style="color:#fff">Minimax</b>',
            tts_tip_break: '💡 Use <code class="es-code" style="background:#333;color:#4ade80;padding:2px 6px;border-radius:4px">&lt;break time="0.5s" /&gt;</code> for a 0.5s pause',
            tts_tip_drag: '📁 Drag & drop <b style="color:#fff">.txt, .srt</b> files here',
            tts_upload: 'Upload',
            tts_upload_file: 'Upload file (.txt .srt .zip)',
            tts_upload_folder: 'Upload folder',
            tts_clear: 'Clear',
            tts_clear_title: 'Clear all text',
            tts_fee_info: 'Includes processing fees',
            tts_fee_processing: '• Includes processing fee: <span style="color: #fff; font-weight: 600;">x1.12</span>',
            tts_fee_srt: '• SRT format is more expensive: <span style="color: #fff; font-weight: 600;">x1.2</span>',
            tts_provider_eleven_desc: 'Natural voices and a larger user base.',
            tts_provider_minimax_desc: 'Speaks Vietnamese well. Can use cloned voices.',
            tts_provider_kingcong_desc: 'High quality KingCong voices (voice clone).',
            tts_kingcong_model: 'Select KingCong Model',
            tts_punct_delay: 'Punctuation Delay',
            tts_punct_delay_info: 'Automatically add pauses after punctuation marks',
            tts_punct_beta_warn: 'This feature is in beta testing and may not work consistently.',
            tts_punct_period: 'Period',
            tts_punct_comma: 'Comma',
            tts_punct_exclamation: 'Exclamation',
            tts_punct_question: 'Question',
            tts_delay: 'Delay',
            tts_quick_select: 'Quick select:',
            tts_or_enter: 'Or enter:',
            tts_add: 'Add',
            tts_refresh: 'Refresh',
            tts_details: 'Details',
            tts_select_model: 'Select language model',
            tts_speed: 'Speed',
            tts_pitch: 'Pitch',
            tts_volume: 'Volume',
            tts_stability: 'Stability',
            tts_similarity: 'Similarity Boost',
            tts_style_exaggeration: 'Style Exaggeration',
            tts_voice_boost: 'Speaker Boost',
            tts_subtitles: 'Subtitles',
            tts_subtitle_fee: '+15% fee',
            tts_reset: 'Reset',
            tts_model_info: 'Model Information',
            tts_history_loading: 'Loading...',
            tts_history_end: 'All history has been displayed',
            tts_vmodal_default: 'Default',
            tts_vmodal_cloned: 'Cloned Voices',
            tts_vmodal_library: 'Voice Library',
            tts_vmodal_favorites: 'Favorites',
            tts_vmodal_search: 'Search for a voice...',
            tts_vmodal_sort_most_used: 'Most Used',
            tts_vmodal_sort_trending: 'Trending',
            tts_vmodal_sort_newest: 'Newest',
            tts_vmodal_sort_chars: 'Characters Generated',
            tts_vmodal_filter_lang: 'Language',
            tts_vmodal_filter_gender: 'Gender',
            tts_vmodal_filter_age: 'Age',
            tts_vmodal_all: 'All',
            tts_vmodal_male: 'Male',
            tts_vmodal_female: 'Female',
            tts_vmodal_young: 'Young',
            tts_vmodal_middle_aged: 'Middle-aged',
            tts_vmodal_old: 'Old',
            tts_vmodal_loading: 'Loading voice list...',
            tts_vmodal_tab_default: 'Default',
            tts_vmodal_tab_cloned: 'Cloned Voices',
            tts_vmodal_tab_library: 'Voice Library',
            tts_vmodal_tab_favorites: 'Favorites',
            tts_vmodal_filter_provider: 'Provider',
            tts_vmodal_filter_style: 'Style',
            tts_vmodal_provider_maziao: 'Maziao (Recommended)',
            tts_vmodal_style_narrative: 'Narrative',
            tts_vmodal_style_conversational: 'Conversational',
            tts_vmodal_style_news: 'News',
            tts_vmodal_style_audiobook: 'Audiobook',
            tts_vmodal_style_education: 'Education',
            tts_vmodal_use: 'Use',
            tts_vmodal_preview: 'Preview',
            tts_vmodal_favorite: 'Favorite',
            tts_vmodal_delete_voice: 'Delete voice',
            tts_vmodal_filter_accent: 'Accent',
            tts_vmodal_reset: 'Reset',
            tts_vmodal_clone_new: 'Clone new voice',
            tts_vmodal_clone_desc: 'Create your own voice',
            tts_del_task_title: 'Delete Task',
            tts_del_task_warn: 'Are you sure you want to delete this task? This action cannot be undone.',
            tts_del_task_note: 'If the task is stuck for more than 24 hours, credits will be refunded.',
            tts_del_task_cancel: 'Cancel',
            tts_del_task_confirm: 'Delete',
            tts_history_modal_title: 'History',
            tts_history_modal_refresh: 'Refresh',
            tts_history_modal_delete: 'Delete',
            tts_history_modal_download_audio: 'Download Audio',
            tts_history_modal_download_srt: 'Download SRT',
            tts_history_modal_loading: 'Loading data...',
            tts_drop_overlay_title: 'Drop file here',
            tts_drop_overlay_desc: 'Supports .txt, .srt, .zip',
            tts_creative: 'Creative',
            tts_natural: 'Natural',
            tts_robust: 'Robust',
            tts_text_placeholder: 'Enter text to convert...',
            tts_normalize: 'Normalize',
            tts_normalize_vn: 'Normalize Vietnamese',
            tts_normalize_title: 'Normalize text for TTS',
            tts_normalize_vn_title: 'Convert for AI to read correctly (ai→aai, im→yim)',
            tts_pronunciation: 'Pronunciation',
            tts_pronunciation_title: 'Add pronunciation for abbreviations',
            tts_pron_original: 'Original Word',
            tts_pron_pronunciation: 'Pronunciation',
            tts_pron_language: 'Language',
            tts_pron_list: 'List',
            tts_pron_filter_all: 'All',
            tts_pron_empty: 'No words added yet',
            tts_subtitles: 'Subtitles',
            tts_cancel: 'Cancel',
            tts_close: 'Close',
            tts_voice_search_placeholder: 'Search for a voice...',
            tts_voice_loading: 'Loading voice list...',
            tts_clone_name_placeholder: 'E.g.: My voice',
            tts_note: 'Note:',
            tts_clear_all: 'Clear all',
            tts_process_all: 'Process all',
            tts_delete_text: 'Delete text',
            tts_cancel_action: 'Cancel',
            tts_process_now: 'Process now',
            tts_bulk_delete: 'Delete',
            tts_bulk_download_audio: 'Download Audio',
            tts_bulk_download_srt: 'Download SRT',
            tts_bulk_download_json: 'Download JSON',
            tts_tn_whitespace: 'Normalize whitespace',
            tts_tn_punctuation: 'Remove stray punctuation',
            tts_tn_content: 'Process content',
            tts_tn_use_pron: 'Use "Pronunciation" word list',
            tts_apply_changes: 'Apply changes',

            // Pro Tool
            pt_title: 'Pro Tool',
            pt_ready: 'Ready',
            pt_maintenance: 'Maintenance',
            pt_maintenance_title: 'Under Maintenance',
            pt_maintenance_message: 'Pro Tool feature is under maintenance. Please come back later.',
            pt_maintenance_status: 'In Progress',
            pt_tasks: 'tasks',
            pt_provider: 'Provider',
            pt_voice: 'Voice',
            pt_clone: 'Clone',
            pt_my_clones: 'My Clones',
            pt_load: 'Load',
            pt_library: 'Library',
            pt_voice_id: 'Voice ID',
            pt_voice_id_placeholder: 'Voice ID...',
            pt_add_to_library: 'Add to library',
            pt_model: 'Model',
            pt_voice_settings: 'Voice Settings',
            pt_reset: 'Reset',
            pt_reset_defaults: 'Reset to defaults',
            pt_speed: 'Speed',
            pt_stability: 'Stability',
            pt_similarity: 'Similarity',
            pt_style: 'Style',
            pt_creative: 'Creative',
            pt_natural: 'Natural',
            pt_robust: 'Robust',
            pt_speaker_boost: 'Speaker Boost',
            pt_pitch: 'Pitch',
            pt_volume: 'Volume',
            pt_auto_srt: 'Auto SRT',
            pt_auto_srt_tip: 'Auto-generate .srt subtitles',
            pt_advanced_settings: 'Advanced Settings',
            pt_delay_between_segments: 'Delay between segments',
            pt_delay_unit: '(s) when joining files',
            pt_silent_by_character: 'Silent by Character',
            pt_silent_char_tip: 'Insert <break time="0.5s"/> for 0.5 second pause in Elevenlabs.\nInsert <#0.5#> for 0.5 second pause in Minimax.',
            pt_character_label: 'Characters:',
            pt_options: 'Options',
            pt_split_by_chars: 'Split by Characters',
            pt_split_by_chars_tip: 'Split by character count entered next to it',
            pt_auto_split: 'Auto Split',
            pt_auto_split_tip: 'Split by the characters entered next to it',
            pt_auto_split_placeholder: 'Split characters...',
            pt_one_line_one_file: '1 Line = 1 File',
            pt_one_line_tip: 'Each line = separate audio file',
            pt_advanced: 'Advanced',
            pt_threads: 'Threads (1-10)',
            pt_reset_all_settings: 'Reset All Settings',
            pt_toolbar_file: 'File',
            pt_toolbar_file_tip: 'Import .txt, .srt',
            pt_toolbar_folder: 'Folder',
            pt_toolbar_folder_tip: 'Import entire folder',
            pt_toolbar_mp3: 'MP3',
            pt_toolbar_mp3_tip: 'Import MP3 to join',
            pt_start: 'Start',
            pt_start_tip: 'Start processing',
            pt_stop: 'Stop',
            pt_stop_tip: 'Stop processing',
            pt_join_mp3_srt: 'Join MP3 & SRT',
            pt_join_mp3_srt_tip: 'Join MP3 + create SRT',
            pt_backup: 'Backup',
            pt_backup_tip: 'History / Backup',
            pt_open_output_tip: 'Open output',
            pt_clear_all_tip: 'Clear all',
            pt_output_name: 'Output Name',
            pt_content: 'Content',
            pt_status: 'Status',
            pt_voice_library: 'Voice Library',
            pt_voice_library_hint: 'Use <code>#1</code> <code>#2</code> <code>#3</code> at the start of each line to map voices in multi-speaker dialogs.',
            pt_project: 'Project',
            pt_name: 'Name',
            pt_boost: 'Boost',
            pt_actions: 'Actions',
            pt_cancel: 'Cancel',
            pt_save: 'Save',
            pt_choose_voice: 'Choose Voice',
            pt_default: 'Default',
            pt_loading: 'Loading...',
            pt_close: 'Close',
            pt_saved_projects: 'Saved Projects',
            pt_no_projects: 'No projects saved',
            pt_cloned_voices_title: 'My Cloned Voices (Minimax)',
            pt_clone_new_voice: 'Clone New Voice',
            
            // JS specific
            js_select_voice: 'Select voice...',
            js_processing: 'Processing',
            js_done: 'Done',
            js_failed: 'Failed',
            js_timeout: 'Timeout',
            js_queued: 'Queued',
            js_no_voices_found: 'No voices found',
            js_clear_filters: 'Clear filters',
            js_searching: 'Searching...',
            js_not_found: 'Not found',
            js_delete_confirm: 'Are you sure you want to delete?',
            js_delete_success: 'Successfully deleted',
            js_select_voice_first: 'Please select a voice first!',
            js_enter_text: 'Please enter text!',
            js_copied: 'Copied!',
            js_added_to_favorites: 'Added to favorites',
            js_removed_from_favorites: 'Removed from favorites',

            // Status text (for history cards)
            js_status_processing_dots: 'Processing...',
            js_status_waiting: 'Waiting',
            js_status_done_short: 'Done',
            js_status_completed: 'Completed',
            js_status_failed_text: 'Failed',
            js_status_error: 'Status error',
            js_just_finished: 'Just now',
            js_delete_task: 'Delete task',
            js_delete_history: 'Delete history',
            js_delete_completed_history: 'Delete completed history',

            // Model Sidebar
            tts_model_info_title: 'Model Information',
            tts_kingcong_model_title: 'Select KingCong Model',

            // Voice Cloning sample text
            vc_sample_text_too_long: 'Sample text too long (Max 500 characters)',

            // Empty state tips
            tts_tip_minimax_delay: '💡 Insert <b>&lt;#0.5#&gt;</b> for a 0.5 second pause.',
            tts_tip_drag_txt: '📁 Drag & drop <b>.txt</b> file here.',
            tts_tip_kingcong_delay: 'Insert <code class="es-code">[delay=0.5s]</code> for a 0.5 second pause',
            tts_tip_max_chars: 'Maximum <b>250,000 characters</b>/task',
            tts_tip_vn_use_minimax: 'For Vietnamese, use <b>Minimax</b>',
            tts_tip_eleven_break: 'Insert <code class="es-code">&lt;break time="0.5s" /&gt;</code> for a 0.5 second pause',

            // Detail History status badges
            dh_status_done: 'Done',
            dh_status_error: 'Error',
            dh_status_processing: 'Processing',

            // Detail History credits labels
            dh_credits_used: 'Credits used',
            dh_credits_refunded: 'Refunded',
            dh_credits_frozen: 'Credits frozen',

            // Detail History queue/processing
            dh_queue: 'Queue',
            dh_queue_position: 'Queue #',
            dh_processing_percent: 'Processing',
            dh_error_unknown: 'Unknown error',

            // Download
            dh_download: 'Download',
            dh_download_expires: 'Download (expires in 72 hours)',
            dh_subtitle_srt: 'Subtitle (SRT)',
            dh_quick_download: 'Quick download',
            dh_no_download_link: 'No download link',

            // Model sidebar
            model_languages: 'Languages:',
            model_see_more: 'See more',

            // ElevenLabs Models
            model_eleven_v3_name: 'Eleven v3 (Alpha)',
            model_eleven_v3_badge: 'Newest',
            model_eleven_v3_desc: 'The most expressive model. Supports over 70 languages. Requires more prompt engineering techniques than previous models. Currently in alpha stage and stability will improve over time.',

            model_eleven_multilingual_v2_name: 'Eleven Multilingual v2 (Not for Vietnamese)',
            model_eleven_multilingual_v2_badge: 'High Quality',
            model_eleven_multilingual_v2_desc: 'The most realistic and emotional mode, supporting 29 languages. Suitable for dubbing, audiobooks.',

            model_eleven_turbo_v2_5_name: 'Eleven Turbo v2.5',
            model_eleven_turbo_v2_5_badge: '50% cheaper',
            model_eleven_turbo_v2_5_desc: 'High quality model with low latency, supporting 32 languages. Suitable for speed-critical applications.',

            model_eleven_turbo_v2_name: 'Eleven Turbo v2',
            model_eleven_turbo_v2_badge: '50% cheaper',
            model_eleven_turbo_v2_desc: 'English model with low latency. Suitable for developer applications.',

            model_eleven_flash_v2_5_name: 'Eleven Flash v2.5',
            model_eleven_flash_v2_5_badge: '50% cheaper',
            model_eleven_flash_v2_5_desc: 'Ultra-low latency model, supporting 32 languages. Ideal for chatbots and conversations.',

            model_eleven_flash_v2_name: 'Eleven Flash v2',
            model_eleven_flash_v2_badge: '50% cheaper',
            model_eleven_flash_v2_desc: 'English model with ultra-low latency. Ideal for conversations.',

            model_eleven_monolingual_v1_name: 'Eleven Monolingual v1',
            model_eleven_monolingual_v1_badge: 'Basic',
            model_eleven_monolingual_v1_desc: 'Basic English model with low cost.',

            model_default_badge_saving: 'Saving',
            model_default_badge_standard: 'Standard',
            model_default_desc: 'Quality text-to-speech model.',

            // KingCong Models
            model_kc_lingual_v1_name: 'KingCong Speech V1',
            model_kc_lingual_v1_badge: 'Voice Cloning',
            model_kc_lingual_v1_desc: 'Multilingual model supporting voice cloning. High quality, suitable for many applications.',
            model_kc_lingual_v1_langs: 'Vietnam, USA, China, India, France, Finland, Germany, Italy, Russia, Spain',

            model_kc_jeck_name: 'KingCong Speech',
            model_kc_jeck_badge: 'Voice Cloning',
            model_kc_jeck_desc: 'Specialized model for East Asian languages with voice cloning capability.',
            model_kc_jeck_langs: 'USA, China, Japan, Korea',

            model_kc_base_name: 'KingCong Speech Base',
            model_kc_base_badge: 'No Cloning',
            model_kc_base_desc: 'Basic model without voice cloning support. Suitable for regular TTS.',
            model_kc_base_langs: 'USA, Japan, China, Spain, India, Italy, Portugal, France',

            // Model dropdown short descriptions
            model_kc_lingual_v1_short: 'Supports 10 languages: Vietnam, USA, China, India, France, Finland, Germany, Italy, Russia, Spain',
            model_kc_jeck_short: 'Supports 4 languages: USA, China, Japan, Korea',
        }
    },

    // Get translation
    t(key) {
        return this.translations[this.currentLang][key] || this.translations['vi'][key] || key;
    },

    // Set language
    setLanguage(lang) {
        if (this.translations[lang]) {
            this.currentLang = lang;
            localStorage.setItem('app_language', lang);
            this.applyTranslations();
            
            // Broadcast to other windows
            if (window.electronAPI && window.electronAPI.setLanguage) {
                window.electronAPI.setLanguage(lang);
            }

            document.dispatchEvent(new CustomEvent('app:language-changed', { detail: { lang } }));
            return true;
        }
        return false;
    },

    // Toggle language
    toggleLanguage() {
        const newLang = this.currentLang === 'vi' ? 'en' : 'vi';
        this.setLanguage(newLang);
        return newLang;
    },

    // Apply translations to all elements with data-i18n attribute
    applyTranslations() {
        // Text content
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (this.translations[this.currentLang][key]) {
                el.innerHTML = this.translations[this.currentLang][key];
            }
        });

        // Placeholders
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (this.translations[this.currentLang][key]) {
                el.placeholder = this.translations[this.currentLang][key];
            }
        });

        // Titles
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            if (this.translations[this.currentLang][key]) {
                el.title = this.translations[this.currentLang][key];
            }
        });

        // Tooltip data-tip attributes
        document.querySelectorAll('[data-i18n-tip]').forEach(el => {
            const key = el.getAttribute('data-i18n-tip');
            if (this.translations[this.currentLang][key]) {
                el.setAttribute('data-tip', this.translations[this.currentLang][key]);
            }
        });

        // Update language switcher button
        const langBtn = document.getElementById('langSwitchBtn');
        if (langBtn) {
            langBtn.textContent = this.currentLang.toUpperCase();
        }
    },

    // Initialize
    init() {
        // Apply saved language
        const savedLang = localStorage.getItem('app_language');
        if (savedLang && this.translations[savedLang]) {
            this.currentLang = savedLang;
        }

        // Apply translations on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.applyTranslations();
                this.updateLangButton();
            });
        } else {
            this.applyTranslations();
            this.updateLangButton();
        }
    },

    // Update language button text
    updateLangButton() {
        const langBtn = document.getElementById('langSwitchBtn');
        if (langBtn) {
            langBtn.textContent = this.currentLang.toUpperCase();
            langBtn.title = this.currentLang === 'vi' ? 'Switch to English' : 'Chuyển sang Tiếng Việt';
        }
    }
};

// Auto initialize
i18n.init();

// Global function for language toggle
function toggleAppLanguage() {
    const newLang = i18n.toggleLanguage();
    i18n.updateLangButton();
    console.log('Language changed to:', newLang);
    // Check local storage again in case initialized before UI ready
    const savedLang = localStorage.getItem('app_language');
    if (savedLang && i18n.currentLang !== savedLang) {
        i18n.setLanguage(savedLang);
    }
}

// Expose to window for onclick handlers
window.toggleAppLanguage = toggleAppLanguage;
window.i18n = i18n;
