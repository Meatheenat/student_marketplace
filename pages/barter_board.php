<?php
/**
 * ============================================================================================
 * 🔄 BNCC MARKETPLACE - THE ULTIMATE BARTER COMMANDER (V 4.1.0 - NO SEARCH)
 * ============================================================================================
 * Design Philosophy: Poster-Centric / High-Contrast Solid UX
 * Engineering: Custom Utility-First CSS + JS Interaction Framework
 * Focus: Pure Content Delivery (Grid Only, No Filtering Overhead)
 * --------------------------------------------------------------------------------------------
 */

require_once '../includes/functions.php';

// --------------------------------------------------------------------------------------------
// [CONTROLLER] 1. DATA ARCHITECTURE (PURE STREAM)
// --------------------------------------------------------------------------------------------
$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;

// SQL Query ดึงข้อมูลประกาศแลกเปลี่ยนทั้งหมดเรียงตามใหม่ล่าสุด (ไม่มีการ Filter)
$query = "SELECT b.*, u.fullname, u.profile_img, u.role as user_role, u.created_at as user_since
          FROM barter_posts b 
          JOIN users u ON b.user_id = u.id 
          WHERE b.status = 'open' 
          ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$barter_entries = $stmt->fetchAll();

$pageTitle = "กระดานแลกเปลี่ยนของชาว BNCC";
require_once '../includes/header.php';
?>

<style>
    /* 🎨 COMPONENT: THEME DESIGN TOKENS */
    :root {
        --core-primary: #4f46e5;
        --core-primary-hover: #4338ca;
        --core-primary-light: #e0e7ff;
        --core-primary-dark: #312e81;
        --core-primary-alpha: rgba(79, 70, 229, 0.15);
        
        --core-success: #10b981;
        --core-success-hover: #059669;
        --core-success-light: #d1fae5;
        --core-success-dark: #064e3b;
        --core-success-alpha: rgba(16, 185, 129, 0.15);

        --core-danger: #ef4444;
        --core-danger-hover: #dc2626;
        --core-danger-light: #fee2e2;
        --core-danger-dark: #7f1d1d;
        --core-danger-alpha: rgba(239, 68, 68, 0.15);

        --core-warning: #f59e0b;
        --core-warning-hover: #d97706;
        --core-warning-light: #fef3c7;
        --core-warning-dark: #78350f;
        --core-warning-alpha: rgba(245, 158, 11, 0.15);

        --core-info: #3b82f6;
        --core-info-hover: #2563eb;
        --core-info-light: #dbeafe;
        --core-info-dark: #1e3a8a;
        --core-info-alpha: rgba(59, 130, 246, 0.15);

        --surface-main: #ffffff;
        --surface-alt: #f8fafc;
        --surface-card: #ffffff;
        --surface-hover: #f1f5f9;
        --surface-glass: rgba(255, 255, 255, 0.8);
        
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --text-tertiary: #94a3b8;
        --text-inverse: #ffffff;

        --border-light: #f1f5f9;
        --border-main: #e2e8f0;
        --border-strong: #cbd5e1;

        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
        --shadow-glow-primary: 0 0 20px rgba(79, 70, 229, 0.3);

        --radius-sm: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
        --radius-2xl: 1.5rem;
        --radius-3xl: 2rem;
        --radius-full: 9999px;

        --font-sans: 'Prompt', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        
        --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-bounce: 500ms cubic-bezier(0.34, 1.56, 0.64, 1);

        --z-negative: -1;
        --z-base: 0;
        --z-dropdown: 50;
        --z-sticky: 100;
        --z-modal-backdrop: 200;
        --z-modal: 250;
        --z-toast: 300;
    }

    html[data-theme="dark"], .dark-theme {
        --surface-main: #020617;
        --surface-alt: #0f172a;
        --surface-card: #1e293b;
        --surface-hover: #334155;
        --surface-glass: rgba(15, 23, 42, 0.8);
        
        --text-primary: #f8fafc;
        --text-secondary: #cbd5e1;
        --text-tertiary: #64748b;
        --text-inverse: #0f172a;

        --border-light: #1e293b;
        --border-main: #334155;
        --border-strong: #475569;

        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.5);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.6), 0 2px 4px -1px rgba(0, 0, 0, 0.4);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.7), 0 4px 6px -2px rgba(0, 0, 0, 0.5);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.8), 0 10px 10px -5px rgba(0, 0, 0, 0.6);
        --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.9);
    }

    *, ::before, ::after { box-sizing: border-box; margin: 0; padding: 0; border-width: 0; border-style: solid; border-color: var(--border-main); }
    html { font-family: var(--font-sans); line-height: 1.5; -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }
    body { background-color: var(--surface-main); color: var(--text-primary); transition: background-color var(--transition-normal), color var(--transition-normal); -webkit-font-smoothing: antialiased; }
    a { color: inherit; text-decoration: inherit; }
    img, svg, video, canvas, audio, iframe, embed, object { display: block; max-width: 100%; height: auto; }
    button, input, optgroup, select, textarea { font-family: inherit; font-size: 100%; line-height: inherit; color: inherit; margin: 0; padding: 0; background: transparent; }
    button, [role="button"] { cursor: pointer; }

    /* Utility Engine */
    .container-xl { max-width: 1536px; margin-left: auto; margin-right: auto; padding-left: 2rem; padding-right: 2rem; }

    .d-none { display: none !important; }
    .d-block { display: block; }
    .d-inline-block { display: inline-block; }
    .d-flex { display: flex; }
    .d-grid { display: grid; }

    .flex-row { flex-direction: row; }
    .flex-col { flex-direction: column; }
    .flex-wrap { flex-wrap: wrap; }
    .flex-nowrap { flex-wrap: nowrap; }

    .items-start { align-items: flex-start; }
    .items-center { align-items: center; }
    .items-end { align-items: flex-end; }
    .items-stretch { align-items: stretch; }

    .justify-start { justify-content: flex-start; }
    .justify-center { justify-content: center; }
    .justify-end { justify-content: flex-end; }
    .justify-between { justify-content: space-between; }
    .justify-around { justify-content: space-around; }

    .flex-1 { flex: 1 1 0%; }
    .flex-auto { flex: 1 1 auto; }
    .flex-none { flex: none; }
    .shrink-0 { flex-shrink: 0; }

    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .gap-0 { gap: 0px; }
    .gap-1 { gap: 0.25rem; }
    .gap-2 { gap: 0.5rem; }
    .gap-3 { gap: 0.75rem; }
    .gap-4 { gap: 1rem; }
    .gap-5 { gap: 1.25rem; }
    .gap-6 { gap: 1.5rem; }
    .gap-8 { gap: 2rem; }
    .gap-10 { gap: 2.5rem; }
    .gap-12 { gap: 3rem; }

    .m-0 { margin: 0px; }
    .m-1 { margin: 0.25rem; }
    .m-2 { margin: 0.5rem; }
    .m-3 { margin: 0.75rem; }
    .m-4 { margin: 1rem; }
    .m-6 { margin: 1.5rem; }
    .m-8 { margin: 2rem; }
    .m-10 { margin: 2.5rem; }

    .mt-0 { margin-top: 0px; }
    .mt-1 { margin-top: 0.25rem; }
    .mt-2 { margin-top: 0.5rem; }
    .mt-3 { margin-top: 0.75rem; }
    .mt-4 { margin-top: 1rem; }
    .mt-6 { margin-top: 1.5rem; }
    .mt-8 { margin-top: 2rem; }
    .mt-10 { margin-top: 2.5rem; }
    .mt-12 { margin-top: 3rem; }
    .mt-16 { margin-top: 4rem; }
    .mt-auto { margin-top: auto; }

    .mb-0 { margin-bottom: 0px; }
    .mb-1 { margin-bottom: 0.25rem; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mb-3 { margin-bottom: 0.75rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mb-6 { margin-bottom: 1.5rem; }
    .mb-8 { margin-bottom: 2rem; }
    .mb-10 { margin-bottom: 2.5rem; }
    .mb-12 { margin-bottom: 3rem; }
    .mb-16 { margin-bottom: 4rem; }
    .mb-auto { margin-bottom: auto; }

    .ml-0 { margin-left: 0px; }
    .ml-1 { margin-left: 0.25rem; }
    .ml-2 { margin-left: 0.5rem; }
    .ml-3 { margin-left: 0.75rem; }
    .ml-4 { margin-left: 1rem; }
    .ml-6 { margin-left: 1.5rem; }
    .ml-8 { margin-left: 2rem; }
    .ml-auto { margin-left: auto; }

    .mr-0 { margin-right: 0px; }
    .mr-1 { margin-right: 0.25rem; }
    .mr-2 { margin-right: 0.5rem; }
    .mr-3 { margin-right: 0.75rem; }
    .mr-4 { margin-right: 1rem; }
    .mr-6 { margin-right: 1.5rem; }
    .mr-8 { margin-right: 2rem; }
    .mr-auto { margin-right: auto; }

    .p-0 { padding: 0px; }
    .p-1 { padding: 0.25rem; }
    .p-2 { padding: 0.5rem; }
    .p-3 { padding: 0.75rem; }
    .p-4 { padding: 1rem; }
    .p-5 { padding: 1.25rem; }
    .p-6 { padding: 1.5rem; }
    .p-8 { padding: 2rem; }
    .p-10 { padding: 2.5rem; }

    .pt-0 { padding-top: 0px; }
    .pt-1 { padding-top: 0.25rem; }
    .pt-2 { padding-top: 0.5rem; }
    .pt-3 { padding-top: 0.75rem; }
    .pt-4 { padding-top: 1rem; }
    .pt-6 { padding-top: 1.5rem; }
    .pt-8 { padding-top: 2rem; }
    .pt-10 { padding-top: 2.5rem; }

    .pb-0 { padding-bottom: 0px; }
    .pb-1 { padding-bottom: 0.25rem; }
    .pb-2 { padding-bottom: 0.5rem; }
    .pb-3 { padding-bottom: 0.75rem; }
    .pb-4 { padding-bottom: 1rem; }
    .pb-6 { padding-bottom: 1.5rem; }
    .pb-8 { padding-bottom: 2rem; }
    .pb-10 { padding-bottom: 2.5rem; }

    .pl-0 { padding-left: 0px; }
    .pl-1 { padding-left: 0.25rem; }
    .pl-2 { padding-left: 0.5rem; }
    .pl-3 { padding-left: 0.75rem; }
    .pl-4 { padding-left: 1rem; }
    .pl-6 { padding-left: 1.5rem; }
    .pl-8 { padding-left: 2rem; }

    .pr-0 { padding-right: 0px; }
    .pr-1 { padding-right: 0.25rem; }
    .pr-2 { padding-right: 0.5rem; }
    .pr-3 { padding-right: 0.75rem; }
    .pr-4 { padding-right: 1rem; }
    .pr-6 { padding-right: 1.5rem; }
    .pr-8 { padding-right: 2rem; }

    .w-full { width: 100%; }
    .w-auto { width: auto; }
    .w-screen { width: 100vw; }
    .h-full { height: 100%; }
    .h-auto { height: auto; }
    .h-screen { height: 100vh; }
    .max-w-full { max-width: 100%; }

    .absolute { position: absolute; }
    .relative { position: relative; }
    .fixed { position: fixed; }
    .sticky { position: sticky; }

    .top-0 { top: 0px; }
    .right-0 { right: 0px; }
    .bottom-0 { bottom: 0px; }
    .left-0 { left: 0px; }
    .inset-0 { top: 0px; right: 0px; bottom: 0px; left: 0px; }

    .z-0 { z-index: 0; }
    .z-10 { z-index: 10; }
    .z-20 { z-index: 20; }
    .z-30 { z-index: 30; }
    .z-40 { z-index: 40; }
    .z-50 { z-index: var(--z-dropdown); }

    .bg-primary { background-color: var(--core-primary); }
    .bg-success { background-color: var(--core-success); }
    .bg-danger { background-color: var(--core-danger); }
    .bg-warning { background-color: var(--core-warning); }
    .bg-info { background-color: var(--core-info); }
    .bg-surface { background-color: var(--surface-main); }
    .bg-card { background-color: var(--surface-card); }
    .bg-base { background-color: var(--surface-alt); }
    .bg-transparent { background-color: transparent; }

    .text-primary { color: var(--core-primary); }
    .text-success { color: var(--core-success); }
    .text-danger { color: var(--core-danger); }
    .text-warning { color: var(--core-warning); }
    .text-info { color: var(--core-info); }
    .text-main { color: var(--text-primary); }
    .text-sub { color: var(--text-secondary); }
    .text-muted { color: var(--text-tertiary); }
    .text-white { color: #ffffff; }

    .text-xs { font-size: 0.75rem; line-height: 1rem; }
    .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
    .text-base { font-size: 1rem; line-height: 1.5rem; }
    .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
    .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
    .text-2xl { font-size: 1.5rem; line-height: 2rem; }
    .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
    .text-4xl { font-size: 2.25rem; line-height: 2.5rem; }
    .text-5xl { font-size: 3rem; line-height: 1; }

    .font-light { font-weight: 300; }
    .font-normal { font-weight: 400; }
    .font-medium { font-weight: 500; }
    .font-semibold { font-weight: 600; }
    .font-bold { font-weight: 700; }
    .font-extrabold { font-weight: 800; }
    .font-black { font-weight: 900; }

    .text-left { text-align: left; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-justify { text-align: justify; }

    .uppercase { text-transform: uppercase; }
    .lowercase { text-transform: lowercase; }
    .capitalize { text-transform: capitalize; }
    .italic { font-style: italic; }

    .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    .rounded-none { border-radius: 0px; }
    .rounded-sm { border-radius: var(--radius-sm); }
    .rounded-md { border-radius: var(--radius-md); }
    .rounded-lg { border-radius: var(--radius-lg); }
    .rounded-xl { border-radius: var(--radius-xl); }
    .rounded-2xl { border-radius: var(--radius-2xl); }
    .rounded-3xl { border-radius: var(--radius-3xl); }
    .rounded-full { border-radius: var(--radius-full); }

    .border { border-width: 1px; }
    .border-2 { border-width: 2px; }
    .border-4 { border-width: 4px; }
    .border-t { border-top-width: 1px; }
    .border-r { border-right-width: 1px; }
    .border-b { border-bottom-width: 1px; }
    .border-l { border-left-width: 1px; }

    .border-solid { border-style: solid; }
    .border-dashed { border-style: dashed; }
    .border-dotted { border-style: dotted; }

    .border-color-main { border-color: var(--border-main); }
    .border-color-light { border-color: var(--border-light); }
    .border-color-strong { border-color: var(--border-strong); }
    .border-color-primary { border-color: var(--core-primary); }
    .border-color-success { border-color: var(--core-success); }
    .border-color-danger { border-color: var(--core-danger); }
    .border-color-warning { border-color: var(--core-warning); }

    .shadow-none { box-shadow: none; }
    .shadow-sm { box-shadow: var(--shadow-sm); }
    .shadow-md { box-shadow: var(--shadow-md); }
    .shadow-lg { box-shadow: var(--shadow-lg); }
    .shadow-xl { box-shadow: var(--shadow-xl); }
    .shadow-2xl { box-shadow: var(--shadow-2xl); }

    .opacity-0 { opacity: 0; }
    .opacity-25 { opacity: 0.25; }
    .opacity-50 { opacity: 0.5; }
    .opacity-75 { opacity: 0.75; }
    .opacity-100 { opacity: 1; }

    .transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 300ms; }
    .duration-150 { transition-duration: 150ms; }
    .duration-300 { transition-duration: 300ms; }
    .duration-500 { transition-duration: 500ms; }
    .ease-in-out { transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
    .ease-bounce { transition-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1); }

    .cursor-pointer { cursor: pointer; }
    .cursor-not-allowed { cursor: not-allowed; }
    .pointer-events-none { pointer-events: none; }
    .pointer-events-auto { pointer-events: auto; }

    .overflow-hidden { overflow: hidden; }
    .overflow-auto { overflow: auto; }
    .overflow-x-auto { overflow-x: auto; }
    .overflow-y-auto { overflow-y: auto; }

    .object-cover { object-fit: cover; }
    .object-contain { object-fit: contain; }

    @keyframes reveal-up {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse-soft {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    @keyframes slide-in-right {
        0% { opacity: 0; transform: translateX(100%); }
        100% { opacity: 1; transform: translateX(0); }
    }
    @keyframes fade-out-up {
        0% { opacity: 1; transform: translateY(0); }
        100% { opacity: 0; transform: translateY(-20px); }
    }
    @keyframes float-y {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    .animate-reveal-up { animation: reveal-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-pulse-soft { animation: pulse-soft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

    .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: var(--radius-lg);
        font-weight: 700; font-size: 1rem; line-height: 1.5; text-align: center;
        white-space: nowrap; transition: var(--transition-bounce);
        outline: none; text-decoration: none; border: 2px solid transparent;
    }
    .btn:active { transform: scale(0.96); }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; pointer-events: none; }

    .btn-primary { background-color: var(--core-primary); color: #ffffff; box-shadow: var(--shadow-md); }
    .btn-primary:hover { background-color: var(--core-primary-hover); box-shadow: var(--shadow-glow-primary); transform: translateY(-2px); }

    .btn-secondary { background-color: var(--surface-alt); color: var(--text-primary); border-color: var(--border-strong); }
    .btn-secondary:hover { background-color: var(--surface-hover); border-color: var(--core-primary); color: var(--core-primary); transform: translateY(-2px); }

    .btn-danger { background-color: var(--core-danger); color: #ffffff; }
    .btn-danger:hover { background-color: var(--core-danger-hover); box-shadow: 0 4px 15px var(--core-danger-alpha); transform: translateY(-2px); }

    .btn-icon { width: 44px; height: 44px; padding: 0; border-radius: var(--radius-full); }

    .badge {
        display: inline-flex; align-items: center; padding: 0.25rem 0.75rem;
        border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; gap: 0.375rem;
    }
    .badge-primary { background-color: var(--core-primary-light); color: var(--core-primary-dark); }
    .dark-theme .badge-primary { background-color: var(--core-primary-alpha); color: var(--core-primary-light); }
    .badge-success { background-color: var(--core-success-light); color: var(--core-success-dark); }
    .dark-theme .badge-success { background-color: var(--core-success-alpha); color: var(--core-success-light); }

    /* Masonry Grid Layout */
    .masonry-grid-layout {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 2rem;
        align-items: start;
    }

    /* Barter Card Entity */
    .barter-card-entity {
        background: var(--surface-card);
        border: 2px solid var(--border-main);
        border-radius: var(--radius-2xl);
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease, border-color 0.4s ease;
        transform-style: preserve-3d;
        will-change: transform;
    }
    .barter-card-entity:hover { transform: translateY(-12px) scale(1.01); box-shadow: var(--shadow-2xl); border-color: var(--core-primary); z-index: 10; }
    
    .card-visual-chamber { position: relative; width: 100%; height: 280px; overflow: hidden; background: var(--surface-alt); flex-shrink: 0; }
    .card-visual-chamber img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        transform-origin: center center;
    }
    .barter-card-entity:hover .card-visual-chamber img { transform: scale(1.1) rotate(1deg); }

    .floating-status-pill {
        position: absolute; top: 1rem; right: 1rem;
        background: var(--surface-glass); backdrop-filter: blur(8px);
        border: 1px solid var(--border-light); border-radius: var(--radius-lg);
        padding: 0.5rem 1rem; font-size: 0.75rem; font-weight: 900;
        color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em;
        display: flex; align-items: center; gap: 0.5rem; box-shadow: var(--shadow-md); z-index: 2;
    }
    .floating-status-pill.is-yours { background: var(--core-primary); color: #fff; border-color: var(--core-primary-hover); }

    .preview-overlay-btn {
        position: absolute; inset: 0; background: rgba(15, 23, 42, 0.5);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: var(--transition-normal); z-index: 3; cursor: zoom-in;
    }
    .card-visual-chamber:hover .preview-overlay-btn { opacity: 1; }
    .preview-icon-circle {
        width: 60px; height: 60px; border-radius: 50%; background: var(--surface-main);
        color: var(--text-primary); display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; transform: scale(0.8); transition: var(--transition-bounce);
    }
    .card-visual-chamber:hover .preview-icon-circle { transform: scale(1); }

    .card-data-chamber { padding: 2rem; display: flex; flex-direction: column; flex-grow: 1; position: relative; z-index: 2; background: var(--surface-card); }

    .entity-headline { font-size: 1.5rem; font-weight: 900; line-height: 1.2; color: var(--text-primary); margin-bottom: 1.5rem; }

    .nexus-exchange-module {
        display: flex; flex-direction: column; gap: 1rem;
        background: var(--surface-alt); border: 1px solid var(--border-main);
        border-radius: var(--radius-xl); padding: 1.5rem; margin-bottom: 1.5rem;
        position: relative;
    }

    .nexus-node { display: flex; align-items: flex-start; gap: 1rem; position: relative; z-index: 2; flex: 1; }
    .nexus-node-icon {
        width: 48px; height: 48px; border-radius: var(--radius-lg); flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
    }
    .icon-source { background: var(--core-success-light); color: var(--core-success-dark); border: 1px solid var(--core-success); }
    .icon-target { background: var(--core-primary-light); color: var(--core-primary-dark); border: 1px solid var(--core-primary); }
    .dark-theme .icon-source { background: var(--core-success-alpha); color: var(--core-success); border-color: var(--core-success-dark); }
    .dark-theme .icon-target { background: var(--core-primary-alpha); color: var(--core-primary); border-color: var(--core-primary-dark); }

    .nexus-node-content { display: flex; flex-direction: column; flex-grow: 1; min-width: 0; }
    .nexus-node-label { font-size: 0.7rem; font-weight: 900; text-transform: uppercase; color: var(--text-tertiary); letter-spacing: 0.1em; margin-bottom: 0.25rem; }
    .nexus-node-value { font-size: 1.125rem; font-weight: 800; color: var(--text-primary); word-break: break-word; }

    .nexus-flow-connector {
        display: flex; align-items: center; justify-content: center;
        position: relative; padding-left: 1.5rem; color: var(--text-tertiary);
    }

    .nexus-flow-connector::before {
        content: ''; position: absolute; left: 23px; top: -15px; bottom: -15px; width: 2px;
        background-image: linear-gradient(to bottom, var(--border-strong) 50%, transparent 50%);
        background-size: 2px 8px; background-repeat: repeat-y; z-index: 1;
    }

    .swap-indicator-icon {
        background: var(--surface-alt); padding: 0.25rem 0; position: relative; z-index: 2;
        font-size: 1.25rem; transition: var(--transition-bounce);
    }
    .barter-card-entity:hover .swap-indicator-icon { color: var(--core-primary); transform: rotate(180deg) scale(1.2); }

    .entity-details-text { font-size: 1rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 2rem; flex-grow: 1; }

    .entity-footer-bridge {
        margin-top: auto; padding-top: 1.5rem; border-top: 2px solid var(--border-main);
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    }

    .author-signature { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.5rem; border-radius: var(--radius-lg); transition: var(--transition-fast); margin-left: -0.5rem; }
    .author-signature:hover { background: var(--surface-hover); }
    .author-signature-pfp { width: 48px; height: 48px; border-radius: var(--radius-full); object-fit: cover; border: 2px solid var(--core-primary); padding: 2px; }
    .author-signature-meta { display: flex; flex-direction: column; }
    .author-signature-name { font-size: 1rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .author-signature-role { font-size: 0.75rem; font-weight: 700; color: var(--core-primary); text-transform: uppercase; margin-top: 0.25rem; }

    .action-trigger-btn {
        width: 48px; height: 48px; border-radius: var(--radius-lg); background: var(--surface-alt);
        color: var(--text-primary); display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; text-decoration: none; border: 2px solid var(--border-main);
        transition: var(--transition-bounce); position: relative; overflow: hidden;
    }
    .action-trigger-btn::before { content: ''; position: absolute; inset: 0; background: var(--core-primary); transform: scaleY(0); transform-origin: bottom; transition: var(--transition-normal); z-index: 0; }
    .action-trigger-btn:hover { border-color: var(--core-primary); color: #fff; transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .action-trigger-btn:hover::before { transform: scaleY(1); }
    .action-trigger-btn i { position: relative; z-index: 1; }

    /* Toast & Lightbox */
    .toast-notification-system {
        position: fixed; bottom: 2rem; right: 2rem; z-index: var(--z-toast);
        display: flex; flex-direction: column; gap: 1rem; pointer-events: none;
    }
    .toast-item {
        background: var(--surface-card); border: 2px solid var(--border-main);
        border-radius: var(--radius-lg); padding: 1rem 1.5rem; box-shadow: var(--shadow-xl);
        display: flex; align-items: center; gap: 1rem; pointer-events: auto;
        animation: slide-in-right 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }
    .toast-item.closing { animation: fade-out-up 0.4s forwards; }
    .toast-icon { width: 32px; height: 32px; border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; font-size: 1rem; }
    .toast-icon.success { background: var(--core-success-light); color: var(--core-success-dark); }

    .lightbox-modal-system {
        position: fixed; inset: 0; z-index: var(--z-modal-backdrop);
        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; visibility: hidden; transition: var(--transition-normal);
    }
    .lightbox-modal-system.is-open { opacity: 1; visibility: visible; }
    .lightbox-content-wrapper { position: relative; max-width: 90vw; max-height: 90vh; }
    .lightbox-image-target { max-width: 100%; max-height: 90vh; border-radius: var(--radius-xl); box-shadow: var(--shadow-2xl); transform: scale(0.95); transition: var(--transition-bounce); }
    .lightbox-modal-system.is-open .lightbox-image-target { transform: scale(1); }
    .lightbox-close-trigger {
        position: absolute; top: -2rem; right: -2rem; width: 48px; height: 48px;
        border-radius: var(--radius-full); background: var(--surface-main); color: var(--text-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        border: none; cursor: pointer; transition: var(--transition-bounce); box-shadow: var(--shadow-lg);
    }
    .lightbox-close-trigger:hover { background: var(--core-danger); color: #fff; transform: rotate(90deg) scale(1.1); }

    /* Empty State */
    .empty-void-state {
        grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 6rem 2rem; background: var(--surface-card); border: 4px dashed var(--border-main);
        border-radius: var(--radius-3xl); text-align: center; margin-top: 2rem;
    }
    .empty-void-icon { font-size: 6rem; color: var(--border-strong); margin-bottom: 2rem; animation: float-y 4s ease-in-out infinite; }
    .empty-void-headline { font-size: 2.25rem; font-weight: 900; color: var(--text-primary); margin-bottom: 1rem; }
    .empty-void-subtext { font-size: 1.125rem; color: var(--text-secondary); max-width: 600px; margin-bottom: 2.5rem; line-height: 1.6; }

    @media (max-width: 1024px) {
        .masonry-grid-layout { grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); }
    }

    @media (max-width: 640px) {
        .masonry-grid-layout { grid-template-columns: 1fr; }
        .card-data-chamber { padding: 1.5rem; }
        .nexus-exchange-module { padding: 1rem; }
        .entity-headline { font-size: 1.25rem; }
        .lightbox-close-trigger { top: 1rem; right: 1rem; }
    }
</style>

<main class="container-xl py-10" id="mainBarterContext">

    <header class="flex-col md:flex-row d-flex justify-between items-start md:items-end gap-6 mb-10 animate-reveal-up">
        <div>
            <div class="d-flex items-center gap-3 mb-4">
                <span class="badge badge-primary"><i class="fas fa-globe-asia"></i> กระดานสาธารณะ</span>
                <span class="text-xs font-black text-muted uppercase tracking-widest">ระบบแลกเปลี่ยน v4.1</span>
            </div>
            <h1 class="text-5xl font-black mb-2 text-main">Barter <span class="text-primary">Ecosystem</span></h1>
            <p class="text-xl font-bold text-sub">เปลี่ยนของที่ไม่ได้ใช้ เป็นของที่อยากได้ แบบไร้เงินสด 100%</p>
        </div>
        
        <div class="flex-none w-full md:w-auto">
            <a href="post_barter.php" class="btn btn-primary w-full md:w-auto" style="padding: 1.25rem 2.5rem; font-size: 1.125rem;">
                <i class="fas fa-plus-square"></i> สร้างประกาศแลกเปลี่ยน
            </a>
        </div>
    </header>

    <div class="masonry-grid-layout" id="barterLayoutTarget">
        <?php if (count($barter_entries) > 0): ?>
            <?php foreach ($barter_entries as $index => $item): 
                $img_path = !empty($item['image_url']) ? "../assets/images/barter/".$item['image_url'] : "../assets/images/products/default.png";
                $poster_avatar = !empty($item['profile_img']) ? "../assets/images/profiles/".$item['profile_img'] : "../assets/images/profiles/default_profile.png";
                $is_mine = ($user_id == $item['user_id']);
                $delay = ($index % 8) * 0.05;
            ?>
                <article class="barter-card-entity card-reveal-target js-tilt" data-tilt data-tilt-max="5" data-tilt-speed="400" data-tilt-perspective="1000" style="animation-delay: <?= $delay ?>s;">
                    <div class="card-visual-chamber">
                        <img src="<?= $img_path ?>" alt="<?= e($item['title']) ?>" loading="lazy" class="js-lightbox-trigger" data-highres="<?= $img_path ?>">
                        <div class="preview-overlay-btn js-lightbox-trigger" data-highres="<?= $img_path ?>">
                            <div class="preview-icon-circle"><i class="fas fa-expand-arrows-alt"></i></div>
                        </div>
                        
                        <div class="floating-status-pill <?= $is_mine ? 'is-yours' : '' ?>">
                            <?php if($is_mine): ?>
                                <i class="fas fa-star"></i> ประกาศของฉัน
                            <?php else: ?>
                                <i class="fas fa-sync fa-spin" style="animation-duration: 3s;"></i> พร้อมแลก
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-data-chamber">
                        <h2 class="entity-headline line-clamp-2" title="<?= e($item['title']) ?>"><?= e($item['title']) ?></h2>

                        <div class="nexus-exchange-module">
                            <div class="nexus-node">
                                <div class="nexus-node-icon icon-source"><i class="fas fa-box-open"></i></div>
                                <div class="nexus-node-content">
                                    <span class="nexus-node-label">I Have (มีของ)</span>
                                    <div class="nexus-node-value line-clamp-2"><?= e($item['item_have']) ?></div>
                                </div>
                            </div>

                            <div class="nexus-flow-connector">
                                <i class="fas fa-exchange-alt swap-indicator-icon"></i>
                            </div>

                            <div class="nexus-node">
                                <div class="nexus-node-icon icon-target"><i class="fas fa-hand-holding-heart"></i></div>
                                <div class="nexus-node-content">
                                    <span class="nexus-node-label">I Want (อยากได้)</span>
                                    <div class="nexus-node-value line-clamp-2"><?= e($item['item_want']) ?></div>
                                </div>
                            </div>
                        </div>

                        <p class="entity-details-text line-clamp-3">
                            <?= e($item['description']) ?>
                        </p>

                        <div class="entity-footer-bridge">
                            <a href="view_profile.php?id=<?= $item['user_id'] ?>" class="author-signature">
                                <img src="<?= $poster_avatar ?>" class="author-signature-pfp" alt="Profile">
                                <div class="author-signature-meta">
                                    <span class="author-signature-name"><?= e($item['fullname']) ?></span>
                                    <span class="author-signature-role">
                                        <?= getUserBadge($item['user_role']) ?> <?= htmlspecialchars($item['user_role']) ?>
                                    </span>
                                </div>
                            </a>

                            <div class="d-flex items-center gap-2">
                                <button type="button" class="action-trigger-btn js-share-btn" data-url="<?= BASE_URL ?>/pages/barter_detail.php?id=<?= $item['id'] ?>" aria-label="Share">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                                <a href="barter_detail.php?id=<?= $item['id'] ?>" class="action-trigger-btn" aria-label="View Details" style="background:var(--core-primary); color:#fff; border-color:var(--core-primary);">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-void-state animate-reveal-up">
                <i class="fas fa-box-open empty-void-icon"></i>
                <h2 class="empty-void-headline">ยังไม่มีการแลกเปลี่ยนใดๆ</h2>
                <p class="empty-void-subtext">
                    ยังไม่มีประกาศในระบบ ณ ตอนนี้<br>
                    คุณอาจเป็นคนแรกที่สร้างโอกาสใหม่ๆ ในการแลกเปลี่ยนสิ่งของที่นี่
                </p>
                <div class="d-flex items-center justify-center gap-4 flex-wrap">
                    <a href="post_barter.php" class="btn btn-primary px-8 py-4"><i class="fas fa-plus"></i> สร้างประกาศของฉัน</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</main>

<div class="toast-notification-system" id="globalToastContainer" aria-live="polite"></div>

<div class="lightbox-modal-system" id="masterLightbox" aria-hidden="true" role="dialog">
    <div class="lightbox-content-wrapper">
        <button class="lightbox-close-trigger" id="lightboxCloseBtn" aria-label="Close Lightbox"><i class="fas fa-times"></i></button>
        <img src="" alt="High Resolution Preview" class="lightbox-image-target" id="lightboxImageTarget">
    </div>
</div>

<script>
/**
 * ============================================================================================
 * BNCC BARTER ENGINE LOGIC - V4 (PURE STREAM)
 * ============================================================================================
 */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // MODULE: SCROLL REVEAL (INTERSECTION OBSERVER)
    const RevealEngine = {
        init() {
            const targets = document.querySelectorAll('.card-reveal-target');
            targets.forEach(t => {
                t.style.opacity = '0';
                t.style.transform = 'translateY(40px)';
            });

            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-reveal-up');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

            targets.forEach(t => observer.observe(t));
        }
    };

    // MODULE: LIGHTBOX VIEWER
    const LightboxEngine = {
        modal: document.getElementById('masterLightbox'),
        imgTarget: document.getElementById('lightboxImageTarget'),
        closeBtn: document.getElementById('lightboxCloseBtn'),
        triggers: document.querySelectorAll('.js-lightbox-trigger'),

        init() {
            if (!this.modal || !this.imgTarget) return;

            this.triggers.forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const src = trigger.getAttribute('data-highres');
                    if (src) this.open(src);
                });
            });

            this.closeBtn.addEventListener('click', () => this.close());
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) this.close();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.close();
            });
        },

        open(src) {
            this.imgTarget.src = src;
            this.modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.modal.classList.remove('is-open');
            setTimeout(() => { this.imgTarget.src = ''; }, 300);
            document.body.style.overflow = '';
        }
    };

    // MODULE: TOAST NOTIFICATION SYSTEM
    const ToastEngine = {
        container: document.getElementById('globalToastContainer'),

        show(message, type = 'success') {
            if (!this.container) return;

            const toast = document.createElement('div');
            toast.className = 'toast-item';
            
            const iconClass = type === 'success' ? 'fa-check text-success' : 'fa-info text-info';
            const iconBg = type === 'success' ? 'bg-success-light' : 'bg-info-light';

            toast.innerHTML = `
                <div class="toast-icon ${type}"><i class="fas ${iconClass}"></i></div>
                <div class="text-sm font-bold text-main">${message}</div>
            `;

            this.container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('closing');
                toast.addEventListener('animationend', () => toast.remove());
            }, 3000);
        }
    };

    // MODULE: SHARE FUNCTIONALITY
    const ShareEngine = {
        init() {
            const btns = document.querySelectorAll('.js-share-btn');
            btns.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const url = btn.getAttribute('data-url');
                    
                    try {
                        await navigator.clipboard.writeText(url);
                        ToastEngine.show('คัดลอกลิงก์สำหรับแชร์เรียบร้อยแล้ว', 'success');
                        
                        // Icon animation feedback
                        const icon = btn.querySelector('i');
                        const oldClass = icon.className;
                        icon.className = 'fas fa-check text-success';
                        setTimeout(() => icon.className = oldClass, 2000);
                    } catch (err) {
                        console.error('Failed to copy: ', err);
                    }
                });
            });
        }
    };

    // MODULE: VANILLA TILT 3D EFFECT (Lightweight implementation)
    const TiltEngine = {
        init() {
            const elements = document.querySelectorAll('[data-tilt]');
            
            elements.forEach(el => {
                el.addEventListener('mousemove', (e) => this.handleTilt(e, el));
                el.addEventListener('mouseleave', () => this.resetTilt(el));
            });
        },

        handleTilt(e, el) {
            // Only apply on Desktop to prevent weird mobile behavior
            if (window.innerWidth < 1024) return;

            const max = parseInt(el.getAttribute('data-tilt-max')) || 10;
            const rect = el.getBoundingClientRect();
            
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const percentX = (x - centerX) / centerX;
            const percentY = -(y - centerY) / centerY;
            
            el.style.transform = `perspective(1000px) rotateY(${percentX * max}deg) rotateX(${percentY * max}deg) scale3d(1.02, 1.02, 1.02)`;
        },

        resetTilt(el) {
            el.style.transform = `perspective(1000px) rotateY(0deg) rotateX(0deg) scale3d(1, 1, 1)`;
        }
    };

    // BOOT SEQUENCE
    RevealEngine.init();
    LightboxEngine.init();
    ShareEngine.init();
    TiltEngine.init();

    console.log("%c BNCC BARTER PURE STREAM ACTIVE ", "background: #4f46e5; color: white; font-weight: 900; padding: 5px 10px; border-radius: 5px;");
});
</script>

<?php require_once '../includes/footer.php'; ?>