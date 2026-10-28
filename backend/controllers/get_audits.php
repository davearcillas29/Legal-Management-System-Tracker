<?php 
// Returns unified list of all audits 
header('Content-Type: application/json');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/moa_extractor.php';

// Ensure database schema is aligned
ensure_moa_database_schema($conn);

$audits = [];
$today = strtotime(date('Y-m-d'));
$errors = [];

// 1. Fetch from incoming_email_audits via n8n
$sqlEmail = "SELECT id, thread_id, sender_email, subject, summary_html, original_pdf_path, signed_pdf_path, status, moa_title, partner_organization, signatory, date_issued, effective_date, date_signed, expiration_date, key_dates, created_at FROM incoming_email_audits ORDER BY id DESC";
$resultEmail = $conn->query($sqlEmail);

if (!$resultEmail) {
    $errors[] = "Email audits query failed: " . $conn->error;
} else {
    while ($row = $resultEmail->fetch_assoc()) {
        $dateIssued     = $row['date_issued'] ?? null;
        $effectiveDate  = $row['effective_date'] ?? null;
        $dateSigned     = $row['date_signed'] ?? null;
        $expirationDate = $row['expiration_date'] ?? null;
        $keyDates       = $row['key_dates'] ?? null;
        $partnerOrg     = $row['partner_organization'] ?? null;
        $signatory      = $row['signatory'] ?? null;
        $moaTitle       = $row['moa_title'] ?? null;

        // On-the-fly extraction fallback if dates are not yet populated in DB
        if ((empty($effectiveDate) && empty($expirationDate)) || empty($keyDates)) {
            $extracted = extract_moa_details($row['summary_html'], [], $row['original_pdf_path']);
            if (empty($dateIssued) && !empty($extracted['date_issued'])) $dateIssued = $extracted['date_issued'];
            if (empty($effectiveDate) && !empty($extracted['effective_date'])) $effectiveDate = $extracted['effective_date'];
            if (empty($dateSigned) && !empty($extracted['date_signed'])) $dateSigned = $extracted['date_signed'];
            if (empty($expirationDate) && !empty($extracted['expiration_date'])) $expirationDate = $extracted['expiration_date'];
            if (empty($keyDates) && !empty($extracted['key_dates'])) $keyDates = $extracted['key_dates'];
            if (empty($partnerOrg) && !empty($extracted['partner_organization'])) $partnerOrg = $extracted['partner_organization'];
            if (empty($signatory) && !empty($extracted['signatory'])) $signatory = $extracted['signatory'];
            if (empty($moaTitle) && !empty($extracted['moa_title'])) $moaTitle = $extracted['moa_title'];
        }

        $rawStatus = strtoupper($row['status'] ?: 'DRAFT');
        $status = $rawStatus;

        // If marked as signed or active, dynamically calculate expiration status
        if (in_array($rawStatus, ['SIGNED', 'ACTIVE', 'EXPIRING', 'EXPIRED']) && !empty($expirationDate)) {
            $exp = strtotime($expirationDate);
            $diffDays = ($exp - $today) / 86400;
            if ($diffDays < 0) {
                $status = 'EXPIRED';
            } elseif ($diffDays <= 30) {
                $status = 'EXPIRING';
            } else {
                $status = 'ACTIVE';
            }
        }

        $audits[] = [
            'id' => (int)$row['id'],
            'source' => 'email',
            'thread_id' => $row['thread_id'],
            'sender_email' => $row['sender_email'],
            'partner_organization' => $partnerOrg ?: $row['sender_email'],
            'subject' => $row['subject'],
            'moa_title' => $moaTitle ?: $row['subject'],
            'signatory' => $signatory ?: null,
            'date_issued' => $dateIssued ?: null,
            'effective_date' => $effectiveDate ?: null,
            'date_signed' => $dateSigned ?: null,
            'expiration_date' => $expirationDate ?: null,
            'key_dates' => $keyDates ?: null,
            'summary_html' => $row['summary_html'],
            'original_pdf_path' => $row['original_pdf_path'],
            'signed_pdf_path' => $row['signed_pdf_path'] ?? null,
            'status' => $status,
            'created_at' => $row['created_at']
        ];
    }
}

// 2. Fetch audits from intake records
$sqlIntake = "SELECT id, moa_title, partner_organization, signatory, date_issued, effective_date, date_signed, expiration_date, key_dates, status, original_pdf_path, summary_html, created_at FROM audits ORDER BY id DESC";
$resultIntake = $conn->query($sqlIntake);

if (!$resultIntake) {
    $errors[] = "Intake audits query failed: " . $conn->error;
} else {
    while ($row = $resultIntake->fetch_assoc()) {
        $status = strtoupper($row['status'] ?: 'ACTIVE');
        // Update status based on current date and expiration date
        if (!empty($row['expiration_date'])) {
            $exp = strtotime($row['expiration_date']);
            $diffDays = ($exp - $today) / 86400;
            if ($diffDays < 0) {
                $status = 'EXPIRED';
            } elseif ($diffDays <= 30) {
                $status = 'EXPIRING';
            } else {
                $status = 'ACTIVE';
            }
        }

        $audits[] = [
            'id' => (int)$row['id'],
            'source' => 'intake',
            'thread_id' => null,
            'sender_email' => $row['partner_organization'],
            'partner_organization' => $row['partner_organization'],
            'subject' => $row['moa_title'],
            'moa_title' => $row['moa_title'],
            'signatory' => $row['signatory'],
            'date_issued' => $row['date_issued'],
            'effective_date' => $row['effective_date'],
            'date_signed' => $row['date_signed'],
            'expiration_date' => $row['expiration_date'],
            'key_dates' => $row['key_dates'] ?? null,
            'summary_html' => $row['summary_html'],
            'original_pdf_path' => $row['original_pdf_path'],
            'signed_pdf_path' => null,
            'status' => $status,
            'created_at' => $row['created_at']
        ];
    }
}

// 3. Sort unified list by created_at DESC
usort($audits, function ($a, $b) {
    $tA = !empty($a['created_at']) ? strtotime($a['created_at']) : 0;
    $tB = !empty($b['created_at']) ? strtotime($b['created_at']) : 0;
    if ($tA === $tB) {
        return ($b['id'] ?? 0) - ($a['id'] ?? 0);
    }
    return $tB - $tA;
});

// Return JSON response
http_response_code(200);
echo json_encode([
    "status" => !empty($errors) ? "error" : "success",
    "errors" => $errors,
    "count" => count($audits),
    "data" => $audits
]);

$conn->close();
?>