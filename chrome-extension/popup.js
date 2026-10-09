/* License: by cs.baguosps@gmail.com */
document.addEventListener('DOMContentLoaded', async () => {
    // DOM Elements
    const auditForm         = document.getElementById('auditForm');
    const inputQuery        = document.getElementById('inputQuery');
    const btnClearInput     = document.getElementById('btnClearInput');
    const selectLimit       = document.getElementById('selectLimit');
    const limitBadge        = document.getElementById('limitBadge');
    const chipBtns          = document.querySelectorAll('.chip-btn');
    const btnStartAudit     = document.getElementById('btnStartAudit');
    const btnStartText      = document.getElementById('btnStartText');
    const btnReloadExt      = document.getElementById('btnReloadExt');
    const btnReloadFull     = document.getElementById('btnReloadFull');
    const btnReloadFullText = document.getElementById('btnReloadFullText');
    const btnOpenWeb        = document.getElementById('btnOpenWeb');
    const toastBanner       = document.getElementById('toastBanner');
    const toastIcon         = document.getElementById('toastIcon');
    const toastMessage      = document.getElementById('toastMessage');
    const detectedTabCard   = document.getElementById('detectedTabCard');
    const detectedTabTitle  = document.getElementById('detectedTabTitle');
    const btnUseDetected    = document.getElementById('btnUseDetected');

    // Active Job Elements
    const activeAuditCard   = document.getElementById('activeAuditCard');
    const activeStatusLabel = document.getElementById('activeStatusLabel');
    const activeCountText   = document.getElementById('activeCountText');
    const activePlaceName   = document.getElementById('activePlaceName');
    const activeProgressBar = document.getElementById('activeProgressBar');
    const btnOpenAuditTab   = document.getElementById('btnOpenAuditTab');
    const btnStopAudit      = document.getElementById('btnStopAudit');

    let detectedTabUrl = '';
    let detectedTabQuery = '';
    let currentActiveJob = null;
    let toastTimeout = null;

    // Toast helper
    function showToast(message, type = 'info', duration = 3000) {
        if (!toastBanner) return;
        if (toastTimeout) clearTimeout(toastTimeout);

        toastBanner.className = `toast-banner ${type}`;
        if (toastIcon) {
            toastIcon.textContent = type === 'success' ? '✓' : type === 'warning' ? '⚠' : type === 'error' ? '✕' : 'ℹ';
        }
        if (toastMessage) {
            toastMessage.textContent = message;
        }

        toastBanner.classList.remove('hidden');
        toastTimeout = setTimeout(() => {
            toastBanner.classList.add('hidden');
        }, duration);
    }

    // Update Limit UI (dropdown & chips)
    function setLimitValue(val) {
        const numVal = Math.min(1000, Math.max(1, Number(val) || 1000));
        selectLimit.value = String(numVal);
        limitBadge.textContent = `${numVal.toLocaleString('id-ID')} Ulasan`;

        chipBtns.forEach(chip => {
            if (chip.getAttribute('data-value') === String(numVal)) {
                chip.classList.add('active');
            } else {
                chip.classList.remove('active');
            }
        });
    }

    // Event listener for limit select dropdown
    selectLimit.addEventListener('change', () => {
        setLimitValue(selectLimit.value);
    });

    // Event listeners for chip buttons
    chipBtns.forEach(chip => {
        chip.addEventListener('click', () => {
            const val = chip.getAttribute('data-value');
            setLimitValue(val);
        });
    });

    // Clear input button
    inputQuery.addEventListener('input', () => {
        if (inputQuery.value.trim().length > 0) {
            btnClearInput.classList.remove('hidden');
        } else {
            btnClearInput.classList.add('hidden');
        }
    });

    btnClearInput.addEventListener('click', () => {
        inputQuery.value = '';
        btnClearInput.classList.add('hidden');
        inputQuery.focus();
    });

    // 1. Detect Active Chrome Tab
    try {
        const [activeTab] = await chrome.tabs.query({ active: true, currentWindow: true });
        if (activeTab && activeTab.url) {
            const url = activeTab.url;
            const isMaps = /maps\.google|google\.[^/]+\/maps|maps\.app\.goo\.gl|goo\.gl\/maps/i.test(url);
            const isSearch = /google\.[^/]+\/search/i.test(url);

            if (isMaps || isSearch) {
                let cleanTitle = (activeTab.title || '').replace(/\s*-\s*Google Maps.*$/i, '').trim();
                cleanTitle = cleanTitle.replace(/\s*-\s*Penelusuran Google.*$/i, '').trim();
                cleanTitle = cleanTitle.replace(/\s*-\s*Google Search.*$/i, '').trim();

                if (cleanTitle && !/^(google maps|maps|google|penelusuran)$/i.test(cleanTitle)) {
                    detectedTabUrl = url;
                    detectedTabQuery = cleanTitle;
                    detectedTabTitle.textContent = cleanTitle;
                    detectedTabCard.classList.remove('hidden');
                }
            }
        }
    } catch (e) {
        console.warn('GriView Popup: Tidak dapat memeriksa tab aktif:', e);
    }

    // Use detected tab
    btnUseDetected.addEventListener('click', () => {
        if (detectedTabUrl) {
            // Jika shortlink atau link Maps, isi URL atau nama tempat
            inputQuery.value = detectedTabUrl;
            btnClearInput.classList.remove('hidden');
            detectedTabCard.classList.add('hidden');
            inputQuery.focus();
            showToast(`Menggunakan tautan dari tab aktif: ${detectedTabQuery}`, 'info', 2000);
        }
    });

    // 2. Restore saved settings & Base URL
    try {
        const stored = await chrome.storage.local.get([
            'griview_base_url',
            'griview_last_query',
            'griview_last_limit'
        ]);

        if (stored.griview_last_limit) {
            setLimitValue(stored.griview_last_limit);
        }

        // Jika input masih kosong dan ada last query, gunakan sebagai placeholder/default
        if (!inputQuery.value && stored.griview_last_query) {
            inputQuery.value = stored.griview_last_query;
            btnClearInput.classList.remove('hidden');
        }

        if (stored.griview_base_url && typeof stored.griview_base_url === 'string') {
            btnOpenWeb.href = `${stored.griview_base_url.replace(/\/+$/, '')}/index.php?c=review&a=audit`;
            btnOpenWeb.classList.remove('hidden');
        } else {
            btnOpenWeb.classList.add('hidden');
        }
    } catch (e) {}

    // 3. Form Submit / Start Audit
    auditForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const rawInput = (inputQuery.value || '').trim();

        if (!rawInput) {
            inputQuery.classList.add('shake');
            setTimeout(() => inputQuery.classList.remove('shake'), 400);
            inputQuery.focus();
            showToast('Silakan isi Link Google Maps atau Nama Tempat!', 'warning', 2500);
            return;
        }

        const limit = Math.min(1000, Math.max(1, Number(selectLimit.value) || 1000));

        // Save last used query & limit
        try {
            await chrome.storage.local.set({
                griview_last_query: rawInput,
                griview_last_limit: limit
            });
        } catch (e) {}

        btnStartAudit.disabled = true;
        btnStartText.textContent = 'Menyiapkan Audit...';

        const isUrl = /^https?:\/\//i.test(rawInput);
        let targetUrl = '';
        let targetPlaceName = '';

        if (isUrl) {
            targetUrl = rawInput;
            try {
                const parsed = new URL(targetUrl);
                parsed.searchParams.set('griview_auto_audit', '1');
                parsed.searchParams.set('griview_limit', String(limit));
                parsed.hash = '#griview_auto_audit=1';
                targetUrl = parsed.toString();
            } catch (err) {
                const sep = targetUrl.includes('?') ? '&' : '?';
                targetUrl = targetUrl + sep + `griview_auto_audit=1&griview_limit=${limit}#griview_auto_audit=1`;
            }
        } else {
            targetPlaceName = rawInput;
            targetUrl = `https://www.google.com/search?q=${encodeURIComponent(rawInput)}&hl=en-us&griview_auto_audit=1&griview_limit=${limit}`;
        }

        // Arm the audit in storage & send message
        const auditPayload = {
            timestamp: Date.now(),
            placeName: targetPlaceName,
            targetUrl: isUrl ? rawInput : '',
            maxReviews: limit
        };

        try {
            await chrome.storage.local.set({ griview_armed_audit: auditPayload });
            chrome.runtime.sendMessage({
                type: 'ARM_PENDING_AUDIT',
                audit: auditPayload
            }).catch(() => {});
        } catch (e) {}

        showToast('Membuka tab audit...', 'success', 2000);
        btnStartText.textContent = 'Membuka Tab... 🚀';

        // Buka tab target
        setTimeout(async () => {
            try {
                const [activeTab] = await chrome.tabs.query({ active: true, currentWindow: true });
                if (activeTab && (activeTab.url === 'chrome://newtab/' || activeTab.url === 'about:blank')) {
                    await chrome.tabs.update(activeTab.id, { url: targetUrl });
                } else {
                    await chrome.tabs.create({ url: targetUrl, active: true });
                }
                setTimeout(() => window.close(), 600);
            } catch (err) {
                console.error('Gagal membuka tab audit:', err);
                chrome.tabs.create({ url: targetUrl, active: true });
                setTimeout(() => window.close(), 600);
            }
        }, 300);
    });

    // 4. Reload Ekstensi Handler
    async function handleReloadExtension(btn, labelEl) {
        if (btn) btn.disabled = true;
        if (labelEl) labelEl.textContent = 'Memuat ulang...';
        showToast('Memuat ulang ekstensi Chrome... 🔄', 'info', 2000);

        setTimeout(() => {
            try {
                chrome.runtime.reload();
            } catch (e) {
                console.error('Gagal reload ekstensi:', e);
                window.location.reload();
            }
        }, 300);
    }

    if (btnReloadExt) {
        btnReloadExt.addEventListener('click', () => {
            handleReloadExtension(btnReloadExt, null);
        });
    }

    if (btnReloadFull) {
        btnReloadFull.addEventListener('click', () => {
            handleReloadExtension(btnReloadFull, btnReloadFullText);
        });
    }

    // 5. Monitor Live Active Audit Job (jika ada yang sedang berjalan)
    async function checkActiveAuditStatus() {
        try {
            const data = await chrome.storage.local.get(null);
            const auditKeys = Object.keys(data).filter(k => k.startsWith('audit:'));
            if (!auditKeys.length) {
                activeAuditCard.classList.add('hidden');
                return;
            }

            // Cari job yang paling terbaru
            let latestJob = null;
            for (const key of auditKeys) {
                const job = data[key];
                if (!job) continue;
                if (!latestJob || (job.updatedAt || 0) > (latestJob.updatedAt || 0)) {
                    latestJob = job;
                }
            }

            if (!latestJob) {
                activeAuditCard.classList.add('hidden');
                return;
            }

            const isRecent = (Date.now() - (latestJob.updatedAt || Date.now())) < 180000; // 3 menit
            const isRunning = latestJob.status === 'starting' || latestJob.status === 'running';

            if (isRunning || (latestJob.status === 'complete' && isRecent)) {
                currentActiveJob = latestJob;
                activeAuditCard.classList.remove('hidden');

                activePlaceName.textContent = latestJob.placeName || 'Google Business';
                const count = Number(latestJob.count || 0);
                const max = Number(latestJob.maxReviews || 1000);
                activeCountText.textContent = `${count.toLocaleString('id-ID')} / ${max.toLocaleString('id-ID')}`;

                const pct = Math.min(100, Math.max(5, Math.round((count / Math.max(1, max)) * 100)));
                activeProgressBar.style.width = `${pct}%`;

                if (isRunning) {
                    activeStatusLabel.textContent = 'Sedang Berjalan';
                    btnStopAudit.classList.remove('hidden');
                } else {
                    activeStatusLabel.textContent = 'Selesai';
                    activeStatusLabel.parentElement.style.color = '#34d399';
                    btnStopAudit.classList.add('hidden');
                }
            } else {
                activeAuditCard.classList.add('hidden');
            }
        } catch (e) {}
    }

    // Check status segera dan per 2 detik
    checkActiveAuditStatus();
    const statusInterval = setInterval(checkActiveAuditStatus, 2000);
    window.addEventListener('unload', () => clearInterval(statusInterval));

    // Button Buka Tab Audit
    btnOpenAuditTab.addEventListener('click', async () => {
        if (!currentActiveJob) return;
        const targetUrl = chrome.runtime.getURL(`audit.html?job=${encodeURIComponent(currentActiveJob.jobId)}`);

        if (currentActiveJob.tabId) {
            try {
                await chrome.tabs.update(currentActiveJob.tabId, { active: true });
                window.close();
                return;
            } catch (e) {}
        }

        chrome.tabs.create({ url: targetUrl, active: true });
        window.close();
    });

    // Button Stop Audit
    btnStopAudit.addEventListener('click', async () => {
        if (!currentActiveJob) return;
        btnStopAudit.disabled = true;
        btnStopAudit.textContent = 'Berhenti...';

        try {
            if (currentActiveJob.tabId) {
                chrome.tabs.sendMessage(currentActiveJob.tabId, { type: 'STOP_REVIEW_AUDIT' }).catch(() => {});
            }
            chrome.runtime.sendMessage({ type: 'STOP_REVIEW_AUDIT' }).catch(() => {});
            showToast('Permintaan stop dikirim...', 'warning', 2000);
            setTimeout(checkActiveAuditStatus, 500);
        } catch (e) {}
    });
});
