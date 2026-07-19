<!-- Image OCR Upload Component -->
<div class="ocr-upload-box">
    <input type="file" id="imageUpload" accept="image/*"
        style="display:none" onchange="extractTextFromImage(this)">
    <button class="btn-ocr" type="button"
        onclick="document.getElementById('imageUpload').click()">
        📷 Upload Image (OCR)
    </button>
    <span id="ocrStatus" class="ocr-status"></span>
</div>

<style>
.ocr-upload-box {
    display:flex; align-items:center; gap:12px;
    margin-top:12px; flex-wrap:wrap;
}
.btn-ocr {
    padding:10px 20px;
    background:rgba(124,124,255,0.1);
    color:#7c7cff;
    border:1px solid rgba(124,124,255,0.3);
    border-radius:10px; font-size:13px; font-weight:600;
    cursor:pointer; transition:all 0.2s;
    display:flex; align-items:center; gap:6px;
}
.btn-ocr:hover {
    background:rgba(124,124,255,0.2);
    border-color:rgba(124,124,255,0.5);
    transform:translateY(-1px);
}
.btn-ocr:disabled {
    opacity:0.5; cursor:not-allowed; transform:none;
}
.ocr-status {
    font-size:12px; color:#888;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tesseract.js/5.0.4/tesseract.min.js"></script>
<script>
async function extractTextFromImage(input) {
    const file = input.files[0];
    if (!file) return;

    const status = document.getElementById('ocrStatus');
    const btn = document.querySelector('.btn-ocr');
    const textArea = document.getElementById('userText'); // detector textarea id

    btn.disabled = true;
    status.style.color = '#7c7cff';
    status.textContent = '🔍 Reading text from image...';

    try {
        const result = await Tesseract.recognize(file, 'eng', {
            logger: m => {
                if (m.status === 'recognizing text') {
                    status.textContent = `🔍 Reading... ${Math.round(m.progress * 100)}%`;
                }
            }
        });

        const extractedText = result.data.text.trim();

        if (!extractedText) {
            status.style.color = '#ffd700';
            status.textContent = '⚠️ No text found in image.';
            return;
        }

        textArea.value = extractedText;
        if (typeof updateCount === 'function') updateCount();

        status.style.color = '#00cc66';
        status.textContent = `✅ Extracted ${extractedText.length} characters!`;

    } catch (err) {
        status.style.color = '#ff6b6b';
        status.textContent = '❌ OCR failed: ' + err.message;
    } finally {
        input.value = '';
        btn.disabled = false;
    }
}
</script>