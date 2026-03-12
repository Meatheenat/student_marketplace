<?php
require_once '../includes/functions.php';
if (!isLoggedIn()) redirect('../auth/login.php');

$pageTitle = "กล่องข้อความ - BNCC Market";
require_once '../includes/header.php';

$db = getDB();
$my_id = $_SESSION['user_id'];
$target_id = $_GET['user'] ?? null;
$target_user = null;

$contacts_stmt = $db->prepare("
    SELECT u.id, u.fullname, u.profile_img, u.role, MAX(m.created_at) as last_msg,
           SUM(CASE WHEN m.receiver_id = ? AND m.sender_id = u.id AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
    FROM users u
    JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.id)
    WHERE u.id != ?
    GROUP BY u.id
    ORDER BY last_msg DESC
");
$contacts_stmt->execute([$my_id, $my_id, $my_id, $my_id]);
$contacts = $contacts_stmt->fetchAll();

if ($target_id) {
    $t_stmt = $db->prepare("SELECT id, fullname, profile_img, role FROM users WHERE id = ?");
    $t_stmt->execute([$target_id]);
    $target_user = $t_stmt->fetch();

    $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
       ->execute([$target_id, $my_id]);
}
?>
<style>
    :root {
        --cht-bg: #f0f2f8;
        --cht-surface: #ffffff;
        --cht-border: #e2e8f0;
        --cht-text: #0f172a;
        --cht-muted: #64748b;
        --cht-primary: #4f46e5;
        --cht-primary-light: rgba(79,70,229,0.08);
        --cht-green: #10b981;
        --cht-mine-bg: #4f46e5;
        --cht-mine-text: #ffffff;
        --cht-other-bg: #e8ecf4;
        --cht-other-text: #0f172a;
        --cht-radius: 20px;
        --cht-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    html[data-theme="dark"],
    html.dark-theme {
        --cht-bg: #080b12;
        --cht-surface: #111827;
        --cht-border: #1e293b;
        --cht-text: #f1f5f9;
        --cht-muted: #64748b;
        --cht-primary: #6366f1;
        --cht-primary-light: rgba(99,102,241,0.12);
        --cht-mine-bg: #6366f1;
        --cht-other-bg: #1e293b;
        --cht-other-text: #e2e8f0;
    }

    .cht-page {
        padding: 24px 0 80px;
    }

    .cht-shell {
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .cht-layout {
        display: grid;
        grid-template-columns: 320px 1fr;
        height: calc(100vh - 160px);
        min-height: 600px;
        background: var(--cht-surface);
        border: 1.5px solid var(--cht-border);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(15,23,42,0.1);
    }

    @media (max-width: 768px) {
        .cht-layout {
            grid-template-columns: 1fr;
            height: auto;
            min-height: unset;
        }

        .cht-sidebar { display: none; }
        .cht-sidebar.mobile-show { display: flex; height: 100vh; position: fixed; inset: 0; z-index: 9999; border-radius: 0; }
    }

    /* ===== SIDEBAR ===== */
    .cht-sidebar {
        display: flex;
        flex-direction: column;
        border-right: 1.5px solid var(--cht-border);
        background: var(--cht-bg);
        overflow: hidden;
    }

    .cht-sidebar-header {
        padding: 24px 20px 18px;
        border-bottom: 1.5px solid var(--cht-border);
        background: var(--cht-surface);
        flex-shrink: 0;
    }

    .cht-sidebar-title {
        font-size: 1.1rem;
        font-weight: 900;
        letter-spacing: -0.5px;
        color: var(--cht-text);
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }

    .cht-sidebar-title-icon {
        width: 34px;
        height: 34px;
        background: var(--cht-primary);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.85rem;
    }

    .cht-search {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--cht-bg);
        border: 1.5px solid var(--cht-border);
        border-radius: 50px;
        padding: 9px 16px;
        transition: border-color 0.2s;
    }

    .cht-search:focus-within {
        border-color: var(--cht-primary);
    }

    .cht-search i { color: var(--cht-muted); font-size: 0.85rem; flex-shrink: 0; }

    .cht-search-input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--cht-text);
        width: 100%;
    }

    .cht-search-input::placeholder { color: var(--cht-muted); }

    .cht-contacts-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px 0;
    }

    .cht-contacts-list::-webkit-scrollbar { width: 4px; }
    .cht-contacts-list::-webkit-scrollbar-thumb { background: var(--cht-border); border-radius: 10px; }

    .cht-contact {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 13px 18px;
        text-decoration: none;
        color: var(--cht-text);
        transition: all 0.2s;
        position: relative;
        border-left: 3px solid transparent;
    }

    .cht-contact:hover {
        background: var(--cht-primary-light);
        border-left-color: rgba(79,70,229,0.3);
    }

    .cht-contact.active {
        background: var(--cht-surface);
        border-left-color: var(--cht-primary);
    }

    .cht-contact-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .cht-contact-avatar {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        object-fit: cover;
        border: 2px solid var(--cht-border);
        display: block;
        transition: border-color 0.2s;
    }

    .cht-contact.active .cht-contact-avatar { border-color: var(--cht-primary); }

    .cht-contact-info { flex: 1; min-width: 0; }

    .cht-contact-name {
        font-weight: 800;
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 3px;
    }

    .cht-contact-role {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--cht-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cht-unread-badge {
        background: var(--cht-primary);
        color: #fff;
        font-size: 0.68rem;
        font-weight: 900;
        min-width: 20px;
        height: 20px;
        border-radius: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        flex-shrink: 0;
        animation: badgePop 0.4s var(--cht-ease);
    }

    @keyframes badgePop {
        0% { transform: scale(0); }
        70% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    .cht-empty-contacts {
        padding: 40px 20px;
        text-align: center;
        color: var(--cht-muted);
    }

    .cht-empty-contacts i { font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 12px; }
    .cht-empty-contacts p { font-size: 0.85rem; font-weight: 600; }

    /* ===== MAIN AREA ===== */
    .cht-main {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: var(--cht-surface);
    }

    /* --- Header --- */
    .cht-main-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 28px;
        border-bottom: 1.5px solid var(--cht-border);
        background: var(--cht-surface);
        flex-shrink: 0;
        gap: 16px;
    }

    .cht-header-user {
        display: flex;
        align-items: center;
        gap: 14px;
        text-decoration: none;
        color: inherit;
        flex: 1;
        min-width: 0;
        transition: opacity 0.2s;
    }

    .cht-header-user:hover { opacity: 0.8; }

    .cht-header-avatar-wrap { position: relative; flex-shrink: 0; }

    .cht-header-avatar {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        object-fit: cover;
        border: 2px solid var(--cht-primary);
        display: block;
    }

    .cht-online-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 13px;
        height: 13px;
        background: var(--cht-green);
        border-radius: 50%;
        border: 2.5px solid var(--cht-surface);
    }

    .cht-online-dot::before {
        content: '';
        position: absolute;
        inset: -3px;
        background: rgba(16,185,129,0.35);
        border-radius: 50%;
        animation: onlinePing 2s infinite;
    }

    @keyframes onlinePing {
        0% { transform: scale(1); opacity: 0.8; }
        70%, 100% { transform: scale(2); opacity: 0; }
    }

    .cht-header-name {
        font-size: 1.05rem;
        font-weight: 900;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cht-header-status {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--cht-green);
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 3px;
    }

    .cht-header-actions { display: flex; gap: 8px; flex-shrink: 0; }

    .cht-hdr-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1.5px solid var(--cht-border);
        background: var(--cht-bg);
        color: var(--cht-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.2s;
    }

    .cht-hdr-btn:hover { border-color: var(--cht-primary); color: var(--cht-primary); background: var(--cht-primary-light); }

    /* --- Messages Body --- */
    .cht-body {
        flex: 1;
        overflow-y: auto;
        padding: 28px 32px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        background: var(--cht-bg);
    }

    .cht-body::-webkit-scrollbar { width: 5px; }
    .cht-body::-webkit-scrollbar-thumb { background: var(--cht-border); border-radius: 10px; }

    .cht-date-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 14px 0;
        opacity: 0.45;
    }

    .cht-date-divider::before,
    .cht-date-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--cht-border);
    }

    .cht-date-divider span {
        font-size: 0.72rem;
        font-weight: 800;
        color: var(--cht-muted);
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cht-msg-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        animation: msgIn 0.35s var(--cht-ease) both;
    }

    @keyframes msgIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .cht-msg-row.mine { flex-direction: row-reverse; }

    .cht-msg-avatar {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        border: 1.5px solid var(--cht-border);
    }

    .cht-msg-avatar.ghost { opacity: 0; pointer-events: none; }

    .cht-bubble-wrap { display: flex; flex-direction: column; max-width: 62%; gap: 3px; }
    .cht-msg-row.mine .cht-bubble-wrap { align-items: flex-end; }

    .cht-bubble {
        padding: 12px 18px;
        border-radius: 18px;
        font-size: 0.97rem;
        line-height: 1.6;
        word-break: break-word;
        position: relative;
    }

    .cht-bubble.mine {
        background: var(--cht-mine-bg);
        color: var(--cht-mine-text);
        border-bottom-right-radius: 5px;
        box-shadow: 0 4px 14px rgba(79,70,229,0.2);
    }

    .cht-bubble.other {
        background: var(--cht-other-bg);
        color: var(--cht-other-text);
        border-bottom-left-radius: 5px;
    }

    .cht-bubble-time {
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--cht-muted);
        opacity: 0.7;
        padding: 0 4px;
    }

    .cht-msg-row.mine .cht-bubble-time { text-align: right; }

    /* --- Typing indicator --- */
    .cht-typing-indicator {
        display: none;
        align-items: flex-end;
        gap: 8px;
        padding: 4px 0;
        animation: msgIn 0.3s var(--cht-ease) both;
    }

    .cht-typing-indicator.show { display: flex; }

    .cht-typing-bubble {
        background: var(--cht-other-bg);
        border-radius: 18px;
        border-bottom-left-radius: 5px;
        padding: 14px 18px;
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .cht-typing-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--cht-muted);
        animation: typingBounce 1.2s infinite;
    }

    .cht-typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .cht-typing-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typingBounce {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
        30% { transform: translateY(-6px); opacity: 1; }
    }

    /* --- Footer Input --- */
    .cht-footer {
        padding: 16px 24px;
        border-top: 1.5px solid var(--cht-border);
        background: var(--cht-surface);
        flex-shrink: 0;
    }

    .cht-input-row {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        background: var(--cht-bg);
        border: 2px solid var(--cht-border);
        border-radius: 20px;
        padding: 10px 10px 10px 20px;
        transition: border-color 0.25s;
    }

    .cht-input-row:focus-within { border-color: var(--cht-primary); }

    .cht-textarea {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.97rem;
        font-weight: 600;
        color: var(--cht-text);
        resize: none;
        min-height: 24px;
        max-height: 120px;
        line-height: 1.5;
        font-family: inherit;
        overflow-y: auto;
        padding: 4px 0;
    }

    .cht-textarea::placeholder { color: var(--cht-muted); }

    .cht-send-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: var(--cht-primary);
        color: #fff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        transition: all 0.3s var(--cht-ease);
        box-shadow: 0 6px 16px rgba(79,70,229,0.3);
        align-self: flex-end;
    }

    .cht-send-btn:hover { transform: scale(1.08) rotate(5deg); box-shadow: 0 10px 24px rgba(79,70,229,0.45); }
    .cht-send-btn:active { transform: scale(0.95); }
    .cht-send-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

    .cht-footer-hint {
        text-align: center;
        font-size: 0.7rem;
        color: var(--cht-muted);
        margin-top: 8px;
        font-weight: 600;
        opacity: 0.6;
    }

    /* --- Empty state (no chat selected) --- */
    .cht-empty-state {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        background: var(--cht-bg);
        padding: 40px;
        text-align: center;
    }

    .cht-empty-icon {
        width: 96px;
        height: 96px;
        background: var(--cht-primary-light);
        border-radius: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: var(--cht-primary);
        animation: floatEmpty 3.5s ease-in-out infinite;
    }

    @keyframes floatEmpty {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .cht-empty-title {
        font-size: 1.4rem;
        font-weight: 900;
        letter-spacing: -0.5px;
        color: var(--cht-text);
    }

    .cht-empty-sub {
        font-size: 0.9rem;
        color: var(--cht-muted);
        font-weight: 600;
        max-width: 280px;
    }

    /* Loading skeleton */
    .cht-skeleton {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 28px 32px;
    }

    .cht-skel-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
    }

    .cht-skel-row.right { flex-direction: row-reverse; }

    .cht-skel-circle {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: linear-gradient(90deg, var(--cht-border) 25%, var(--cht-bg) 50%, var(--cht-border) 75%);
        background-size: 400% 100%;
        animation: skelShim 1.5s infinite;
        flex-shrink: 0;
    }

    .cht-skel-bubble {
        height: 44px;
        border-radius: 16px;
        background: linear-gradient(90deg, var(--cht-border) 25%, var(--cht-bg) 50%, var(--cht-border) 75%);
        background-size: 400% 100%;
        animation: skelShim 1.5s infinite;
    }

    @keyframes skelShim {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* ===== IMAGE UPLOAD ===== */
    .cht-img-preview-bar {
        display: none;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        background: var(--cht-primary-light);
        border-top: 1.5px solid var(--cht-border);
        animation: slideUpBar 0.3s var(--cht-ease);
    }

    @keyframes slideUpBar {
        from { transform: translateY(10px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }

    .cht-img-preview-bar.show { display: flex; }

    .cht-img-thumb-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .cht-img-thumb {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid var(--cht-border);
        display: block;
    }

    .cht-img-remove {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 20px;
        height: 20px;
        background: #ef4444;
        color: #fff;
        border-radius: 50%;
        border: 2px solid var(--cht-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.55rem;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .cht-img-remove:hover { transform: scale(1.2); }

    .cht-img-preview-info {
        flex: 1;
        min-width: 0;
    }

    .cht-img-preview-name {
        font-size: 0.82rem;
        font-weight: 800;
        color: var(--cht-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cht-img-preview-size {
        font-size: 0.72rem;
        color: var(--cht-muted);
        font-weight: 600;
        margin-top: 2px;
    }

    .cht-attach-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1.5px solid var(--cht-border);
        background: var(--cht-bg);
        color: var(--cht-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .cht-attach-btn:hover {
        border-color: var(--cht-primary);
        color: var(--cht-primary);
        background: var(--cht-primary-light);
    }

    /* Image bubble */
    .cht-img-bubble {
        max-width: 260px;
        border-radius: 16px;
        overflow: hidden;
        cursor: zoom-in;
        border: 2px solid var(--cht-border);
        transition: transform 0.25s var(--cht-ease), box-shadow 0.25s;
        display: block;
    }

    .cht-img-bubble:hover {
        transform: scale(1.03);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .cht-img-bubble img {
        width: 100%;
        height: auto;
        display: block;
        max-height: 280px;
        object-fit: cover;
    }

    /* Sending overlay on image bubble */
    .cht-bubble-sending {
        opacity: 0.55;
        pointer-events: none;
    }

    .cht-bubble-sending::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.3);
        border-radius: inherit;
    }

    /* Lightbox */
    .cht-lightbox {
        position: fixed;
        inset: 0;
        z-index: 999999;
        background: rgba(0,0,0,0.92);
        display: none;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(6px);
        cursor: zoom-out;
        animation: lightboxFade 0.3s ease;
    }

    @keyframes lightboxFade {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    .cht-lightbox.open { display: flex; }

    .cht-lightbox-img {
        max-width: 92vw;
        max-height: 92vh;
        object-fit: contain;
        border-radius: 16px;
        animation: lightboxZoom 0.35s var(--cht-ease);
        user-select: none;
    }

    @keyframes lightboxZoom {
        from { transform: scale(0.85); opacity: 0; }
        to   { transform: scale(1);    opacity: 1; }
    }

    .cht-lightbox-close {
        position: fixed;
        top: 20px;
        right: 24px;
        width: 44px;
        height: 44px;
        background: rgba(255,255,255,0.12);
        border: 1.5px solid rgba(255,255,255,0.2);
        border-radius: 14px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.1rem;
        transition: background 0.2s;
    }

    .cht-lightbox-close:hover { background: rgba(255,255,255,0.25); }

    .cht-lightbox-hint {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        color: rgba(255,255,255,0.7);
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cht-upload-progress {
        height: 3px;
        background: var(--cht-border);
        border-radius: 0 0 0 0;
        overflow: hidden;
        display: none;
    }

    .cht-upload-progress.show { display: block; }

    .cht-upload-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--cht-primary), #7c3aed);
        border-radius: 0;
        transition: width 0.3s ease;
        width: 0%;
    }
</style>

<div class="cht-page">
    <div class="cht-shell">
        <div class="cht-layout">

            <!-- SIDEBAR -->
            <aside class="cht-sidebar" id="chtSidebar">
                <div class="cht-sidebar-header">
                    <div class="cht-sidebar-title">
                        <div class="cht-sidebar-title-icon"><i class="fas fa-inbox"></i></div>
                        ข้อความของคุณ
                    </div>
                    <div class="cht-search">
                        <i class="fas fa-search"></i>
                        <input type="text" class="cht-search-input" placeholder="ค้นหาการสนทนา..." id="contactSearch">
                    </div>
                </div>

                <div class="cht-contacts-list" id="contactsList">
                    <?php if (count($contacts) > 0): ?>
                        <?php foreach ($contacts as $c):
                            $c_img = !empty($c['profile_img'])
                                ? "../assets/images/profiles/" . $c['profile_img']
                                : "../assets/images/profiles/default_profile.png";
                            $role_map = ['admin' => '👑 Admin', 'teacher' => '📚 Teacher', 'seller' => '🏪 Seller', 'buyer' => '🛍 Buyer'];
                            $role_label = $role_map[$c['role']] ?? $c['role'];
                        ?>
                        <a href="chat.php?user=<?= $c['id'] ?>"
                           class="cht-contact <?= ($target_id == $c['id']) ? 'active' : '' ?>"
                           data-name="<?= strtolower(e($c['fullname'])) ?>">
                            <div class="cht-contact-avatar-wrap">
                                <img src="<?= $c_img ?>" class="cht-contact-avatar" alt="<?= e($c['fullname']) ?>">
                            </div>
                            <div class="cht-contact-info">
                                <div class="cht-contact-name"><?= e($c['fullname']) ?></div>
                                <div class="cht-contact-role"><?= $role_label ?></div>
                            </div>
                            <?php if ($c['unread_count'] > 0): ?>
                            <div class="cht-unread-badge"><?= $c['unread_count'] > 99 ? '99+' : $c['unread_count'] ?></div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="cht-empty-contacts">
                            <i class="far fa-comment-dots"></i>
                            <p>ยังไม่มีประวัติการพูดคุย</p>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- MAIN CHAT -->
            <div class="cht-main">
                <?php if ($target_user):
                    $t_img = !empty($target_user['profile_img'])
                        ? "../assets/images/profiles/" . $target_user['profile_img']
                        : "../assets/images/profiles/default_profile.png";
                    $role_map2 = ['admin' => '👑 Admin', 'teacher' => '📚 Teacher', 'seller' => '🏪 Seller', 'buyer' => '🛍 Buyer'];
                    $role_label2 = $role_map2[$target_user['role']] ?? $target_user['role'];
                ?>

                    <div class="cht-main-header">
                        <a href="view_profile.php?id=<?= $target_user['id'] ?>" class="cht-header-user">
                            <div class="cht-header-avatar-wrap">
                                <img src="<?= $t_img ?>" class="cht-header-avatar" alt="<?= e($target_user['fullname']) ?>">
                                <div class="cht-online-dot"></div>
                            </div>
                            <div>
                                <div class="cht-header-name"><?= e($target_user['fullname']) ?></div>
                                <div class="cht-header-status">
                                    <i class="fas fa-circle" style="font-size:0.5rem;"></i>
                                    กำลังใช้งาน · <?= $role_label2 ?>
                                </div>
                            </div>
                        </a>
                        <div class="cht-header-actions">
                            <a href="view_profile.php?id=<?= $target_user['id'] ?>" class="cht-hdr-btn" title="ดูโปรไฟล์">
                                <i class="fas fa-user"></i>
                            </a>
                        </div>
                    </div>

                    <div class="cht-body" id="chatBody">
                        <div class="cht-skeleton" id="chatSkeleton">
                            <div class="cht-skel-row"><div class="cht-skel-circle"></div><div class="cht-skel-bubble" style="width:42%;"></div></div>
                            <div class="cht-skel-row right"><div class="cht-skel-circle"></div><div class="cht-skel-bubble" style="width:55%;"></div></div>
                            <div class="cht-skel-row"><div class="cht-skel-circle"></div><div class="cht-skel-bubble" style="width:38%;"></div></div>
                            <div class="cht-skel-row right"><div class="cht-skel-circle"></div><div class="cht-skel-bubble" style="width:30%;"></div></div>
                        </div>
                    </div>

                    <div class="cht-typing-indicator" id="typingIndicator">
                        <img src="<?= $t_img ?>" class="cht-msg-avatar" alt="">
                        <div class="cht-typing-bubble">
                            <div class="cht-typing-dot"></div>
                            <div class="cht-typing-dot"></div>
                            <div class="cht-typing-dot"></div>
                        </div>
                    </div>

                    <div class="cht-footer">
                        <div class="cht-upload-progress" id="uploadProgress">
                            <div class="cht-upload-progress-bar" id="uploadProgressBar"></div>
                        </div>
                        <div class="cht-img-preview-bar" id="imgPreviewBar">
                            <div class="cht-img-thumb-wrap">
                                <img src="" id="imgThumb" class="cht-img-thumb" alt="preview">
                                <div class="cht-img-remove" id="imgRemoveBtn" title="ลบรูป">
                                    <i class="fas fa-times"></i>
                                </div>
                            </div>
                            <div class="cht-img-preview-info">
                                <div class="cht-img-preview-name" id="imgPreviewName">ชื่อไฟล์</div>
                                <div class="cht-img-preview-size" id="imgPreviewSize">ขนาดไฟล์</div>
                            </div>
                        </div>
                        <div class="cht-input-row">
                            <input type="file" id="imgFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                            <button type="button" class="cht-attach-btn" id="attachBtn" title="แนบรูปภาพ">
                                <i class="fas fa-image"></i>
                            </button>
                            <textarea id="msgInput"
                                      class="cht-textarea"
                                      placeholder="พิมพ์ข้อความถึง <?= e($target_user['fullname']) ?>..."
                                      rows="1"
                                      autocomplete="off"></textarea>
                            <button id="sendBtn" class="cht-send-btn" disabled title="ส่งข้อความ">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div class="cht-footer-hint">กด Enter ส่ง · Shift+Enter ขึ้นบรรทัดใหม่ · <i class="fas fa-image"></i> แนบรูปได้สูงสุด 5MB</div>
                    </div>

                    <script>
                    (function() {
                        const TARGET_ID  = <?= (int)$target_user['id'] ?>;
                        const MY_IMG     = '<?= !empty($_SESSION['profile_img']) ? "../assets/images/profiles/" . $_SESSION['profile_img'] : "../assets/images/profiles/default_profile.png" ?>';
                        const TARGET_IMG = '<?= $t_img ?>';

                        const chatBody       = document.getElementById('chatBody');
                        const skeleton       = document.getElementById('chatSkeleton');
                        const msgInput       = document.getElementById('msgInput');
                        const sendBtn        = document.getElementById('sendBtn');
                        const attachBtn      = document.getElementById('attachBtn');
                        const imgFileInput   = document.getElementById('imgFileInput');
                        const imgPreviewBar  = document.getElementById('imgPreviewBar');
                        const imgThumb       = document.getElementById('imgThumb');
                        const imgRemoveBtn   = document.getElementById('imgRemoveBtn');
                        const imgPreviewName = document.getElementById('imgPreviewName');
                        const imgPreviewSize = document.getElementById('imgPreviewSize');
                        const uploadProgress = document.getElementById('uploadProgress');
                        const uploadProgressBar = document.getElementById('uploadProgressBar');

                        let lastMsgId   = 0;
                        let isFirstLoad = true;
                        let pendingFile = null;

                        // ── Auto-resize textarea ──
                        msgInput.addEventListener('input', function() {
                            this.style.height = 'auto';
                            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                            updateSendBtn();
                        });

                        msgInput.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                if (!sendBtn.disabled) sendMsg();
                            }
                        });

                        sendBtn.addEventListener('click', sendMsg);

                        // ── Paste image from clipboard ──
                        document.addEventListener('paste', function(e) {
                            const items = e.clipboardData?.items;
                            if (!items) return;
                            for (const item of items) {
                                if (item.type.startsWith('image/')) {
                                    const file = item.getAsFile();
                                    if (file) setPreviewFile(file);
                                    break;
                                }
                            }
                        });

                        // ── Drag & drop image onto chatBody ──
                        chatBody.addEventListener('dragover', e => { e.preventDefault(); chatBody.style.outline = '2px dashed var(--cht-primary)'; });
                        chatBody.addEventListener('dragleave', () => { chatBody.style.outline = ''; });
                        chatBody.addEventListener('drop', e => {
                            e.preventDefault();
                            chatBody.style.outline = '';
                            const file = e.dataTransfer?.files?.[0];
                            if (file && file.type.startsWith('image/')) setPreviewFile(file);
                        });

                        // ── Attach button → open file picker ──
                        attachBtn.addEventListener('click', () => imgFileInput.click());

                        imgFileInput.addEventListener('change', function() {
                            if (this.files[0]) setPreviewFile(this.files[0]);
                            this.value = '';
                        });

                        imgRemoveBtn.addEventListener('click', clearPreview);

                        function setPreviewFile(file) {
                            const maxSize = 5 * 1024 * 1024;
                            if (!file.type.startsWith('image/')) { showToast('รองรับเฉพาะไฟล์รูปภาพเท่านั้น', 'error'); return; }
                            if (file.size > maxSize) { showToast('ไฟล์ต้องไม่เกิน 5MB', 'error'); return; }

                            pendingFile = file;
                            const reader = new FileReader();
                            reader.onload = e => { imgThumb.src = e.target.result; };
                            reader.readAsDataURL(file);

                            imgPreviewName.textContent = file.name.length > 28 ? file.name.substring(0, 25) + '...' : file.name;
                            imgPreviewSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
                            imgPreviewBar.classList.add('show');
                            updateSendBtn();
                        }

                        function clearPreview() {
                            pendingFile = null;
                            imgThumb.src = '';
                            imgPreviewBar.classList.remove('show');
                            updateSendBtn();
                        }

                        function updateSendBtn() {
                            sendBtn.disabled = msgInput.value.trim() === '' && !pendingFile;
                        }

                        // ── Build message row ──
                        function buildMsgRow(msg) {
                            const isMine = msg.is_mine;
                            const row    = document.createElement('div');
                            row.className = 'cht-msg-row' + (isMine ? ' mine' : '');

                            const avatarSrc   = isMine ? MY_IMG : TARGET_IMG;
                            const bubbleClass = isMine ? 'mine' : 'other';

                            let contentHtml = '';

                            if (msg.image_path) {
                                const imgSrc = '../assets/images/chat/' + msg.image_path;
                                contentHtml += `
                                    <div class="cht-img-bubble" onclick="openLightbox('${imgSrc}')">
                                        <img src="${imgSrc}" alt="รูปภาพ" loading="lazy">
                                    </div>`;
                            }

                            if (msg.message && msg.message.trim()) {
                                const safeText = msg.message.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
                                contentHtml += `<div class="cht-bubble ${bubbleClass}">${safeText}</div>`;
                            }

                            row.innerHTML = `
                                <img src="${avatarSrc}" class="cht-msg-avatar" alt="">
                                <div class="cht-bubble-wrap">
                                    ${contentHtml}
                                    <div class="cht-bubble-time">${msg.time}</div>
                                </div>`;
                            return row;
                        }

                        // ── Fetch messages ──
                        function fetchMessages() {
                            fetch(`../ajax/chat_api.php?action=fetch&other_user_id=${TARGET_ID}&last_id=${lastMsgId}`)
                                .then(r => r.json())
                                .then(data => {
                                    if (isFirstLoad) { skeleton.remove(); isFirstLoad = false; }

                                    if (data.status === 'success' && data.messages.length > 0) {
                                        const wasAtBottom = chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight < 100;

                                        data.messages.forEach(msg => {
                                            chatBody.appendChild(buildMsgRow(msg));
                                            lastMsgId = msg.id;
                                        });

                                        if (wasAtBottom) chatBody.scrollTop = chatBody.scrollHeight;
                                    }
                                })
                                .catch(() => {
                                    if (isFirstLoad) { skeleton.remove(); isFirstLoad = false; }
                                });
                        }

                        // ── Send message (text + optional image) ──
                        function sendMsg() {
                            const text = msgInput.value.trim();
                            if (!text && !pendingFile) return;

                            sendBtn.disabled = true;
                            sendBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';

                            const fd = new FormData();
                            fd.append('action', 'send');
                            fd.append('receiver_id', TARGET_ID);
                            fd.append('message', text);
                            if (pendingFile) fd.append('chat_image', pendingFile);

                            const textSnapshot = text;
                            msgInput.value = '';
                            msgInput.style.height = 'auto';
                            clearPreview();

                            // Show upload progress for image
                            if (pendingFile || fd.get('chat_image')) {
                                uploadProgress.classList.add('show');
                                let prog = 0;
                                const progTimer = setInterval(() => {
                                    prog = Math.min(prog + 12, 85);
                                    uploadProgressBar.style.width = prog + '%';
                                }, 80);

                                fetch('../ajax/chat_api.php', { method: 'POST', body: fd })
                                    .then(r => r.json())
                                    .then(data => {
                                        clearInterval(progTimer);
                                        uploadProgressBar.style.width = '100%';
                                        setTimeout(() => {
                                            uploadProgress.classList.remove('show');
                                            uploadProgressBar.style.width = '0%';
                                        }, 400);
                                        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                                        if (data.status === 'success') { fetchMessages(); msgInput.focus(); }
                                        else showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                                    })
                                    .catch(() => {
                                        clearInterval(progTimer);
                                        uploadProgress.classList.remove('show');
                                        sendBtn.disabled = false;
                                        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                                        showToast('ส่งไม่สำเร็จ กรุณาลองใหม่', 'error');
                                    });
                            } else {
                                fetch('../ajax/chat_api.php', { method: 'POST', body: fd })
                                    .then(r => r.json())
                                    .then(data => {
                                        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                                        if (data.status === 'success') { fetchMessages(); msgInput.focus(); }
                                    })
                                    .catch(() => {
                                        sendBtn.disabled = false;
                                        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                                    });
                            }
                        }

                        // ── Lightbox ──
                        window.openLightbox = function(src) {
                            const lb    = document.getElementById('chtLightbox');
                            const lbImg = document.getElementById('chtLightboxImg');
                            lbImg.src = src;
                            lb.classList.add('open');
                            document.body.style.overflow = 'hidden';
                        };

                        document.getElementById('chtLightbox')?.addEventListener('click', function(e) {
                            if (e.target === this || e.target.id === 'chtLightboxClose') {
                                this.classList.remove('open');
                                document.body.style.overflow = '';
                            }
                        });

                        document.addEventListener('keydown', e => {
                            if (e.key === 'Escape') {
                                document.getElementById('chtLightbox')?.classList.remove('open');
                                document.body.style.overflow = '';
                            }
                        });

                        // ── Toast ──
                        function showToast(msg, type = 'info') {
                            const colors = { error: ['rgba(239,68,68,0.1)', 'rgba(239,68,68,0.3)', '#991b1b'], info: ['rgba(79,70,229,0.1)', 'rgba(79,70,229,0.3)', '#4338ca'] };
                            const [bg, border, color] = colors[type] || colors.info;
                            const t = document.createElement('div');
                            t.style.cssText = `position:fixed;bottom:100px;left:50%;transform:translateX(-50%) translateY(10px);background:${bg};border:1.5px solid ${border};color:${color};padding:10px 22px;border-radius:50px;font-weight:800;font-size:0.88rem;z-index:999999;opacity:0;transition:all 0.35s var(--cht-ease);white-space:nowrap;backdrop-filter:blur(10px)`;
                            t.textContent = msg;
                            document.body.appendChild(t);
                            requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateX(-50%) translateY(0)'; });
                            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 3000);
                        }

                        // ── Contact search ──
                        document.getElementById('contactSearch')?.addEventListener('input', function() {
                            const q = this.value.toLowerCase();
                            document.querySelectorAll('.cht-contact').forEach(c => {
                                c.style.display = c.dataset.name.includes(q) ? '' : 'none';
                            });
                        });

                        fetchMessages();
                        setInterval(fetchMessages, 2000);
                    })();
                    </script>

                <?php else: ?>
                    <div class="cht-empty-state">
                        <div class="cht-empty-icon"><i class="fas fa-comments"></i></div>
                        <div class="cht-empty-title">เลือกการสนทนา</div>
                        <div class="cht-empty-sub">เลือกรายชื่อจากด้านซ้ายเพื่อเริ่มต้นแชท</div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Lightbox -->
<div class="cht-lightbox" id="chtLightbox">
    <button class="cht-lightbox-close" id="chtLightboxClose"><i class="fas fa-times"></i></button>
    <img class="cht-lightbox-img" id="chtLightboxImg" src="" alt="ขยายรูป">
    <div class="cht-lightbox-hint"><i class="fas fa-times" style="margin-right:6px;"></i> คลิกหรือกด ESC เพื่อปิด</div>
</div>

<?php require_once '../includes/footer.php'; ?>