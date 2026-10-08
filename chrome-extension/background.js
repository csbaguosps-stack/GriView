/* License: by cs.baguosps@gmail.com */
const MAX_REVIEWS = 1000;
const jobByTabKey = 'griviewAuditJobsByTab';
const startingAudits = new Set();

function makeJobId() {
    return `${Date.now()}_${crypto.randomUUID().slice(0, 8)}`;
}

function searchAuditUrl(place, sourceUrl, jobId, maxReviews) {
    let url = new URL('https://www.google.com/search');
    if (place.reviewUrl) {
        try {
            const candidate = new URL(place.reviewUrl);
            if ((candidate.hostname === 'www.google.com' || candidate.hostname === 'google.com') && candidate.pathname === '/search') url = candidate;
        } catch {}
    }

    url.searchParams.set('q', `${place.placeName} ${place.address || ''}`.trim());
    url.searchParams.set('hl', 'en-us');
    url.searchParams.set('empty', '0');
    url.searchParams.set('name', place.placeName);
    url.searchParams.set('addr', place.address || '');
    url.searchParams.set('urlFrom', sourceUrl || `https://www.google.com/search?q=${encodeURIComponent(place.placeName)}`);
    url.searchParams.set('phantomAuditComplete', 'review');
    url.searchParams.set('auditId', jobId);
    url.searchParams.set('processed', '0');
    if (maxReviews) {
        url.searchParams.set('maxReviews', String(maxReviews));
        url.searchParams.set('griview_limit', String(maxReviews));
    }

    const sourceHash = sourceUrl ? new URL(sourceUrl).hash : '';
    let reviewUrlHash = '';
    try {
        reviewUrlHash = place.reviewUrl ? new URL(place.reviewUrl).hash : '';
    } catch {}
    const reviewHash = place.reviewHash || reviewUrlHash || (sourceHash.startsWith('#lrd=') ? sourceHash : '');
    if (reviewHash.startsWith('#lrd=')) url.hash = reviewHash;
    return url.href;
}

async function startAudit(sourceTab, place) {
    const jobId = makeJobId();
    let maxLimit = Math.min(1000, Math.max(1, Number(place?.maxReviews) || 0));
    if (!maxLimit) {
        try {
            const stored = await chrome.storage.local.get('griview_armed_audit');
            if (stored.griview_armed_audit?.maxReviews) {
                maxLimit = Math.min(1000, Math.max(1, Number(stored.griview_armed_audit.maxReviews)));
            }
        } catch (e) {}
    }
    if (!maxLimit) maxLimit = MAX_REVIEWS;

    const url = searchAuditUrl(place, sourceTab?.url, jobId, maxLimit);

    // 1 TAB SAJA: Gunakan tab yang sudah dibuka (sourceTab), JANGAN buat tab baru!
    let tabId;
    if (sourceTab?.id) {
        tabId = sourceTab.id;
        await chrome.tabs.update(tabId, { url });
    } else {
        const tabOptions = { url, active: true };
        const tab = await chrome.tabs.create(tabOptions);
        tabId = tab.id;
    }

    const mapping = await chrome.storage.local.get(jobByTabKey);
    const jobsByTab = mapping[jobByTabKey] || {};
    jobsByTab[String(tabId)] = jobId;

    await chrome.storage.local.set({
        [jobByTabKey]: jobsByTab,
        [`audit:${jobId}`]: {
            jobId,
            tabId: tabId,
            placeName: place.placeName,
            address: place.address || '',
            mapsUrl: url,
            reviewUrl: place.reviewUrl || '',
            reviewHash: place.reviewHash || '',
            status: 'starting',
            count: 0,
            maxReviews: maxLimit,
            reviews: [],
            message: `Membuka panel review Google Search (${maxLimit.toLocaleString()} ulasan)...`
        }
    });
}

async function getJobForTab(tabId) {
    const stored = await chrome.storage.local.get(jobByTabKey);
    const jobId = stored[jobByTabKey]?.[String(tabId)];
    if (!jobId) return null;
    const result = await chrome.storage.local.get(`audit:${jobId}`);
    return result[`audit:${jobId}`] || null;
}

async function saveProgress(job, message, senderTabId) {
    const stored = await chrome.storage.local.get(`audit:${job.jobId}`);
    const current = stored[`audit:${job.jobId}`] || job;
    const maxLimit = Math.min(1000, Math.max(1, Number(current.maxReviews) || Number(job.maxReviews) || MAX_REVIEWS));
    const reviews = new Map((current.reviews || []).map(review => [review.id, review]));
    for (const review of message.reviews || []) {
        if (review.id && reviews.size < maxLimit) reviews.set(review.id, review);
    }

    const updated = {
        ...current,
        status: message.status || current.status,
        count: reviews.size,
        maxReviews: maxLimit,
        reviews: Array.from(reviews.values()).slice(0, maxLimit),
        message: message.message || current.message,
        updatedAt: Date.now()
    };
    await chrome.storage.local.set({ [`audit:${job.jobId}`]: updated });

    // Siarkan pembaruan status ke tab-tab aktif (agar tab awal GriView langsung menerima sinyal)
    try {
        const isFinished = updated.status === 'complete' || updated.status === 'stopped';
        chrome.tabs.query({}, (tabs) => {
            for (const t of tabs || []) {
                if (t.id && t.id !== senderTabId) {
                    chrome.tabs.sendMessage(t.id, {
                        type: isFinished ? 'GRIVIEW_AUDIT_COMPLETED' : 'GRIVIEW_AUDIT_PROGRESS',
                        jobId: job.jobId,
                        placeName: updated.placeName,
                        count: updated.count,
                        maxReviews: maxLimit,
                        status: updated.status,
                        message: updated.message
                    }).catch(() => {});
                }
            }
        });
    } catch (e) {}

    if (message.status === 'complete' || message.status === 'error') {
        const tabId = updated.tabId || job.tabId || senderTabId;
        if (tabId) {
            await chrome.tabs.update(tabId, {
                url: chrome.runtime.getURL(`audit.html?job=${encodeURIComponent(job.jobId)}`),
                active: true
            });
        }
    }
}

chrome.runtime.onMessage.addListener((message, sender) => {
    if (message.type === 'GRIVIEW_AUDIT_SYNCED') {
        try {
            chrome.tabs.query({}, (tabs) => {
                for (const t of tabs || []) {
                    chrome.tabs.sendMessage(t.id, {
                        type: 'GRIVIEW_AUDIT_COMPLETED',
                        ...message
                    }).catch(() => {});
                }
            });
        } catch (e) {}
        return;
    }

    if (message.type === 'ARM_PENDING_AUDIT') {
        const audit = message.audit;
        if (audit) {
            chrome.storage.local.set({ griview_armed_audit: audit });
        }
        return;
    }

    if (message.type === 'START_REVIEW_AUDIT') {
        const tabId = sender.tab?.id || 'popup';
        const placeKey = (message.place?.placeName || '').trim();
        const requestKey = `${tabId}_${placeKey}`;

        // Debounce: cegah eksekusi berulang jika sudah diproses dalam 5 detik
        if (startingAudits.has(requestKey)) {
            console.log('GriView: Mengabaikan request audit ganda untuk', requestKey);
            return;
        }
        startingAudits.add(requestKey);
        setTimeout(() => startingAudits.delete(requestKey), 5000);

        startAudit(sender.tab, message.place).catch(error => {
            console.error('GriView could not open the audit tab:', error);
            startingAudits.delete(requestKey);
        });
        return;
    }

    if (message.type === 'MAPS_AUDIT_READY' && sender.tab?.id) {
        getJobForTab(sender.tab.id).then(async job => {
            if (job) {
                chrome.tabs.sendMessage(sender.tab.id, {
                    type: 'BEGIN_REVIEW_AUDIT',
                    jobId: job.jobId,
                    placeName: job.placeName,
                    maxReviews: job.maxReviews || MAX_REVIEWS
                });
                return;
            }

            // Periksa apakah ada audit yang dipersenjatai dari GriView Web
            const stored = await chrome.storage.local.get('griview_armed_audit');
            const armed = stored.griview_armed_audit;
            if (armed && (Date.now() - (armed.timestamp || 0) < 120000)) {
                // Tab ini adalah target audit!
                await chrome.storage.local.remove('griview_armed_audit');
                const jobId = makeJobId();
                const mapping = await chrome.storage.local.get(jobByTabKey);
                const jobsByTab = mapping[jobByTabKey] || {};
                jobsByTab[String(sender.tab.id)] = jobId;

                const max = armed.maxReviews || MAX_REVIEWS;
                await chrome.storage.local.set({
                    [jobByTabKey]: jobsByTab,
                    [`audit:${jobId}`]: {
                        jobId,
                        tabId: sender.tab.id,
                        placeName: armed.placeName || '',
                        address: '',
                        mapsUrl: sender.tab.url || '',
                        status: 'starting',
                        count: 0,
                        maxReviews: max,
                        reviews: [],
                        message: 'Memulai auto-audit ulasan...'
                    }
                });

                chrome.tabs.sendMessage(sender.tab.id, {
                    type: 'BEGIN_REVIEW_AUDIT',
                    jobId: jobId,
                    placeName: armed.placeName || '',
                    maxReviews: max
                });
            }
        });
        return;
    }

    if (message.type === 'REVIEW_AUDIT_PROGRESS' && sender.tab?.id) {
        getJobForTab(sender.tab.id).then(job => job && saveProgress(job, message, sender.tab.id));
        return;
    }

    if (message.type === 'STOP_REVIEW_AUDIT' && sender.tab?.id) {
        chrome.tabs.sendMessage(sender.tab.id, { type: 'STOP_REVIEW_AUDIT' }).catch(() => {});
        return;
    }

    if (message.type === 'RESTART_REVIEW_AUDIT') {
        const jobId = message.jobId;
        chrome.storage.local.get(`audit:${jobId}`).then(result => {
            const job = result[`audit:${jobId}`];
            if (job) {
                startAudit(sender.tab, {
                    placeName: job.placeName,
                    address: job.address,
                    mapsUrl: job.mapsUrl,
                    reviewUrl: job.reviewUrl,
                    reviewHash: job.reviewHash
                });
            }
        });
    }
});

chrome.tabs.onRemoved.addListener(async tabId => {
    const stored = await chrome.storage.local.get(jobByTabKey);
    const jobsByTab = stored[jobByTabKey] || {};
    delete jobsByTab[String(tabId)];
    await chrome.storage.local.set({ [jobByTabKey]: jobsByTab });
});