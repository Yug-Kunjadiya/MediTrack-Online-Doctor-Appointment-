<?php
require_once __DIR__ . '/config/db.php'; // Use __DIR__ for reliable includes

// Ensure user is logged in for any chat action
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$current_user_id = (int)$_SESSION['id'];
$current_user_role = $_SESSION['role']; 

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($action === 'send_message') {
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $receiver_role = isset($_POST['receiver_role']) ? trim($_POST['receiver_role']) : '';
    $message_text = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

    if (empty($receiver_id) || empty($receiver_role) || $message_text === '' || empty($appointment_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields (receiver, message, or appointment).']);
        exit;
    }
    if (!in_array($receiver_role, ['user', 'doctor'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid receiver role.']);
        exit;
    }
    if ($current_user_id == $receiver_id && $current_user_role == $receiver_role) {
        echo json_encode(['success' => false, 'message' => 'Cannot send message to yourself.']);
        exit;
    }

    // Verify appointment is approved and involves the sender/receiver for this specific chat
    $can_chat_sql = "SELECT id FROM appointments WHERE id = ? AND status = 'approved' AND 
                     ((user_id = ? AND doctor_id = ?) OR (user_id = ? AND doctor_id = ?))";
    $stmt_verify_chat = mysqli_prepare($conn, $can_chat_sql);
    
    $param_user_for_appt_check = ($current_user_role == 'user') ? $current_user_id : $receiver_id;
    $param_doctor_for_appt_check = ($current_user_role == 'doctor') ? $current_user_id : $receiver_id;

    mysqli_stmt_bind_param($stmt_verify_chat, "iiiii", $appointment_id, 
        $param_user_for_appt_check, $param_doctor_for_appt_check, 
        $param_user_for_appt_check, $param_doctor_for_appt_check);
    mysqli_stmt_execute($stmt_verify_chat);
    mysqli_stmt_store_result($stmt_verify_chat);

    if (mysqli_stmt_num_rows($stmt_verify_chat) == 0) {
        echo json_encode(['success' => false, 'message' => 'Chat is not enabled for this appointment or participants.']);
        mysqli_stmt_close($stmt_verify_chat);
        exit;
    }
    mysqli_stmt_close($stmt_verify_chat);

    $sql_insert = "INSERT INTO messages (appointment_id, sender_id, sender_role, receiver_id, receiver_role, message_text, timestamp) 
                   VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if (!$stmt_insert) {
        error_log("Send message prepare error: " . mysqli_error($conn));
        echo json_encode(['success' => false, 'message' => 'Server error preparing message.']);
        exit;
    }
    mysqli_stmt_bind_param($stmt_insert, "iissss", $appointment_id, $current_user_id, $current_user_role, $receiver_id, $receiver_role, $message_text);

    if (mysqli_stmt_execute($stmt_insert)) {
        $new_message_id = mysqli_insert_id($conn);
        
        // Fetch the newly sent message to return to the client
        $sql_fetch_sent = "SELECT m.id, m.sender_id, m.sender_role, m.message_text, m.timestamp,
                                  IF(m.sender_role='user', u.name, d.name) as sender_name
                           FROM messages m
                           LEFT JOIN users u ON m.sender_id = u.id AND m.sender_role = 'user'
                           LEFT JOIN doctors d ON m.sender_id = d.id AND m.sender_role = 'doctor'
                           WHERE m.id = ?";
        $stmt_fetch_sent_msg = mysqli_prepare($conn, $sql_fetch_sent);
        mysqli_stmt_bind_param($stmt_fetch_sent_msg, "i", $new_message_id);
        mysqli_stmt_execute($stmt_fetch_sent_msg);
        $result_sent_msg = mysqli_stmt_get_result($stmt_fetch_sent_msg);
        $sent_message_data = mysqli_fetch_assoc($result_sent_msg);
        mysqli_stmt_close($stmt_fetch_sent_msg);

        if ($sent_message_data) {
            $sent_message_data['timestamp_formatted'] = date("M j, g:i A", strtotime($sent_message_data['timestamp']));
            $sent_message_data['message_text'] = nl2br(htmlspecialchars($sent_message_data['message_text']));
            $sent_message_data['sender_name'] = htmlspecialchars($sent_message_data['sender_name'] ?? 'Unknown');
            echo json_encode(['success' => true, 'message' => 'Message sent.', 'sent_message' => $sent_message_data]);
        } else {
            // Should ideally not happen if insert was successful
            echo json_encode(['success' => true, 'message' => 'Message sent, but could not retrieve it immediately.']);
        }
    } else {
        error_log("Send message execute error: " . mysqli_stmt_error($stmt_insert));
        echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
    }
    mysqli_stmt_close($stmt_insert);

} elseif ($action === 'fetch_messages') {
    $other_user_id = isset($_GET['other_user_id']) ? (int)$_GET['other_user_id'] : 0;
    $other_user_role = isset($_GET['other_user_role']) ? trim($_GET['other_user_role']) : '';
    $appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
    $last_message_id = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0; // ID of the last message client has

    if (empty($other_user_id) || empty($other_user_role) || empty($appointment_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields for fetching messages.']);
        exit;
    }
     if (!in_array($other_user_role, ['user', 'doctor'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid other user role specified.']);
        exit;
    }

    // Mark messages as read: from the 'other_user' to the 'current_user' for this specific appointment
    // This should happen when current_user views the chat.
    $sql_mark_read = "UPDATE messages SET is_read = 1 
                      WHERE appointment_id = ? 
                      AND sender_id = ? AND sender_role = ?  /* Message came FROM other_user */
                      AND receiver_id = ? AND receiver_role = ? /* Message was sent TO current_user */
                      AND is_read = 0";
    $stmt_mark_read = mysqli_prepare($conn, $sql_mark_read);
    if($stmt_mark_read){
        mysqli_stmt_bind_param($stmt_mark_read, "iisis", $appointment_id, $other_user_id, $other_user_role, $current_user_id, $current_user_role);
        mysqli_stmt_execute($stmt_mark_read); 
        mysqli_stmt_close($stmt_mark_read);
    } else {
        error_log("Mark read prepare error: " . mysqli_error($conn));
    }


    // Fetch messages FOR THIS CONVERSATION (between current_user and other_user for this appointment_id)
    // that are NEWER than the last_message_id the client has.
    $sql_fetch = "SELECT m.id, m.sender_id, m.sender_role, m.message_text, m.timestamp,
                         IF(m.sender_role='user', u.name, d.name) as sender_name
                  FROM messages m
                  LEFT JOIN users u ON m.sender_id = u.id AND m.sender_role = 'user'
                  LEFT JOIN doctors d ON m.sender_id = d.id AND m.sender_role = 'doctor'
                  WHERE m.appointment_id = ? 
                  AND ((m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?) /* Current to Other */
                       OR (m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?)) /* Other to Current */
                  AND m.id > ?  /* Fetch only messages with ID greater than what client last saw */
                  ORDER BY m.timestamp ASC, m.id ASC"; // Order by timestamp then ID for strict ordering
    
    $stmt_fetch = mysqli_prepare($conn, $sql_fetch);
    if (!$stmt_fetch) {
        error_log("Fetch messages prepare error: " . mysqli_error($conn));
        echo json_encode(['success' => false, 'message' => 'Server error preparing to fetch messages.']);
        exit;
    }

    mysqli_stmt_bind_param($stmt_fetch, "iisssissii", 
        $appointment_id, 
        $current_user_id, $current_user_role, $other_user_id, $other_user_role,
        $other_user_id, $other_user_role, $current_user_id, $current_user_role,
        $last_message_id
    );

    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['timestamp_formatted'] = date("M j, g:i A", strtotime($row['timestamp']));
        $row['message_text'] = nl2br(htmlspecialchars($row['message_text']));
        $row['sender_name'] = htmlspecialchars($row['sender_name'] ?? 'Unknown');
        $messages[] = $row;
    }
    mysqli_stmt_close($stmt_fetch);
    echo json_encode(['success' => true, 'messages' => $messages]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
}

mysqli_close($conn);
?>