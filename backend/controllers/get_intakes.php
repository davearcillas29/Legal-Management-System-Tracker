<?php 
// This header code sets the response format to JSON (JavaScript Object Notation)
header('Content-Type: application/json');

// This connects to the database 
require_once __DIR__ . '/../database/db.php';

// This gets the audit records that is in the database, starting with the latest record
$sql = "SELECT id, moa_title, partner_organization, signatory, date_issued, effective_date, date_signed, expiration_date, key_dates, status, original_pdf_path, summary_html, created_at FROM audits ORDER BY id DESC";
$result = $conn->query($sql);

if ($result) {

    // This stores the audit records
    $intakes = [];

    // This get today's date
    $today = strtotime(date('Y-m-d'));

    // This checks each audit record
    while ($row = $result->fetch_assoc()) {
        $row['source'] = 'intake';

        // This checks the expiration date of the MOA
        if (!empty($row['expiration_date'])) {
            $exp = strtotime($row['expiration_date']);
            $diffDays = ($exp - $today) / 86400;

            // This sets the status that is based on the expiration date
            if ($diffDays < 0) {
                $row['status'] = 'EXPIRED';
            } elseif ($diffDays <= 30) {
                $row['status'] = 'EXPIRING';
            } else {
                $row['status'] = 'ACTIVE';
            }
        }
        // This adds the record to the list
        $intakes[] = $row;
    }

    // This sends as JSON
    echo json_encode([
        "status" => "success", 
        "success" => true, 
        "count" => count($intakes), 
        "data" => $intakes
    ]);
} else {

    // This shows an error if there something went wrong 
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "success" => false, 
        "message" => $conn->error
    ]);
}
// Closes the database connection
$conn->close();
?>