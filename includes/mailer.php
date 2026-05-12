<?php
function sendMail($to, $subject, $message) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Notes Platform <no-reply@notes.com>\r\n";

    $html = "
    <html><body>
    <div style='font-family:sans-serif;max-width:500px;margin:auto'>
        <h2 style='color:#534AB7'>Notes Platform</h2>
        <p>{$message}</p>
        <hr>
        <small style='color:#888'>This is an automated message. Please do not reply.</small>
    </div>
    </body></html>";

    return mail($to, $subject, $html, $headers);
}

function notifyApproval($email, $noteTitle) {
    $subject = "Your note has been approved!";
    $message = "Great news! Your note <strong>{$noteTitle}</strong> has been approved and is now live on the platform.";
    return sendMail($email, $subject, $message);
}

function notifyRejection($email, $noteTitle, $reason) {
    $subject = "Update on your note submission";
    $message = "Your note <strong>{$noteTitle}</strong> was not approved.<br><br><strong>Reason:</strong> {$reason}";
    return sendMail($email, $subject, $message);
}

function notifyComment($email, $noteTitle, $commenterName) {
    $subject = "New comment on your note";
    $message = "<strong>{$commenterName}</strong> commented on your note <strong>{$noteTitle}</strong>.";
    return sendMail($email, $subject, $message);
}
?>