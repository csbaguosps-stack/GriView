/* License: by cs.baguosps@gmail.com */
(function () {
    const searchParams = new URLSearchParams(location.search);
    if (searchParams.get('phantomAuditComplete') === 'review') return;
    if (window.__griviewAuditButtonInstalled) return;
    window.__griviewAuditButtonInstalled = true;

    const isAutoAudit = searchParams.get('griview_auto_audit') === '1' ||
                        searchParams.get('griview_auto') === '1' ||
                        location.hash.includes('griview_auto_audit');

    let autoTriggered = false;

    function showAutoNotification(text) {
        let notif = document.getElementById('griview-auto-indicator');
        if (!notif) {
            notif = document.createElement('div');
            notif.id = 'griview-auto-indicator';
            notif.innerHTML = `
                <div style="width:22px;height:22px;border:3px solid #10b981;border-top-color:transparent;border-radius:50%;animation:griview-spin 0.8s linear infinite;flex-shrink:0;"></div>
                <div style="line-height:1.3;">
                    <div style="font-weight:700;color:#f8fafc;font-size:13px;letter-spacing:0.2px;">⚡ GriView Auto-Audit Aktif</div>
                    <div style="font-size:11.5px;color:#94a3b8;margin-top:2px;" id="griview-auto-msg">${text}</div>
                </div>
            `;
            Object.assign(notif.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: '2147483647',
                background: '#17191d',
                color: '#f8fafc',
                padding: '12px 18px',
                borderRadius: '10px',
                boxShadow: '0 10px 30px rgba(0,0,0,0.35)',
                display: 'flex',
                alignItems: 'center',
                gap: '12px',
                fontFamily: 'system-ui, -apple-system, sans-serif',
                borderLeft: '4px solid #10b981',
                animation: 'griview-fadein 0.3s ease-out'
            });

            document.documentElement.appendChild(notif);
        } else {
            const msgEl = document.getElementById('griview-auto-msg');
            if (msgEl) msgEl.textContent = text;
        }
    }

    function visible(element) {
        return !!element && element.getClientRects().length > 0;
    }

    function findBusinessPanel() {
        const knownPanel = document.querySelector('#rhs');
        const placeBlock = document.querySelector('[data-attrid="kc:/local:place"]');
        if (knownPanel && placeBlock && knownPanel.contains(placeBlock)) return knownPanel;
        if (placeBlock) return placeBlock.closest('#rhs') || placeBlock.parentElement?.parentElement?.parentElement;

        const title = document.querySelector('[data-attrid="title"]');
        return title?.closest('#rhs') || title?.parentElement?.parentElement?.parentElement?.parentElement;
    }

    function readPlace(panel) {
        const titleCandidates = [
            panel.querySelector('[data-attrid="title"]'),
            panel.querySelector('.kno-ecr-pt'),
            ...panel.querySelectorAll('h1, h2, h3')
        ].filter(Boolean);
        const title = titleCandidates.find(candidate =>
            !/^(hasil terkait|related searches|results for|tempat terkait)$/i.test(candidate.textContent.trim())
        );
        const placeName = title?.textContent?.trim();
        if (!placeName || placeName.length > 140) return null;

        const addressNode = panel.querySelector('[data-attrid*="address"]');
        const address = addressNode?.textContent?.replace(/^Alamat:\s*/i, '').trim() || '';
        const links = Array.from(panel.querySelectorAll('a[href]'));
        const reviewControl = Array.from(panel.querySelectorAll('a[href], button, [role="button"]')).find(control => {
            const label = `${control.getAttribute('aria-label') || ''} ${control.innerText || control.textContent || ''}`.trim();
            return /(?:\d[\d,.]*\s*)?(?:google\s*)?(?:reviews?|ulasan)/i.test(label) &&
                !/write|tulis|reply|balas|reviewer/i.test(label);
        });
        const reviewLink = links.find(link => link.href.includes('#lrd=')) ||
            (reviewControl?.href ? reviewControl : null) ||
            (reviewControl?.closest('a[href]') || null);
        const mapsLink = links.find(link =>
            /maps\.app\.goo\.gl|google\.[^/]+\/maps\/place\/|google\.[^/]+\/maps\/search|google\.[^/]+\/maps\?(?:.*&)?(?:cid|q)=/.test(link.href) &&
            !/\/maps\/dir\//.test(link.href)
        );
        const reviewUrl = reviewLink?.href || reviewControl?.getAttribute('data-href') || '';
        const mapCid = mapsLink?.href.match(/!1s(0x[\da-f]+:0x[\da-f]+)/i)?.[1] || '';
        const reviewHash = (reviewUrl.includes('#lrd=') ? new URL(reviewUrl).hash : '') ||
            (location.hash.startsWith('#lrd=') ? location.hash : '') ||
            (mapCid ? `#lrd=${mapCid},1,,,` : '');

        return {
            placeName,
            address,
            mapsUrl: mapsLink?.href || '',
            reviewUrl,
            reviewHash
        };
    }

    function checkAndAutoRun() {
        if (!isAutoAudit || autoTriggered || window.__griviewAutoAuditTriggered) return;
        const panel = findBusinessPanel();
        if (!panel) return;
        const place = readPlace(panel);
        if (!place || !place.placeName) return;

        autoTriggered = true;
        window.__griviewAutoAuditTriggered = true;
        showAutoNotification(`Profil terdeteksi: <strong>${place.placeName}</strong>. Membuka panel ulasan otomatis...`);
        console.log('GriView Auto-Audit: Menjalankan audit otomatis untuk', place.placeName);

        setTimeout(() => {
            chrome.runtime.sendMessage({ type: 'START_REVIEW_AUDIT', place });
        }, 400);
    }

    function installButton() {
        const panel = findBusinessPanel();
        if (!panel) return;
        const place = readPlace(panel);
        if (!place) return;

        if (isAutoAudit && !autoTriggered) {
            checkAndAutoRun();
        }

        if (panel.querySelector('.griview-audit-launch')) return;

        const tools = document.createElement('div');
        tools.className = 'griview-audit-tools';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'griview-audit-launch';
        button.innerHTML = '<span class="griview-audit-symbol">✓</span><span>Review Audit</span>';
        button.title = 'Audit hingga 1.000 review di tab baru';

        if (autoTriggered) {
            button.disabled = true;
            button.querySelector('span:last-child').textContent = 'Audit Berjalan Otomatis...';
        }

        button.addEventListener('click', () => {
            button.disabled = true;
            button.querySelector('span:last-child').textContent = 'Membuka audit...';
            chrome.runtime.sendMessage({ type: 'START_REVIEW_AUDIT', place });
        });
        tools.append(button);

        const category = Array.from(panel.querySelectorAll('[data-attrid], div, span'))
            .find(element => visible(element) && /^(categories|kategori)\s*:/i.test(element.textContent.trim()) && element.children.length < 5);
        const anchor = category?.closest('[data-attrid]') || category;
        if (anchor?.parentElement) anchor.parentElement.insertBefore(tools, anchor.nextSibling);
        else panel.append(tools);
    }

    if (isAutoAudit) {
        showAutoNotification('Mendeteksi profil Google Maps...');
        let pollCount = 0;
        const pollInterval = setInterval(() => {
            pollCount++;
            checkAndAutoRun();
            if (autoTriggered || pollCount > 40) {
                clearInterval(pollInterval);
            }
        }, 300);
    }

    let installQueued = false;
    function queueInstall() {
        if (installQueued) return;
        installQueued = true;
        setTimeout(() => {
            installQueued = false;
            installButton();
            if (isAutoAudit && !autoTriggered) checkAndAutoRun();
        }, 200);
    }

    installButton();
    new MutationObserver(queueInstall).observe(document.documentElement, { childList: true, subtree: true });
})();