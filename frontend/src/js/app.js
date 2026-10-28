document.addEventListener('DOMContentLoaded', () => {
    // 1. User Logout Handler
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', () => {
            localStorage.clear();
            sessionStorage.clear();
            window.location.href = '../controllers/logout.php';
        });
    }


    // State management
    let moaData = [];
    let currentFilter = 'DRAFT'; // 'all', 'DRAFT', 'CHECKED', 'SIGNED_GROUP', 'ACTIVE', 'EXPIRING', 'EXPIRED'
    let searchQuery = '';


    // DOM Elements
    const cardListContainer = document.querySelector('.card-list');
    const searchInput = document.querySelector('.search-box input');
    const resultsCountEl = document.querySelector('.results-count');
    const navItems = document.querySelectorAll('.nav-item, .nav-subitem');


    // Main Modal DOM Elements
    const modalCheckbox = document.getElementById('moa-modal');
    const modalId = document.querySelector('.modal-id');
    const modalTitle = document.querySelector('.modal-title');
    const modalOrg = document.querySelector('.modal-org');
    const modalDays = document.querySelector('.modal-days');
    const modalProgressFill = document.querySelector('.modal .progress-fill');
    const modalBodyTop = document.querySelector('.modal-body-top');
    const modalBody = document.querySelector('.modal-body');
    const modalFooter = document.querySelector('.modal-footer');


    // Signed Details Modal DOM Elements
    const signedOverlay = document.getElementById('signed-modal-overlay');
    const signedForm = document.getElementById('signed-form');
    const signedCloseBtn = document.getElementById('signed-modal-close');
    const signedBackdropBtn = document.getElementById('signed-modal-backdrop');


    // MOA DASHBOARD LOGIC: DATA FETCHING & POLLING
    async function fetchAudits(isBackgroundPoll = false) {
        // Skip background refresh if main modal or signed modal is active or user is typing
        const isModalOpen = (modalCheckbox && modalCheckbox.checked) || (signedOverlay && (signedOverlay.classList.contains('active') || signedOverlay.style.display === 'flex'));
        const isUserTyping = searchInput && document.activeElement === searchInput;


        if (isBackgroundPoll && (isModalOpen || isUserTyping)) {
            return;
        }


        try {
            const response = await fetch('../controllers/get_audits.php');
            const result = await response.json();


            if (Array.isArray(result)) {
                moaData = result;
            } else if (result.data && Array.isArray(result.data)) {
                moaData = result.data;
            } else {
                moaData = [];
            }


            renderCards();
        } catch (error) {
            console.error('Error fetching MOA records:', error);
            if (!isBackgroundPoll && cardListContainer) {
                cardListContainer.innerHTML = `
                    <div style="padding: 24px; text-align: center; color: var(--red-text); background: #fff; border-radius: 12px;">
                        Failed to load MOA records. Please verify ../controllers/get_audits.php endpoint.
                    </div>`;
            }
        }
    }


    // Auto-polling every 10 seconds
    setInterval(() => {
        fetchAudits(true);
    }, 10000);


    // MOA DASHBOARD LOGIC: SANITIZATION HELPERS
    function stripHtml(htmlString) {
        if (!htmlString) return 'No review text available.';
        const tmp = document.createElement('DIV');
        tmp.innerHTML = htmlString;
        return tmp.textContent || tmp.innerText || '';
    }


    function cleanText(str) {
        if (!str) return '';
        return str
            .replace(/^=\s*/, '')
            .replace(/=\?UTF-8\?[QB]\?(.*?)\?=/gi, '$1')
            .trim();
    }


    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g,
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }


    function resolvePdfPath(path) {
        if (!path || path === '#' || path === 'null' || path === 'undefined') return '#';
        if (path.startsWith('http://') || path.startsWith('https://')) return path;
        let clean = path.replace(/\\/g, '/');
        if (clean.startsWith('../')) return clean;
        clean = clean.replace(/^(\.\/|\/)+/, '');
        if (clean.startsWith('backend/uploads/')) {
            return '../' + clean.replace('backend/', '');
        }
        if (clean.startsWith('uploads/')) {
            return '../' + clean;
        }
        return '../uploads/' + clean;
    }


    function getStatusBadgeClass(status) {
        const s = (status || '').toUpperCase();
        switch (s) {
            case 'DRAFT': return { badge: 'amber', border: 'status-expiring', fill: 'amber', label: 'Draft' };
            case 'CHECKED': return { badge: 'blue', border: 'status-signed', fill: 'blue', label: 'Checked' };
            case 'SIGNED':
            case 'ACTIVE': return { badge: 'green', border: 'status-active', fill: 'navy', label: 'Signed & Active' };
            case 'EXPIRING': return { badge: 'amber', border: 'status-expiring', fill: 'amber', label: 'Expiring Soon' };
            case 'EXPIRED': return { badge: 'gray', border: 'status-expired', fill: 'gray', label: 'Expired' };
            default: return { badge: 'amber', border: 'status-expiring', fill: 'amber', label: status || 'Draft' };
        }
    }


    // MOA DASHBOARD LOGIC: FILTERING & SEARCH
    function renderCards() {
        if (!cardListContainer) return;


        const filtered = moaData.filter(item => {
            const statusUpper = (item.status || 'DRAFT').toUpperCase();

            if (currentFilter !== 'all' && currentFilter !== 'SIGNED_GROUP') {
                if (currentFilter === 'ACTIVE') {
                    if (statusUpper !== 'ACTIVE' && statusUpper !== 'SIGNED') return false;
                } else if (currentFilter === 'EXPIRING') {
                    if (statusUpper !== 'EXPIRING') return false;
                } else if (currentFilter === 'EXPIRED') {
                    if (statusUpper !== 'EXPIRED') return false;
                } else if (currentFilter === 'CHECKED') {
                    if (statusUpper !== 'CHECKED') return false;
                } else if (currentFilter === 'DRAFT') {
                    if (statusUpper !== 'DRAFT') return false;
                } else if (statusUpper !== currentFilter) {
                    return false;
                }
            } else if (currentFilter === 'SIGNED_GROUP') {
                if (!['SIGNED', 'ACTIVE', 'EXPIRING', 'EXPIRED'].includes(statusUpper)) return false;
            }


            if (searchQuery.trim() !== '') {
                const query = searchQuery.toLowerCase();
                const subject = (cleanText(item.subject || item.moa_title) || '').toLowerCase();
                const sender = (cleanText(item.partner_organization || item.sender_email) || '').toLowerCase();
                const signatory = (cleanText(item.signatory) || '').toLowerCase();
                const id = String(item.id || '');
                const idFormatted = `moa-${String(item.id).padStart(4, '0')}`.toLowerCase();
                return subject.includes(query) || sender.includes(query) || signatory.includes(query) || id.includes(query) || idFormatted.includes(query);
            }


            return true;
        });


        if (resultsCountEl) {
            resultsCountEl.textContent = `${filtered.length} result${filtered.length === 1 ? '' : 's'}`;
        }


        if (filtered.length === 0) {
            cardListContainer.innerHTML = `
                <div style="padding: 32px; text-align: center; color: var(--text-faint); background: #fff; border-radius: 14px; border: 1px solid var(--card-border);">
                    No MOA documents found matching your criteria.
                </div>`;
            return;
        }


        cardListContainer.innerHTML = filtered.map(item => {
            const style = getStatusBadgeClass(item.status);
            const dateStr = item.created_at ? item.created_at.split(' ')[0] : 'N/A';

            const rawHtml = (item.summary_html || '').replace(/><\/?[^>]+>/g, '> <');
            const cleanSnippet = stripHtml(rawHtml).replace(/\s+/g, ' ').trim().slice(0, 140);
            const plainSummary = cleanSnippet ? cleanSnippet + '...' : 'No summary available.';


            const cleanedTitle = cleanText(item.subject || item.moa_title) || 'Untitled MOA';
            const cleanedSender = cleanText(item.partner_organization || item.sender_email) || 'Unknown Partner/Sender';
            const source = item.source || 'email';


            const expDateStr = item.expiration_date ? item.expiration_date : 'N/A';
            const metaRight = `
                <div class="meta-date">Received: ${dateStr}</div>
                <div class="meta-date" style="font-weight:600; color:var(--text-dark);">Expires: ${expDateStr}</div>
            `;


            return `
                <div class="moa-card ${style.border}" data-id="${item.id}" data-source="${source}">
                    <div class="card-top">
                        <div class="card-icon-box">
                            <span class="icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </span>
                        </div>
                        <div style="flex-grow:1;">
                            <div class="card-id-row">
                                <span class="card-id">MOA-${String(item.id).padStart(4, '0')}</span>
                                <span class="badge ${style.badge}">${style.label}</span>
                            </div>
                            <h3 class="card-title">${escapeHtml(cleanedTitle)}</h3>
                            <div class="card-org">
                                <span class="icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </span>
                                ${escapeHtml(cleanedSender)}
                            </div>
                        </div>
                        <div class="card-meta-right">
                            <div class="card-meta-cols">
                                ${metaRight}
                            </div>
                            <span class="chevron"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </div>
                    </div>

                    <div style="margin-top: 12px; font-size: 13.5px; color: var(--text-body); line-height: 1.5;">
                        ${escapeHtml(plainSummary)}
                    </div>
                </div>
            `;
        }).join('');


        document.querySelectorAll('.moa-card').forEach(card => {
            card.addEventListener('click', () => {
                const id = card.getAttribute('data-id');
                const source = card.getAttribute('data-source');
                const selectedItem = moaData.find(d => String(d.id) === String(id) && (d.source || 'email') === source);
                if (selectedItem) openDetailModal(selectedItem);
            });
        });
    }


    // MOA DASHBOARD LOGIC: DYNAMIC MODAL RENDERING
    function openDetailModal(item) {
        if (!modalCheckbox) return;


        const style = getStatusBadgeClass(item.status);
        const dateStr = item.created_at ? item.created_at.split(' ')[0] : 'N/A';
        const cleanedTitle = cleanText(item.subject || item.moa_title) || 'Untitled MOA';
        const cleanedSender = cleanText(item.partner_organization || item.sender_email) || '';
        const statusUpper = (item.status || 'DRAFT').toUpperCase();
        const isIntake = (item.source === 'intake') || Boolean(item.expiration_date || item.signatory);


        if (modalId) modalId.textContent = `MOA-${String(item.id).padStart(4, '0')}`;
        if (modalTitle) modalTitle.textContent = cleanedTitle;
        if (modalOrg) {
            modalOrg.innerHTML = `
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="1"/></svg></span>
                ${escapeHtml(cleanedSender) || 'Unknown Partner'}
            `;
        }


        let daysInfo = `Status: ${style.label}`;
        if (item.expiration_date) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const exp = new Date(item.expiration_date);
            exp.setHours(0, 0, 0, 0);
            const diffDays = Math.round((exp - today) / (1000 * 60 * 60 * 24));
            if (diffDays < 0) {
                daysInfo = `Expired ${Math.abs(diffDays)} day${Math.abs(diffDays) === 1 ? '' : 's'} ago`;
            } else if (diffDays === 0) {
                daysInfo = 'Expires today';
            } else {
                daysInfo = `${diffDays} day${diffDays === 1 ? '' : 's'} remaining`;
            }
        }
        if (modalDays) modalDays.textContent = daysInfo;


        if (modalProgressFill) {
            modalProgressFill.className = `progress-fill ${style.fill}`;
            modalProgressFill.style.width = '100%';
        }


        const validText = item.expiration_date ? `Valid Until: ${item.expiration_date}` : `Received: ${dateStr}`;


        if (modalBodyTop) {
            modalBodyTop.innerHTML = `
                <span class="badge ${style.badge}">${style.label}</span>
                <span class="valid-until">${validText}</span>
            `;
        }


        if (modalBody) {
            if (isIntake) {
                modalBody.innerHTML = `
                    <div class="modal-body-top">
                        <span class="badge ${style.badge}">${style.label}</span>
                        <span class="valid-until">${validText}</span>
                    </div>

                    <div class="info-card">
                        <div class="info-label">MOA Record Details</div>
                        <div class="info-text" style="max-height: 200px; overflow-y: auto;">
                            ${item.summary_html || '<p>Signed MOA document filed into registry.</p>'}
                        </div>
                    </div>


                    <div class="info-grid">
                        <div>
                            <div class="info-label">Partner Organization</div>
                            <div class="info-value">${escapeHtml(item.partner_organization || cleanedSender || 'N/A')}</div>
                        </div>
                        <div>
                            <div class="info-label">Signatory By</div>
                            <div class="info-value">${escapeHtml(item.signatory || 'N/A')}</div>
                        </div>
                        <div>
                            <div class="info-label">Date Issued</div>
                            <div class="info-value">${escapeHtml(item.date_issued || 'N/A')}</div>
                        </div>
                        <div>
                            <div class="info-label">Effective Date</div>
                            <div class="info-value">${escapeHtml(item.effective_date || 'N/A')}</div>
                        </div>
                        <div>
                            <div class="info-label">Date Signed</div>
                            <div class="info-value">${escapeHtml(item.date_signed || 'N/A')}</div>
                        </div>
                        <div>
                            <div class="info-label">Expiration Date</div>
                            <div class="info-value">${escapeHtml(item.expiration_date || 'N/A')}</div>
                        </div>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `
                    <div class="modal-body-top">
                        <span class="badge ${style.badge}">${style.label}</span>
                        <span class="valid-until">Received: ${dateStr}</span>
                    </div>

                    <div class="info-card">
                        <div class="info-label">AI Legal Compliance Review</div>
                        <div class="info-text" style="max-height: 250px; overflow-y: auto;">
                            ${item.summary_html || '<p>No summary generated.</p>'}
                        </div>
                    </div>


                    <div class="info-grid">
                        <div>
                            <div class="info-label">Sender Email</div>
                            <div class="info-value">${escapeHtml(cleanedSender) || 'N/A'}</div>
                        </div>
                    </div>
                `;
            }
        }


        const pdfPath = resolvePdfPath(item.original_pdf_path);


        // 1. DRAFT STATUS
        if (statusUpper === 'DRAFT') {
            if (modalFooter) {
                modalFooter.innerHTML = `
                    <a href="${pdfPath}" target="_blank" class="icon-btn" title="Open Original PDF" style="text-decoration:none;">
                        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span>
                    </a>
                    <button id="saveStatusBtn" class="btn-intake" style="margin-left: auto; padding: 10px 20px; font-size: 13.5px;">
                        Make Review
                    </button>
                `;


                const saveStatusBtn = document.getElementById('saveStatusBtn');
                if (saveStatusBtn) {
                    saveStatusBtn.addEventListener('click', async () => {
                        try {
                            const response = await fetch('../controllers/update_status.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id: item.id, status: 'CHECKED' })
                            });


                            if (response.ok) {
                                currentFilter = 'CHECKED';
                                item.status = 'CHECKED';


                                navItems.forEach(nav => {
                                    const text = nav.querySelector('.nav-text')?.textContent.trim().toUpperCase();
                                    if (text === 'CHECKED') {
                                        nav.classList.add('active');
                                    } else {
                                        nav.classList.remove('active');
                                    }
                                });


                                await fetchAudits();
                                openDetailModal(item);
                            } else {
                                alert('Failed to update status.');
                            }
                        } catch (err) {
                            console.error('Update status error:', err);
                            alert('Server error updating status.');
                        }
                    });
                }
            }
        }
        // 2. CHECKED STATUS - UPDATED LINE 437
        else if (statusUpper === 'CHECKED') {
            const currentExpDate = item.expiration_date || '';


            if (modalBody) {
                modalBody.innerHTML += `
                    <div class="info-card" style="margin-top: 16px;">
                        <div style="display: flex; gap: 12px; margin-bottom: 14px;">
                            <div style="flex: 1;">
                                <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px; color: var(--text-heading, #1e293b);">Recipient Email Address</label>
                                <input type="email" id="recipientEmail" value="${escapeHtml(cleanText(item.sender_email))}" style="width: 100%; border-radius: 8px; border: 1px solid var(--card-border, #e2e8f0); padding: 8px 10px; font-size: 13px; box-sizing: border-box;" />
                            </div>
                            <div style="flex: 1;">
                                <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px; color: var(--text-heading, #1e293b);">Expiration Date</label>
                                <input type="date" id="expirationDate" value="${currentExpDate}" style="width: 100%; border-radius: 8px; border: 1px solid var(--card-border, #e2e8f0); padding: 8px 10px; font-size: 13px; box-sizing: border-box;" />
                            </div>
                        </div>


                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px; color: var(--text-heading, #1e293b);">Recommended Changes / Notes</label>
                        <textarea id="recommendedChanges" rows="4" placeholder="Enter each recommended change on a new line..." style="width: 100%; border-radius: 8px; border: 1px solid var(--card-border, #e2e8f0); padding: 10px; font-family: inherit; font-size: 13px; line-height: 1.5; resize: vertical; box-sizing: border-box;"></textarea>


                        <div style="margin-top: 14px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px; color: var(--text-heading, #1e293b);">Attach Revised Document (Optional)</label>
                            <input type="file" id="revisedDocument" accept=".pdf,.doc,.docx" style="width: 100%; font-size: 13px; padding: 6px; border: 1px dashed var(--card-border, #cbd5e1); border-radius: 8px; background: #f8fafc;" />
                        </div>
                    </div>
                `;
            }


            if (modalFooter) {
                modalFooter.innerHTML = `
                    <a href="${pdfPath}" target="_blank" class="icon-btn" title="Open Original PDF" style="text-decoration:none;">
                        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span>
                    </a>
                    <div style="margin-left: auto; display: flex; gap: 8px; align-items: center;">
                        <button id="sendReplyBtn" class="btn-intake" style="padding: 10px 18px; font-size: 13.5px;">
                            Send Reply
                        </button>
                        <button id="signedBtn" class="btn-intake" style="padding: 10px 18px; font-size: 13.5px; background-color: var(--green-color, #008450); border-color: var(--green-color, #008450); color: #ffffff !important; font-weight: 600;">
                            Signed
                        </button>
                    </div>
                `;


                // Send Reply Button Handler
                const sendReplyBtn = document.getElementById('sendReplyBtn');
                if (sendReplyBtn) {
                    sendReplyBtn.addEventListener('click', async () => {
                        const recipientEmail = document.getElementById('recipientEmail').value.trim();
                        const rawNotes = document.getElementById('recommendedChanges').value.trim();
                        const fileInput = document.getElementById('revisedDocument');


                        if (!recipientEmail) {
                            alert('Please enter a recipient email address.');
                            return;
                        }


                        let bulletedNotes = '';
                        if (rawNotes) {
                            const lines = rawNotes.split('\n').filter(line => line.trim() !== '');
                            bulletedNotes = '<ul>' + lines.map(line => `<li>${escapeHtml(line.replace(/^[•\-\*]\s*/, ''))}</li>`).join('') + '</ul>';
                        }


                        sendReplyBtn.disabled = true;
                        sendReplyBtn.textContent = 'Sending...';


                        const formData = new FormData();
                        formData.append('audit_id', item.id);
                        formData.append('recipient_email', recipientEmail);
                        formData.append('recommended_changes', bulletedNotes);
                        formData.append('raw_notes', rawNotes);
                        formData.append('status', item.status);
                        formData.append('message_id', item.message_id || '');
                        formData.append('thread_id', item.thread_id || '');


                        if (fileInput && fileInput.files[0]) {
                            formData.append('revised_document', fileInput.files[0]);
                        }


                        try {
                            const response = await fetch('../controllers/send_reply.php', {
                                method: 'POST',
                                body: formData
                            });


                            const result = await response.json();


                            if (response.ok && result.success) {
                                alert('Reply and revised document successfully sent!');
                                if (modalCheckbox) modalCheckbox.checked = false;
                                fetchAudits();
                            } else {
                                const codeInfo = result.n8n_code ? ` (HTTP Status ${result.n8n_code})` : '';
                                const detailMsg = result.n8n_response || result.curl_error || 'Unknown server response.';
                                alert(`Error: ${result.error || 'Failed to dispatch payload.'}${codeInfo}\n\nServer Response: ${detailMsg}`);
                            }
                        } catch (err) {
                            console.error('Dispatch error:', err);
                            alert('Server error connecting to ../controllers/send_reply.php.');
                        } finally {
                            sendReplyBtn.disabled = false;
                            sendReplyBtn.textContent = 'Send Reply';
                        }
                    });
                }


                // Signed Button Handler
                const signedBtn = document.getElementById('signedBtn');
                if (signedBtn) {
                    signedBtn.addEventListener('click', () => {
                        if (modalCheckbox) modalCheckbox.checked = false;


                        if (signedOverlay) {
                            if (signedForm) signedForm.reset();


                            const idInput = document.getElementById('signed_audit_id');
                            const partnerInput = document.getElementById('signed_partner') || document.querySelector('#signed-form [name="partner_organization"]');


                            if (partnerInput) {
                            partnerInput.value = cleanText(item.partner_organization || cleanedSender || '');
                            partnerInput.readOnly = true; // Locks the field
                            partnerInput.style.backgroundColor = '#f1f5f9'; // Visual cue for locked state
                            partnerInput.style.cursor = 'not-allowed';
                        }


                            const titleInput = document.getElementById('signed_title') || document.querySelector('#signed-form [name="moa_title"]');
                            const dateReceivedInput = document.getElementById('signed_date_received') || document.querySelector('#signed-form [name="date_received"]');
                            const expDateInput = document.getElementById('signed_expiration_date') || document.querySelector('#signed-form [name="expiration_date"]');


                            if (idInput) idInput.value = item.id;
                            if (partnerInput) partnerInput.value = cleanText(item.partner_organization || cleanedSender || '');
                            if (titleInput) titleInput.value = cleanedTitle;
                            if (dateReceivedInput) dateReceivedInput.value = item.created_at ? item.created_at.split(' ')[0] : '';
                            if (expDateInput && item.expiration_date) expDateInput.value = item.expiration_date;


                            const dateSignedInput = document.getElementById('signed_date_signed') || document.querySelector('#signed-form [name="date_signed"]');
                            if (dateSignedInput) {
                                dateSignedInput.value = new Date().toISOString().split('T')[0];
                            }


                            signedOverlay.classList.add('active');
                            signedOverlay.style.display = 'flex';
                        } else {
                            alert('Signed confirmation modal element (#signed-modal-overlay) not found in DOM.');
                        }
                    });
                }
            }
        }
        // 3. READ-ONLY STATUSES (Fixed SVG sizing to prevent CSS blowout)
        else {
            if (modalFooter) {
                const originalPdf = resolvePdfPath(item.original_pdf_path);
                const signedPdf = item.signed_pdf_path ? resolvePdfPath(item.signed_pdf_path) : null;


                modalFooter.innerHTML = `
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <!-- Original Incoming Document Button -->
                        <a href="${originalPdf}" target="_blank" title="Open Original PDF" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: var(--navy-900); font-family: var(--font-body); white-space: nowrap; height: auto; max-height: 38px; box-sizing: border-box;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px !important; height: 16px !important; min-width: 16px; min-height: 16px; flex-shrink: 0;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            <span>Original Document</span>
                        </a>


                        <!-- Final Signed Document Button (Displays if signed_pdf_path exists) -->
                        ${signedPdf ? `
                        <a href="${signedPdf}" target="_blank" title="Open Final Signed PDF" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; border: 1px solid var(--green-color, #008450); background: #f0fdf4; color: var(--green-text, #047857); font-family: var(--font-body); white-space: nowrap; height: auto; max-height: 38px; box-sizing: border-box;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px !important; height: 16px !important; min-width: 16px; min-height: 16px; flex-shrink: 0;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><polyline points="9 11 12 14 22 4"/></svg>
                            <span>Signed Document</span>
                        </a>
                        ` : ''}
                    </div>


                    <div style="margin-left: auto; display: flex; gap: 8px; align-items: center;">
                        <span class="badge ${style.badge}" style="padding: 8px 14px; font-size: 13px;">${style.label}</span>
                    </div>
                `;
            }
        }


        if (modalCheckbox) modalCheckbox.checked = true;
    }


    // SIGNED MODAL: SUBMISSION & CLOSING HANDLERS
    const closeSignedModal = () => {
        if (signedOverlay) {
            signedOverlay.classList.remove('active');
            signedOverlay.style.display = 'none';
            if (signedForm) signedForm.reset();
        }
    };


    if (signedCloseBtn) signedCloseBtn.addEventListener('click', closeSignedModal);
    if (signedBackdropBtn) signedBackdropBtn.addEventListener('click', closeSignedModal);


    if (signedForm) {
        signedForm.addEventListener('submit', async (e) => {
            e.preventDefault();


            const submitBtn = signedForm.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Updating Record...';
            }


            const formData = new FormData(signedForm);
            formData.append('status', 'SIGNED');


            try {
                const response = await fetch('../controllers/update_status.php', {
                    method: 'POST',
                    body: formData
                });


                const result = await response.json();


                if (response.ok && result.success) {
                    alert('MOA successfully marked as Signed!');
                    closeSignedModal();


                    currentFilter = 'SIGNED_GROUP';
                    navItems.forEach(nav => {
                        const text = nav.querySelector('.nav-text')?.textContent.trim().toUpperCase();
                        if (text === 'SIGNED') {
                            nav.classList.add('active');
                        } else {
                            nav.classList.remove('active');
                        }
                    });


                    await fetchAudits();
                } else {
                    alert('Error: ' + (result.error || result.message || 'Failed to update record.'));
                }
            } catch (err) {
                console.error('Signed submit error:', err);
                alert('An unexpected server error occurred while updating status.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    }


    // Nav items navigation
    navItems.forEach(nav => {
        nav.addEventListener('click', (e) => {
            e.stopPropagation();


            navItems.forEach(i => i.classList.remove('active'));
            nav.classList.add('active');


            const navText = nav.querySelector('.nav-text');
            if (!navText) return;


            const text = navText.textContent.trim().toUpperCase();


            if (text === 'DRAFT') currentFilter = 'DRAFT';
            else if (text === 'CHECKED') currentFilter = 'CHECKED';
            else if (text === 'SIGNED') currentFilter = 'SIGNED_GROUP';
            else if (text === 'ACTIVE') currentFilter = 'ACTIVE';
            else if (text === 'EXPIRING SOON') currentFilter = 'EXPIRING';
            else if (text.includes('EXPIRED')) currentFilter = 'EXPIRED';
            else currentFilter = 'all';


            renderCards();
        });
    });


    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            renderCards();
        });
    }


    fetchAudits();


    // Intake Modal Section
    const intakeOverlay = document.getElementById('intake-modal-overlay');
    const btnIntake = document.getElementById('btn-intake');
    const closeBtn = document.getElementById('intake-modal-close');
    const backdropBtn = document.getElementById('intake-modal-backdrop');
    const intakeForm = document.getElementById('intake-form');


    const dropzone = document.getElementById('pdf-dropzone');
    const fileInput = document.getElementById('pdf-file-input');
    const dropzoneEmpty = document.getElementById('dropzone-empty');
    const dropzoneFile = document.getElementById('dropzone-file');
    const fileNameSpan = document.getElementById('dropzone-file-name');
    const fileSizeSpan = document.getElementById('dropzone-file-size');
    const removeFileBtn = document.getElementById('dropzone-remove');
    const pdfErrorMsg = document.getElementById('pdf-error-msg');


    function resetFileDropzone() {
        if (fileInput) fileInput.value = '';
        if (fileNameSpan) fileNameSpan.textContent = '';
        if (fileSizeSpan) fileSizeSpan.textContent = '';
        if (dropzoneEmpty && dropzoneFile) {
            dropzoneEmpty.hidden = false;
            dropzoneFile.hidden = true;
        }
        if (dropzone) {
            dropzone.classList.remove('has-file');
            dropzone.style.borderColor = '';
        }
        if (pdfErrorMsg) {
            pdfErrorMsg.style.display = 'none';
        }
    }


    if (btnIntake && intakeOverlay) {
        btnIntake.addEventListener('click', () => {
            resetFileDropzone();
            if (intakeForm) intakeForm.reset();
            intakeOverlay.classList.add('active');
        });
    }


    const closeModal = () => {
        if (intakeOverlay) {
            intakeOverlay.classList.remove('active');
            if (intakeForm) intakeForm.reset();
            resetFileDropzone();
        }
    };


    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdropBtn) backdropBtn.addEventListener('click', closeModal);


    if (dropzone && fileInput) {
        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('#dropzone-remove')) return;
            fileInput.click();
        });


        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });


        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });


        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileUI(e.dataTransfer.files[0]);
            }
        });


        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                updateFileUI(fileInput.files[0]);
            }
        });
    }


    function updateFileUI(file) {
        if (fileNameSpan && fileSizeSpan && dropzone) {
            fileNameSpan.textContent = file.name;
            fileSizeSpan.textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            dropzone.classList.add('has-file');
            if (dropzoneEmpty) dropzoneEmpty.hidden = true;
            if (dropzoneFile) dropzoneFile.hidden = false;
            dropzone.style.borderColor = '';
            if (pdfErrorMsg) pdfErrorMsg.style.display = 'none';
        }
    }


    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            resetFileDropzone();
        });
    }


    if (intakeForm) {
        intakeForm.addEventListener('submit', (e) => {
            e.preventDefault();


            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                if (pdfErrorMsg) pdfErrorMsg.style.display = 'block';
                if (dropzone) dropzone.style.borderColor = 'red';
                return;
            }


            const submitBtn = intakeForm.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Saving Document...';
            }


            const formData = new FormData(intakeForm);
            formData.append('action', 'intake_document');


            fetch('../controllers/save_intake.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(async data => {
                if (data.success) {
                    alert('Document successfully saved into the Signed registry!');
                    closeModal();


                    currentFilter = 'SIGNED_GROUP';


                    navItems.forEach(nav => {
                        const text = nav.querySelector('.nav-text')?.textContent.trim().toUpperCase();
                        if (text === 'SIGNED') {
                            nav.classList.add('active');
                        } else {
                            nav.classList.remove('active');
                        }
                    });


                    await fetchAudits();


                    if (data.id) {
                        const newCard = document.querySelector(`.moa-card[data-id="${data.id}"][data-source="intake"]`);
                        if (newCard) {
                            newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            newCard.style.outline = '3px solid var(--navy-600)';
                            setTimeout(() => { newCard.style.outline = ''; }, 3000);
                        }
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to save document.'));
                }
            })
            .catch(error => {
                console.error('Submission error:', error);
                alert('An unexpected error occurred while saving the document.');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            });
        });
    }


    window.loadDocumentRegistry = fetchAudits;
});