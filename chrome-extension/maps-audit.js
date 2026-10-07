/* License: by cs.baguosps@gmail.com */
(function () {
    if (window.__griviewMapsAuditReady) return;
    window.__griviewMapsAuditReady = true;

    const delay = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));
    const searchAuditMode = location.pathname === '/search' && new URLSearchParams(location.search).get('phantomAuditComplete') === 'review';
    let running = false;
    let overlay;

    function updateOverlay(count, max, message) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'griview-audit-progress';
            overlay.innerHTML = '<strong>GriView Review Audit</strong><span class="griview-audit-count"></span><div class="griview-audit-track"><i></i></div><small class="griview-audit-message"></small>';
            Object.assign(overlay.style, {
                position: 'fixed', top: '16px', right: '16px', zIndex: '2147483647',
                width: '280px', padding: '14px', borderRadius: '7px',
                background: '#202228', color: '#fff', font: '13px Arial, sans-serif',
                boxShadow: '0 6px 24px rgba(0,0,0,.3)'
            });
            const track = overlay.querySelector('.griview-audit-track');
            Object.assign(track.style, { height: '5px', margin: '9px 0', background: '#41434b', borderRadius: '4px', overflow: 'hidden' });
            Object.assign(track.firstElementChild.style, { display: 'block', height: '100%', width: '0', background: '#838cff', transition: 'width .2s ease' });
            overlay.querySelector('.griview-audit-count').style.cssText = 'display:block;margin-top:8px;color:#d5d6dc';
            overlay.querySelector('.griview-audit-message').style.cssText = 'display:block;color:#b8bac2;font-size:11px';
            document.documentElement.append(overlay);
        }
        overlay.querySelector('.griview-audit-count').textContent = `${count.toLocaleString()} / ${max.toLocaleString()} review`;
        overlay.querySelector('.griview-audit-track i').style.width = `${Math.min(100, Math.round(count / max * 100))}%`;
        overlay.querySelector('.griview-audit-message').textContent = message;
    }

    function updateProcessedCount(count) {
        if (!searchAuditMode) return;
        const url = new URL(location.href);
        url.searchParams.set('processed', String(count));
        history.replaceState(history.state, '', url.href);
    }

    function getStarPaths(card) {
        return Array.from(card.querySelectorAll('svg path'))
            .filter(path => /^M6\s+\.6L2\.6\s+11\.1/.test(path.getAttribute('d') || ''));
    }

    function getReviewRating(card) {
        const ratingNode = Array.from(card.querySelectorAll('[role="img"], [aria-label]'))
            .find(node => /(?:rated\s*)?[1-5](?:[.,]\d)?\s*(?:out of 5|stars?|bintang)|rating\s*[1-5]/i.test(node.getAttribute('aria-label') || ''));
        const ratingText = ratingNode?.getAttribute('aria-label') || '';
        const ratingMatch = ratingText.match(/(?:rated\s*)?([1-5])(?:[.,]\d)?\s*(?:out of 5|stars?|bintang)/i) || ratingText.match(/rating\s*([1-5])/i);
        if (ratingMatch) return Number(ratingMatch[1]);

        const stars = getStarPaths(card);
        if (stars.length) {
            const filled = stars.filter(path => /#fabb05|#fbbc04|#f4b400|rgb\(250,\s*187,\s*5\)/i.test(path.getAttribute('fill') || getComputedStyle(path).fill));
            return Math.max(1, filled.length);
        }

        const visibleStars = (card.innerText || '').match(/[★☆]{3,}/)?.[0];
        return visibleStars ? Math.max(1, (visibleStars.match(/★/g) || []).length) : 5;
    }

    function hasReviewRating(card) {
        const labeledRating = Array.from(card.querySelectorAll('[role="img"], [aria-label]'))
            .some(node => /(?:rated\s*)?[1-5](?:[.,]\d)?\s*(?:out of 5|stars?|bintang)|rating\s*[1-5]/i.test(node.getAttribute('aria-label') || ''));
        return labeledRating || !!card.querySelector('span.kvMYJc, span.hCCjke') || getStarPaths(card).length >= 3 || /[★☆]{3,}/.test(card.innerText || card.textContent || '');
    }

    function isReviewDateText(text) {
        if (!text || typeof text !== 'string') return false;
        const clean = text.trim();
        if (!clean || clean.length > 55) return false;
        if (/^[★☆\s]+$/.test(clean)) return false;

        // Relative date patterns (English & Indonesian)
        const relativePattern = /^(?:edited\s*|diedit\s*)?(?:a|an|\d+)\s*(?:seconds?|minutes?|hours?|days?|weeks?|months?|years?|detik|menit|jam|hari|minggu|bulan|tahun)\s*(?:ago|yang lalu|lalu)?$/i;
        if (relativePattern.test(clean)) return true;

        // Simple relative keywords
        const simpleKeywords = /^(?:edited\s*|diedit\s*)?(?:just now|baru saja|yesterday|kemarin|today|hari ini|sehari lalu|seminggu lalu|sebulan lalu|setahun lalu|sejam lalu|semenit lalu)$/i;
        if (simpleKeywords.test(clean)) return true;

        // Standard calendar dates like: "12 Okt 2023", "Oct 12, 2023", "12/10/2023"
        const calendarPattern = /^(?:edited\s*|diedit\s*)?(?:\d{1,2}\s+[a-z]{3,9}\s+\d{4}|[a-z]{3,9}\s+\d{1,2},?\s+\d{4}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})$/i;
        if (calendarPattern.test(clean)) return true;

        return false;
    }

    function hasReviewDate(text) {
        if (!text || typeof text !== 'string') return false;
        if (isReviewDateText(text)) return true;
        const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
        return lines.some(line => isReviewDateText(line));
    }

    function findSearchReviewCards(root) {
        const authorLinks = Array.from(root.querySelectorAll('a[href*="/maps/contrib/"], a[href*="contrib.google"], [data-href*="/maps/contrib/"]'));
        const cards = [];
        for (const authorLink of authorLinks) {
            let candidate = authorLink.parentElement;
            for (let depth = 0; candidate && candidate !== root && depth < 10; depth++, candidate = candidate.parentElement) {
                const contributors = candidate.querySelectorAll('a[href*="/maps/contrib/"], a[href*="contrib.google"], [data-href*="/maps/contrib/"]');
                if (contributors.length > 1) break;
                const text = candidate.innerText || '';
                if (text.length >= 30 && text.length <= 2400 && hasReviewDate(text)) {
                    cards.push(candidate);
                    break;
                }
            }
        }
        return cards;
    }

    function findReviewDrawer() {
        return Array.from(document.querySelectorAll('.xOR97e, [role="dialog"], [aria-modal="true"], [class*="review-dialog"]'))
            .find(element => element.getClientRects().length > 0 &&
                /sort by/i.test(element.innerText || '') &&
                !!element.querySelector('a[href*="/maps/contrib/"], a[href*="contrib.google"]')) || null;
    }

    function findCards() {
        const root = searchAuditMode ? findReviewDrawer() || document : document;
        const cards = Array.from(root.querySelectorAll('.bwb7ce, div[data-review-id], div.jftiEf, div.gws-localreviews__google-review, [data-attrid*="review"], [role="article"]'));
        if (searchAuditMode) cards.push(...findSearchReviewCards(root));

        return Array.from(new Set(cards)).filter(card => {
            const contributors = card.querySelectorAll('a[href*="/maps/contrib/"], a[href*="contrib.google"], [data-href*="/maps/contrib/"]');
            if (contributors.length > 1) return false;
            return hasReviewRating(card) || (searchAuditMode && contributors.length === 1 && hasReviewDate(card.innerText || ''));
        });
    }

    function findScrollContainer() {
        const drawerList = searchAuditMode ? findReviewDrawer()?.querySelector('.RVCQse') : null;
        if (drawerList && drawerList.scrollHeight > drawerList.clientHeight + 50) return drawerList;

        const card = findCards()[0];
        let element = card;
        while (element?.parentElement) {
            element = element.parentElement;
            const style = getComputedStyle(element);
            if (['auto', 'scroll'].includes(style.overflowY) && element.scrollHeight > element.clientHeight + 50) return element;
        }
        const dialogs = Array.from(document.querySelectorAll('.xOR97e, [role="dialog"], [aria-modal="true"], .review-dialog-list, [data-attrid*="reviews"]'));
        const scrollableDialog = dialogs.find(element => element.scrollHeight > element.clientHeight + 100 && ['auto', 'scroll'].includes(getComputedStyle(element).overflowY));
        if (scrollableDialog) return scrollableDialog;
        return Array.from(document.querySelectorAll('.RVCQse, div.m6QErb.DxyBCb, div.DxyBCb, div.m6QErb, .review-dialog-list, [data-attrid*="reviews"]'))
            .find(element => element.scrollHeight > element.clientHeight + 100) || null;
    }

    function parsePhotoCount(value) {
        const match = String(value || '').match(/(?:^|[\s,·])([\d,.]+)\s*(?:foto|photos?)(?:\b|$)/i);
        if (!match) return null;
        const count = Number(match[1].replace(/[,.]/g, ''));
        return Number.isFinite(count) ? count : null;
    }

    function getReviewerName(authorNode, profileNode) {
        const profileLabel = `${profileNode?.getAttribute('aria-label') || ''} ${profileNode?.innerText || profileNode?.textContent || ''}`.trim();
        const authorLabel = `${authorNode?.getAttribute('aria-label') || ''} ${authorNode?.innerText || authorNode?.textContent || profileLabel}`.trim();
        return (authorNode?.innerText?.trim() || authorNode?.textContent?.trim() || authorLabel)
            .split(/[\r\n,|]/)[0]
            .replace(/\s+(?:local guide|\d+\s*(?:reviews?|ulasan)).*$/i, '')
            .trim();
    }

    function getReviewerPhotoCount(card, authorNode, profileNode) {
        const contributor = profileNode || authorNode;
        if (!contributor) return null;
        const candidates = [
            contributor.querySelector?.('.YE6B9b, [class*="contributor"], [class*="reviewer"], [class*="author"]'),
            ...Array.from(contributor.querySelectorAll?.('.YE6B9b, [class*="contributor"], [class*="reviewer"], [class*="author"]') || []),
            contributor
        ].filter(Boolean);

        for (const candidate of candidates) {
            const text = `${candidate.getAttribute?.('aria-label') || ''} ${candidate.innerText || candidate.textContent || ''}`;
            const count = parsePhotoCount(text);
            if (count !== null) return count;
        }
        return null;
    }

    function getReviewPhotoCount(card, author) {
        // 1. Tombol foto spesifik review (English & Indonesian)
        const photoButtons = Array.from(card.querySelectorAll('button[aria-label], [role="button"][aria-label]'))
            .filter(btn => {
                const label = (btn.getAttribute('aria-label') || '').toLowerCase();
                return /photo\s+\d+|foto\s+\d+|buka foto|open photo|foto ulasan|review photo/i.test(label);
            });
        if (photoButtons.length) return new Set(photoButtons).size;

        // 2. Elemen thumbnail Google Maps (data-photo-index, background-image)
        const photoThumbs = Array.from(card.querySelectorAll('[data-photo-index], .KtRffc, .Tya61d, [style*="background-image"]:not([class*="avatar"]):not([class*="profile"])'));
        if (photoThumbs.length) return photoThumbs.length;

        // 3. Tombol media umum
        const mediaButtons = Array.from(card.querySelectorAll('button[aria-label], [role="button"][aria-label]'))
            .filter(button => /photo|foto/i.test(button.getAttribute('aria-label') || ''));
        return mediaButtons.length || 0;
    }

    function getReviewDate(card) {
        const dateNode = card.querySelector('span.rsqaWe, span.xRkPPb, span.dehysf, [class*="review-date"], [class*="reviewDate"], span.Zv77vd, .fP1Qef');
        if (dateNode?.textContent?.trim()) {
            const raw = dateNode.textContent.trim();
            if (raw.length <= 55 && !/^[★☆\s]+$/.test(raw)) {
                return raw;
            }
        }
        const lines = (card.innerText || '').split('\n').map(line => line.trim()).filter(Boolean);
        const dateLine = lines.find(line => isReviewDateText(line));
        if (dateLine) return dateLine;

        // Toleransi format "Edited · a month ago"
        const fuzzyDate = lines.find(line => line.length <= 45 && /\b(?:ago|lalu|kemarin|yesterday|hari ini|today)\b/i.test(line) && !/^[★☆\s]+$/.test(line));
        if (fuzzyDate) return fuzzyDate;

        return '';
    }

    function expandReviewTexts() {
        let expanded = 0;
        for (const card of findCards()) {
            const controls = Array.from(card.querySelectorAll('button, [role="button"], .FrlmTe, .w8nwRe, span.review-more-link, span.w8nwRe'));
            for (const control of controls) {
                const label = `${control.getAttribute('aria-label') || ''} ${control.innerText || control.textContent || ''}`.trim().toLowerCase();
                const jsact = (control.getAttribute('jsaction') || '').toLowerCase();
                const cls = (control.className || '').toString().toLowerCase();
                const expandsReview =
                    cls.includes('w8nwre') ||
                    cls.includes('review-more-link') ||
                    cls.includes('m77dve') ||
                    cls.includes('frlmte') ||
                    jsact.includes('expandreview') ||
                    jsact.includes('review.expand') ||
                    /read more of .+review|^(?:read more|more|see more|lihat ulasan lengkap|lihat selengkapnya|baca selengkapnya|lebih banyak|lainnya|selengkapnya)$/i.test(label) ||
                    /lihat ulasan lengkap|baca selengkapnya|lihat selengkapnya/i.test(label);

                if (!expandsReview || !control.getClientRects().length || control.getAttribute('aria-expanded') === 'true') continue;
                try {
                    control.click();
                    expanded++;
                } catch(e) {}
            }
        }
        return expanded;
    }

    function countWords(text) {
        return (String(text || '').match(/[\p{L}\p{M}\p{N}]+(?:['’\-][\p{L}\p{M}\p{N}]+)*/gu) || []).length;
    }

    function getReviewText(card, author) {
        const textNode = card.querySelector('span.wiI7pd, span.wiI7m:not(.fRhCdd), div.MyEned span, div.Jtu6Td, div.review-full-text, [class*="review-text"], [class*="reviewText"]');
        let text = '';
        if (textNode?.textContent?.trim()) {
            text = textNode.textContent.trim();
        } else {
            const lines = (card.innerText || '').split('\n').map(line => line.trim()).filter(Boolean);
            text = lines.find(line =>
                line.length > 2 &&
                line !== author &&
                !isReviewDateText(line) &&
                !/\b(?:ago|lalu)\b/i.test(line) &&
                !/^[★☆\s]+$/.test(line) &&
                !/^new$/i.test(line) &&
                !/^(?:more|read more|see more|lihat ulasan lengkap|lihat selengkapnya|baca selengkapnya|lebih banyak|lainnya|selengkapnya)$/i.test(line) &&
                !/^(?:local guide\s*[·,]?\s*)?\d+[\d,.]*\s*(?:reviews?|ulasan)?\s*[·,]?\s*\d+[\d,.]*\s*(?:photos?|foto)$/i.test(line) &&
                !/^(?:local guide\s*[·,]?\s*)?\d+[\d,.]*\s*(?:reviews?|ulasan)$/i.test(line) &&
                !/^\d+[\d,.]*\s*(?:photos?|foto)$/i.test(line) &&
                !/^(local guide|see translation|terjemahkan|show original|tampilkan asli|response from owner|tanggapan dari pemilik)/i.test(line)
            ) || '';
        }

        // Jika teks yang didapat ternyata adalah format tanggal, jangan jadikan komentar ulasan!
        if (isReviewDateText(text) || (text.length <= 45 && /\b(?:ago|lalu|kemarin|yesterday)\b/i.test(text))) {
            text = '';
        }

        return text.replace(/[\s\.\…]+(?:lihat ulasan lengkap|lihat selengkapnya|baca selengkapnya|selengkapnya|more|read more|lainnya)$/i, '').trim();
    }

    function extractReviews() {
        return findCards().map(card => {
            const rating = getReviewRating(card);
            let date = getReviewDate(card);
            const profileNode = card.querySelector('a[href*="/maps/contrib/"], [data-href*="/maps/contrib/"]');
            const authorNode = card.querySelector('div.d4r55, span.WNxzHc, button.WEBjve, .fontBodyMedium.fontTitleSmall, [class*="reviewer-name"], [class*="author-name"]') || profileNode;
            const author = getReviewerName(authorNode, profileNode);
            let text = getReviewText(card, author);

            // Validasi & pemulihan jika tanggal dan teks sempat tertukar:
            if (date.length > 55 && (isReviewDateText(text) || text.length <= 45)) {
                const temp = text;
                text = date;
                date = temp;
            } else if (isReviewDateText(text)) {
                if (!date) date = text;
                text = '';
            }

            const wordCount = countWords(text);
            const id = card.getAttribute('data-review-id') || [author, rating, date, text].join('|');
            return {
                id,
                author: author || 'Nama tidak tersedia',
                reviewerPhotoCount: getReviewerPhotoCount(card, authorNode, profileNode),
                reviewPhotoCount: getReviewPhotoCount(card, author),
                rating,
                text,
                wordCount,
                date,
                reply: card.querySelector('div.CDe7pd, div.wiI7m.fRhCdd, div.d6SCIc')?.textContent?.trim() || '',
                localGuide: /local guide/i.test(card.innerText || '')
            };
        });
    }

    async function openBestPlace(placeName) {
        if (!location.pathname.includes('/maps/search')) return;
        const terms = (placeName || '').toLocaleLowerCase().match(/[a-z0-9]+/g) || [];
        const candidates = Array.from(document.querySelectorAll('div.Nv2PK a.hfpxzc, a[href*="/maps/place/"], a[href*="/place/"]'));
        if (!candidates.length) return;

        const best = candidates.map(candidate => {
            const label = `${candidate.innerText || ''} ${candidate.getAttribute('aria-label') || ''} ${candidate.href}`.toLocaleLowerCase();
            const score = terms.reduce((total, term) => total + (term.length > 2 && label.includes(term) ? 1 : 0), 0);
            return { candidate, score };
        }).sort((left, right) => right.score - left.score)[0];

        if (best) {
            best.candidate.click();
            await delay(3500);
        }
    }

    function findSearchReviewControl() {
        const panel = document.querySelector('#rhs') || document.querySelector('[data-attrid="kc:/local:place"]')?.closest('#rhs');
        if (!panel) return null;
        return Array.from(panel.querySelectorAll('a[href], button, [role="button"]')).find(control => {
            const label = `${control.getAttribute('aria-label') || ''} ${control.innerText || control.textContent || ''}`.trim();
            return /(?:\d[\d,.]*\s*)?(?:google\s*)?(?:reviews?|ulasan)/i.test(label) &&
                !/write|tulis|reply|balas|reviewer|photo|foto/i.test(label);
        }) || null;
    }

    async function openReviews() {
        if (searchAuditMode && location.hash.startsWith('#lrd=')) {
            updateOverlay(0, 1000, 'Membaca kartu review di panel Google Search...');
            for (let attempt = 0; attempt < 40; attempt++) {
                if (findCards().length) {
                    updateOverlay(0, 1000, 'Daftar review ditemukan, menyiapkan pengurutan...');
                    return true;
                }
                await delay(500);
            }
            throw new Error('Panel review Google Search tidak memuat kartu review dari hash #lrd. Pastikan link review profil tersedia.');
        }

        if (searchAuditMode) {
            const reviewControl = findSearchReviewControl();
            if (!reviewControl) {
                throw new Error('Panel bisnis Google Search tidak memiliki kontrol jumlah ulasan yang dapat dibuka. Coba buka profil bisnis lengkap lalu jalankan audit lagi.');
            }
            updateOverlay(0, 1000, 'Membuka panel jumlah ulasan...');
            reviewControl.click();
            for (let attempt = 0; attempt < 40; attempt++) {
                if (location.hash.startsWith('#lrd=') && findCards().length) return true;
                const cardCount = findCards().length;
                const sortControlVisible = Array.from(document.querySelectorAll('button, [role="button"], [role="combobox"]'))
                    .some(control => /sort by|sort reviews|urutkan|most relevant|paling relevan/i.test(`${control.getAttribute('aria-label') || ''} ${control.innerText || control.textContent || ''}`));
                if (cardCount > 3 || sortControlVisible) return true;
                await delay(500);
            }
            throw new Error('Kontrol jumlah ulasan sudah diklik, tetapi Google Search tidak membuka drawer review. Link #lrd/CID tidak tersedia pada profil ini.');
        }

        const controls = Array.from(document.querySelectorAll('button[role="tab"], [role="tab"], button, a[role="button"]'));
        const isReviewControl = control => {
            const label = `${control.getAttribute('aria-label') || ''} ${control.innerText || ''}`.toLowerCase();
            return /ulasan|reviews?/.test(label) && !/tulis|write|bagikan|share|foto|photo|reply|balas|reviewer|reviewed/.test(label);
        };
        const reviewControls = controls.filter(isReviewControl);
        const reviewTab = reviewControls.find(control => control.getAttribute('role') === 'tab');
        const reviewButton = reviewTab || reviewControls.find(control => /\d/.test(`${control.getAttribute('aria-label') || ''} ${control.innerText || ''}`)) || reviewControls[0];
        const initialCardCount = findCards().length;
        const initialContainer = findScrollContainer();
        const initialScrollHeight = initialContainer?.scrollHeight || 0;

        if (!reviewButton) {
            throw new Error('Tombol Reviews/Ulasan tidak ditemukan. Pastikan halaman Google Search membuka panel review profil bisnis, bukan hanya preview.');
        }

        updateOverlay(0, 1000, 'Membuka daftar Reviews/Ulasan penuh...');
        if (reviewButton.getAttribute('aria-selected') !== 'true') reviewButton.click();

        for (let attempt = 0; attempt < 40; attempt++) {
            const selectedTab = reviewButton.getAttribute('aria-selected') === 'true';
            const sortControl = Array.from(document.querySelectorAll('button')).some(button => {
                const label = `${button.getAttribute('aria-label') || ''} ${button.innerText || ''}`.toLowerCase();
                return /urutkan|sort reviews|most relevant|paling relevan/.test(label);
            });
            const currentCardCount = findCards().length;
            const currentContainer = findScrollContainer();
            const expandedList = currentContainer && (
                currentContainer !== initialContainer || currentContainer.scrollHeight > initialScrollHeight + 100
            );
            if (selectedTab || sortControl || currentCardCount > initialCardCount || expandedList) return true;
            await delay(500);
        }
        throw new Error('Google tidak membuka daftar Reviews/Ulasan penuh. Pastikan panel review profil bisnis terbuka, lalu jalankan audit lagi.');
    }

    async function waitForNewReviews(reviews, timeout = 1400) {
        const deadline = Date.now() + timeout;
        while (Date.now() < deadline) {
            if (extractReviews().some(review => !reviews.has(review.id))) return true;
            await delay(500);
        }
        return false;
    }

    async function sortReviewsNewest() {
        const labelOf = element => `${element.getAttribute('aria-label') || element.innerText || element.textContent || ''}`.replace(/\s+/g, ' ').trim().toLowerCase();
        const reviewCard = findCards()[0];
        const reviewDialog = findReviewDrawer() || reviewCard?.closest('[role="dialog"], [aria-modal="true"]');
        const scope = reviewDialog || document;
        const getSortElements = () => Array.from(scope.querySelectorAll('button, [role], a[href], [tabindex], [jsaction], span, div'));
        const getNewestOption = () => getSortElements().find(element => /^(newest|most recent|terbaru|paling baru)(?:\s+\d+)?$/i.test(labelOf(element)));
        const getClickable = element => element?.closest('button, [role="button"], [role="radio"], [role="option"], [tabindex], a[href], [jsaction]') || element;

        let newestOption = getNewestOption();
        if (newestOption) {
            if (newestOption.getAttribute('aria-checked') === 'true' || newestOption.getAttribute('aria-selected') === 'true') return true;
            updateOverlay(0, 1000, 'Memilih Newest...');
            const target = getClickable(newestOption);
            target.click();
            for (let attempt = 0; attempt < 20; attempt++) {
                if (newestOption.getAttribute('aria-checked') === 'true' || newestOption.getAttribute('aria-selected') === 'true') return true;
                await delay(100);
            }
            throw new Error('Newest terdeteksi tetapi Google tidak mengaktifkannya. Audit dihentikan sebelum scroll.');
        }

        const getSortControl = () => getSortElements()
            .find(control => /^(sort by|sort reviews|urutkan|most relevant|paling relevan)$/i.test(labelOf(control)));
        let sortControl = getSortControl();
        for (let attempt = 0; !sortControl && attempt < 20; attempt++) {
            await delay(300);
            sortControl = getSortControl();
        }
        if (!sortControl) {
            const labels = getSortElements().map(labelOf).filter(label => /sort|relevant|newest|recent|terbaru/i.test(label)).slice(0, 8);
            throw new Error(`Kontrol Newest tidak ditemukan pada panel review. Elemen pengurutan terdeteksi: ${labels.join(' | ') || 'tidak ada'}.`);
        }

        updateOverlay(0, 1000, 'Memilih Sort by: Newest...');
        getClickable(sortControl).click();

        for (let attempt = 0; attempt < 30; attempt++) {
            newestOption = getNewestOption();
            if (newestOption) {
                getClickable(newestOption).click();
                await delay(1200);
                return true;
            }
            await delay(200);
        }

        throw new Error('Opsi Newest/Terbaru tidak muncul setelah Sort by dibuka. Audit dihentikan sebelum menghitung review.');
    }

    async function startAudit(jobId, maxReviews, placeName) {
        if (running) return;
        running = true;
        const max = Math.min(1000, Math.max(1, Number(maxReviews) || 1000));
        const reviews = new Map();
        let bottomStale = 0;

        try {
            updateOverlay(0, max, 'Membuka daftar review...');
            await openBestPlace(placeName);
            if (!await openReviews()) throw new Error('Daftar review tidak ditemukan. Pastikan Google Maps menampilkan profil dengan review publik.');
            await sortReviewsNewest();

            for (let step = 0; step < 1800 && reviews.size < max && bottomStale < 3; step++) {
                if (expandReviewTexts()) await delay(250);
                const added = [];
                for (const review of extractReviews()) {
                    if (!reviews.has(review.id) && reviews.size < max) {
                        reviews.set(review.id, review);
                        added.push(review);
                    }
                }

                updateOverlay(reviews.size, max, 'Scroll otomatis dan membaca review...');
                updateProcessedCount(reviews.size);
                chrome.runtime.sendMessage({
                    type: 'REVIEW_AUDIT_PROGRESS',
                    jobId,
                    status: 'running',
                    reviews: added,
                    message: `Mengumpulkan ${reviews.size} dari ${max} review...`
                });

                if (reviews.size >= max) break;
                const container = findScrollContainer();
                if (!container) {
                    window.scrollTo(0, document.documentElement.scrollHeight);
                    const loadedMore = await waitForNewReviews(reviews);
                    bottomStale = loadedMore ? 0 : bottomStale + 1;
                } else {
                    const stepSize = Math.max(900, Math.floor(container.clientHeight * 2.5));
                    container.scrollTop = Math.min(container.scrollTop + stepSize, container.scrollHeight);
                    container.dispatchEvent(new Event('scroll', { bubbles: true }));
                    container.dispatchEvent(new WheelEvent('wheel', { bubbles: true, deltaY: stepSize }));
                    const loadedMore = await waitForNewReviews(reviews);

                    const maxScrollTop = Math.max(0, container.scrollHeight - container.clientHeight);
                    const atBottom = maxScrollTop - container.scrollTop <= 32;
                    bottomStale = atBottom && !loadedMore ? bottomStale + 1 : 0;
                }
            }

            const message = reviews.size >= max
                ? `Batas ${max.toLocaleString()} review tercapai.`
                : bottomStale >= 3
                    ? `Daftar review habis. ${reviews.size.toLocaleString()} review berhasil dimuat.`
                    : `Daftar selesai. ${reviews.size.toLocaleString()} review berhasil dimuat.`;
            chrome.runtime.sendMessage({ type: 'REVIEW_AUDIT_PROGRESS', jobId, status: 'complete', message });
        } catch (error) {
            chrome.runtime.sendMessage({ type: 'REVIEW_AUDIT_PROGRESS', jobId, status: 'error', message: error.message });
        }
    }

    chrome.runtime.onMessage.addListener(message => {
        if (message.type === 'BEGIN_REVIEW_AUDIT') startAudit(message.jobId, message.maxReviews, message.placeName);
    });
    chrome.runtime.sendMessage({ type: 'MAPS_AUDIT_READY' });

    // Auto-audit trigger KHUSUS untuk direct Google Maps URL (bukan Google Search)
    const isGoogleMapsOnly = location.pathname.startsWith('/maps') || location.hostname.startsWith('maps.google');
    if (isGoogleMapsOnly && !searchAuditMode && (location.href.includes('griview_auto_audit=1') || location.href.includes('griview_auto=1') || location.hash.includes('griview_auto_audit'))) {
        let mapsAutoTriggered = false;
        let checks = 0;
        const mapsInterval = setInterval(() => {
            checks++;
            if (mapsAutoTriggered || checks > 25) {
                clearInterval(mapsInterval);
                return;
            }
            const titleEl = document.querySelector('h1.DUwDvf, [role="main"] h1');
            const placeName = titleEl?.textContent?.trim() || document.title.replace(/\s*-\s*Google Maps.*$/i, '').trim();
            if (placeName && placeName.length > 2 && !/google maps/i.test(placeName)) {
                mapsAutoTriggered = true;
                clearInterval(mapsInterval);
                const addressEl = document.querySelector('[data-item-id="address"]');
                const address = addressEl?.textContent?.trim() || '';
                chrome.runtime.sendMessage({
                    type: 'START_REVIEW_AUDIT',
                    place: { placeName, address, mapsUrl: location.href }
                });
            }
        }, 800);
    }
})();
