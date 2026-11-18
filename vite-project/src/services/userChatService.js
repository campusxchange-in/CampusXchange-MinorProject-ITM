// Chat / message calls. URLs come from userApiConfig.js (via userApiClient).
import { userGet, userPost, userPostForm } from "./userApiClient";

export const startUserChat = (productId, message) =>
    userPost("chat_start.php", { product_id: productId, message });

export const fetchUserChats = () => userGet("chats_list.php");
// statusFrom: lowest id of my messages not yet "read" (0 = none) so their ticks update.
// markRead:   true only while the chat is open AND visible on screen.
export const fetchUserChatMessages = (chatId, afterId, statusFrom = 0, markRead = true) =>
    userGet("chat_messages.php", {
        chat_id: chatId,
        after_id: afterId,
        status_from: statusFrom,
        mark_read: markRead ? "1" : "0",
    });
// Report the other person of a chat (multipart: chat_id, report_category, reason, description, evidence[]).
export const reportUserFromChat = (formData) => userPostForm("report_user_create.php", formData);

// Heartbeat: tells the server "this user has the app open right now".
export const pingUserPresence = () => userPost("presence_ping.php", {});
export const sendUserChatMessage = (chatId, message) =>
    userPost("message_send.php", { chat_id: chatId, message });
