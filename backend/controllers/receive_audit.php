<?php 
// Connect to database
require_once __DIR__ . '/../database/db.php';

//getting information from n8n
$thread_id = $_POST['thread_id'] ?? null;
$sender_email = $_POST['sender_email'] ?? null;
$subject = $_POST['subject'] ?? null;
$summary_html = $_POST['summary_html'] ?? null;
$status = 'DRAFT';

//sets pdf_path to nothing since there is no valid file path yet
$pdf_path = null;

//gets a copy file then checks if pdf file was uploaded without any errors and saved in the temporary file
if(isset($_FILES['original_pdf']) && $_FILES['original_pdf']['error'] === UPLOAD_ERR_OK){
    $file_tmp = $_FILES['original_pdf']['tmp_name'];
    $file_extension = strtolower(pathinfo($_FILES['original_pdf']['name'], PATHINFO_EXTENSION));

    // 1. Strict Extension Whitelist Check (Only allow PDF)
    if ($file_extension !== 'pdf') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid file extension. Only PDF files are allowed."]);
        exit();
    }

    // 2. Strict MIME Type Verification
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    if ($mime_type !== 'application/pdf') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid file content type. Only PDF files are allowed."]);
        exit();
    }

    $upload_dir = __DIR__ . '/../uploads/';

    // Creates upload folder automatically if it doesn't exist yet[cite: 6]
    if(!is_dir($upload_dir)){
        mkdir($upload_dir, 0777, true);
    }

    // Generates a secure, unique filename locked to .pdf
    $unique_filename = 'MOA_' . time() . '_' . uniqid() . '.pdf';
    $target_filepath = $upload_dir . $unique_filename;

    // Moves the file from the temporary folder to the uploads folder[cite: 6]
    if(move_uploaded_file($file_tmp, $target_filepath)){
        $pdf_path = 'uploads/' . $unique_filename; // Saves the filepath of the transferred file[cite: 6]
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
        exit();
    }
}

//prepare SQL statement to safely insert the email audit data
$stmt = $conn->prepare("INSERT INTO incoming_email_audits (thread_id, sender_email, subject, summary_html, original_pdf_path, status) VALUES (?, ?, ?, ?, ?, ?)");

//bind the PHP variable as String ("ssssss") to their database columns
$stmt->bind_param("ssssss", $thread_id, $sender_email, $subject, $summary_html, $pdf_path, $status);

//executes the transfer of information towards the database
//checks if the transfer is successful or not
//sends confirmation or error message to the ai automation workflow
if($stmt->execute()){
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Draft record saved successfully"]);
}else{
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

//closes statement and database connection
$stmt->close();
$conn->close();
?>