// ==========================================
// 1. KHAI BÁO BIẾN TOÀN CỤC
// ==========================================
let selectedLanguage = 'Auto';
let selectedGender = 'male';
let selectedProvider = 'minimax'; // minimax or kingcong
let selectedModel = 'lingual_speech_v1'; // Kingcong model
let selectedFile = null;
let previewUrl = null; // Biến lưu URL file nghe thử

// Cấu hình giới hạn cho từng provider
const providerLimits = {
    minimax: {
        maxSize: 20 * 1024 * 1024, // 20MB
        minDuration: 10,
        maxDuration: 120,
        allowedFormats: ['.mp3'],
        formatText: '.mp3'
    },
    kingcong: {
        maxSize: 1 * 1024 * 1024, // 1MB
        minDuration: 1,
        maxDuration: 6,
        allowedFormats: ['.mp3', '.wav'],
        formatText: '.mp3, .wav'
    }
};

// ==========================================
// 2. DỮ LIỆU VĂN BẢN MẪU (40 QUỐC GIA)
// ==========================================
const defaultPreviewTexts = {
    "Auto": "Xin chào, hãy nhập văn bản bất kỳ và tôi sẽ tự động nhận diện ngôn ngữ của bạn.", // ✅ Thêm dòng này
    "English": "Hello, I'm delighted to assist you with our voice services. Choose a voice that resonates with you, and let's begin our creative audio journey together",
    "Vietnamese": "Xin chào, tôi rất vui được hỗ trợ bạn với các dịch vụ giọng nói của chúng tôi. Hãy chọn một giọng nói phù hợp với bạn và cùng bắt đầu hành trình âm thanh sáng tạo của chúng ta",
    "Arabic": "مرحبًا، يسعدني أن أقدم لكم خدماتنا الصوتي. أختر النبرة التي تثير اهتمامك، ودعنا ننطلق معاً في رحلة ساحرة لصناعة الصوت",
    "Cantonese": "你好,好開心可以為你提供配音服務。揀選你喜歡嘅聲音,同我一齊開啟聲音創作嘅精彩旅程",
    "Chinese (Mandarin)": "您好,很高兴能为您提供配音服务。选择您感兴趣的音色,让我们一起开启声音创作的奇幻之旅吧。",
    "Dutch": "Hallo, ik ben verheugd je te assisteren met onze stemdiensten. Kies een stem die bij je past en laten we samen aan onze creatieve audio-reis beginnen.",
    "French": "Bonjour, je suis ravi de vous accompagner dans nos services vocaux. Choisissez la voix qui vous inspire et créons ensemble votre projet sonore",
    "German": "Guten Tag, ich freue mich, Sie bei unseren Sprachdiensten unterstützen zu dürfen. Wählen Sie Ihre bevorzugte Stimme aus und lassen Sie uns gemeinsam Ihre Audio-Welt gestalten",
    "Indonesian": "Selamat datang, senang bisa membantu Anda dengan layanan suara kami. Silakan pilih karakter suara yang Anda sukai dan mari mulai perjalanan kreatif bersama",
    "Italian": "Buongiorno, sono lieto di assistervi con i nostri servizi vocali. Scegliete la voce che preferite e iniziamo insieme questo percorso creativo",
    "Japanese": "このたびは、音声生成サービスをご利用いただきありがとうございます。お好みの声をお選びいただき、音声創作の旅を楽しみましょう。",
    "Korean": "안녕하세요, 음성 서비스를 제공해 드릴 수 있어서 기쁩니다. 마음에 드시는 목소리를 선택하시면 함께 멋진 음성 제작을 시작해보도록 하겠습니다.",
    "Portuguese": "Olá, é um prazer ajudá-lo com nossos serviços de voz. Escolha a voz que mais combina com você e vamos juntos criar algo especial",
    "Russian": "Здравствуйте, рад помочь вам с нашими голосовыми услугами. Выберите понравившийся голос, и давайте вместе создадим что-то особенное.",
    "Spanish": "Hola, es un placer ayudarte con nuestros servicios de voz. Elige el tono de voz que más te guste y comencemos juntos esta aventura creativa",
    "Turkish": "Merhaba, ses hizmetlerimizle size yardımcı olmaktan mutluluk duyuyorum. İhtiyaçlarınıza en uygun sesi seçin ve birlikte yaratıcı bir ses yolculuğuna başlayalım",
    "Ukrainian": "Привіт, я радий допомогти вам з нашими голосовими послугами. Виберіть голос, який вам подобається, і давайте розпочнемо нашу творчу аудіо-подорож разом.",
    "Thai": "สวัสดีค่ะ/ครับ ยินดีอย่างยิ่งที่ได้ช่วยคุณกับบริการเสียงของเรา เลือกเสียงที่เชื่อมโยง แล้วมาเริ่มต้นการเดินทางสร้างสรรค์ด้านเสียงไปด้วยกัน",
    "Polish": "Cześć, z przyjemnością pomogę Ci w korzystaniu z naszych usług głosowych. Wybierz głos, który z Tobą rezonuje, i rozpocznijmy razem kreatywną podróż dźwiękową",
    "Romanian": "Bună, mă bucur să te asist cu serviciile noastre vocale. Alege o voce care rezonează cu tine și hai să începem împreună călătoria noastră audio creativă",
    "Greek": "Γεια σας, είναι χαρά μου να σας βοηθήσω με τις υπηρεσίες φωνής μας. Επιλέξτε μια φωνή που σας εκφράζει και ας ξεκινήσουμε μαζί το δημιουργικό μας ηχητικό ταξίδι",
    "Czech": "Dobrý den, je mi potěšením vám pomoci s našimi hlasovými službami. Vyberte si hlas, který s vámi rezonuje, a pojďme společně zahájit tvůrčí zvukovou cestu",
    "Finnish": "Hei, ilo auttaa sinua äänipalveluissamme. Valitse ääni, joka puhuttelee sinua, ja aloitetaan yhdessä luova äänimatka",
    "Hindi": "नमस्ते, हमारी वॉयस सेवाओं में आपकी सहायता करके मुझे खुशी हो रही है। वह आवाज़ चुनें जो आपके मन को भाए, और आइए मिलकर एक रचनात्मक ऑडियो यात्रा शुरू करें",
    "Bulgarian": "Здравейте, радвам се да ви помогна с нашите гласови услуги. Изберете глас, който ви харесва, и нека започнем нашето творческо аудио пътешествие заедно.",
    "Danish": "Hej, jeg er glad for at hjælpe dig med vores stemmetjenester. Vælg en stemme, der taler til dig, og lad os begynde vores kreative lydrejse sammen.",
    "Hebrew": "שלום, אני שמח לסייע לך עם שירותי הקול שלנו. בחר בקול שמדבר אליך, ובואו נתחיל יחד את המסע האודיו היצירתי שלנו.",
    "Malay": "Hello, saya gembira untuk membantu anda dengan perkhidmatan suara kami. Pilih suara yang sesuai dengan anda, dan mari kita mulakan perjalanan audio kreatif kita bersama-sama.",
    "Persian": "سلام، من خوشحالم که در خدمات صوتی ما به شما کمک کنم. صدایی را انتخاب کنید که با شما طنین‌انداز باشد و بیایید سفر صوتی خلاقانه‌مان را با هم آغاز کنیم.",
    "Slovak": "Ahoj, som rád, že vám môžem pomôcť s našimi hlasovými službami. Vyberte si hlas, ktorý vám vyhovuje, a začnime spolu naše kreatívne zvukové putovanie.",
    "Swedish": "Hej, jag är glad att hjälpa dig med våra rösttjänster. Välj en röst som tilltalar dig, och låt oss börja vår kreativa ljudresa tillsammans.",
    "Croatian": "Pozdrav, drago mi je pomoći vam s našim glasovnim uslugama. Odaberite glas koji vam odgovara i počnimo zajedno naše kreativno zvučno putovanje.",
    "Filipino": "Pozdrav, drago mi je pomoći vam s našim glasovnim uslugama. Odaberite glas koji vam odgovara i počnimo zajedno naše kreativno zvučno putovanje.",
    "Hungarian": "Üdvözlöm, örömmel segítek Önnek hangszolgáltatásainkkal. Válasszon egy hangot, amely megtetszik Önnek, és kezdjük el együtt kreatív audio utazásunkat.",
    "Norwegian": "Hei, jeg er glad for å hjelpe deg med våre stemmetjenester. Velg en stemme som appellerer til deg, og la oss begynne vår kreative lydreise sammen.",
    "Slovenian": "Pozdravljeni, vesel sem, da vam lahko pomagam z našimi glasovnimi storitvami. Izberite glas, ki se vam zdi privlačen, in skupaj začnimo naše ustvarjalno zvočno potovanje.",
    "Catalan": "Hola, em complau ajudar-vos amb els nostres serveis de veu. Trieu una veu que us ressoni, i comencem junts el nostre viatge àudio creatiu.",
    "Nynorsk": "Hei, eg er glad for å hjelpe deg med stemmetenestene våre. Vel ein stemme som talar til deg, og lat oss byrje den kreative lydreisa vår saman.",
    "Tamil": "Hei, eg er glad for å hjelpe deg med stemmetenestene våre. Vel ein stemme som talar til deg, og lat oss byrje den kreative lydreisa vår saman.",
    "Afrikaans": "Hallo, ek is bly om jou te help met ons stemdienste. Kies 'n stem wat met jou resoneer, en kom ons begin ons kreatiewe klankjoernaal saam."
};

// ==========================================
// 3. KHỞI TẠO (DOCUMENT READY)
// ==========================================
$(document).ready(function() {
    // 🔥 THÊM ĐOẠN NÀY ĐỂ UI HIỆN "TỰ XÁC ĐỊNH"
    $('#selectedLangText').text('Tự xác định'); 
    $('#previewText').attr('placeholder', `Mẫu câu: ${defaultPreviewTexts['Auto']}`);
    // ------------------------------------------
    // Tải danh sách giọng
    loadClonedVoices();
    updateCharCount();

    // Sự kiện click ra ngoài để đóng dropdown
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.custom-dropdown').length) {
            $('.dropdown-options').removeClass('show');
            $('.dropdown-selected').removeClass('active');
        }
    });
    
    // Đóng modal khi click bên ngoài
    $(document).on('click', '#deleteModal', function(e) {
        if (e.target.id === 'deleteModal') {
            closeDeleteModal();
        }
    });

    // Đóng modal khi nhấn ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#deleteModal').is(':visible')) {
            closeDeleteModal();
        }
    });
    $('#removeNoiseToggle').prop('checked', true);
});

// ==========================================
// 4. LOGIC DROPDOWN & UI
// ==========================================
function toggleDropdown(id, el) {
    $('.dropdown-options').not('#' + id).removeClass('show');
    $('.dropdown-selected').not(el).removeClass('active');
    $('#' + id).toggleClass('show');
    $(el).toggleClass('active');
}

function selectItem(type, value, htmlText, el) {
    if (type === 'lang') {
        selectedLanguage = value;
        $('#selectedLangText').html(htmlText);
        $('#langOptions input').val('');
        $('.dropdown-item').show();

        if ($('#previewText').val().trim() === '') {
            let sample = defaultPreviewTexts[value] || defaultPreviewTexts["English"];
            $('#previewText').attr('placeholder', `Mẫu câu: ${sample.substring(0, 40)}...`);
        }
    } else if (type === 'gender') {
        selectedGender = value;
        $('#selectedGenderText').html(htmlText);
    }

    $(el).closest('.dropdown-options').find('.dropdown-item').removeClass('selected');
    $(el).addClass('selected');
    $('.dropdown-options').removeClass('show');
    $('.dropdown-selected').removeClass('active');
}

// Provider selection
function selectProvider(value, htmlText, el) {
    selectedProvider = value;
    $('#selectedProviderText').html(htmlText);

    $(el).closest('.dropdown-options').find('.dropdown-item').removeClass('selected');
    $(el).addClass('selected');
    $('.dropdown-options').removeClass('show');
    $('.dropdown-selected').removeClass('active');

    // Switch UI based on provider
    switchProviderUI(value);

    // Reload cloned voices khi chuyển provider
    loadClonedVoices();
}

// Switch provider UI
function switchProviderUI(provider) {
    // Reset file khi switch provider
    removeFile();

    if (provider === 'kingcong') {
        // Hiện Kingcong fields
        $('#kingcongFields').show();
        $('#uploadBoxKingcong').show();

        // Ẩn Minimax fields
        $('#minimaxFields').hide();
        $('.minimax-upload').hide();
        $('#noiseToggleWrapper').hide();
    } else {
        // Hiện Minimax fields
        $('#minimaxFields').show();
        $('.minimax-upload').show();
        $('#noiseToggleWrapper').show();

        // Ẩn Kingcong fields
        $('#kingcongFields').hide();
        $('#uploadBoxKingcong').hide();
    }
}

// Select model (Kingcong) - Dropdown version
function selectModel(model, displayName, element) {
    selectedModel = model;

    // Update UI
    $('#selectedModelText').text(displayName);
    $('#modelOptions .dropdown-item').removeClass('selected');
    $(element).addClass('selected');

    // Close dropdown
    $('#modelOptions').removeClass('show');
    $('#modelDropdown .dropdown-selected').removeClass('active');
}

function filterLang(input) {
    let filter = input.value.toUpperCase();
    let items = document.getElementById("langOptions").getElementsByClassName("dropdown-item");
    
    for (let i = 0; i < items.length; i++) {
        let txtValue = items[i].textContent || items[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            items[i].style.display = "flex";
        } else {
            items[i].style.display = "none";
        }
    }
}

function updateCharCount() {
    const count = $('#previewText').val().length;
    $('#charCount').text(`${count} / 500 ký tự`);
}

// ==========================================
// 5. XỬ LÝ FILE (UPLOAD & PREVIEW)
// ==========================================
function handleFileSelect(input) {
    if (input.files && input.files.length > 0) {
        validateAndSetFile(input.files[0]);
    }
}

function handleDrop(e) {
    e.preventDefault(); e.stopPropagation();
    $('#uploadBox, #uploadBoxKingcong').removeClass('drag-over');
    if (e.dataTransfer.files.length > 0) {
        $('#fileInput')[0].files = e.dataTransfer.files;
        validateAndSetFile(e.dataTransfer.files[0]);
    }
}

function handleDragOver(e) {
    e.preventDefault();
    if (selectedProvider === 'kingcong') {
        $('#uploadBoxKingcong').addClass('drag-over');
    } else {
        $('#uploadBox').addClass('drag-over');
    }
}

function handleDragLeave(e) {
    e.preventDefault();
    $('#uploadBox, #uploadBoxKingcong').removeClass('drag-over');
}

// Biến lưu thông tin file tạm để trim
let pendingTrimFile = null;
let pendingTrimDuration = 0;

function validateAndSetFile(file) {
    const limits = providerLimits[selectedProvider];
    const fileName = file.name.toLowerCase();

    console.log('📁 Validating file:', {
        name: file.name,
        size: (file.size / 1024 / 1024).toFixed(2) + 'MB',
        provider: selectedProvider,
        limits: limits
    });

    // Kiểm tra định dạng
    const isValidFormat = limits.allowedFormats.some(ext => fileName.endsWith(ext));
    if (!isValidFormat) {
        showToast('error', `❌ Sai định dạng! Chỉ chấp nhận: ${limits.formatText}`);
        $('#fileInput').val('');
        return;
    }

    // Giới hạn upload tạm thời cao hơn để cho phép trim (50MB)
    const uploadLimit = 50 * 1024 * 1024;
    if (file.size > uploadLimit) {
        showToast('error', `❌ File quá lớn! Tối đa 50MB để xử lý.`);
        $('#fileInput').val('');
        return;
    }

    const objectUrl = URL.createObjectURL(file);
    const testAudio = new Audio(objectUrl);

    testAudio.onloadedmetadata = function() {
        const duration = testAudio.duration;
        URL.revokeObjectURL(objectUrl);

        console.log('⏱️ Audio duration:', duration.toFixed(1) + 's', 'Max allowed:', limits.maxDuration + 's');

        // Kiểm tra thời lượng tối thiểu
        if (duration < limits.minDuration) {
            showToast('error', `❌ File quá ngắn (${duration.toFixed(1)}s). Tối thiểu ${limits.minDuration} giây!`);
            removeFile();
            return;
        }

        // Kiểm tra thời lượng tối đa - Hiện popup trim thay vì báo lỗi
        if (duration > limits.maxDuration) {
            console.log('🎬 File too long! Opening trim modal...');
            // Lưu file tạm và hiện popup trim
            pendingTrimFile = file;
            pendingTrimDuration = duration;
            openTrimModal(file, duration, limits.maxDuration, limits.minDuration);
            return;
        }

        // Kiểm tra kích thước file (chỉ khi duration OK)
        const maxSizeMB = limits.maxSize / (1024 * 1024);
        if (file.size > limits.maxSize) {
            showToast('error', `❌ File quá lớn (${(file.size / 1024 / 1024).toFixed(1)}MB)! Tối đa ${maxSizeMB}MB.`);
            $('#fileInput').val('');
            return;
        }

        // File hợp lệ - set luôn
        console.log('✅ File valid!');
        setValidFile(file, duration);
    };

    testAudio.onerror = function() {
        console.error('❌ Audio load error');
        showToast('error', '❌ File lỗi không thể đọc!');
        removeFile();
    };
}

// Hàm set file hợp lệ
function setValidFile(file, duration) {
    selectedFile = file;

    // Ẩn upload box phù hợp
    if (selectedProvider === 'kingcong') {
        $('#uploadBoxKingcong').hide();
    } else {
        $('#uploadBox').hide();
    }

    $('#fileInfo').css('display', 'block');
    $('#fileName').text(file.name);
    $('#fileSize').text((file.size / (1024 * 1024)).toFixed(2) + ' MB');

    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = URL.createObjectURL(file);

    const audioPlayer = document.getElementById('audioPreviewPlayer');
    audioPlayer.src = previewUrl;

    showToast('success', `✅ File hợp lệ! (${duration.toFixed(1)}s)`);
}

// ==========================================
// TRIM AUDIO MODAL
// ==========================================
let trimAudioDuration = 0;

function openTrimModal(file, duration, maxDuration, minDuration) {
    console.log('🎵 Opening trim modal:', { duration, maxDuration, minDuration, fileName: file.name });

    trimAudioDuration = duration;

    // Cập nhật thông tin
    $('#trimOriginalDuration').text(duration.toFixed(1));
    $('#trimMaxDuration').text(maxDuration);

    // Set giá trị mặc định
    const defaultEnd = Math.min(maxDuration, duration);
    $('#trimStart').val(0).attr('max', duration - minDuration);
    $('#trimEnd').val(defaultEnd).attr('max', duration);

    // Cập nhật time markers
    $('#waveformTimeStart').text('0:00');
    $('#waveformTimeEnd').text(formatTime(duration));

    // Load audio để preview
    const trimUrl = URL.createObjectURL(file);
    const audioEl = document.getElementById('trimAudioPlayer');
    audioEl.src = trimUrl;

    // Cập nhật duration display và selection
    updateTrimDuration();

    // Hiện modal TRƯỚC rồi mới vẽ waveform
    $('#trimModal').css('display', 'flex').hide().fadeIn(200, function() {
        // Vẽ waveform SAU khi modal đã hiển thị
        setTimeout(() => {
            drawWaveform(file);
        }, 100);
    });
    console.log('🎵 Trim modal opened!');
}

// Format time mm:ss
function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// Vẽ waveform
async function drawWaveform(file) {
    const canvas = document.getElementById('trimWaveform');
    if (!canvas) {
        console.error('Canvas not found!');
        return;
    }

    const ctx = canvas.getContext('2d');

    // Set canvas size dựa trên CSS
    const rect = canvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    canvas.width = rect.width * dpr;
    canvas.height = 80 * dpr;
    ctx.scale(dpr, dpr);

    const width = rect.width;
    const height = 80;
    const centerY = height / 2;

    // Clear canvas với màu nền
    ctx.fillStyle = '#0a0a0a';
    ctx.fillRect(0, 0, width, height);

    // Loading indicator
    ctx.fillStyle = '#666';
    ctx.font = '13px Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Đang tải waveform...', width / 2, centerY);

    try {
        // Decode audio
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const arrayBuffer = await file.arrayBuffer();
        const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

        // Get audio data từ cả 2 channel nếu có
        const channel0 = audioBuffer.getChannelData(0);
        const channel1 = audioBuffer.numberOfChannels > 1 ? audioBuffer.getChannelData(1) : channel0;

        // Số điểm sample để vẽ (càng nhiều càng chi tiết)
        const totalSamples = Math.floor(width * 2);
        const blockSize = Math.floor(channel0.length / totalSamples);

        const peaks = [];
        const troughs = [];

        for (let i = 0; i < totalSamples; i++) {
            const startIdx = i * blockSize;
            let max = 0;
            let min = 0;

            for (let j = 0; j < blockSize && (startIdx + j) < channel0.length; j++) {
                const val0 = channel0[startIdx + j];
                const val1 = channel1[startIdx + j];
                const val = (val0 + val1) / 2;

                if (val > max) max = val;
                if (val < min) min = val;
            }

            peaks.push(max);
            troughs.push(min);
        }

        // Normalize
        const maxPeak = Math.max(...peaks.map(Math.abs), ...troughs.map(Math.abs)) || 1;

        // Clear canvas
        ctx.fillStyle = '#0a0a0a';
        ctx.fillRect(0, 0, width, height);

        // Vẽ đường giữa (center line) mờ
        ctx.strokeStyle = 'rgba(99, 102, 241, 0.2)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(0, centerY);
        ctx.lineTo(width, centerY);
        ctx.stroke();

        // Tạo gradient cho waveform
        const gradient = ctx.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, '#818cf8');
        gradient.addColorStop(0.3, '#6366f1');
        gradient.addColorStop(0.5, '#4f46e5');
        gradient.addColorStop(0.7, '#6366f1');
        gradient.addColorStop(1, '#818cf8');

        // Vẽ waveform dạng fill (sóng đặc)
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.moveTo(0, centerY);

        // Vẽ phần trên (peaks)
        for (let i = 0; i < totalSamples; i++) {
            const x = (i / totalSamples) * width;
            const peakHeight = (peaks[i] / maxPeak) * (height / 2 - 4);
            ctx.lineTo(x, centerY - peakHeight);
        }

        // Vẽ phần dưới (troughs) - ngược lại
        for (let i = totalSamples - 1; i >= 0; i--) {
            const x = (i / totalSamples) * width;
            const troughHeight = (Math.abs(troughs[i]) / maxPeak) * (height / 2 - 4);
            ctx.lineTo(x, centerY + troughHeight);
        }

        ctx.closePath();
        ctx.fill();

        // Vẽ đường viền sóng để rõ hơn
        ctx.strokeStyle = 'rgba(129, 140, 248, 0.6)';
        ctx.lineWidth = 0.5;
        ctx.beginPath();
        ctx.moveTo(0, centerY);

        for (let i = 0; i < totalSamples; i++) {
            const x = (i / totalSamples) * width;
            const peakHeight = (peaks[i] / maxPeak) * (height / 2 - 4);
            ctx.lineTo(x, centerY - peakHeight);
        }
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(0, centerY);
        for (let i = 0; i < totalSamples; i++) {
            const x = (i / totalSamples) * width;
            const troughHeight = (Math.abs(troughs[i]) / maxPeak) * (height / 2 - 4);
            ctx.lineTo(x, centerY + troughHeight);
        }
        ctx.stroke();

        console.log('✅ Waveform drawn successfully!', { totalSamples, blockSize });

        // Update selection after waveform drawn
        updateTrimSelection();

        audioContext.close();

    } catch (error) {
        console.error('Waveform error:', error);
        ctx.fillStyle = '#0a0a0a';
        ctx.fillRect(0, 0, width, height);
        ctx.fillStyle = '#ef4444';
        ctx.font = '13px Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Lỗi tải waveform: ' + error.message, width / 2, height / 2);
    }
}

// Update selection overlay
function updateTrimSelection() {
    if (trimAudioDuration <= 0) return;

    const start = parseFloat($('#trimStart').val()) || 0;
    const end = parseFloat($('#trimEnd').val()) || 0;

    const startPercent = (start / trimAudioDuration) * 100;
    const widthPercent = ((end - start) / trimAudioDuration) * 100;

    $('#trimSelection').css({
        left: startPercent + '%',
        width: Math.max(0, widthPercent) + '%'
    });
}

function closeTrimModal() {
    $('#trimModal').fadeOut(200, function() {
        $(this).css('display', 'none');
    });
    pendingTrimFile = null;
    pendingTrimDuration = 0;
    trimAudioDuration = 0;

    // Clear audio
    const audioEl = document.getElementById('trimAudioPlayer');
    if (audioEl) {
        audioEl.pause();
        audioEl.src = '';
    }
    $('#fileInput').val('');

    // Reset selection
    $('#trimSelection').css({ left: '0%', width: '0%' });
}

function updateTrimDuration() {
    const start = parseFloat($('#trimStart').val()) || 0;
    const end = parseFloat($('#trimEnd').val()) || 0;
    const resultDuration = Math.max(0, end - start);

    $('#trimResultDuration').text(resultDuration.toFixed(1));

    // Validate
    const limits = providerLimits[selectedProvider];
    const isValid = resultDuration >= limits.minDuration && resultDuration <= limits.maxDuration && start < end;

    $('#btnTrimConfirm').prop('disabled', !isValid);

    if (!isValid && resultDuration > 0) {
        $('#trimResultDuration').css('color', '#ef4444');
    } else {
        $('#trimResultDuration').css('color', '#6366f1');
    }

    // Update waveform selection
    updateTrimSelection();
}

async function confirmTrim() {
    if (!pendingTrimFile) {
        showToast('error', 'Không có file để cắt!');
        return;
    }

    const start = parseFloat($('#trimStart').val()) || 0;
    const end = parseFloat($('#trimEnd').val()) || 0;
    const duration = end - start;

    const limits = providerLimits[selectedProvider];
    if (duration < limits.minDuration || duration > limits.maxDuration) {
        showToast('error', `Thời lượng phải từ ${limits.minDuration}s đến ${limits.maxDuration}s!`);
        return;
    }

    // Disable button
    $('#btnTrimConfirm').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Đang cắt...');

    try {
        // Trim audio using Web Audio API
        const trimmedBlob = await trimAudioFile(pendingTrimFile, start, end);

        // Tạo file mới từ blob - file trim luôn là WAV format
        const originalName = pendingTrimFile.name;
        const baseName = originalName.substring(0, originalName.lastIndexOf('.'));
        const newName = `${baseName}_trimmed.wav`;
        const trimmedFile = new File([trimmedBlob], newName, { type: 'audio/wav' });

        // Set file và đóng modal
        setValidFile(trimmedFile, duration);
        closeTrimModal();

        showToast('success', `✅ Đã cắt audio thành công! (${duration.toFixed(1)}s)`);

    } catch (error) {
        console.error('Trim error:', error);
        showToast('error', '❌ Lỗi khi cắt audio: ' + error.message);
    } finally {
        $('#btnTrimConfirm').prop('disabled', false).html('<i class="bi bi-scissors"></i> Cắt & Sử dụng');
    }
}

// Hàm trim audio sử dụng Web Audio API
async function trimAudioFile(file, startTime, endTime) {
    return new Promise(async (resolve, reject) => {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const arrayBuffer = await file.arrayBuffer();
            const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

            const sampleRate = audioBuffer.sampleRate;
            const channels = audioBuffer.numberOfChannels;

            const startSample = Math.floor(startTime * sampleRate);
            const endSample = Math.floor(endTime * sampleRate);
            const trimmedLength = endSample - startSample;

            // Tạo buffer mới
            const trimmedBuffer = audioContext.createBuffer(channels, trimmedLength, sampleRate);

            for (let channel = 0; channel < channels; channel++) {
                const originalData = audioBuffer.getChannelData(channel);
                const trimmedData = trimmedBuffer.getChannelData(channel);

                for (let i = 0; i < trimmedLength; i++) {
                    trimmedData[i] = originalData[startSample + i];
                }
            }

            // Convert to WAV blob
            const wavBlob = audioBufferToWav(trimmedBuffer);
            resolve(wavBlob);

        } catch (error) {
            reject(error);
        }
    });
}

// Convert AudioBuffer to WAV Blob
function audioBufferToWav(buffer) {
    const numChannels = buffer.numberOfChannels;
    const sampleRate = buffer.sampleRate;
    const format = 1; // PCM
    const bitDepth = 16;

    const bytesPerSample = bitDepth / 8;
    const blockAlign = numChannels * bytesPerSample;

    const dataLength = buffer.length * blockAlign;
    const bufferLength = 44 + dataLength;

    const arrayBuffer = new ArrayBuffer(bufferLength);
    const view = new DataView(arrayBuffer);

    // WAV header
    writeString(view, 0, 'RIFF');
    view.setUint32(4, 36 + dataLength, true);
    writeString(view, 8, 'WAVE');
    writeString(view, 12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20, format, true);
    view.setUint16(22, numChannels, true);
    view.setUint32(24, sampleRate, true);
    view.setUint32(28, sampleRate * blockAlign, true);
    view.setUint16(32, blockAlign, true);
    view.setUint16(34, bitDepth, true);
    writeString(view, 36, 'data');
    view.setUint32(40, dataLength, true);

    // Write audio data
    const offset = 44;
    for (let i = 0; i < buffer.length; i++) {
        for (let channel = 0; channel < numChannels; channel++) {
            const sample = Math.max(-1, Math.min(1, buffer.getChannelData(channel)[i]));
            const intSample = sample < 0 ? sample * 0x8000 : sample * 0x7FFF;
            view.setInt16(offset + (i * blockAlign) + (channel * bytesPerSample), intSample, true);
        }
    }

    return new Blob([arrayBuffer], { type: 'audio/wav' });
}

function writeString(view, offset, string) {
    for (let i = 0; i < string.length; i++) {
        view.setUint8(offset + i, string.charCodeAt(i));
    }
}

function removeFile() {
    selectedFile = null;
    $('#fileInput').val('');
    $('#fileInfo').hide();

    // Hiện đúng upload box theo provider
    if (selectedProvider === 'kingcong') {
        $('#uploadBoxKingcong').show();
        $('#uploadBox').hide();
    } else {
        $('#uploadBox').show();
        $('#uploadBoxKingcong').hide();
    }

    const audioPlayer = document.getElementById('audioPreviewPlayer');
    audioPlayer.pause();
    audioPlayer.src = "";
    if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    }
}

// ==========================================
// 6. TẠO CLONE (MAIN FUNCTION)
// ==========================================
function createClone() {
    const name = $('#cloneName').val().trim();

    // Validate tên
    if (!name) {
        showToast('error', 'Vui lòng nhập tên giọng nói!');
        $('#cloneName').focus();
        return;
    }
    if (name.length > 50) {
        showToast('error', 'Tên giọng quá dài (Max 50 ký tự)');
        return;
    }

    // Validate file
    if (!selectedFile) {
        showToast('error', 'Vui lòng chọn file âm thanh!');
        const uploadBox = selectedProvider === 'kingcong' ? 'uploadBoxKingcong' : 'uploadBox';
        document.getElementById(uploadBox).scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const formData = new FormData();
    formData.append('action', 'create_clone');
    formData.append('provider', selectedProvider);
    formData.append('voice_name', name);
    formData.append('file', selectedFile);

    // Thêm dữ liệu khác nhau cho từng provider
    if (selectedProvider === 'kingcong') {
        // Kingcong cần: name, file, model, language, gender
        formData.append('model', selectedModel);
        formData.append('language', selectedLanguage || 'Auto'); // Auto sẽ map thành 'vi' ở backend
        formData.append('gender', selectedGender || 'male');
    } else {
        // Minimax cần: name, file, gender, language, preview_text, noise_reduction
        const userEnteredText = $('#previewText').val().trim();
        const isRemoveNoise = $('#removeNoiseToggle').is(':checked');

        let finalPreviewText = userEnteredText;
        if (finalPreviewText === "") {
            finalPreviewText = defaultPreviewTexts[selectedLanguage] || defaultPreviewTexts["English"];
        }

        if (finalPreviewText.length > 500) {
            showToast('error', 'Văn bản mẫu quá dài (Max 500 ký tự)');
            return;
        }

        formData.append('gender', selectedGender);
        formData.append('language', selectedLanguage);
        formData.append('preview_text', finalPreviewText);
        formData.append('need_noise_reduction', isRemoveNoise ? 'true' : 'false');
    }

    $('#loadingOverlay').css('display', 'flex');
    $('#btnCreate').prop('disabled', true);

    // Cập nhật loading hint theo provider
    if (selectedProvider === 'kingcong') {
        $('.loading-hint').text('Đang tạo giọng nhân bản. Vui lòng chờ...');
    } else {
        $('.loading-hint').text('AI đang học giọng nói của bạn. Quá trình này có thể mất 1-2 phút.');
    }

    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        $('#loadingBar').css('width', progress + '%');
    }, 500);

    // Gửi AJAX
    $.ajax({
        url: '../../ajaxs/voice_cloning3.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 999000,
        success: function(res) {
            clearInterval(progressInterval);
            $('#loadingBar').css('width', '100%');

            setTimeout(() => {
                $('#loadingOverlay').hide();
                $('#btnCreate').prop('disabled', false);
                $('#loadingBar').css('width', '0%');

                if (res.status === 'success') {
                    showToast('success', 'Clone giọng thành công! 🎉');

                    $('#cloneName').val('');
                    if (selectedProvider === 'minimax') {
                        $('#previewText').val('');
                        $('#removeNoiseToggle').prop('checked', true);
                        updateCharCount();
                    }
                    removeFile();

                    setTimeout(() => {
                        loadClonedVoices();
                    }, 1000);
                } else {
                    showToast('error', res.message || 'Có lỗi xảy ra.');
                }
            }, 500);
        },
        error: function(xhr, status, error) {
            clearInterval(progressInterval);
            $('#loadingOverlay').hide();
            $('#btnCreate').prop('disabled', false);
            $('#loadingBar').css('width', '0%');

            console.error('Error:', error);
            if (status === 'timeout') {
                showToast('error', 'Quá thời gian chờ (Timeout).');
            } else if (xhr.status === 413) {
                showToast('error', 'File quá lớn so với cấu hình Server!');
            } else {
                showToast('error', 'Lỗi kết nối hoặc file không hợp lệ.');
            }
        }
    });
}

// ==========================================
// 7. DANH SÁCH & TIỆN ÍCH - ĐÃ SỬA LỖI CÚ PHÁP
// ==========================================
function loadClonedVoices() {
    console.log('[loadClonedVoices] Loading voices for provider:', selectedProvider);
    $.post('../../ajaxs/voice_cloning3.php', { action: 'list_clones', provider: selectedProvider }, function(res) {
        console.log('[loadClonedVoices] Response:', res);
        if (res.status === 'success') {
            console.log('[loadClonedVoices] Found', res.voices.length, 'voices');
            renderGrid(res.voices);
            $('#voiceCount').text(res.voices.length);
        } else {
            $('#clonedVoiceGrid').html(`
                <div class="empty-state">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div class="title">Lỗi tải danh sách</div>
                    <div class="hint">${res.message}</div>
                </div>
            `);
        }
    }, 'json').fail(function() {
        $('#clonedVoiceGrid').html(`
            <div class="empty-state">
                <i class="bi bi-wifi-off"></i>
                <div class="title">Lỗi kết nối</div>
            </div>
        `);
    });
}

function renderGrid(list) {
    if (!list || list.length === 0) {
        $('#clonedVoiceGrid').html(`
            <div class="empty-state">
                <i class="bi bi-mic-mute"></i>
                <div class="title">Chưa có giọng nào</div>
                <div class="hint">Bắt đầu tạo giọng nhân bản ngay!</div>
            </div>
        `);
        return;
    }

    let html = '';
    list.forEach(v => {
        let img = v.cover_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(v.voice_name)}&background=random&color=fff&bold=true`;
        
        let tagsHtml = '';

        // 1. Hiện Ngôn ngữ (nếu có và khác "0")
        if (v.language && v.language !== '0') {
             tagsHtml += `<span class="vc-tag">${v.language}</span>`;
        }

        // 2. Hiện Giới tính (CHỈ HIỆN NẾU KHÁC "0")
        if (v.gender && v.gender !== '0' && v.gender !== 0) {
             tagsHtml += `<span class="vc-tag" style="text-transform: capitalize;">${v.gender}</span>`;
        }
        
        // 3. Hiện các Tag khác (KHÔNG HIỆN AI84/AI33)
        let extraTags = v.tags || v.tag_list; 
        if (extraTags && Array.isArray(extraTags)) {
            tagsHtml += extraTags
                .filter(t => t !== 'AI84' && t !== 'AI33' && t.toUpperCase() !== 'AI84' && t.toUpperCase() !== 'AI33')
                .map(t => `<span class="vc-tag">${t}</span>`)
                .join('');
        }

        let canPlay = !!v.sample_audio;

        html += `
        <div class="voice-card" id="voice-${v.voice_id}">
            <div class="vc-title" title="${v.voice_name}">${v.voice_name}</div>
            
            <div class="vc-tags">
                ${tagsHtml}
            </div>
            
            <div class="vc-footer-row">
                <img src="${img}" class="vc-avatar" onerror="this.src='https://ui-avatars.com/api/?name=V&background=333&color=fff'">
                
                <div class="vc-actions">
                    <button class="btn-icon-action delete" onclick="deleteVoice('${v.voice_id}')" title="Xóa">
                        <i class="bi bi-trash"></i>
                    </button>
                    
                    <button class="btn-icon-action play" onclick="playPreview('${v.sample_audio || ''}')" 
                            ${!canPlay ? 'disabled' : ''} title="Nghe thử">
                        <i class="bi bi-play-circle"></i>
                    </button>
                </div>
            </div>
        </div>
        `;
    });
    $('#clonedVoiceGrid').html(html);
}

// Biến lưu thông tin voice cần xóa
let voiceToDelete = { id: null, name: '' };

function deleteVoice(id) {
    const card = $(`#voice-${id}`);
    const voiceName = card.find('.vc-title').text();
    
    voiceToDelete = { id: id, name: voiceName };
    
    $('#voiceNameToDelete').text(voiceName);
    $('#deleteModal').fadeIn(200);
}

function closeDeleteModal() {
    $('#deleteModal').fadeOut(200);
    voiceToDelete = { id: null, name: '' };
}

function confirmDelete() {
    if (!voiceToDelete.id) {
        showToast('error', 'Không có giọng nào được chọn!');
        return;
    }
    
    $('#deleteModal').fadeOut(200);
    
    const id = voiceToDelete.id;
    const card = $(`#voice-${id}`);
    
    card.css('opacity', '0.5');
    
    $.ajax({
        url: '../../ajaxs/voice_cloning3.php',
        type: 'POST',
        data: {
            action: 'delete_clone',
            voice_id: id,
            provider: selectedProvider
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast('success', '✅ Đã xóa giọng thành công!');
                
                card.fadeOut(300, function() { 
                    $(this).remove();
                    
                    const count = parseInt($('#voiceCount').text());
                    $('#voiceCount').text(Math.max(0, count - 1));
                    
                    if ($('.voice-card').length === 0) {
                        loadClonedVoices();
                    }
                });
            } else {
                card.css('opacity', '1');
                showToast('error', res.message || '❌ Xóa thất bại');
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete Error:', {xhr, status, error});
            card.css('opacity', '1');
            showToast('error', '❌ Lỗi kết nối!');
        }
    });
    
    voiceToDelete = { id: null, name: '' };
}

// Player nghe thử
let audioObj = document.getElementById('previewAudio');
function playPreview(url) {
    if (!url) {
        showToast('info', 'Không có file nghe thử');
        return;
    }
    audioObj.src = url;
    audioObj.play();
    showToast('info', '🎵 Đang phát mẫu giọng...');
}

// Toast thông báo
function showToast(type, message) {
    const toast = $('#toast');
    const icon = toast.find('.toast-icon');
    const text = toast.find('.toast-text');
    
    icon.removeClass('success error info warning bi-check-circle bi-x-circle bi-info-circle bi-exclamation-triangle');
    
    switch(type) {
        case 'success': icon.addClass('success bi-check-circle'); break;
        case 'error': icon.addClass('error bi-x-circle'); break;
        case 'info': icon.addClass('info bi-info-circle'); break;
        case 'warning': icon.addClass('warning bi-exclamation-triangle'); break;
    }
    
    text.text(message);
    toast.addClass('show');
    setTimeout(() => { toast.removeClass('show'); }, 3500);
}