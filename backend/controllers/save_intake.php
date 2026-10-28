<?php 
// 1. This sets the respone format and starts the session
header('Content-Type: application/json');
session_start();

// This checks if the user is logged in 
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

// 2. Connect to database
require_once __DIR__ . '/../database/db.php';

try {

    // 3. This gets the information to be submitted from the form
    // This also checks the field names in the case from the is using another name
    $moaTitle = trim($_POST['intake-title'] ?? $_POST['moa_title'] ?? $_POST['title'] ?? '');
    $partnerOrg = trim($_POST['intake-org'] ?? $_POST['partner_organization'] ?? $_POST['organization'] ?? '');
    $signatory = trim($_POST['intake-signatory'] ?? $_POST['signatory'] ?? '');
    
    $dateIssued = !empty($_POST['intake-date-issued']) ? $_POST['intake-date-issued'] : (!empty($_POST['date_issued']) ? $_POST['date_issued'] : null);
    $effectiveDate = !empty($_POST['intake-effective']) ? $_POST['intake-effective'] : (!empty($_POST['effective_date']) ? $_POST['effective_date'] : null);
    $dateSigned = !empty($_POST['intake-date-signed']) ? $_POST['intake-date-signed'] : (!empty($_POST['date_signed']) ? $_POST['date_signed'] : null);
    $expirationDate = !empty($_POST['intake-expiration']) ? $_POST['intake-expiration'] : (!empty($_POST['expiration_date']) ? $_POST['expiration_date'] : '');
    $keyDates = trim($_POST['intake-key-dates'] ?? $_POST['key_dates'] ?? '');

    // This checks if the required fields has been filled in
    if (empty($moaTitle) || empty($partnerOrg) || empty($expirationDate)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'success' => false, 
            'message' => 'Please fill out all required fields (Title, Partner Organization, and Expiration Date). Received fields: ' . json_encode(array_keys($_POST))
        ]);
        $conn->close();
        exit();
    }

    // 4. This checks for the uploaded PDF file is uploaded
    $pdfPath = null;
    $fileKey = null;
    
    foreach (['pdf-file-input', 'pdf_file', 'document', 'file'] as $key) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
            $fileKey = $key;
            break;
        }
    }

    // This processes the uploaded file
    if ($fileKey !== null) {
        $fileTmpPath = $_FILES[$fileKey]['tmp_name'];
        $fileName = $_FILES[$fileKey]['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // This makes sure the file is uploaded as PDF file
        if ($fileExtension === 'pdf') {
            $uploadFileDir = __DIR__ . '/../uploads/';

            // This creates a new name for the uploaded PDF file
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            // This creates then creates a new name for the uploaded PDF file
            $newFileName = md5(time() . $fileName) . '.pdf';
            $dest_path = $uploadFileDir . $newFileName;

            // This moves the PDF file to the uploads folder
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $pdfPath = 'uploads/' . $newFileName;
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Failed to move uploaded file.']);
                $conn->close();
                exit();
            }
        } else {

            // This shows an error if the file is not a PDF file 
            http_response_code(400);
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid file format. Only PDF files are allowed.']);
            $conn->close();
            exit();
        }
    }

    // 5. This sets the document (MOA) status based of the expiration date
    $timestampToday = strtotime(date('Y-m-d'));
    $timestampExp = strtotime($expirationDate);
    $diffDays = ($timestampExp - $timestampToday) / (60 * 60 * 24);

    // This determines whether the document is expired, expiring soon, or active
    if ($diffDays < 0) {
        $status = 'EXPIRED';
    } elseif ($diffDays <= 30) {
        $status = 'EXPIRING'; 
    } else {
        $status = 'ACTIVE';
    }

    // 6. This creates a summary of the document's information
    $summaryHtml = "<p><strong>Signed MOA Intake Record</strong></p>" .
                   "<p><strong>Signatory:</strong> " . htmlspecialchars($signatory ?: 'N/A') . "</p>" .
                   "<p><strong>Date Issued:</strong> " . htmlspecialchars($dateIssued ?: 'N/A') . "</p>" .
                   "<p><strong>Effective Date:</strong> " . htmlspecialchars($effectiveDate ?: 'N/A') . "</p>" .
                   "<p><strong>Date Signed:</strong> " . htmlspecialchars($dateSigned ?: 'N/A') . "</p>" .
                   "<p><strong>Expiration Date:</strong> " . htmlspecialchars($expirationDate) . "</p>";
    if (!empty($keyDates)) {
        $summaryHtml .= "<p><strong>Key Dates / Deadlines:</strong> " . htmlspecialchars($keyDates) . "</p>";
    }

    // 7. This lets you save the document's information into the database
    $stmt = $conn->prepare("
        INSERT INTO audits 
        (moa_title, partner_organization, signatory, date_issued, effective_date, date_signed, expiration_date, key_dates, status, original_pdf_path, summary_html, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    // This checks if the database statement was created successfully and it adds the form information to the database query
    if ($stmt) {
        $stmt->bind_param("sssssssssss", $moaTitle, $partnerOrg, $signatory, $dateIssued, $effectiveDate, $dateSigned, $expirationDate, $keyDates, $status, $pdfPath, $summaryHtml);

     
        // This saves the information into the database
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            http_response_code(200);

            // This part basically sends a success response to the user
            echo json_encode([
                'status' => 'success', 
                'success' => true, 
                'message' => 'Document successfully saved into the Signed registry.',
                'id' => $newId,
                'doc_status' => $status
            ]);
        } else {

            // Shows an error if the database insert fails
            http_response_code(500);
            echo json_encode([
                'status' => 'error', 
                'success' => false, 
                'message' => $stmt->error
            ]);
        }
        $stmt->close();
    } else {

        // This shows an error if the database statement fails
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'success' => false, 
            'message' => $conn->error
        ]);
    }

} catch (Exception $e) {

    // This handles unexpected server errors
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
// Closes the database connection
$conn->close();
?>