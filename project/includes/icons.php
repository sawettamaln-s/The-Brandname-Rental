<?php
// ไอคอนเส้นแบบ SVG ที่วาดขึ้นเองสำหรับเว็บนี้ (ใช้สีตามข้อความด้วย currentColor)
// เรียก icon('bag') ได้ <svg> ไปแสดงในหน้าเว็บ

const ICONS = [
    // หน้าร้านมีกันสาดลายทาง
    'store' => '<path d="M3 9.5h18"/><path d="M4 9.5 5.5 4h13L20 9.5"/><path d="M4 9.5c0 1.4 1 2.5 2.3 2.5S8.7 10.9 8.7 9.5c0 1.4 1 2.5 2.3 2.5s2.3-1.1 2.3-2.5c0 1.4 1 2.5 2.4 2.5S18 10.9 18 9.5"/><path d="M9.5 4 8.7 9.5M14.5 4l.8 5.5"/><path d="M5 12v8h14v-8"/><path d="M10 20v-4.5h4V20"/>',
    // ตะกร้าเข็น
    'cart' => '<path d="M3 4h2.2l2.3 10.5h10.3L20 7H6.3"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>',
    // ถุงช้อปปิ้งมีป้ายห้อย
    'bag' => '<path d="M5.5 8h13l-1 12.5h-11z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/><path d="m13.2 11.2 2.3 2.3-1.6 1.6-2.3-2.3v-1.6z"/>',
    // ป้ายราคา
    'tag' => '<path d="M3.5 12.2V4.5a1 1 0 0 1 1-1h7.7l8.3 8.3a1 1 0 0 1 0 1.4l-7.1 7.1a1 1 0 0 1-1.4 0z"/><circle cx="8" cy="8" r="1.3"/>',
    // กล่องของขวัญ
    'gift' => '<rect x="4" y="9" width="16" height="11" rx="1"/><path d="M3 9h18v3H3zM12 9v11"/><path d="M12 9c-1.5-3.5-5-4-5-1.6C7 9 12 9 12 9zM12 9c1.5-3.5 5-4 5-1.6C17 9 12 9 12 9z"/>',
    // กล่องพัสดุมีเครื่องหมายถูก (ร้านรับเรื่อง/เสร็จสมบูรณ์)
    'box-check' => '<path d="M4 8.5 12 5l8 3.5v9L12 21l-8-3.5z"/><path d="m4 8.5 8 3.5 8-3.5M12 12v9"/><circle cx="17.5" cy="5.5" r="3" fill="var(--icon-fill, #fff)"/><path d="m16.2 5.5.9.9 1.7-1.8"/>',
    // รถส่งของมีหัวใจ
    'truck' => '<path d="M3 7h11v9H3zM14 10h3.5l2.5 3v3h-6"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="16.5" cy="17.5" r="1.6"/><path d="M8.5 13.2s-2.2-1.3-2.2-2.6c0-.7.5-1.2 1.1-1.2.5 0 .9.3 1.1.7.2-.4.6-.7 1.1-.7.6 0 1.1.5 1.1 1.2 0 1.3-2.2 2.6-2.2 2.6z"/>',
    // กล่องเปิดมีหัวใจลอย (ส่งคืน/ได้รับของ)
    'box-heart' => '<path d="M4 11h16v9H4zM4 11l2-3h12l2 3"/><path d="M10 15h4"/><path d="M12 6.4S9.6 5 9.6 3.6c0-.8.6-1.3 1.2-1.3.5 0 1 .3 1.2.8.2-.5.7-.8 1.2-.8.7 0 1.2.5 1.2 1.3C14.4 5 12 6.4 12 6.4z"/>',
    'user' => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5"/>',
    'logout' => '<path d="M14 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
    'search' => '<circle cx="11" cy="11" r="6"/><path d="m20 20-4.5-4.5"/>',
    'calendar' => '<rect x="4" y="5.5" width="16" height="14" rx="2"/><path d="M4 10h16M8.5 3.5v4M15.5 3.5v4"/>',
    'heart' => '<path d="M12 19.5S4 14.8 4 9.3C4 7 5.7 5.3 7.8 5.3c1.7 0 3.2 1 4.2 2.5 1-1.5 2.5-2.5 4.2-2.5C18.3 5.3 20 7 20 9.3c0 5.5-8 10.2-8 10.2z"/>',
    'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
    'people' => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19c.6-3 2.8-4.6 5.5-4.6s4.9 1.6 5.5 4.6"/><circle cx="16.5" cy="9" r="2.4"/><path d="M15.5 14.5c2.3.1 4.2 1.5 4.8 4"/>',
    'card' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h4"/>',
    'sparkle' => '<path d="M12 3c.6 4.6 2.4 6.4 7 7-4.6.6-6.4 2.4-7 7-.6-4.6-2.4-6.4-7-7 4.6-.6 6.4-2.4 7-7z"/>',
];

function icon(string $name, string $class = ''): string
{
    $paths = ICONS[$name] ?? ICONS['sparkle'];
    return '<svg class="icon' . ($class !== '' ? ' ' . $class : '') . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
}
