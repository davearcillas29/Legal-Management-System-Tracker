<?php
// Set header for JSON response
header('Content-Type: application/json');
session_start();

// Authentication Guard: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "success" => false, "message" => "Unauthorized access. Please log in."]);
    exit();
}

// Connect to database
require_once __DIR__ . '/../database/db.php';

// Parse input payload: Handles both FormData ($_POST) and raw JSON body
$jsonInput = json_decode(file_get_contents('php://input'), true);

$id = $_POST['audit_id'] ?? $_POST['id'] ?? $jsonInput['id'] ?? null;
$rawStatus = $_POST['status'] ?? $jsonInput['status'] ?? null;
$status = $rawStatus ? strtoupper(trim($rawStatus)) : null;

// Check required fields
if (!$id || !$status) {
    http_response_code(400);
    echo json_encode(["status" => "error", "success" => false, "message" => "Missing required fields: id or status"]);
    $conn->close();
    exit();
}

// Extract Signatory Confirmation Metadata
$signatory = $_POST['signatory'] ?? $_POST['signed_by'] ?? $jsonInput['signatory'] ?? null;
$partnerOrg = $_POST['partner_organization'] ?? $jsonInput['partner_organization'] ?? null;
$dateReceived = $_POST['date_issued'] ?? $_POST['date_received'] ?? $jsonInput['date_issued'] ?? $jsonInput['date_received'] ?? null;
$dateSigned = $_POST['date_signed'] ?? $jsonInput['date_signed'] ?? null;
$effectiveDate = $_POST['effective_date'] ?? $jsonInput['effective_date'] ?? null;
$expirationDate = $_POST['expiration_date'] ?? $jsonInput['expiration_date'] ?? null;

// Handle PDF File Upload for Signed Document
$filePath = null;
if (isset($_FILES['signed_document']) && $_FILES['signed_document']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/signed_docs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExtension = pathinfo($_FILES['signed_document']['name'], PATHINFO_EXTENSION);
    $fileName = 'signed_' . $id . '_' . time() . '.' . $fileExtension;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['signed_document']['tmp_name'], $targetPath)) {
        $filePath = 'uploads/signed_docs/' . $fileName;
    }
}

// Build SQL Query dynamically based on status and available fields
if ($status === 'SIGNED') {
    // Saves into date_issued and signed_pdf_path so original_pdf_path remains intact
    $query = "UPDATE incoming_email_audits SET
                status = ?,
                signatory = COALESCE(?, signatory),
                partner_organization = COALESCE(?, partner_organization),
                date_issued = COALESCE(?, date_issued),
                date_signed = COALESCE(?, date_signed),
                effective_date = COALESCE(?, effective_date),
                expiration_date = COALESCE(?, expiration_date),
                signed_pdf_path = COALESCE(?, signed_pdf_path)
                WHERE id = ?";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param(
            "ssssssssi",
            $status,
            $signatory,
            $partnerOrg,
            $dateReceived,
            $dateSigned,
            $effectiveDate,
            $expirationDate,
            $filePath,
            $id
        );
    }
} elseif ($expirationDate) {
    $stmt = $conn->prepare("UPDATE incoming_email_audits SET status = ?, expiration_date = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $status, $expirationDate, $id);
    }
} else {
    $stmt = $conn->prepare("UPDATE incoming_email_audits SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $status, $id);
    }
}

// Execute query and return response
if ($stmt) {
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["status" => "success", "success" => true, "message" => "Status updated successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "success" => false, "message" => $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "success" => false, "message" => $conn->error]);
}

$conn->close();
?>