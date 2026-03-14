<?php
/**
 * ============================================================================================
 * 🔄 BNCC MARKETPLACE - THE ULTIMATE OPEN BARTER COMMANDER (V 5.0.0 - THE TITAN EDITION)
 * ============================================================================================
 * Concept: "Open for Offers" - Users post what they have, others offer anything in return.
 * Design Philosophy: Poster-Centric / High-Contrast Solid UX / Micro-Interaction Heavy
 * Engineering: Custom Hyper-Utility CSS + Modular JavaScript Event Driven Framework
 * Target Concept: High-Complexity Architecture, Production-Ready, Performance Optimized
 * --------------------------------------------------------------------------------------------
 */

require_once '../includes/functions.php';
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array('y' => 'ปี','m' => 'เดือน','w' => 'สัปดาห์','d' => 'วัน','h' => 'ชม.','i' => 'นาที','s' => 'วินาที');
        foreach ($string as $k => &$v) {
            if ($diff->$k) { $v = $diff->$k . ' ' . $v; } else { unset($string[$k]); }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . 'ที่แล้ว' : 'เมื่อสักครู่';
    }
}
// --------------------------------------------------------------------------------------------
// [CORE CONTROLLER] 1. ADVANCED DATA ACQUISITION & PROCESSING STREAM
// --------------------------------------------------------------------------------------------
$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;

// Initialize error tracking and performance metrics (mock for enterprise feel)
$start_time = microtime(true);
$db_errors = [];

try {
    // 🎯 COMPLEX SQL JOIN: Fetching Barter Posts with Author Metadata, Roles, and Statistics
    // Assuming a structure where 'item_want' is now treated as "Open for offers" but might hold specific hints.
    $query = "
        SELECT 
            b.id as barter_id, 
            b.title, 
            b.item_have, 
            b.item_want, 
            b.description, 
            b.image_url, 
            b.status, 
            b.created_at as post_date,
            u.id as author_id,
            u.fullname as author_name, 
            u.profile_img as author_avatar, 
            u.role as author_role, 
            u.created_at as author_joined_date,
            -- Subquery for mock offer count (Enterprise touch)
            (SELECT COUNT(*) FROM messages m WHERE m.receiver_id = u.id AND m.message LIKE '%แลก%') as mock_offer_count
        FROM barter_posts b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.status = 'open' 
        ORDER BY b.created_at DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $barter_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Data Transformation Pipeline (Formatting dates, sanitizing)
    foreach ($barter_entries as &$entry) {
        $entry['formatted_date'] = date('d M Y, H:i', strtotime($entry['post_date']));
        $entry['time_ago'] = time_elapsed_string($entry['post_date']); // Assuming this helper exists, else fallback
        $entry['safe_title'] = htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8');
        $entry['safe_have'] = htmlspecialchars($entry['item_have'], ENT_QUOTES, 'UTF-8');
        $entry['safe_desc'] = htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8');
        $entry['is_mine'] = ($user_id == $entry['author_id']);
        
        // Handle Missing Images with a specific fallback mechanism
        $entry['resolved_image'] = !empty($entry['image_url']) 
            ? "../assets/images/barter/" . htmlspecialchars($entry['image_url'], ENT_QUOTES, 'UTF-8') 
            : "../assets/images/products/default_barter.png"; // Specific default for barter
            
        $entry['resolved_avatar'] = !empty($entry['author_avatar']) 
            ? "../assets/images/profiles/" . htmlspecialchars($entry['author_avatar'], ENT_QUOTES, 'UTF-8') 
            : "../assets/images/profiles/default_profile.png";
    }
    unset($entry); // Break reference

} catch (PDOException $e) {
    // Elegant error handling strategy
    error_log("Barter Fetch Critical Failure: " . $e->getMessage());
    $db_errors[] = "ระบบฐานข้อมูลขัดข้องชั่วคราว ไม่สามารถดึงรายการแลกเปลี่ยนได้";
    $barter_entries = [];
}

$execution_time = microtime(true) - $start_time;

// --------------------------------------------------------------------------------------------
// [HELPER LOGIC] TIME ELAPSED CALCULATOR
// --------------------------------------------------------------------------------------------


$pageTitle = "ตลาดแลกเปลี่ยนเสรี | BNCC Open Barter Hub";
require_once '../includes/header.php';
?>

<style>
    /* -------------------------------------------------------------------------------------
       [PART 1] GLOBAL DESIGN TOKENS & VARIABLE SYSTEM
       ------------------------------------------------------------------------------------- */
    :root {
        /* Core Palette - Open Barter Identity */
        --ob-brand-primary: #4f46e5;
        --ob-brand-primary-hover: #4338ca;
        --ob-brand-primary-light: #e0e7ff;
        --ob-brand-primary-dark: #312e81;
        --ob-brand-primary-glow: rgba(79, 70, 229, 0.3);
        --ob-brand-primary-alpha-10: rgba(79, 70, 229, 0.1);
        
        --ob-brand-success: #10b981;
        --ob-brand-success-hover: #059669;
        --ob-brand-success-light: #d1fae5;
        --ob-brand-success-dark: #064e3b;
        --ob-brand-success-glow: rgba(16, 185, 129, 0.3);
        
        --ob-brand-danger: #ef4444;
        --ob-brand-danger-hover: #dc2626;
        --ob-brand-danger-light: #fee2e2;
        --ob-brand-danger-glow: rgba(239, 68, 68, 0.3);
        
        --ob-brand-warning: #f59e0b;
        --ob-brand-warning-hover: #d97706;
        --ob-brand-warning-light: #fef3c7;
        
        --ob-brand-info: #3b82f6;
        --ob-brand-info-light: #dbeafe;

        /* Surface & Background Palette (Light Theme Default) */
        --ob-surface-100: #ffffff;
        --ob-surface-200: #f8fafc;
        --ob-surface-300: #f1f5f9;
        --ob-surface-400: #e2e8f0;
        
        --ob-bg-canvas: #f8fafc;
        --ob-bg-glass: rgba(255, 255, 255, 0.85);

        /* Typography Color System */
        --ob-text-heading: #0f172a;
        --ob-text-body: #334155;
        --ob-text-muted: #64748b;
        --ob-text-disabled: #94a3b8;
        --ob-text-inverse: #ffffff;

        /* Border System */
        --ob-border-subtle: #f1f5f9;
        --ob-border-default: #e2e8f0;
        --ob-border-strong: #cbd5e1;
        --ob-border-focus: #94a3b8;

        /* Elevation & Depth (Shadows) */
        --ob-shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --ob-shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        --ob-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
        --ob-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        --ob-shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        --ob-shadow-floating: 0 30px 60px -15px rgba(0, 0, 0, 0.2);
        --ob-shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);

        /* Radius System */
        --ob-radius-sm: 0.375rem;
        --ob-radius-md: 0.5rem;
        --ob-radius-lg: 0.75rem;
        --ob-radius-xl: 1rem;
        --ob-radius-2xl: 1.5rem;
        --ob-radius-3xl: 2rem;
        --ob-radius-full: 9999px;

        /* Animation Metrics */
        --ob-ease-linear: cubic-bezier(0.0, 0.0, 1.0, 1.0);
        --ob-ease-in: cubic-bezier(0.4, 0.0, 1.0, 1.0);
        --ob-ease-out: cubic-bezier(0.0, 0.0, 0.2, 1.0);
        --ob-ease-in-out: cubic-bezier(0.4, 0.0, 0.2, 1.0);
        --ob-ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        --ob-ease-elastic: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        
        --ob-duration-fast: 150ms;
        --ob-duration-normal: 300ms;
        --ob-duration-slow: 500ms;
        --ob-duration-epic: 800ms;

        /* Z-Index Hierarchy */
        --ob-z-under: -1;
        --ob-z-base: 0;
        --ob-z-above: 10;
        --ob-z-sticky: 100;
        --ob-z-dropdown: 200;
        --ob-z-modal-bg: 300;
        --ob-z-modal: 400;
        --ob-z-toast: 500;
        
        /* Typography Scale */
        --ob-font-sans: 'Prompt', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    /* Dark Mode Overrides Engine */
    html[data-theme="dark"], .dark-theme {
        --ob-surface-100: #0f172a;
        --ob-surface-200: #1e293b;
        --ob-surface-300: #334155;
        --ob-surface-400: #475569;
        
        --ob-bg-canvas: #020617;
        --ob-bg-glass: rgba(15, 23, 42, 0.85);

        --ob-text-heading: #f8fafc;
        --ob-text-body: #cbd5e1;
        --ob-text-muted: #94a3b8;
        --ob-text-disabled: #64748b;
        --ob-text-inverse: #0f172a;

        --ob-border-subtle: #1e293b;
        --ob-border-default: #334155;
        --ob-border-strong: #475569;
        --ob-border-focus: #64748b;

        --ob-shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
        --ob-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.6), 0 4px 6px -2px rgba(0, 0, 0, 0.4);
        --ob-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.7), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
        --ob-shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
        --ob-shadow-floating: 0 30px 60px -15px rgba(0, 0, 0, 0.9);
    }

    /* -------------------------------------------------------------------------------------
       [PART 2] BASE NORMALIZATION & TYPOGRAPHY
       ------------------------------------------------------------------------------------- */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-family: var(--ob-font-sans); line-height: 1.6; font-size: 16px; scroll-behavior: smooth; }
    body { 
        background-color: var(--ob-bg-canvas); 
        color: var(--ob-text-body); 
        transition: background-color var(--ob-duration-normal) var(--ob-ease-linear), color var(--ob-duration-normal) var(--ob-ease-linear); 
        -webkit-font-smoothing: antialiased; 
        -moz-osx-font-smoothing: grayscale; 
        overflow-x: hidden;
    }
    
    h1, h2, h3, h4, h5, h6 { color: var(--ob-text-heading); font-weight: 700; line-height: 1.2; margin-bottom: 0.5em; }
    p { margin-bottom: 1rem; }
    a { color: var(--ob-brand-primary); text-decoration: none; transition: color var(--ob-duration-fast) var(--ob-ease-out); cursor: pointer; }
    a:hover { color: var(--ob-brand-primary-hover); }
    img, picture, video, canvas, svg { display: block; max-width: 100%; height: auto; }
    button, input, select, textarea { font: inherit; color: inherit; background: transparent; border: none; }
    button { cursor: pointer; }
    button:disabled { cursor: not-allowed; opacity: 0.6; }
    ul, ol { list-style: none; }

    /* -------------------------------------------------------------------------------------
       [PART 3] HYPER-UTILITY CLASSES (Tailwind-like approach for maximum flexibility)
       ------------------------------------------------------------------------------------- */
    /* Layout & Display */
    .ob-block { display: block; }
    .ob-inline-block { display: inline-block; }
    .ob-flex { display: flex; }
    .ob-inline-flex { display: inline-flex; }
    .ob-grid { display: grid; }
    .ob-hidden { display: none !important; }

    /* Flexbox Behaviors */
    .ob-flex-row { flex-direction: row; }
    .ob-flex-col { flex-direction: column; }
    .ob-flex-wrap { flex-wrap: wrap; }
    .ob-flex-nowrap { flex-wrap: nowrap; }
    
    .ob-items-start { align-items: flex-start; }
    .ob-items-center { align-items: center; }
    .ob-items-end { align-items: flex-end; }
    .ob-items-stretch { align-items: stretch; }
    
    .ob-justify-start { justify-content: flex-start; }
    .ob-justify-center { justify-content: center; }
    .ob-justify-end { justify-content: flex-end; }
    .ob-justify-between { justify-content: space-between; }
    .ob-justify-around { justify-content: space-around; }
    
    .ob-flex-1 { flex: 1 1 0%; }
    .ob-flex-auto { flex: 1 1 auto; }
    .ob-flex-none { flex: none; }
    .ob-shrink-0 { flex-shrink: 0; }
    .ob-grow { flex-grow: 1; }

    /* Gap Utilities */
    .ob-gap-0 { gap: 0; }
    .ob-gap-1 { gap: 0.25rem; }
    .ob-gap-2 { gap: 0.5rem; }
    .ob-gap-3 { gap: 0.75rem; }
    .ob-gap-4 { gap: 1rem; }
    .ob-gap-5 { gap: 1.25rem; }
    .ob-gap-6 { gap: 1.5rem; }
    .ob-gap-8 { gap: 2rem; }
    .ob-gap-10 { gap: 2.5rem; }

    /* Spacing (Margin & Padding) Matrix */
    .ob-m-0 { margin: 0; } .ob-m-1 { margin: 0.25rem; } .ob-m-2 { margin: 0.5rem; } .ob-m-4 { margin: 1rem; } .ob-m-auto { margin: auto; }
    .ob-mt-0 { margin-top: 0; } .ob-mt-1 { margin-top: 0.25rem; } .ob-mt-2 { margin-top: 0.5rem; } .ob-mt-4 { margin-top: 1rem; } .ob-mt-6 { margin-top: 1.5rem; } .ob-mt-8 { margin-top: 2rem; } .ob-mt-10 { margin-top: 2.5rem; } .ob-mt-12 { margin-top: 3rem; } .ob-mt-auto { margin-top: auto; }
    .ob-mb-0 { margin-bottom: 0; } .ob-mb-1 { margin-bottom: 0.25rem; } .ob-mb-2 { margin-bottom: 0.5rem; } .ob-mb-4 { margin-bottom: 1rem; } .ob-mb-6 { margin-bottom: 1.5rem; } .ob-mb-8 { margin-bottom: 2rem; } .ob-mb-10 { margin-bottom: 2.5rem; } .ob-mb-12 { margin-bottom: 3rem; } .ob-mb-auto { margin-bottom: auto; }
    .ob-ml-0 { margin-left: 0; } .ob-ml-2 { margin-left: 0.5rem; } .ob-ml-4 { margin-left: 1rem; } .ob-ml-auto { margin-left: auto; }
    .ob-mr-0 { margin-right: 0; } .ob-mr-2 { margin-right: 0.5rem; } .ob-mr-4 { margin-right: 1rem; } .ob-mr-auto { margin-right: auto; }
    
    .ob-p-0 { padding: 0; } .ob-p-2 { padding: 0.5rem; } .ob-p-4 { padding: 1rem; } .ob-p-6 { padding: 1.5rem; } .ob-p-8 { padding: 2rem; }
    .ob-px-2 { padding-left: 0.5rem; padding-right: 0.5rem; } .ob-px-4 { padding-left: 1rem; padding-right: 1rem; } .ob-px-6 { padding-left: 1.5rem; padding-right: 1.5rem; } .ob-px-8 { padding-left: 2rem; padding-right: 2rem; }
    .ob-py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; } .ob-py-4 { padding-top: 1rem; padding-bottom: 1rem; } .ob-py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; } .ob-py-8 { padding-top: 2rem; padding-bottom: 2rem; } .ob-py-10 { padding-top: 2.5rem; padding-bottom: 2.5rem; } .ob-py-16 { padding-top: 4rem; padding-bottom: 4rem; }

    /* Sizing */
    .ob-w-full { width: 100%; } .ob-w-auto { width: auto; } .ob-w-screen { width: 100vw; } .ob-w-half { width: 50%; }
    .ob-h-full { height: 100%; } .ob-h-auto { height: auto; } .ob-h-screen { height: 100vh; }
    .ob-max-w-full { max-width: 100%; } .ob-max-w-screen-xl { max-width: 1280px; } .ob-max-w-screen-2xl { max-width: 1536px; }
    .ob-min-h-screen { min-height: 100vh; }

    /* Positioning */
    .ob-static { position: static; } .ob-relative { position: relative; } .ob-absolute { position: absolute; } .ob-fixed { position: fixed; } .ob-sticky { position: sticky; }
    .ob-top-0 { top: 0; } .ob-right-0 { right: 0; } .ob-bottom-0 { bottom: 0; } .ob-left-0 { left: 0; } .ob-inset-0 { inset: 0; }
    .ob-z-0 { z-index: 0; } .ob-z-10 { z-index: 10; } .ob-z-50 { z-index: 50; } .ob-z-top { z-index: 999; }

    /* Colors & Backgrounds */
    .ob-bg-primary { background-color: var(--ob-brand-primary); }
    .ob-bg-primary-soft { background-color: var(--ob-brand-primary-light); }
    .dark-theme .ob-bg-primary-soft { background-color: var(--ob-brand-primary-alpha-10); }
    .ob-bg-success { background-color: var(--ob-brand-success); }
    .ob-bg-danger { background-color: var(--ob-brand-danger); }
    .ob-bg-warning { background-color: var(--ob-brand-warning); }
    .ob-bg-surface { background-color: var(--ob-surface-100); }
    .ob-bg-surface-alt { background-color: var(--ob-surface-200); }
    .ob-bg-base { background-color: var(--ob-bg-canvas); }
    .ob-bg-transparent { background-color: transparent; }

    .ob-text-primary { color: var(--ob-brand-primary); }
    .ob-text-success { color: var(--ob-brand-success); }
    .ob-text-danger { color: var(--ob-brand-danger); }
    .ob-text-warning { color: var(--ob-brand-warning); }
    .ob-text-main { color: var(--ob-text-heading); }
    .ob-text-body { color: var(--ob-text-body); }
    .ob-text-muted { color: var(--ob-text-muted); }
    .ob-text-white { color: #ffffff; }

    /* Typography Adjustments */
    .ob-text-xs { font-size: 0.75rem; line-height: 1rem; }
    .ob-text-sm { font-size: 0.875rem; line-height: 1.25rem; }
    .ob-text-base { font-size: 1rem; line-height: 1.5rem; }
    .ob-text-lg { font-size: 1.125rem; line-height: 1.75rem; }
    .ob-text-xl { font-size: 1.25rem; line-height: 1.75rem; }
    .ob-text-2xl { font-size: 1.5rem; line-height: 2rem; }
    .ob-text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
    .ob-text-4xl { font-size: 2.25rem; line-height: 2.5rem; }
    .ob-text-5xl { font-size: 3rem; line-height: 1; }
    .ob-text-6xl { font-size: 3.75rem; line-height: 1; }

    .ob-font-light { font-weight: 300; }
    .ob-font-normal { font-weight: 400; }
    .ob-font-medium { font-weight: 500; }
    .ob-font-semibold { font-weight: 600; }
    .ob-font-bold { font-weight: 700; }
    .ob-font-extrabold { font-weight: 800; }
    .ob-font-black { font-weight: 900; }

    .ob-text-center { text-align: center; } .ob-text-left { text-align: left; } .ob-text-right { text-align: right; }
    .ob-uppercase { text-transform: uppercase; } .ob-lowercase { text-transform: lowercase; } .ob-capitalize { text-transform: capitalize; }
    .ob-tracking-tight { letter-spacing: -0.025em; } .ob-tracking-wide { letter-spacing: 0.025em; } .ob-tracking-widest { letter-spacing: 0.1em; }

    .ob-line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .ob-line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .ob-line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    /* Borders & Radius */
    .ob-rounded-none { border-radius: 0; }
    .ob-rounded-sm { border-radius: var(--ob-radius-sm); }
    .ob-rounded-md { border-radius: var(--ob-radius-md); }
    .ob-rounded-lg { border-radius: var(--ob-radius-lg); }
    .ob-rounded-xl { border-radius: var(--ob-radius-xl); }
    .ob-rounded-2xl { border-radius: var(--ob-radius-2xl); }
    .ob-rounded-3xl { border-radius: var(--ob-radius-3xl); }
    .ob-rounded-full { border-radius: var(--ob-radius-full); }

    .ob-border { border-width: 1px; border-style: solid; border-color: var(--ob-border-default); }
    .ob-border-2 { border-width: 2px; border-style: solid; border-color: var(--ob-border-default); }
    .ob-border-t { border-top-width: 1px; border-top-style: solid; border-top-color: var(--ob-border-default); }
    .ob-border-b { border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: var(--ob-border-default); }
    .ob-border-transparent { border-color: transparent; }
    .ob-border-primary { border-color: var(--ob-brand-primary); }

    /* Shadows & Effects */
    .ob-shadow-none { box-shadow: none; }
    .ob-shadow-sm { box-shadow: var(--ob-shadow-sm); }
    .ob-shadow-md { box-shadow: var(--ob-shadow-md); }
    .ob-shadow-lg { box-shadow: var(--ob-shadow-lg); }
    .ob-shadow-xl { box-shadow: var(--ob-shadow-xl); }
    .ob-shadow-floating { box-shadow: var(--ob-shadow-floating); }

    .ob-opacity-0 { opacity: 0; } .ob-opacity-50 { opacity: 0.5; } .ob-opacity-100 { opacity: 1; }
    
    .ob-transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 300ms; }
    .ob-duration-300 { transition-duration: 300ms; }
    .ob-duration-500 { transition-duration: 500ms; }

    .ob-cursor-pointer { cursor: pointer; } .ob-cursor-not-allowed { cursor: not-allowed; }
    .ob-pointer-events-none { pointer-events: none; } .ob-pointer-events-auto { pointer-events: auto; }
    .ob-overflow-hidden { overflow: hidden; }
    .ob-object-cover { object-fit: cover; }

    /* -------------------------------------------------------------------------------------
       [PART 4] CUSTOM ANIMATION KEYFRAMES
       ------------------------------------------------------------------------------------- */
    @keyframes titanRevealUp {
        0% { opacity: 0; transform: translateY(50px) scale(0.98); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes titanFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
    @keyframes titanPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
    }
    @keyframes titanShimmer {
        0% { background-position: -1000px 0; }
        100% { background-position: 1000px 0; }
    }
    @keyframes titanSlideInRight {
        0% { opacity: 0; transform: translateX(50px); }
        100% { opacity: 1; transform: translateX(0); }
    }

    .anim-reveal-up { animation: titanRevealUp 0.8s var(--ob-ease-elastic) forwards; }
    .anim-float { animation: titanFloat 4s ease-in-out infinite; }
    .anim-pulse { animation: titanPulse 2s ease-in-out infinite; }

    /* -------------------------------------------------------------------------------------
       [PART 5] ENTERPRISE COMPONENT LIBRARY
       ------------------------------------------------------------------------------------- */
    
    /* Buttons */
    .btn-core {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
        padding: 0.875rem 1.75rem; border-radius: var(--ob-radius-xl);
        font-weight: 800; font-size: 1rem; line-height: 1.5; text-align: center;
        white-space: nowrap; transition: var(--ob-transition);
        outline: none; border: 2px solid transparent; position: relative; overflow: hidden;
    }
    .btn-core:active { transform: scale(0.95); }
    
    .btn-solid-primary {
        background-color: var(--ob-brand-primary); color: #ffffff;
        box-shadow: 0 4px 10px var(--ob-brand-primary-glow);
    }
    .btn-solid-primary:hover {
        background-color: var(--ob-brand-primary-hover);
        box-shadow: 0 8px 20px var(--ob-brand-primary-glow);
        transform: translateY(-2px);
    }

    .btn-outline-primary {
        background-color: transparent; color: var(--ob-brand-primary);
        border-color: var(--ob-brand-primary);
    }
    .btn-outline-primary:hover {
        background-color: var(--ob-brand-primary-light);
        transform: translateY(-2px);
    }
    .dark-theme .btn-outline-primary:hover { background-color: var(--ob-brand-primary-alpha-10); }

    .btn-ghost-muted {
        background-color: transparent; color: var(--ob-text-muted);
    }
    .btn-ghost-muted:hover {
        background-color: var(--ob-surface-300); color: var(--ob-text-heading);
    }

    /* Badges */
    .badge-pill {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem;
        padding: 0.35rem 1rem; border-radius: var(--ob-radius-full);
        font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;
    }
    .badge-soft-primary { background-color: var(--ob-brand-primary-light); color: var(--ob-brand-primary-dark); }
    .dark-theme .badge-soft-primary { background-color: var(--ob-brand-primary-alpha-10); color: var(--ob-brand-primary-light); }
    
    .badge-soft-success { background-color: var(--ob-brand-success-light); color: var(--ob-brand-success-dark); }
    .dark-theme .badge-soft-success { background-color: rgba(16, 185, 129, 0.1); color: var(--ob-brand-success-light); }

    .badge-solid-dark { background-color: rgba(15, 23, 42, 0.85); color: #ffffff; backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); }
    .dark-theme .badge-solid-dark { background-color: rgba(255, 255, 255, 0.1); border-color: rgba(255,255,255,0.2); }

    /* -------------------------------------------------------------------------------------
       [PART 6] SPECIFIC PAGE ARCHITECTURE: OPEN BARTER HUB
       ------------------------------------------------------------------------------------- */
    
    /* Layout Container */
    .ob-hub-container {
        max-width: 1600px; margin: 0 auto; padding: 4rem 2rem;
        display: flex; flex-direction: column; gap: 3rem;
    }

    /* Master Header Section */
    .ob-master-header {
        display: flex; flex-direction: column; gap: 1.5rem;
        padding: 3rem; background: var(--ob-surface-100);
        border-radius: var(--ob-radius-3xl); border: 2px solid var(--ob-border-default);
        box-shadow: var(--ob-shadow-lg); position: relative; overflow: hidden;
    }
    .ob-master-header::before {
        content: ''; position: absolute; top: -50%; right: -10%; width: 50%; height: 200%;
        background: radial-gradient(circle, var(--ob-brand-primary-alpha-10) 0%, transparent 70%);
        pointer-events: none; z-index: 0;
    }
    .ob-header-content { position: relative; z-index: 1; display: flex; flex-direction: column; gap: 1rem; }
    @media (min-width: 768px) {
        .ob-master-header { flex-direction: row; align-items: center; justify-content: space-between; }
        .ob-header-content { max-width: 60%; }
    }

    /* The Grid System for Posters */
    .ob-poster-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 2.5rem;
        align-items: stretch;
    }

    /* The Titan Poster Card */
    .ob-poster-card {
        background: var(--ob-surface-100);
        border: 2px solid var(--ob-border-default);
        border-radius: var(--ob-radius-2xl);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.5s var(--ob-ease-elastic), box-shadow 0.4s ease, border-color 0.4s ease;
        position: relative;
        will-change: transform;
        height: 100%;
    }

    .ob-poster-card:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: var(--ob-shadow-floating);
        border-color: var(--ob-brand-primary);
        z-index: 10;
    }

    /* Highlight Bar for User's Own Post */
    .ob-poster-card.is-yours::after {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(90deg, var(--ob-brand-primary), var(--ob-brand-warning));
        z-index: 5;
    }

    /* Poster Visual Area */
    .poster-visual-zone {
        width: 100%; height: 280px; position: relative;
        background: var(--ob-surface-300); overflow: hidden; flex-shrink: 0;
    }
    .poster-visual-zone img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .ob-poster-card:hover .poster-visual-zone img { transform: scale(1.08) rotate(1deg); }

    /* Interactive Overlay on Image */
    .poster-visual-overlay {
        position: absolute; inset: 0; background: rgba(15, 23, 42, 0.4);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity var(--ob-duration-normal); z-index: 2;
        cursor: zoom-in;
    }
    .ob-poster-card:hover .poster-visual-overlay { opacity: 1; }
    .expand-icon-trigger {
        width: 50px; height: 50px; border-radius: 50%; background: var(--ob-surface-100);
        color: var(--ob-text-heading); display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; transform: scale(0.5); transition: transform var(--ob-duration-bounce);
    }
    .ob-poster-card:hover .expand-icon-trigger { transform: scale(1); }

    /* Floating Badges on Image */
    .poster-badge-top-left { position: absolute; top: 1rem; left: 1rem; z-index: 3; }
    .poster-badge-bottom-right { position: absolute; bottom: 1rem; right: 1rem; z-index: 3; }

    /* Poster Data Area */
    .poster-data-zone {
        padding: 2rem; display: flex; flex-direction: column; flex-grow: 1;
        background: var(--ob-surface-100); position: relative; z-index: 4;
    }

    /* The "I Have" Statement */
    .statement-have {
        font-size: 1.4rem; font-weight: 900; line-height: 1.3; color: var(--ob-text-heading);
        margin-bottom: 1.5rem; word-break: break-word;
    }

    /* The "Open Offer" Logic Block */
    .offer-logic-block {
        display: flex; align-items: flex-start; gap: 1rem;
        background: var(--ob-surface-200); border: 1px dashed var(--ob-border-strong);
        border-radius: var(--ob-radius-xl); padding: 1.25rem; margin-bottom: 1.5rem;
        transition: var(--ob-transition);
    }
    .ob-poster-card:hover .offer-logic-block {
        border-color: var(--ob-brand-primary); border-style: solid;
        background: var(--ob-brand-primary-light);
    }
    .dark-theme .ob-poster-card:hover .offer-logic-block { background: var(--ob-brand-primary-alpha-10); }

    .offer-logic-icon {
        width: 40px; height: 40px; border-radius: var(--ob-radius-md); flex-shrink: 0;
        background: var(--ob-surface-100); color: var(--ob-brand-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
        box-shadow: var(--ob-shadow-sm); transition: transform var(--ob-duration-bounce);
    }
    .ob-poster-card:hover .offer-logic-icon { transform: rotate(180deg) scale(1.1); background: var(--ob-brand-primary); color: #fff; }

    .offer-logic-content { display: flex; flex-direction: column; gap: 0.25rem; }
    .offer-logic-title { font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: var(--ob-text-muted); letter-spacing: 0.05em; }
    .ob-poster-card:hover .offer-logic-title { color: var(--ob-brand-primary); }
    .offer-logic-desc { font-size: 0.95rem; font-weight: 700; color: var(--ob-text-body); line-height: 1.4; }

    /* Description */
    .poster-desc-text {
        font-size: 0.95rem; color: var(--ob-text-muted); line-height: 1.6;
        margin-bottom: 2rem; flex-grow: 1;
    }

    /* Footer Bridge (Author & Actions) */
    .poster-footer-bridge {
        margin-top: auto; padding-top: 1.5rem; border-top: 2px solid var(--ob-border-default);
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    }

    /* Author Identity Component */
    .author-id-component {
        display: flex; align-items: center; gap: 0.75rem; text-decoration: none;
        padding: 0.5rem; border-radius: var(--ob-radius-lg); transition: var(--ob-transition);
        margin-left: -0.5rem; /* Optical alignment */
    }
    .author-id-component:hover { background: var(--ob-surface-200); }
    .author-avatar-img {
        width: 44px; height: 44px; border-radius: var(--ob-radius-full);
        object-fit: cover; border: 2px solid var(--ob-border-strong);
    }
    .author-meta-stack { display: flex; flex-direction: column; }
    .author-name-text { font-size: 0.95rem; font-weight: 800; color: var(--ob-text-heading); line-height: 1.2; }
    .author-role-text { font-size: 0.7rem; font-weight: 700; color: var(--ob-brand-primary); text-transform: uppercase; margin-top: 0.15rem; }

    /* Action Buttons inside Card */
    .action-btn-group { display: flex; align-items: center; gap: 0.5rem; }
    .btn-circle-action {
        width: 44px; height: 44px; border-radius: var(--ob-radius-lg);
        background: var(--ob-surface-200); color: var(--ob-text-body);
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        border: 2px solid var(--ob-border-default); transition: var(--ob-transition);
    }
    .btn-circle-action:hover { border-color: var(--ob-brand-primary); color: var(--ob-brand-primary); background: var(--ob-surface-100); transform: translateY(-2px); }
    
    .btn-solid-action {
        height: 44px; padding: 0 1.25rem; border-radius: var(--ob-radius-lg);
        background: var(--ob-brand-primary); color: #fff; font-weight: 800; font-size: 0.9rem;
        display: flex; align-items: center; gap: 0.5rem; border: none; transition: var(--ob-transition);
    }
    .btn-solid-action:hover { background: var(--ob-brand-primary-hover); transform: translateY(-2px); box-shadow: 0 4px 10px var(--ob-brand-primary-glow); }

    /* The Void (Empty State) */
    .the-void-state {
        grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 8rem 2rem; background: var(--ob-surface-100); border: 4px dashed var(--ob-border-default);
        border-radius: var(--ob-radius-3xl); text-align: center; margin-top: 2rem;
    }
    .void-icon-massive { font-size: 7rem; color: var(--ob-border-strong); margin-bottom: 2rem; animation: titanFloat 4s ease-in-out infinite; }
    .void-headline { font-size: 2.5rem; font-weight: 900; color: var(--ob-text-heading); margin-bottom: 1rem; }
    .void-subtext { font-size: 1.15rem; color: var(--ob-text-muted); max-width: 600px; margin-bottom: 3rem; line-height: 1.6; }

    /* Lightbox Subsystem */
    .lightbox-portal {
        position: fixed; inset: 0; z-index: var(--ob-z-modal-bg);
        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; visibility: hidden; transition: var(--ob-transition);
    }
    .lightbox-portal.is-active { opacity: 1; visibility: visible; }
    .lightbox-frame { position: relative; max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center; }
    .lightbox-img { max-width: 100%; max-height: 90vh; border-radius: var(--ob-radius-2xl); box-shadow: var(--ob-shadow-floating); transform: scale(0.9); transition: transform 0.5s var(--ob-ease-elastic); }
    .lightbox-portal.is-active .lightbox-img { transform: scale(1); }
    .lightbox-close {
        position: absolute; top: -3rem; right: -3rem; width: 50px; height: 50px;
        border-radius: var(--ob-radius-full); background: var(--ob-surface-100); color: var(--ob-text-heading);
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        border: none; cursor: pointer; transition: var(--ob-transition); box-shadow: var(--ob-shadow-lg); z-index: 10;
    }
    .lightbox-close:hover { background: var(--ob-brand-danger); color: #fff; transform: rotate(90deg) scale(1.1); }
    
    /* Skeleton Loader */
    .skeleton-mode .poster-visual-zone { background: linear-gradient(90deg, var(--ob-border-default) 25%, var(--ob-border-subtle) 50%, var(--ob-border-default) 75%); background-size: 200% 100%; animation: titanShimmer 2s infinite linear; }
    .skeleton-mode .statement-have, .skeleton-mode .poster-desc-text, .skeleton-mode .author-name-text { color: transparent !important; background: var(--ob-border-default); border-radius: var(--ob-radius-sm); animation: titanPulse 2s infinite; }
    
    /* System Alerts */
    .system-alert-panel {
        background: var(--ob-brand-danger-light); border: 2px solid var(--ob-brand-danger);
        color: var(--ob-brand-danger-dark); padding: 1rem 1.5rem; border-radius: var(--ob-radius-lg);
        margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; font-weight: 700;
    }
    .dark-theme .system-alert-panel { background: rgba(239, 68, 68, 0.1); color: var(--ob-brand-danger); }

    /* Responsive Architecture */
    @media (max-width: 1024px) {
        .ob-poster-grid { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
        .lightbox-close { top: 1rem; right: 1rem; }
    }
    @media (max-width: 768px) {
        .ob-hub-container { padding: 2rem 1rem; gap: 2rem; }
        .ob-master-header { padding: 2rem; }
        .ob-text-5xl { font-size: 2.5rem; }
        .ob-poster-grid { grid-template-columns: 1fr; }
        .poster-data-zone { padding: 1.5rem; }
        .statement-have { font-size: 1.25rem; }
    }
</style>

<main class="ob-hub-container" id="mainBarterContext">

    <?php if (!empty($db_errors)): ?>
        <?php foreach ($db_errors as $err): ?>
            <div class="system-alert-panel anim-reveal-up">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($err) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <header class="ob-master-header scroll-reveal-target" style="opacity: 0;">
        <div class="ob-header-content">
            <div class="ob-flex ob-items-center ob-gap-4">
                <span class="badge-pill badge-soft-primary"><i class="fas fa-globe-asia"></i> กระดานสาธารณะ</span>
                <span class="ob-text-xs ob-font-black ob-text-muted ob-uppercase" style="letter-spacing: 2px;">BNCC Barter Engine v5.0</span>
            </div>
            <h1 class="ob-text-5xl ob-font-black ob-text-heading ob-tracking-tight" style="margin-bottom: 0;">
                Open <span class="ob-text-primary">Barter Hub</span>
            </h1>
            <p class="ob-text-lg ob-font-bold ob-text-body">
                มีของไม่ได้ใช้? นำมาเสนอที่นี่ ใครมีอะไรอยากแลกก็เสนอมาได้เลย เปิดกว้างสำหรับทุกคนในวิทยาลัย!
            </p>
        </div>
        
        <div class="ob-flex-none">
            <a href="post_barter.php" class="btn-core btn-solid-primary" style="padding: 1.25rem 2.5rem; font-size: 1.125rem;">
                <i class="fas fa-box-open"></i> สร้างประกาศ "มีของมาแลก"
            </a>
        </div>
    </header>

    <div class="ob-poster-grid">
        <?php if (count($barter_entries) > 0): ?>
            <?php foreach ($barter_entries as $index => $item): 
                $delay = ($index % 8) * 0.05; // Staggered delay logic
            ?>
                <article class="ob-poster-card scroll-reveal-target js-tilt-card <?= $item['is_mine'] ? 'is-yours' : '' ?>" style="opacity: 0; animation-delay: <?= $delay ?>s;">
                    
                    <div class="poster-visual-zone">
                        <img src="<?= $item['resolved_image'] ?>" alt="<?= $item['safe_title'] ?>" loading="lazy" class="js-lightbox-trigger" data-img="<?= $item['resolved_image'] ?>">
                        
                        <div class="poster-visual-overlay js-lightbox-trigger" data-img="<?= $item['resolved_image'] ?>">
                            <div class="expand-icon-trigger"><i class="fas fa-expand"></i></div>
                        </div>
                        
                        <div class="poster-badge-top-left">
                            <span class="badge-pill badge-solid-dark">
                                <i class="far fa-clock"></i> <?= $item['time_ago'] ?>
                            </span>
                        </div>
                        <?php if($item['is_mine']): ?>
                            <div class="poster-badge-bottom-right">
                                <span class="badge-pill" style="background: var(--ob-brand-warning); color: #000;">
                                    <i class="fas fa-star"></i> ประกาศของคุณ
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="poster-data-zone">
                        <h2 class="statement-have ob-line-clamp-2" title="<?= $item['safe_have'] ?>">
                            <span class="ob-text-muted ob-font-bold" style="font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">I HAVE (สิ่งที่มี)</span>
                            <?= $item['safe_have'] ?>
                        </h2>

                        <div class="offer-logic-block">
                            <div class="offer-logic-icon"><i class="fas fa-sync-alt"></i></div>
                            <div class="offer-logic-content">
                                <span class="offer-logic-title">I WANT (สิ่งที่อยากได้)</span>
                                <span class="offer-logic-desc">เปิดรับทุกข้อเสนอ! ลองเสนอของที่คุณมีมาแลกเปลี่ยนกันได้เลย</span>
                            </div>
                        </div>

                        <p class="poster-desc-text ob-line-clamp-3">
                            <strong class="ob-text-heading">รายละเอียด:</strong> <?= $item['safe_desc'] ?>
                        </p>

                        <div class="poster-footer-bridge">
                            <a href="view_profile.php?id=<?= $item['author_id'] ?>" class="author-id-component">
                                <img src="<?= $item['resolved_avatar'] ?>" class="author-avatar-img" alt="<?= $item['author_name'] ?>">
                                <div class="author-meta-stack">
                                    <span class="author-name-text"><?= $item['author_name'] ?></span>
                                    <span class="author-role-text">
                                        <?= getUserBadge($item['author_role']) ?> <?= htmlspecialchars($item['author_role']) ?>
                                    </span>
                                </div>
                            </a>

                            <div class="action-btn-group">
                                <?php if($item['is_mine']): ?>
                                    <a href="../seller/edit_barter.php?id=<?= $item['barter_id'] ?>" class="btn-circle-action" title="แก้ไขประกาศ">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn-circle-action js-copy-trigger" data-link="<?= BASE_URL ?>/pages/barter_detail.php?id=<?= $item['barter_id'] ?>" title="คัดลอกลิงก์">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <a href="barter_detail.php?id=<?= $item['barter_id'] ?>" class="btn-solid-action">
                                        เสนอของแลก <i class="fas fa-paper-plane"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="the-void-state scroll-reveal-target" style="opacity: 0;">
                <i class="fas fa-box-open void-icon-massive"></i>
                <h2 class="void-headline">กระดานยังว่างเปล่า!</h2>
                <p class="void-subtext">
                    ขณะนี้ยังไม่มีใครนำสิ่งของมาลงประกาศบนกระดานแลกเปลี่ยน<br>
                    โอกาสเป็นของคุณแล้ว! นำของที่ไม่ได้ใช้มาประเดิมสร้างข้อเสนอแรกของระบบกันเลย
                </p>
                <div class="ob-flex ob-items-center ob-justify-center ob-gap-4 ob-flex-wrap">
                    <a href="post_barter.php" class="btn-core btn-solid-primary" style="padding: 1.25rem 2.5rem; font-size: 1.1rem;">
                        <i class="fas fa-plus"></i> สร้างประกาศชิ้นแรก
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: none;" aria-hidden="true">
        Page generated in <?= number_format($execution_time, 4) ?> seconds.
        Total items: <?= count($barter_entries) ?>
    </div>

</main>

<div class="lightbox-portal" id="enterpriseLightbox" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="lightbox-frame">
        <button class="lightbox-close" id="closeLightboxBtn" aria-label="ปิดรูปภาพ"><i class="fas fa-times"></i></button>
        <img src="" alt="Expanded View" class="lightbox-img" id="targetLightboxImg">
    </div>
</div>

<div id="toastAnchorPoint" style="position: fixed; bottom: 2rem; right: 2rem; z-index: var(--ob-z-toast); display: flex; flex-direction: column; gap: 1rem; pointer-events: none;"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    /**
     * MODULE 1: INTERSECTION OBSERVER (SCROLL REVEAL)
     * Handles the elegant entrance of cards as they scroll into view.
     */
    const RevealEngine = {
        init() {
            const targets = document.querySelectorAll('.scroll-reveal-target');
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('anim-reveal-up');
                        obs.unobserve(entry.target);
                    }
                });
            }, { root: null, threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

            targets.forEach(t => observer.observe(t));
        }
    };

    /**
     * MODULE 2: VANILLA 3D TILT EFFECT
     * Lightweight cursor-tracking 3D effect for cards (Desktop only)
     */
    const TiltEngine = {
        init() {
            const cards = document.querySelectorAll('.js-tilt-card');
            
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => this.tilt(e, card));
                card.addEventListener('mouseleave', () => this.reset(card));
            });
        },
        tilt(e, card) {
            if (window.innerWidth < 1024) return; // Disable on touch devices
            
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Calculate percentage from center (-1 to 1)
            const cx = rect.width / 2;
            const cy = rect.height / 2;
            const px = (x - cx) / cx;
            const py = -(y - cy) / cy;
            
            // Max tilt angle = 4 degrees
            card.style.transform = `perspective(1000px) rotateY(${px * 4}deg) rotateX(${py * 4}deg) scale3d(1.02, 1.02, 1.02)`;
        },
        reset(card) {
            card.style.transform = `perspective(1000px) rotateY(0deg) rotateX(0deg) scale3d(1, 1, 1)`;
        }
    };

    /**
     * MODULE 3: LIGHTBOX PREVIEWER
     * Handles full-screen image viewing without navigating away.
     */
    const LightboxEngine = {
        portal: document.getElementById('enterpriseLightbox'),
        img: document.getElementById('targetLightboxImg'),
        closeBtn: document.getElementById('closeLightboxBtn'),
        triggers: document.querySelectorAll('.js-lightbox-trigger'),

        init() {
            if (!this.portal || !this.img) return;

            this.triggers.forEach(t => {
                t.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const src = t.getAttribute('data-img');
                    if (src) this.open(src);
                });
            });

            this.closeBtn.addEventListener('click', () => this.close());
            this.portal.addEventListener('click', (e) => {
                if (e.target === this.portal) this.close();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.portal.classList.contains('is-active')) this.close();
            });
        },
        open(src) {
            this.img.src = src;
            this.portal.classList.add('is-active');
            this.portal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        },
        close() {
            this.portal.classList.remove('is-active');
            this.portal.setAttribute('aria-hidden', 'true');
            setTimeout(() => { this.img.src = ''; }, 400); // Clear after transition
            document.body.style.overflow = '';
        }
    };

    /**
     * MODULE 4: TOAST & CLIPBOARD SYSTEM
     * Handles link copying and elegant user feedback notifications.
     */
    const UtilityEngine = {
        toastAnchor: document.getElementById('toastAnchorPoint'),
        copyBtns: document.querySelectorAll('.js-copy-trigger'),

        init() {
            this.copyBtns.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const link = btn.getAttribute('data-link');
                    
                    try {
                        await navigator.clipboard.writeText(link);
                        this.toast('คัดลอกลิงก์สำเร็จ นำไปส่งให้เพื่อนได้เลย!', 'success');
                        
                        // Icon feedback
                        const icon = btn.querySelector('i');
                        icon.className = 'fas fa-check';
                        setTimeout(() => { icon.className = 'fas fa-link'; }, 2000);
                        
                    } catch (err) {
                        this.toast('ไม่สามารถคัดลอกลิงก์ได้ กรุณาลองใหม่', 'error');
                        console.error('Clipboard Error:', err);
                    }
                });
            });
        },
        toast(msg, type) {
            if (!this.toastAnchor) return;
            
            const el = document.createElement('div');
            el.style.cssText = `
                background: var(--ob-surface-100); border: 2px solid var(--ob-border-strong);
                padding: 1rem 1.5rem; border-radius: var(--ob-radius-lg); box-shadow: var(--ob-shadow-floating);
                display: flex; align-items: center; gap: 1rem; pointer-events: auto;
                animation: titanSlideInRight 0.4s var(--ob-ease-elastic) forwards;
            `;
            
            const iconColor = type === 'success' ? 'var(--ob-brand-success)' : 'var(--ob-brand-danger)';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            el.innerHTML = `
                <div style="color: ${iconColor}; font-size: 1.5rem;"><i class="fas ${iconClass}"></i></div>
                <div style="font-size: 0.95rem; font-weight: 800; color: var(--ob-text-heading);">${msg}</div>
            `;
            
            this.toastAnchor.appendChild(el);
            
            setTimeout(() => {
                el.style.animation = 'fade-out-up 0.4s forwards';
                el.addEventListener('animationend', () => el.remove());
            }, 3000);
        }
    };

    // BOOT SEQUENCE
    RevealEngine.init();
    TiltEngine.init();
    LightboxEngine.init();
    UtilityEngine.init();

    console.log("%c BNCC TITAN BARTER ENGINE BOOTED ", "background: #4f46e5; color: white; font-weight: 900; padding: 6px 12px; border-radius: 8px; font-size: 14px;");
});
</script>

<?php require_once '../includes/footer.php'; ?>