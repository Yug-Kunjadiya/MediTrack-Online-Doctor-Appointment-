<?php
$base_url = "/meditrack"; 

if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: " . $base_url . "/auth/login.php");
    exit;
}

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$chat_with_id = isset($_GET['with_id']) ? (int)$_GET['with_id'] : 0; 
$chat_with_role = isset($_GET['with_role']) ? trim($_GET['with_role']) : ''; 

$current_user_id = (int)$_SESSION['id'];
$current_user_role = $_SESSION['role'];
$page_title_default = "Chat"; 

if ($current_user_role == 'user') {
    require_once __DIR__ . '/user/partials/user_header.php'; 
    $page_title_default = "Chat with Doctor";
} elseif ($current_user_role == 'doctor') {
    require_once __DIR__ . '/doctor/partials/doctor_header.php'; 
    $page_title_default = "Chat with Patient";
} else {
    echo "Error: User role not identified.";
    exit;
}

if (empty($appointment_id) || empty($chat_with_id) || empty($chat_with_role)) {
    set_message("Invalid chat parameters provided.", "danger");
    redirect($base_url . "/" . $current_user_role . "/index.php");
}
if (!in_array($chat_with_role, ['user', 'doctor'])) {
    set_message("Invalid chat participant role specified.", "danger");
    redirect($base_url . "/" . $current_user_role . "/index.php");
}
if ($current_user_id == $chat_with_id && $current_user_role == $chat_with_role) {
    set_message("Cannot initiate chat with yourself.", "warning");
    redirect($base_url . "/" . $current_user_role . "/index.php");
}

$can_chat_sql = "SELECT a.id as app_id, 
                    IF(? = 'user', d.name, u.name) as other_person_name, /* Get name of the OTHER person */
                    a.status as appointment_status
                 FROM appointments a
                 LEFT JOIN users u ON a.user_id = u.id /* User associated with appointment */
                 LEFT JOIN doctors d ON a.doctor_id = d.id /* Doctor associated with appointment */
                 WHERE a.id = ? AND 
                       ((a.user_id = ? AND a.doctor_id = ?) OR (a.user_id = ? AND a.doctor_id = ?))";

$stmt_verify = mysqli_prepare($conn, $can_chat_sql);

// Determine who is user and who is doctor for THIS appointment
$user_in_appt = ($current_user_role == 'user') ? $current_user_id : $chat_with_id;
$doctor_in_appt = ($current_user_role == 'doctor') ? $current_user_id : $chat_with_id;

// First param is for the IF condition: current_user_role determines which name to fetch (doctor's or user's name)
mysqli_stmt_bind_param($stmt_verify, "siiiii", $current_user_role, $appointment_id, 
    $user_in_appt, $doctor_in_appt, 
    $user_in_appt, $doctor_in_appt); // Check both potential pairings for the appointment
mysqli_stmt_execute($stmt_verify);
$verify_result = mysqli_stmt_get_result($stmt_verify);
$chat_context = mysqli_fetch_assoc($verify_result);
mysqli_stmt_close($stmt_verify);

if (!$chat_context || $chat_context['appointment_status'] !== 'approved') {
    set_message("Chat is not available for this appointment. The appointment must be approved.", "danger");
    $redirect_page = ($current_user_role == 'user') ? "/user/my_appointments.php" : "/doctor/view_appointments.php";
    redirect($base_url . $redirect_page);
}
$other_person_name_display = htmlspecialchars($chat_context['other_person_name']);
$page_title_dynamic = "Chat with " . (($current_user_role == 'user' && $chat_with_role == 'doctor') ? " " : "") . $other_person_name_display;
?>
<style>
    .chat-container { max-width: 800px; margin: auto; }
    .chat-box {
        height: calc(100vh - 330px); /* Adjust based on your header/footer/form height */
        min-height: 300px;
        max-height: 500px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
        display: flex; 
        flex-direction: column; 
    }
    .message { margin-bottom: 15px; padding: 10px 15px; border-radius: 20px; max-width: 75%; word-wrap: break-word; clear: both; }
    .message.sent { background-color: #0d6efd; color: white; margin-left: auto; align-self: flex-end; border-bottom-right-radius: 5px; }
    .message.received { background-color: #e9ecef; color: #333; margin-right: auto; align-self: flex-start; border-bottom-left-radius: 5px;}
    .message .sender-name { font-weight: bold; font-size: 0.9em; margin-bottom: 3px; display: block; }
    .message .message-content { /* Added for better structure if needed */ }
    .message .timestamp { font-size: 0.75em; display: block; text-align: right; margin-top: 5px; }
    .message.sent .timestamp { color: #cce5ff; } 
    .message.received .timestamp { color: #6c757d; } 
    #messageForm textarea { resize: none; }
    .chat-input-group { display: flex; }
    .chat-input-group textarea { flex-grow: 1; border-top-right-radius: 0; border-bottom-right-radius: 0;}
    .chat-input-group button { border-top-left-radius: 0; border-bottom-left-radius: 0;}
</style>

<div class="chat-container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-chat-dots-fill"></i> <?php echo $page_title_dynamic; ?></h4>
            <small>Regarding Appointment ID: <?php echo $appointment_id; ?></small>
        </div>
        <div class="card-body">
            <div class="chat-box" id="chatBox">
                <p class="text-center text-muted mt-auto" id="chatStatusMessage">Loading messages...</p>
            </div>
            <form id="messageForm" class="mt-3">
                <input type="hidden" name="action" value="send_message">
                <input type="hidden" name="receiver_id" value="<?php echo $chat_with_id; ?>">
                <input type="hidden" name="receiver_role" value="<?php echo $chat_with_role; ?>">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                
                <div class="chat-input-group">
                    <textarea class="form-control" id="messageText" name="message_text" rows="2" placeholder="Type your message..." required></textarea>
                    <button class="btn btn-primary" type="submit" id="sendMessageBtn">
                        <i class="bi bi-send-fill"></i> <span class="d-none d-sm-inline">Send</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chatBox');
    const messageForm = document.getElementById('messageForm');
    const messageTextarea = document.getElementById('messageText');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const chatStatusMessageP = document.getElementById('chatStatusMessage');

    const currentUserId = <?php echo $current_user_id; ?>;
    const currentUserRole = '<?php echo $current_user_role; ?>';
    const otherUserId = <?php echo $chat_with_id; ?>;
    const otherUserRole = '<?php echo $chat_with_role; ?>';
    const appointmentId = <?php echo $appointment_id; ?>;
    let lastMessageId = 0; 
    let isFetching = false;
    const POLLING_INTERVAL = 3500; // Poll every 3.5 seconds
    let pollingIntervalId = null;
    let initialLoadComplete = false;

    function appendMessageToBox(msg) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');
        messageDiv.dataset.messageId = msg.id;
        const isSent = (parseInt(msg.sender_id) === currentUserId && msg.sender_role === currentUserRole);
        messageDiv.classList.add(isSent ? 'sent' : 'received');
        
        let senderDisplayName = msg.sender_name;
        if(isSent) {
            senderDisplayName = "You";
        } else {
            senderDisplayName = (msg.sender_role === 'doctor' ? ' ' : '') + msg.sender_name;
        }

        messageDiv.innerHTML = `
            <span class="sender-name">${senderDisplayName}</span>
            <div class="message-content">${msg.message_text}</div>
            <span class="timestamp">${msg.timestamp_formatted}</span>
        `;
        chatBox.appendChild(messageDiv);
        if (parseInt(msg.id) > lastMessageId) {
            lastMessageId = parseInt(msg.id);
        }
    }

    function scrollToBottom(force = false) {
        const threshold = 100; 
        const isNearBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < threshold;
        if (force || isNearBottom) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    async function fetchMessages(isInitialLoad = false) {
        if (isFetching && !isInitialLoad) return;
        isFetching = true;

        let fetchUrl = `<?php echo $base_url; ?>/chat_actions.php?action=fetch_messages&other_user_id=${otherUserId}&other_user_role=${otherUserRole}&appointment_id=${appointmentId}&last_message_id=${isInitialLoad ? 0 : lastMessageId}`;
        console.log(`[Chat] Fetching messages from URL: ${fetchUrl}`);

        if(isInitialLoad) {
            chatBox.innerHTML = ''; // Clear for full load
            chatStatusMessageP.textContent = 'Loading messages...';
            chatStatusMessageP.style.display = 'block';
        }

        try {
            const response = await fetch(fetchUrl);
            console.log(`[Chat] Fetch response status: ${response.status}`);
            const data = await response.json();
            console.log('[Chat] Fetch response data:', data);

            if (data.success && Array.isArray(data.messages)) {
                if (data.messages.length > 0) {
                    if (chatBox.contains(chatStatusMessageP)) chatStatusMessageP.style.display = 'none';
                    data.messages.forEach(msg => {
                        console.log(`[Chat] Appending message ID: ${msg.id} from sender: ${msg.sender_name}`);
                        appendMessageToBox(msg);
                    });
                    scrollToBottom(isInitialLoad || data.messages.some(m => parseInt(m.sender_id) === currentUserId && m.sender_role === currentUserRole)); // Scroll if it's an initial load or if one of the new messages is ours
                } else if (isInitialLoad && data.messages.length === 0) {
                    chatStatusMessageP.textContent = 'No messages yet. Start the conversation!';
                    chatStatusMessageP.style.display = 'block';
                }
            } else if (!data.success) {
                console.error("Error fetching messages:", data.message);
                if (isInitialLoad) chatStatusMessageP.textContent = `Error: ${data.message || 'Could not load messages.'}`;
            }
        } catch (error) {
            console.error("Network error fetching messages:", error);
            if (isInitialLoad) chatStatusMessageP.textContent = 'Network error. Could not load messages.';
        } finally {
            isFetching = false;
            if (isInitialLoad) initialLoadComplete = true;
        }
    }

    messageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const messageText = messageTextarea.value.trim();
        if (!messageText) return;

        sendMessageBtn.disabled = true;
        const originalBtnHtml = sendMessageBtn.innerHTML;
        sendMessageBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

        const formData = new FormData(messageForm);
        try {
            const response = await fetch('<?php echo $base_url; ?>/chat_actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                messageTextarea.value = '';
                messageTextarea.focus(); 
                if (data.sent_message) { 
                    if (chatBox.contains(chatStatusMessageP)) chatStatusMessageP.style.display = 'none';
                    appendMessageToBox(data.sent_message); 
                    scrollToBottom(true); 
                } else { // Fallback if sent_message isn't returned, trigger a poll
                    fetchMessages(false);
                }
            } else {
                alert('Error sending message: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error("Error sending message:", error);
            alert('Network error. Could not send message.');
        } finally {
            sendMessageBtn.disabled = false;
            sendMessageBtn.innerHTML = originalBtnHtml;
        }
    });
    
    messageTextarea.addEventListener('keypress', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            messageForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    });

    // Initial fetch of all messages for the chat
    fetchMessages(true); // Pass true for initial full load
    
    if (pollingIntervalId) clearInterval(pollingIntervalId);
    pollingIntervalId = setInterval(() => {
        if(initialLoadComplete && !document.hidden) { // Only poll if initial load is done and tab is visible
            fetchMessages(false);
        }
    }, POLLING_INTERVAL);

    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            if (pollingIntervalId) { /* console.log("Chat polling paused."); */ }
        } else {
            if(initialLoadComplete) {
                // console.log("Chat polling resumed.");
                fetchMessages(false); // Fetch immediately when tab becomes visible
            }
        }
    });
});
</script>

<?php
if ($current_user_role == 'user') {
    require_once __DIR__ . '/user/partials/user_footer.php';
} elseif ($current_user_role == 'doctor') {
    require_once __DIR__ . '/doctor/partials/doctor_footer.php';
}
// No mysqli_close here, handled by footer or chat_actions.php
?>