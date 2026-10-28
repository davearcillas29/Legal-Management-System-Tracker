<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../database/db.php';

$auditIdRaw = $_POST['audit_id'] ?? null;
$rawRecipient = $_POST['recipient_email'] ?? '';

if (!$auditIdRaw || empty($rawRecipient)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing audit ID or recipient email.']);
    exit;
}

$auditId = intval($auditIdRaw);
$emailArray = preg_split('/[\s,]+/', $rawRecipient, -1, PREG_SPLIT_NO_EMPTY);
$validEmails = array_filter($emailArray, function($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
});
$recipient = implode(', ', $validEmails);

if (empty($recipient)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid recipient email address provided.']);
    exit;
}

$status = $_POST['status'] ?? 'CHECKED';
$recommendedChanges = $_POST['recommended_changes'] ?? '';
$subject = $_POST['subject'] ?? ("MOA Review Update: MOA-" . str_pad($auditId, 4, '0', STR_PAD_LEFT));

$payload = [
    'audit_id'            => $auditId,
    'recipient_email'     => $recipient,
    'recipient'           => $recipient,
    'subject'             => $subject,
    'recommended_changes' => $recommendedChanges,
    'htmlBody'            => $recommendedChanges,
    'message_id'          => $_POST['message_id'] ?? null,
    'thread_id'           => $_POST['thread_id'] ?? null
];

if (isset($_FILES['revised_document']) && $_FILES['revised_document']['error'] === UPLOAD_ERR_OK) {
    $payload['revised_document'] = new CURLFile(
        $_FILES['revised_document']['tmp_name'],
        $_FILES['revised_document']['type'],
        $_FILES['revised_document']['name']
    );
}

function dispatchToN8n($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'code'     => $httpCode,
        'response' => $response,
        'error'    => $curlError
    ];
}

$endpoints = [
    'http://localhost:5678/webhook/send-reply',
    'http://localhost:5678/webhook-test/send-reply'
];

$res = ['code' => 404, 'response' => '', 'error' => ''];

foreach ($endpoints as $url) {
    $res = dispatchToN8n($url, $payload);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        break;
    }
}

if ($res['code'] >= 200 && $res['code'] < 300) {
    $stmt = $conn->prepare("INSERT INTO reply_logs (audit_id, recipient_email, subject, message_body) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $auditId, $recipient, $subject, $recommendedChanges);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true, 
        'message' => 'Reply sent to n8n workflow and logged successfully.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error'        => 'Failed to trigger n8n workflow.',
        'n8n_code'     => $res['code'],
        'curl_error'   => $res['error'],
        'n8n_response' => $res['response']
    ]);
}
?>