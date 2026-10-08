/* License: by cs.baguosps@gmail.com */
/**
 * GriView Bridge Script - Menghubungkan GriView Web dengan Chrome Extension
 * Berjalan pada halaman web GriView untuk mendeteksi ekstensi dan sinkronisasi Base URL.
 * 100% CSP-Compliant (tidak menggunakan injeksi script inline).
 */
(function () {
    // 1. Abaikan Google Search, Google Maps, dan domain eksternal non-GriView
    const host = (window.location.hostname || '').toLowerCase();
    if (host.includes('google.') || host.includes('gstatic.') || host.includes('googleapis.') || host.includes('g.co')) {
        return;
    }

    function isGriViewPage() {
        return !!(
            document.querySelector('meta[name="griview-app"]') ||
            document.querySelector('meta[name="griview-base-url"]') ||
            (document.title && document.title.includes('GriView')) ||
            document.getElementById('modalSyncGoogle') ||
            document.getElementById('extensionStatusBanner')
        );
    }

    function markInstalled() {
        try {
            // Tandai atribut DOM (aman dari CSP & terbaca langsung oleh skrip web)
            if (document.documentElement) {
                document.documentElement.setAttribute('data-griview-extension', 'installed');
                document.documentElement.setAttribute('data-griview-version', '1.0.5');
            }

            // Simpan status aktif di storage browser lokal
            try {
                sessionStorage.setItem('griview_extension_active', '1');
                localStorage.setItem('griview_extension_active', '1');
            } catch (e) {}

            // Kirim CustomEvent ke halaman web
            try {
                window.dispatchEvent(new CustomEvent('GriViewExtensionReady', {
                    detail: { version: '1.0.5', installed: true }
                }));
            } catch (e) {}

            // Deteksi & simpan Base URL aktif GriView secara otomatis ke chrome.storage.local
            const metaBase = document.querySelector('meta[name="griview-base-url"]');
            let detectedBaseUrl = null;
            if (metaBase && metaBase.content) {
                detectedBaseUrl = metaBase.content.trim().replace(/\/+$/, '');
            } else if (isGriViewPage()) {
                detectedBaseUrl = (window.location.origin + window.location.pathname.replace(/\/index\.php.*$/, '')).replace(/\/+$/, '');
            }

            if (detectedBaseUrl && typeof chrome !== 'undefined' && chrome.storage && chrome.storage.local) {
                chrome.storage.local.set({ griview_base_url: detectedBaseUrl });
            }
        } catch (e) {}
    }

    function initBridge() {
        if (!isGriViewPage()) {
            return;
        }
        markInstalled();
    }

    // Jalankan segera dan saat DOM siap
    initBridge();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBridge);
    } else {
        setTimeout(initBridge, 100);
    }

    // Dengarkan pesan dari GriView Web (handshake via postMessage & arming audit)
    window.addEventListener('message', function (event) {
        if (!event.data) return;

        if (event.data.type === 'GRIVIEW_PING_EXTENSION') {
            markInstalled();
            window.postMessage({
                type: 'GRIVIEW_PONG_EXTENSION',
                installed: true,
                version: '1.0.5'
            }, '*');
        } else if (event.data.type === 'GRIVIEW_ARM_AUDIT') {
            const auditData = {
                timestamp: Date.now(),
                placeName: event.data.placeName || '',
                cid: event.data.cid || '',
                reviewHash: event.data.reviewHash || '',
                targetUrl: event.data.url || event.data.targetUrl || '',
                maxReviews: Number(event.data.maxReviews) || 1000,
                storeId: event.data.storeId || ''
            };

            if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.local) {
                chrome.storage.local.set({ griview_armed_audit: auditData });
            }

            if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.sendMessage) {
                chrome.runtime.sendMessage({
                    type: 'ARM_PENDING_AUDIT',
                    audit: auditData
                }).catch(() => {});
            }

            window.postMessage({
                type: 'GRIVIEW_ARM_AUDIT_ACK',
                success: true,
                placeName: auditData.placeName
            }, '*');
        }
    });

    // Dengarkan perubahan chrome.storage untuk pembaruan status audit ke halaman web GriView
    if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.onChanged) {
        chrome.storage.onChanged.addListener(function (changes, area) {
            if (area !== 'local') return;

            // 1. Sinkronisasi sukses ke GriView backend
            if (changes.griview_last_sync && changes.griview_last_sync.newValue) {
                const sync = changes.griview_last_sync.newValue;
                window.postMessage({
                    type: 'GRIVIEW_AUDIT_COMPLETED',
                    jobId: sync.jobId,
                    placeName: sync.placeName,
                    count: sync.count,
                    downloadUrl: sync.downloadUrl,
                    auditUrl: sync.auditUrl,
                    status: 'complete',
                    source: 'last_sync'
                }, '*');
            }

            // 2. Perubahan progres atau status job audit
            for (const key in changes) {
                if (key.startsWith('audit:')) {
                    const job = changes[key].newValue;
                    if (!job) continue;
                    if (job.status === 'complete' || job.status === 'stopped') {
                        window.postMessage({
                            type: 'GRIVIEW_AUDIT_COMPLETED',
                            jobId: job.jobId,
                            placeName: job.placeName,
                            count: job.count || (job.reviews ? job.reviews.length : 0),
                            maxReviews: job.maxReviews,
                            status: job.status,
                            message: job.message,
                            source: 'storage_audit'
                        }, '*');
                    } else if (job.status === 'running' || job.status === 'starting') {
                        window.postMessage({
                            type: 'GRIVIEW_AUDIT_PROGRESS',
                            jobId: job.jobId,
                            placeName: job.placeName,
                            count: job.count || (job.reviews ? job.reviews.length : 0),
                            maxReviews: job.maxReviews,
                            status: job.status,
                            message: job.message
                        }, '*');
                    }
                }
            }
        });
    }

    // Dengarkan pesan broadcast runtime dari background
    if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.onMessage) {
        chrome.runtime.onMessage.addListener(function (msg) {
            if (!msg) return;
            if (msg.type === 'GRIVIEW_AUDIT_COMPLETED' || msg.type === 'GRIVIEW_AUDIT_PROGRESS' || msg.type === 'GRIVIEW_AUDIT_SYNCED') {
                window.postMessage(msg, '*');
            }
        });
    }
})();
