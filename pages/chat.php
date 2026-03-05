<?php
/**
 * BNCC Market - Real-time Chat System
 * [SOLID HIGH-CONTRAST EDITION]
 * Project: BNCC Student Marketplace [Cite: User Summary]
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
    /* ============================================================
       🛠️ SOLID DESIGN SYSTEM - CHAT INTERFACE
       ============================================================ */
    :root {
        --chat-bg: #f1f5f9;
        --chat-card: #ffffff;
        --chat-border: #cbd5e1;
        --chat-text: #0f172a;
        --chat-primary: #4f46e5;
        --chat-bubble-mine: #4f46e5;
        --chat-bubble-other: #e2e8f0;
    }

    .dark-theme {
        --chat-bg: #0b0e14;
        --chat-card: #161b26;
        --chat-border: #2d3748;
        --chat-text: #ffffff;
        --chat-primary: #6366f1;
        --chat-bubble-mine: #6366f1;
        --chat-bubble-other: #334155;
    }

    body {
        background-color: var(--chat-bg) !important;
        color: var(--chat-text);
        transition: background 0.3s ease;
    }

    .chat-master-wrapper {
        padding: 30px 0 60px;
    }

    .chat-container { 
        display: flex; 
        height: 75vh; 
        background: var(--chat-card); 
        border-radius: 24px; 
        border: 2px solid var(--chat-border); 
        overflow: hidden; 
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05); 
    }

    /* 📋 Sidebar Contacts */
    .chat-sidebar { 
        width: 320px; 
        border-right: 2px solid var(--chat-border); 
        background: var(--chat-bg); 
        overflow-y: auto; 
        display: flex;
        flex-direction: column;
    }
    
    .sidebar-header {
        padding: 25px 20px;
        font-weight: 900;
        font-size: 1.2rem;
        border-bottom: 2px solid var(--chat-border);
        background: var(--chat-card);
        color: var(--chat-text);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .contact-item { 
        padding: 15px 20px; 
        display: flex; 
        gap: 15px; 
        align-items: center; 
        border-bottom: 1px solid var(--chat-border); 
        cursor: pointer; 
        text-decoration: none; 
        color: var(--chat-text); 
        transition: 0.2s; 
    }
    .contact-item:hover { background: rgba(99, 102, 241, 0.05); }
    .contact-item.active { 
        background: var(--chat-card); 
        border-left: 4px solid var(--chat-primary);
    }
    
    .contact-avatar { 
        width: 50px; height: 50px; 
        border-radius: 50%; 
        object-fit: cover;
        border: 2px solid var(--chat-border);
    }

    /* 💬 Chat Main Area */
    .chat-main { 
        flex: 1; 
        display: flex; 
        flex-direction: column; 
        background: var(--chat-card); 
    }

    .chat-header { 
        padding: 20px 30px; 
        border-bottom: 2px solid var(--chat-border); 
        display: flex; 
        align-items: center; 
        gap: 20px; 
        background: var(--chat-bg); 
    }
    .chat-header a:hover { transform: translateX(5px); }

    .chat-body { 
        flex: 1; 
        padding: 30px; 
        overflow-y: auto; 
        display: flex; 
        flex-direction: column; 
        gap: 20px; 
        background: var(--chat-bg); 
    }

    /* 🎈 Chat Bubbles */
    .msg-bubble { 
        max-width: 65%; 
        padding: 15px 20px; 
        border-radius: 20px; 
        font-size: 1rem; 
        line-height: 1.5; 
        position: relative; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    
    .msg-mine { 
        background: var(--chat-bubble-mine); 
        color: white; 
        align-self: flex-end; 
        border-bottom-right-radius: 4px; 
    }
    
    .msg-other { 
        background: var(--chat-bubble-other); 
        color: var(--chat-text); 
        align-self: flex-start; 
        border-bottom-left-radius: 4px; 
    }
    
    .msg-time { 
        font-size: 0.7rem; 
        opacity: 0.7; 
        margin-top: 8px; 
        text-align: right; 
        font-weight: 700;
    }

    /* ⌨️ Chat Footer (Input) */
    .chat-footer { 
        padding: 20px 30px; 
        border-top: 2px solid var(--chat-border); 
        background: var(--chat-card); 
        display: flex; 
        gap: 15px; 
        align-items: center;
    }
    
    .chat-input { 
        flex: 1; 
        padding: 18px 25px; 
        border-radius: 30px; 
        border: 2px solid var(--chat-border); 
        background: var(--chat-bg); 
        color: var(--chat-text); 
        outline: none; 
        font-size: 1.05rem;
        font-weight: 600;
        transition: 0.3s;
    }
    .chat-input:focus { border-color: var(--chat-primary); }
    
    .chat-send-btn { 
        background: var(--chat-primary); 
        color: white; 
        width: 60px; height: 60px; 
        border-radius: 50%; 
        display: flex; justify-content: center; align-items: center; 
        border: none; cursor: pointer; 
        transition: 0.2s; 
        font-size: 1.2rem;
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
    }
    .chat-send-btn:hover { transform: scale(1.1) rotate(10deg); }

    /* Custom Scrollbar สำหรับห้องแชท */
    .chat-body::-webkit-scrollbar, .chat-sidebar::-webkit-scrollbar { width: 6px; }
    .chat-body::-webkit-scrollbar-track, .chat-sidebar::-webkit-scrollbar-track { background: transparent; }
    .chat-body::-webkit-scrollbar-thumb, .chat-sidebar::-webkit-scrollbar-thumb { background: var(--chat-border); border-radius: 10px; }
</style>

<div class="chat-master-wrapper">
    <div class="container">
        <div class="chat-container">
            
            <div class="chat-sidebar">
                <div class="sidebar-header">
                    <i class="fas fa-inbox text-primary"></i> ข้อความของคุณ
                </div>
                
                <?php if(count($contacts) > 0): ?>
                    <?php foreach($contacts as $c): 
                        $c_img = !empty($c['profile_img']) ? "../assets/images/profiles/".$c['profile_img'] : "../assets/images/profiles/default_profile.png";
                    ?>
                        <a href="chat.php?user=<?= $c['id'] ?>" class="contact-item <?= ($target_id == $c['id']) ? 'active' : '' ?>">
                            <img src="<?= $c_img ?>" class="contact-avatar">
                            <div>
                                <div style="font-weight: 800; font-size: 1.05rem; margin-bottom: 3px;"><?= e($c['fullname']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--chat-primary); font-weight: 700; text-transform: uppercase;"><i class="fas fa-user-tag"></i> <?= e($c['role']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 30px 20px; text-align: center; color: var(--text-muted); font-weight: 600; font-size: 0.9rem;">
                        ยังไม่มีประวัติการพูดคุย
                    </div>
                <?php endif; ?>
            </div>

            <div class="chat-main">
                <?php if ($target_user): 
                    $t_img = !empty($target_user['profile_img']) ? "../assets/images/profiles/".$target_user['profile_img'] : "../assets/images/profiles/default_profile.png";
                ?>
                    <div class="chat-header">
                        <a href="view_profile.php?id=<?= $target_user['id'] ?>" style="display: flex; align-items: center; gap: 15px; text-decoration: none; color: inherit; transition: 0.3s;">
                            <img src="<?= $t_img ?>" class="contact-avatar" style="border: 2px solid var(--chat-primary);">
                            <div>
                                <div style="font-weight: 900; font-size: 1.2rem; color: var(--chat-text);"><?= e($target_user['fullname']) ?></div>
                                <div style="font-size: 0.8rem; color: #10b981; font-weight: 700; margin-top: 3px;">
                                    <i class="fas fa-circle" style="font-size:0.5rem; vertical-align: middle;"></i> กำลังสนทนา (คลิกเพื่อดูโปรไฟล์)
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="chat-body" id="chat-box">
                        </div>
                    
                    <form class="chat-footer" id="chat-form" onsubmit="sendMessage(event)">
                        <input type="text" id="msg-input" class="chat-input" placeholder="พิมพ์ข้อความถึง <?= e($target_user['fullname']) ?>..." autocomplete="off" required autofocus>
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
                                    // เลื่อนลงล่างสุดอัตโนมัติเมื่อมีข้อความใหม่
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
                                    input.focus(); // ให้เคอร์เซอร์กลับมาที่ช่องพิมพ์
                                }
                            });
                        }

                        // เรียกดึงข้อความครั้งแรก
                        fetchMessages();
                        // ตั้งเวลาดึงข้อความใหม่ทุกๆ 2 วินาที (2000 ms)
                        setInterval(fetchMessages, 2000);
                    </script>

                <?php else: ?>
                    <div style="flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; color:var(--chat-border); background: var(--chat-bg);">
                        <i class="far fa-comments" style="font-size: 6rem; margin-bottom: 25px;"></i>
                        <h2 style="color: var(--chat-text); font-weight: 900;">เลือกแชทเพื่อเริ่มต้นสนทนา</h2>
                        <p style="color: var(--text-muted); font-weight: 600;">เลือกลูกค้าหรือผู้ขายจากรายการด้านซ้ายมือได้เลยครับ</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>