<?php
/**
 * BNCC Market - Real-time Chat System
 */
require_once '../includes/functions.php';
if (!isLoggedIn()) redirect('../auth/login.php');

$pageTitle = "กล่องข้อความ - BNCC Market";
require_once '../includes/header.php';

$db = getDB();
$my_id = $_SESSION['user_id'];
$target_id = $_GET['user'] ?? null;
$target_user = null;

// 1. ดึงรายชื่อคนที่เคยคุยด้วยล่าสุด
$contacts_stmt = $db->prepare("
    SELECT u.id, u.fullname, u.profile_img, u.role, MAX(m.created_at) as last_msg
    FROM users u
    JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.id)
    WHERE u.id != ?
    GROUP BY u.id 
    ORDER BY last_msg DESC
");
$contacts_stmt->execute([$my_id, $my_id, $my_id]);
$contacts = $contacts_stmt->fetchAll();

// 2. ถ้ามีการเลือกคนคุย ให้ดึงข้อมูลคนนั้นมา
if ($target_id) {
    $t_stmt = $db->prepare("SELECT id, fullname, profile_img, role FROM users WHERE id = ?");
    $t_stmt->execute([$target_id]);
    $target_user = $t_stmt->fetch();
}
?>

<style>
    .chat-container { display: flex; height: 75vh; background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: var(--shadow-lg); margin-top: 20px; }
    .chat-sidebar { width: 300px; border-right: 1px solid var(--border-color); background: var(--bg-body); overflow-y: auto; }
    .chat-main { flex: 1; display: flex; flex-direction: column; background: var(--bg-card); }
    .contact-item { padding: 15px; display: flex; gap: 15px; align-items: center; border-bottom: 1px solid var(--border-color); cursor: pointer; text-decoration: none; color: var(--text-main); transition: 0.2s; }
    .contact-item:hover, .contact-item.active { background: rgba(99, 102, 241, 0.1); }
    .contact-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
    
    .chat-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; background: var(--bg-body); }
    .chat-body { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: #f8fafc; }
    .dark-theme .chat-body { background: #0f172a; }
    
    .msg-bubble { max-width: 60%; padding: 12px 18px; border-radius: 20px; font-size: 0.95rem; line-height: 1.5; position: relative; }
    .msg-mine { background: var(--primary); color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
    .msg-other { background: #e2e8f0; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 4px; }
    .dark-theme .msg-other { background: #334155; color: #f8fafc; }
    .msg-time { font-size: 0.7rem; opacity: 0.7; margin-top: 5px; text-align: right; }
    
    .chat-footer { padding: 20px; border-top: 1px solid var(--border-color); background: var(--bg-body); display: flex; gap: 10px; }
    .chat-input { flex: 1; padding: 15px 20px; border-radius: 50px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); outline: none; }
    .chat-send-btn { background: var(--primary); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center; border: none; cursor: pointer; transition: 0.2s; }
    .chat-send-btn:hover { transform: scale(1.1); }
</style>

<div class="chat-container">
    <div class="chat-sidebar">
        <div style="padding: 20px; font-weight: 800; font-size: 1.2rem; border-bottom: 1px solid var(--border-color);">
            <i class="fas fa-inbox text-primary"></i> ข้อความของคุณ
        </div>
        <?php foreach($contacts as $c): 
            $c_img = !empty($c['profile_img']) ? "../assets/images/profiles/".$c['profile_img'] : "../assets/images/profiles/default_profile.png";
        ?>
            <a href="chat.php?user=<?= $c['id'] ?>" class="contact-item <?= ($target_id == $c['id']) ? 'active' : '' ?>">
                <img src="<?= $c_img ?>" class="contact-avatar">
                <div>
                    <div style="font-weight: 600;"><?= e($c['fullname']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;"><?= e($c['role']) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="chat-main">
        <?php if ($target_user): 
            $t_img = !empty($target_user['profile_img']) ? "../assets/images/profiles/".$target_user['profile_img'] : "../assets/images/profiles/default_profile.png";
        ?>
            <div class="chat-header">
                <a href="view_profile.php?id=<?= $target_user['id'] ?>" style="display: flex; align-items: center; gap: 15px; text-decoration: none; color: inherit; transition: 0.2s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                    <img src="<?= $t_img ?>" class="contact-avatar" style="border: 2px solid transparent; transition: 0.2s;">
                    <div>
                        <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);"><?= e($target_user['fullname']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--primary);"><i class="fas fa-circle" style="font-size:0.5rem;"></i> กำลังสนทนา (คลิกเพื่อดูโปรไฟล์)</div>
                    </div>
                </a>
            </div>
            
            <div class="chat-body" id="chat-box">
                </div>
            
            <form class="chat-footer" id="chat-form" onsubmit="sendMessage(event)">
                <input type="text" id="msg-input" class="chat-input" placeholder="พิมพ์ข้อความที่นี่..." autocomplete="off" required>
                <button type="submit" class="chat-send-btn"><i class="fas fa-paper-plane"></i></button>
            </form>

            <script>
                const targetUserId = <?= $target_user['id'] ?>;
                let lastMsgId = 0;
                const chatBox = document.getElementById('chat-box');

                // ฟังก์ชันดึงข้อความใหม่ (AJAX Polling)
                function fetchMessages() {
                    fetch(`../ajax/chat_api.php?action=fetch&other_user_id=${targetUserId}&last_id=${lastMsgId}`)
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success' && data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                const isMine = msg.is_mine;
                                const bubbleClass = isMine ? 'msg-mine' : 'msg-other';
                                
                                const msgDiv = document.createElement('div');
                                msgDiv.className = `msg-bubble ${bubbleClass}`;
                                msgDiv.innerHTML = `
                                    ${msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;")}
                                    <div class="msg-time">${msg.time}</div>
                                `;
                                chatBox.appendChild(msgDiv);
                                lastMsgId = msg.id; // อัปเดต ID ล่าสุด
                            });
                            // เลื่อนลงล่างสุด
                            chatBox.scrollTop = chatBox.scrollHeight;
                        }
                    });
                }

                // ฟังก์ชันส่งข้อความ
                function sendMessage(e) {
                    e.preventDefault();
                    const input = document.getElementById('msg-input');
                    const msg = input.value.trim();
                    if(!msg) return;

                    const formData = new FormData();
                    formData.append('action', 'send');
                    formData.append('receiver_id', targetUserId);
                    formData.append('message', msg);

                    fetch('../ajax/chat_api.php', {
                        method: 'POST',
                        body: formData
                    }).then(res => res.json()).then(data => {
                        if(data.status === 'success') {
                            input.value = ''; // ล้างช่องพิมพ์
                            fetchMessages(); // ดึงแชทมาโชว์ทันที
                        }
                    });
                }

                // เรียกดึงข้อความครั้งแรก
                fetchMessages();
                // ตั้งเวลาดึงข้อความใหม่ทุกๆ 2 วินาที (2000 ms)
                setInterval(fetchMessages, 2000);
            </script>

        <?php else: ?>
            <div style="flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; color:var(--text-muted);">
                <i class="far fa-comments" style="font-size: 5rem; margin-bottom: 20px; opacity: 0.2;"></i>
                <h2>เลือกรายชื่อผู้ติดต่อเพื่อเริ่มแชท</h2>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>