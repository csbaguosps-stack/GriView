/* License: by cs.baguosps@gmail.com */
const MAX_REVIEWS = 1000;
const jobByTabKey = 'griviewAuditJobsByTab';
const startingAudits = new Set();

function makeJobId() {
    return `${Date.now()}_${crypto.randomUUID().slice(0, 8)}`;
}

function searchAuditUrl(place, sourceUrl, jobId) {
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
    const url = searchAuditUrl(place, sourceTab?.url, jobId);

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
            maxReviews: MAX_REVIEWS,
            reviews: [],
            message: 'Membuka panel review Google Search...'
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

async function saveProgress(job, message) {
    const stored = await chrome.storage.local.get(`audit:${job.jobId}`);
    const current = stored[`audit:${job.jobId}`] || job;
    const reviews = new Map((current.reviews || []).map(review => [review.id, review]));
    for (const review of message.reviews || []) {
        if (review.id) reviews.set(review.id, review);
    }

    const updated = {
        ...current,
        status: message.status || current.status,
        count: reviews.size,
        reviews: Array.from(reviews.values()).slice(0, MAX_REVIEWS),
        message: message.message || current.message,
        updatedAt: Date.now()
    };
    await chrome.storage.local.set({ [`audit:${job.jobId}`]: updated });

    if (message.status === 'complete' || message.status === 'error') {
        const tabId = updated.tabId;
        if (tabId) {
            await chrome.tabs.update(tabId, {
                url: chrome.runtime.getURL(`audit.html?job=${encodeURIComponent(job.jobId)}`),
                active: true
            });
        }
    }
}

chrome.runtime.onMessage.addListener((message, sender) => {
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
        getJobForTab(sender.tab.id).then(job => {
            if (job) {
                chrome.tabs.sendMessage(sender.tab.id, {
                    type: 'BEGIN_REVIEW_AUDIT',
                    jobId: job.jobId,
                    placeName: job.placeName,
                    maxReviews: MAX_REVIEWS
                });
            }
        });
        return;
    }

    if (message.type === 'REVIEW_AUDIT_PROGRESS' && sender.tab?.id) {
        getJobForTab(sender.tab.id).then(job => job && saveProgress(job, message));
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