

<?php
session_start();


/// 1. Check if the user is logged in, if not redirect them to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// 2. Set maximum allowed idle to 15 minutes (900 seconds)
$timeout_duration = 900;


// 3. Checks if the time difference exceeds your limit
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session expired: destroy session and send them back with an error
    session_unset();
    session_destroy();
    header("Location: login.php?error=timeout");
    exit();
}


// 4. Reset the inactive timer to current time
$_SESSION['last_activity'] = time();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MOA Management System · ADZU Legal Affairs</title>
<!-- Load css files and custom web fonts from Google Fonts -->
<link rel="stylesheet" href="../../frontend/src/css/main.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="app">

  <!--  SIDEBAR -->
  <aside class="sidebar">

    <div class="main_logo">
      <img src="../../frontend/src/img/adzu_seal.png" alt="ADZU Logo" class="main_logo">
    </div>

    <div class="brand-name">LEGIT DOCS</div>
    <div class="brand-sub">Legal Affairs Office</div>
    <hr class="sidebar-divider">

    <div class="nav-label">Overview</div>
    <ul class="nav-list">
      <li class="nav-item active">
        <span class="icon nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
        <span class="nav-text">Draft</span>
      </li>
      <li class="nav-item">
        <span class="icon nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
        <span class="nav-text">Checked</span>
      </li>
      <li class="nav-item-group">
        <div class="nav-item nav-item-parent">
          <span class="icon nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
          <span class="nav-text">Signed</span>
        </div>
        <ul class="nav-sublist">
          <li class="nav-subitem">
            <span class="icon nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="8.5 12 11 14.5 15.5 9.5"/></svg></span>
            <span class="nav-text">Active</span>
          </li>
          <li class="nav-subitem">
            <span class="icon nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
            <span class="nav-text">Expiring Soon</span>
          </li>
          <li class="nav-subitem">
            <span class="icon nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></span>
            <span class="nav-text">Expired/Archived</span>
          </li>
        </ul>
      </li>
    </ul>


    <!-- Sidebar buttom footer section -->
    <div class="sidebar-footer">
      <hr class="sidebar-divider">
      <div class="nav-item nav-logout" id="btn-logout">
        <span class="icon nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
        <span class="nav-text">Logout</span>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">

    <header class="topbar">
      <p class="eyebrow">Ateneo de Zamboanga University</p>
      <div class="topbar-row">
        <div>
          <h1 class="page-title">MOA Management System</h1>
        </div>
        <button class="btn-intake" id="btn-intake">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
          Intake Document
        </button>
      </div>
    </header>

    <div class="content">

      <!-- Checkbox drives the pure-CSS modal below -->
      <input type="checkbox" id="moa-modal" class="modal-toggle">

      <div class="search-row">
        <div class="search-box">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input type="text" placeholder="Search by MOA title, partner institution, or ID number…">
        </div>
        <div class="results-count">0 results</div>
      </div>

      <div class="card-list" id="card-list">
      </div>

      <!-- Modal -->
      <div class="modal-overlay">
        <label for="moa-modal" class="modal-backdrop" aria-label="Close"></label>
        <div class="modal" role="dialog" aria-modal="true" aria-label="Clinical Clerkship Program details">

          <div class="modal-header">
            <label for="moa-modal" class="modal-close" aria-label="Close">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>
            </label>

            <div class="modal-id"></div>
            <h2 class="modal-title"></h2>
            <div class="modal-org">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="1"/><line x1="9" y1="10" x2="9" y2="10.01"/><line x1="15" y1="10" x2="15" y2="10.01"/></svg></span>

            </div>

            <div class="modal-progress-row">
              <span></span>
              <span class="modal-days"></span>
            </div>
            <div class="progress-track"><div class="progress-fill amber" style="width:92%"></div></div>
          </div>

          <div class="modal-body">
            <div class="modal-body-top"> <!-- this-->
              <span class="badge amber"></span>
              <span class="valid-until"></span>
            </div>

            <div class="info-card">
              <div class="info-label"></div>
              <p class="info-text"></p>
            </div>
          </div>

          <div class="modal-footer">
            <button class="icon-btn" aria-label="Download">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
            </button>
            <button class="icon-btn" aria-label="Open in new tab">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span>
            </button>
          </div>
        </div>
    </div>
  </main>
</div>


<!-- Intake Document & Upload pop-up Modal -->
<div class="modal-overlay" id="intake-modal-overlay">
  <button class="modal-backdrop" id="intake-modal-backdrop" aria-label="Close"></button>
  <div class="modal" role="dialog" aria-modal="true" aria-label="Intake new document" style="max-width:520px;">
    <div class="modal-header" style="padding-bottom:22px;">
      <button class="modal-close" id="intake-modal-close" aria-label="Close" type="button">
        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>
      </button>
      <div class="modal-id">New Record</div>
      <h2 class="modal-title" style="padding-right:40px;">Intake Document</h2>
      <p style="font-size:13.5px; color:rgba(255,255,255,.7); margin:0 0 22px;">Upload a signed MOA to file it directly into the Signed registry. Status (Active / Expiring Soon / Expired) is set automatically from the expiration date.</p>
    </div>
    <form id="intake-form">
      <div class="modal-body">
        <div class="intake-field">
          <label for="intake-title">MOA Title</label>
          <input type="text" id="intake-title" name="intake-title" placeholder="e.g. Student Exchange Program" required>
        </div>
        <div class="intake-field">
          <label for="intake-org">Partner Organization</label>
          <input type="text" id="intake-org" name="intake-org" placeholder="e.g. Ateneo de Zamboanga University" required>
        </div>
        <div class="intake-field">
          <label for="intake-signatory">Signatory By</label>
          <input type="text" id="intake-signatory" name="intake-signatory" placeholder="e.g. Rev. Fr. Ernald Andal, S.J., President" required>
        </div>
        <div class="intake-grid-2col">
          <div class="intake-field">
            <label for="intake-date-issued">Date Issued</label>
            <input type="date" id="intake-date-issued" name="intake-date-issued">
          </div>
          <div class="intake-field">
            <label for="intake-effective">Effective Date</label>
            <input type="date" id="intake-effective" name="intake-effective">
          </div>
          <div class="intake-field">
            <label for="intake-date-signed">Date Signed</label>
            <input type="date" id="intake-date-signed" name="intake-date-signed">
          </div>
          <div class="intake-field">
            <label for="intake-expiration">Expiration Date</label>
            <input type="date" id="intake-expiration" name="intake-expiration" required>
          </div>
        </div>
        <div class="intake-field">
          <label>Signed Document (PDF)</label>
          <div class="dropzone" id="pdf-dropzone" tabindex="0" role="button" aria-label="Upload signed PDF document">
            <input type="file" id="pdf-file-input" name="pdf-file-input" accept="application/pdf,.pdf" hidden>
            <div class="dropzone-empty" id="dropzone-empty">
              <span class="icon dropzone-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></span>
              <p class="dropzone-text"><strong>Click to upload</strong> or drag and drop</p>
              <p class="dropzone-hint">PDF only, up to 20MB</p>
            </div>
            <div class="dropzone-file" id="dropzone-file" hidden>
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/><line x1="9" y1="18" x2="13" y2="18"/></svg></span>
              <div class="dropzone-file-info">
                <span class="dropzone-file-name" id="dropzone-file-name"></span>
                <span class="dropzone-file-size" id="dropzone-file-size"></span>
              </div>
              <button type="button" class="dropzone-remove" id="dropzone-remove" aria-label="Remove file">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>
          <span id="pdf-error-msg" style="color: red; font-size: 0.85rem; display: none;">Please attach a signed PDF document.</span>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-renewal" type="submit">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
          Save Document
        </button>
      </div>
    </form>
  </div>
</div>
<!-- SIGNED CONFIRMATION POPUP MODAL -->
<div id="signed-modal-overlay" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(18, 33, 66, 0.65); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
  <div id="signed-modal-backdrop" class="modal-backdrop" style="position: absolute; width: 100%; height: 100%;"></div>

  <div class="modal-card" style="max-width: 520px; width: 90%; background: #ffffff; border-radius: 16px; padding: 28px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); position: relative; z-index: 10001; font-family: var(--font-body, 'Inter', sans-serif);">

    <!-- Modal Header with Cinzel Font -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px;">
      <h3 style="margin: 0; font-family: var(--font-display, 'Cinzel', serif); font-size: 19px; color: var(--navy-900, #1e293b); font-weight: 700; letter-spacing: -0.01em;">Complete Document Signing</h3>
      <button id="signed-modal-close" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
    </div>


    <form id="signed-form" enctype="multipart/form-data" style="font-family: var(--font-body, 'Inter', sans-serif);">
      <input type="hidden" id="signed_audit_id" name="audit_id">


      <!-- Signatory Field -->
      <div style="margin-bottom: 16px;">
        <label style="font-family: var(--font-body, 'Inter', sans-serif); font-weight: 700; display: block; margin-bottom: 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Signatory By</label>
        <input type="text" id="signed_signatory" name="signatory" placeholder="e.g. Atty. Armee Jay L. Cresmundo" required style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 9px 12px; font-size: 13.5px; font-family: var(--font-body, 'Inter', sans-serif); color: #1e293b; box-sizing: border-box; outline: none;">
      </div>


      <!-- Partner Organization -->
      <div style="margin-bottom: 16px;">
        <label style="font-family: var(--font-body, 'Inter', sans-serif); font-weight: 700; display: block; margin-bottom: 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Partner Organization</label>
        <input type="text" id="signed_partner" name="partner_organization" placeholder="e.g. Counterparty Corp." required style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 9px 12px; font-size: 13.5px; font-family: var(--font-body, 'Inter', sans-serif); color: #1e293b; box-sizing: border-box; outline: none;">
      </div>


      <!-- 2x2 Date Grid -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
        <div>
          <label style="font-family: var(--font-body, 'Inter', sans-serif); font-weight: 700; display: block; margin-bottom: 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Date Received</label>
          <input type="date" id="signed_date_received" name="date_issued" required style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 13px; font-family: var(--font-body, 'Inter', sans-serif); color: #1e293b; box-sizing: border-box; outline: none;">
        </div>


        <div>
          <label style="font-family: var(--font-body, 'Inter', sans-serif); font-weight: 700; display: block; margin-bottom: 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Date Signed</label>
          <input type="date" id="signed_date_signed" name="date_signed" required style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 13px; font-family: var(--font-body, 'Inter', sans-serif); color: #1e293b; box-sizing: border-box; outline: none;">
        </div>


        <div>
          <label style="font-family: var(--font-body, 'Inter', sans-serif); font-weight: 700; display: block; margin-bottom: 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Effective Date</label>
          <input type="date" id="signed_effective_date" name="effective_date" style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 13px; font-family: var(--font-body, 'Inter', sans-serif); color: #1e293b; box-sizing: border-box; outline: none;">
        </div>


        <div>
          <label style="font-family: var(--font-body, 'Inter', sans-serif); font-weight: 700; display: block; margin-bottom: 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Expiration Date</label>
          <input type="date" id="signed_expiration_date" name="expiration_date" style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 13px; font-family: var(--font-body, 'Inter', sans-serif); color: #1e293b; box-sizing: border-box; outline: none;">
        </div>
      </div>


      <!-- File Attachment Zone -->
      <div style="margin-bottom: 20px;">
        <label style="font-family: var(--font-body, 'Inter', sans-serif); font-weight: 700; display: block; margin-bottom: 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Attach Final Signed Document</label>
        <input type="file" id="signed_document" name="signed_document" accept=".pdf,.doc,.docx" required style="width: 100%; font-size: 13px; font-family: var(--font-body, 'Inter', sans-serif); padding: 8px; border: 1px dashed #cbd5e1; border-radius: 10px; background: #f8fafc; box-sizing: border-box;">
      </div>


      <!-- Modal Footer Buttons (Uniform Pill Shape) -->
      <div style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 18px;">
        <button type="button" onclick="document.getElementById('signed-modal-overlay').style.display='none'" style="padding: 10px 22px; border-radius: 9999px; border: 1px solid #cbd5e1; background: #ffffff; cursor: pointer; font-family: var(--font-body, 'Inter', sans-serif); font-size: 13.5px; font-weight: 600; color: #1e293b; transition: all 0.2s;">Cancel</button>
        <button type="submit" style="padding: 10px 22px; border-radius: 9999px; border: none; background: #008450; color: #ffffff !important; cursor: pointer; font-family: var(--font-body, 'Inter', sans-serif); font-size: 13.5px; font-weight: 600; box-shadow: 0 4px 12px rgba(0, 132, 80, 0.25); transition: all 0.2s;">Confirm & Move to Signed</button>
      </div>
    </form>
  </div>
</div>
<script src="../../frontend/src/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>