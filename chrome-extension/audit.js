/* License: by cs.baguosps@gmail.com */
const params = new URLSearchParams(location.search);
const jobId = params.get('job');
const pageSize = 50;
let currentJob = null;
let visibleLimit = pageSize;

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[character]));
}

function isReviewDateText(text) {
    if (!text || typeof text !== 'string') return false;
    const clean = text.trim();
    if (!clean || clean.length > 55) return false;
    if (/^[★☆\s]+$/.test(clean)) return false;
    const relativePattern = /^(?:edited\s*|diedit\s*)?(?:a|an|\d+)\s*(?:seconds?|minutes?|hours?|days?|weeks?|months?|years?|detik|menit|jam|hari|minggu|bulan|tahun)\s*(?:ago|yang lalu|lalu)?$/i;
    if (relativePattern.test(clean)) return true;
    const simpleKeywords = /^(?:edited\s*|diedit\s*)?(?:just now|baru saja|yesterday|kemarin|today|hari ini|sehari lalu|seminggu lalu|sebulan lalu|setahun lalu|sejam lalu|semenit lalu)$/i;
    if (simpleKeywords.test(clean)) return true;
    const calendarPattern = /^(?:edited\s*|diedit\s*)?(?:\d{1,2}\s+[a-z]{3,9}\s+\d{4}|[a-z]{3,9}\s+\d{1,2},?\s+\d{4}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})$/i;
    return calendarPattern.test(clean);
}

function countWords(text) {
    return (String(text || '').match(/[\p{L}\p{M}\p{N}]+(?:['’\-][\p{L}\p{M}\p{N}]+)*/gu) || []).length;
}

function normalizeReviewData(review) {
    let date = String(review.date || '').trim();
    let text = String(review.text || '').trim();

    // Deteksi jika tanggal dan ulasan tertukar:
    const dateIsText = date.length > 55 || (!isReviewDateText(date) && date.length > 30);
    const textIsDate = isReviewDateText(text) || (text.length <= 45 && /\b(?:ago|lalu|kemarin|yesterday)\b/i.test(text));

    if (dateIsText && (textIsDate || !text || text === 'Hanya memberikan rating')) {
        const originalText = date;
        date = textIsDate ? text : '';
        text = originalText;
    } else if (textIsDate && !date) {
        date = text;
        text = '';
    } else if (textIsDate && isReviewDateText(date)) {
        text = '';
    } else if (/^(?:local\s*guide\s*[·,]?\s*)?\d+[\d,.]*\s*(?:reviews?|ulasan)$/i.test(text)) {
        text = '';
    }

    const calculatedWords = text ? countWords(text) : 0;
    const wordCount = (Number.isInteger(review.wordCount) && !textIsDate && !dateIsText)
        ? review.wordCount
        : calculatedWords;

    return {
        ...review,
        date,
        text,
        wordCount
    };
}

function findingsFor(review) {
    const findings = [];
    if (review.rating <= 2) findings.push(['Rating rendah', 'finding-high']);
    if (!review.reply) findings.push(['Belum dibalas', '']);
    if (!review.text) findings.push(['Tanpa teks', 'finding-low']);
    else if (review.text.trim().length < 40) findings.push(['Teks singkat', 'finding-low']);
    return findings.length ? findings : [['Lolos pemeriksaan', 'finding-clear']];
}

function escapeSpreadsheetText(value) {
    return String(value ?? '')
        .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function exportAuditXls() {
    const rawReviews = currentJob?.reviews || [];
    if (!rawReviews.length) return;
    const reviews = rawReviews.map(normalizeReviewData);

    const priority = reviews.filter(review => review.rating <= 2).length;
    const unanswered = reviews.filter(review => !review.reply).length;
    const clear = reviews.filter(review => findingsFor(review).length === 1 && findingsFor(review)[0][0] === 'Lolos pemeriksaan').length;
    const average = (reviews.reduce((total, review) => total + (Number(review.rating) || 0), 0) / reviews.length).toFixed(2);
    const exportDate = new Date().toLocaleString('id-ID');
    const headers = ['No', 'Nama reviewer', 'Tanggal review', 'Rating', 'Foto kontributor', 'Foto pada review', 'Komentar review', 'Jumlah kata', 'Balasan pemilik', 'Temuan audit'];
    const bodyRows = reviews.map((review, index) => {
        const contributorPhotoCount = Number.isInteger(review.reviewerPhotoCount) ? review.reviewerPhotoCount : 'Tidak tersedia';
        const reviewPhotoCount = Number.isInteger(review.reviewPhotoCount) ? review.reviewPhotoCount : 'Tidak tersedia';
        const findings = findingsFor(review).map(([label]) => label).join(', ');
        const cells = [
            index + 1,
            review.author || 'Nama tidak tersedia',
            review.date || '',
            review.rating || '',
            contributorPhotoCount,
            reviewPhotoCount,
            review.text || 'Hanya memberikan rating',
            Number.isInteger(review.wordCount) ? review.wordCount : countWords(review.text),
            review.reply || 'Belum dibalas',
            findings
        ];
        return `<tr class="${index % 2 ? 'row-alt' : ''}">${cells.map((value, cellIndex) => {
            const classes = [];
            if ([0, 3, 4, 5, 7].includes(cellIndex)) classes.push('center');
            if (cellIndex === 0) classes.push('col-no');
            if (cellIndex === 1) classes.push('col-author');
            if (cellIndex === 2) classes.push('col-date');
            if (cellIndex === 3) classes.push('col-rating');
            if (cellIndex === 4 || cellIndex === 5) classes.push('col-photo');
            if (cellIndex === 6) classes.push('col-review');
            if (cellIndex === 7) classes.push('col-words');
            if (cellIndex === 8) classes.push('col-reply');
            if (cellIndex === 9) classes.push('col-findings');
            return `<td class="${classes.join(' ')}">${escapeSpreadsheetText(value)}</td>`;
        }).join('')}</tr>`;
    }).join('');

    const html = `<!doctype html><html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Review Audit</x:Name><x:WorksheetOptions><x:FreezePanes/><x:FrozenNoSplit/><x:SplitHorizontal>8</x:SplitHorizontal><x:TopRowBottomPane>8</x:TopRowBottomPane><x:ProtectObjects>False</x:ProtectObjects><x:ProtectScenarios>False</x:ProtectScenarios></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><style>body{font-family:Calibri,Arial,sans-serif;color:#202228}.title{font-size:18pt;font-weight:bold;color:#1e3a8a}.business{font-size:12pt;font-weight:bold;color:#334155}.meta{color:#64748b}.summary th{background:#eef2f7;color:#334155;border:1px solid #cbd5e1;padding:7px}.summary td{font-size:12pt;font-weight:bold;text-align:center;border:1px solid #cbd5e1;padding:8px}.data{border-collapse:collapse;width:100%}.data th{background:#1e3a8a;color:#fff;font-weight:bold;border:1px solid #17306d;padding:8px}.data td{border:1px solid #cbd5e1;padding:7px;vertical-align:top;white-space:normal;word-wrap:break-word}.data .row-alt{background:#f1f5f9}.data .center{text-align:center}.col-no{width:40px;text-align:center}.col-author{width:160px}.col-date{width:115px;text-align:center;white-space:nowrap}.col-rating{width:55px;text-align:center}.col-photo{width:75px;text-align:center}.col-review{width:440px;mso-number-format:"\\@"}.col-words{width:80px;text-align:center}.col-reply{width:320px;mso-number-format:"\\@"}.col-findings{width:160px}.number{mso-number-format:"0"}.text{mso-number-format:"\\@"}</style></head><body><table><tr><td colspan="10" class="title">LAPORAN AUDIT ULASAN GOOGLE MAPS</td></tr><tr><td colspan="10" class="business">${escapeSpreadsheetText(currentJob.placeName || 'Google Business Profile')}</td></tr><tr><td colspan="10" class="meta">Alamat: ${escapeSpreadsheetText(currentJob.address || '-')}</td></tr><tr><td colspan="10" class="meta">Waktu ekspor: ${escapeSpreadsheetText(exportDate)} | Total review: ${reviews.length}</td></tr></table><br><table class="summary"><tr><th>Total diperiksa</th><th>Rating rendah (1-2)</th><th>Belum dibalas</th><th>Lolos pemeriksaan</th><th>Rating rata-rata</th></tr><tr><td>${reviews.length}</td><td>${priority}</td><td>${unanswered}</td><td>${clear}</td><td>${average} / 5</td></tr></table><br><table class="data"><thead><tr>${headers.map(header => `<th>${escapeSpreadsheetText(header)}</th>`).join('')}</tr></thead><tbody>${bodyRows}</tbody></table></body></html>`;
    const blob = new Blob(['\uFEFF', html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const downloadUrl = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    const safeName = (currentJob.placeName || 'review-audit').normalize('NFKD').replace(/[^\p{L}\p{N}-]+/gu, '_').replace(/^_+|_+$/g, '');
    anchor.href = downloadUrl;
    anchor.download = `Review_Audit_${safeName}_${new Date().toISOString().slice(0, 10)}.xls`;
    document.body.append(anchor);
    anchor.click();
    anchor.remove();
    setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
}

function render() {
    if (!currentJob) return;
    const reviews = (currentJob.reviews || []).map(normalizeReviewData);
    const priority = reviews.filter(review => review.rating <= 2).length;
    const unanswered = reviews.filter(review => !review.reply).length;
    const clear = reviews.filter(review => findingsFor(review).length === 1 && findingsFor(review)[0][0] === 'Lolos pemeriksaan').length;
    const query = document.getElementById('searchReviews').value.trim().toLocaleLowerCase();
    const filtered = reviews.filter(review => `${review.author} ${review.text}`.toLocaleLowerCase().includes(query));
    const visible = filtered.slice(0, visibleLimit);

    document.getElementById('placeName').textContent = currentJob.placeName || 'Google Business Profile';
    document.getElementById('address').textContent = currentJob.address || '';
    document.getElementById('statusText').textContent = currentJob.status === 'complete'
        ? 'Audit selesai'
        : currentJob.status === 'error' ? 'Audit terhenti' : 'Mengumpulkan review';
    document.getElementById('totalCount').textContent = reviews.length.toLocaleString();
    document.getElementById('priorityCount').textContent = priority.toLocaleString();
    document.getElementById('unansweredCount').textContent = unanswered.toLocaleString();
    document.getElementById('clearCount').textContent = clear.toLocaleString();
    document.getElementById('progressText').textContent = `${reviews.length.toLocaleString()} / 1.000`;
    document.getElementById('progressBar').style.width = `${Math.min(100, reviews.length / 10)}%`;
    document.getElementById('resultNote').textContent = currentJob.message || `${reviews.length} review sedang diperiksa.`;
    document.getElementById('visibleCount').textContent = `Menampilkan ${visible.length} dari ${filtered.length} review`;
    document.getElementById('loadMore').hidden = visible.length >= filtered.length;
    document.getElementById('exportXls').disabled = reviews.length === 0;

    const rows = document.getElementById('reviewRows');
    if (!visible.length) {
        rows.innerHTML = `<tr><td class="empty-row" colspan="6">${reviews.length ? 'Tidak ada hasil yang cocok.' : 'Review belum dimuat.'}</td></tr>`;
        return;
    }

    rows.innerHTML = visible.map(review => {
        const findings = findingsFor(review).map(([label, style]) => `<span class="finding ${style}">${escapeHtml(label)}</span>`).join('');
        const stars = '★'.repeat(Math.max(0, Math.min(5, review.rating))) + '☆'.repeat(5 - Math.max(0, Math.min(5, review.rating)));
        const comment = review.text || 'Review hanya berisi rating';
        const wordCount = Number.isInteger(review.wordCount) ? review.wordCount : countWords(comment);
        const contributorPhotoCount = Number.isInteger(review.reviewerPhotoCount)
            ? `${review.reviewerPhotoCount.toLocaleString()} foto`
            : 'Tidak tersedia';
        const reviewPhotoCount = Number.isInteger(review.reviewPhotoCount) ? `${review.reviewPhotoCount.toLocaleString()} foto` : 'Tidak tersedia';
        const reviewPhotoLabel = Number.isInteger(review.reviewPhotoCount) ? `${reviewPhotoCount.toLocaleString()} foto` : reviewPhotoCount;
        return `<tr><td><span class="review-author">${escapeHtml(review.author)}</span><span class="review-date">${escapeHtml(review.date)}</span><span class="stars">${stars}</span></td><td>${escapeHtml(comment)}${review.reply ? `<span class="owner-reply">Balasan owner: ${escapeHtml(review.reply)}</span>` : ''}</td><td class="word-count">${wordCount.toLocaleString()}</td><td><span class="photo-count">${escapeHtml(contributorPhotoCount)}</span></td><td><span class="photo-count">${escapeHtml(reviewPhotoLabel)}</span></td><td>${findings}</td></tr>`;
    }).join('');
}

async function getGriViewBaseUrl() {
    try {
        const stored = await chrome.storage.local.get('griview_base_url');
        if (stored.griview_base_url && typeof stored.griview_base_url === 'string' && stored.griview_base_url.trim()) {
            return stored.griview_base_url.trim().replace(/\/+$/, '');
        }
    } catch (e) {}
    return 'http://localhost/griview';
}

let isSyncingToGriView = false;
let lastSyncedCount = 0;

async function syncToGriView() {
    if (!currentJob || !currentJob.reviews || currentJob.reviews.length === 0) return;
    if (isSyncingToGriView || lastSyncedCount === currentJob.reviews.length) return;

    const baseUrl = await getGriViewBaseUrl();
    const syncEl = document.getElementById('syncStatus');
    if (syncEl) {
        syncEl.innerHTML = '<span style="color:#fcd34d;">⏳ Menyimpan otomatis ke GriView Web...</span>';
    }

    isSyncingToGriView = true;
    try {
        const normalizedReviews = currentJob.reviews.map(normalizeReviewData);
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const clientSavedAt = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;

        const payload = {
            job_id: currentJob.jobId || jobId,
            place_name: currentJob.placeName,
            address: currentJob.address,
            place_url: currentJob.url,
            status: currentJob.status,
            reviews: normalizedReviews,
            client_saved_at: clientSavedAt,
            client_time: Date.now()
        };

        const res = await fetch(`${baseUrl}/index.php?c=review&a=syncAudit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            const data = await res.json();
            lastSyncedCount = currentJob.reviews.length;
            if (syncEl) {
                syncEl.innerHTML = `<span>✓ Tersimpan otomatis ke GriView Web (${lastSyncedCount} ulasan)</span> <a href="${baseUrl}/index.php?c=review&a=audit" target="_blank" style="color:#fff;text-decoration:underline;margin-left:8px;">Buka Hasil Audit</a>`;
            }
        } else {
            if (syncEl) {
                syncEl.innerHTML = `<span style="color:#f87171;">⚠️ Gagal tersimpan ke server (${baseUrl}) - HTTP ${res.status}</span>`;
            }
        }
    } catch (e) {
        if (syncEl) {
            syncEl.innerHTML = `<span style="color:#94a3b8;">ℹ️ Server GriView (${baseUrl}) offline atau belum dibuka</span>`;
        }
    } finally {
        isSyncingToGriView = false;
    }
}

async function loadJob() {
    if (!jobId) return;
    const baseUrl = await getGriViewBaseUrl();
    const brandEl = document.querySelector('.brand');
    if (brandEl) brandEl.href = `${baseUrl}/index.php?c=review&a=audit`;
    const openBtn = document.getElementById('openInGriView');
    if (openBtn) openBtn.href = `${baseUrl}/index.php?c=review&a=audit`;

    const stored = await chrome.storage.local.get(`audit:${jobId}`);
    currentJob = stored[`audit:${jobId}`] || null;
    if (!currentJob) {
        document.getElementById('resultNote').textContent = 'Data audit tidak ditemukan di penyimpanan extension.';
        return;
    }
    render();
    syncToGriView();
}

document.getElementById('searchReviews').addEventListener('input', () => {
    visibleLimit = pageSize;
    render();
});
document.getElementById('loadMore').addEventListener('click', () => {
    visibleLimit += pageSize;
    render();
});
document.getElementById('restartAudit').addEventListener('click', () => {
    chrome.runtime.sendMessage({ type: 'RESTART_REVIEW_AUDIT', jobId });
});
document.getElementById('exportXls').addEventListener('click', exportAuditXls);
const openBtn = document.getElementById('openInGriView');
if (openBtn) {
    openBtn.addEventListener('click', async (e) => {
        const baseUrl = await getGriViewBaseUrl();
        openBtn.href = `${baseUrl}/index.php?c=review&a=audit`;
        if (currentJob && currentJob.reviews && currentJob.reviews.length > 0 && lastSyncedCount !== currentJob.reviews.length) {
            e.preventDefault();
            await syncToGriView();
            window.open(`${baseUrl}/index.php?c=review&a=audit`, '_blank');
        }
    });
}
chrome.storage.onChanged.addListener((changes, area) => {
    if (area === 'local' && changes[`audit:${jobId}`]) {
        currentJob = changes[`audit:${jobId}`].newValue;
        render();
        if (currentJob && currentJob.status === 'complete') {
            syncToGriView();
        }
    }
});

loadJob();