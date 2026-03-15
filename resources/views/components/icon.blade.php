@props(['name' => 'info', 'class' => ''])

@php
    $icon = strtolower(trim((string) $name));
    $classes = trim('ui-icon '.$class);
@endphp

@switch($icon)
    @case('dashboard')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="8" height="8"></rect>
            <rect x="13" y="3" width="8" height="5"></rect>
            <rect x="13" y="10" width="8" height="11"></rect>
            <rect x="3" y="13" width="8" height="8"></rect>
        </svg>
        @break

    @case('home')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 10.5L12 3l9 7.5"></path>
            <path d="M5 9.5V21h14V9.5"></path>
            <path d="M9.5 21v-6h5v6"></path>
        </svg>
        @break

    @case('applications')
    @case('document')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 3h7l5 5v13H7z"></path>
            <path d="M14 3v5h5"></path>
            <path d="M9.5 12h6.5M9.5 16h6.5"></path>
        </svg>
        @break

    @case('monitor')
    @case('activity')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 17h18"></path>
            <path d="M6 15l3-4 2.5 3 3-6 3.5 7"></path>
        </svg>
        @break

    @case('announcements')
    @case('announcement')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 10v4"></path>
            <path d="M8 9l10-4v14L8 15z"></path>
            <path d="M8 15l1.5 5h3"></path>
        </svg>
        @break

    @case('enrollment')
    @case('register')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 19v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7.5" r="3.5"></circle>
            <path d="M19 8v6M16 11h6"></path>
        </svg>
        @break

    @case('school-year')
    @case('calendar')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="5" width="18" height="16" rx="2"></rect>
            <path d="M8 3v4M16 3v4M3 10h18"></path>
        </svg>
        @break

    @case('reports')
    @case('chart')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 20h16"></path>
            <rect x="6" y="11" width="3" height="7"></rect>
            <rect x="11" y="8" width="3" height="10"></rect>
            <rect x="16" y="5" width="3" height="13"></rect>
        </svg>
        @break

    @case('users')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="8" cy="8" r="3"></circle>
            <circle cx="16.5" cy="9" r="2.5"></circle>
            <path d="M2.5 19a5.5 5.5 0 0 1 11 0"></path>
            <path d="M14 19a4.5 4.5 0 0 1 7 0"></path>
        </svg>
        @break

    @case('logs')
    @case('timeline')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7v5l3 2"></path>
        </svg>
        @break

    @case('backup')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <ellipse cx="12" cy="5.5" rx="7" ry="3"></ellipse>
            <path d="M5 5.5v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"></path>
            <path d="M5 11.5v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"></path>
        </svg>
        @break

    @case('settings')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a2 2 0 0 1-4 0v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a2 2 0 0 1 0-4h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1L4.8 8.6a2 2 0 1 1 2.8-2.8l.1.1a1 1 0 0 0 1.1.2h.1a1 1 0 0 0 .6-.9V5a2 2 0 0 1 4 0v.2a1 1 0 0 0 .6.9h.1a1 1 0 0 0 1.1-.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1 1 0 0 0-.2 1.1v.1a1 1 0 0 0 .9.6H20a2 2 0 0 1 0 4h-.2a1 1 0 0 0-.4.1"></path>
        </svg>
        @break

    @case('search')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6"></circle>
            <path d="M20 20l-4-4"></path>
        </svg>
        @break

    @case('notification')
    @case('bell')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 18H5a1 1 0 0 1-1-1c2-2 2-4 2-7a5 5 0 1 1 10 0c0 3 0 5 2 7a1 1 0 0 1-1 1h-2"></path>
            <path d="M9.5 19a2.5 2.5 0 0 0 5 0"></path>
        </svg>
        @break

    @case('total')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 4v16M16 4v16M4 8h16M4 16h16"></path>
        </svg>
        @break

    @case('pending')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7v5l3 0"></path>
        </svg>
        @break

    @case('reviewed')
    @case('approved')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M8 12.5l2.6 2.6L16.5 9.2"></path>
        </svg>
        @break

    @case('rejected')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M9 9l6 6M15 9l-6 6"></path>
        </svg>
        @break

    @case('waitlisted')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M10 9v6M14 9v6"></path>
        </svg>
        @break

    @case('governance')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 10h18"></path>
            <path d="M5 10V6l7-3 7 3v4"></path>
            <path d="M5 21v-7M10 21v-7M14 21v-7M19 21v-7"></path>
            <path d="M3 21h18"></path>
        </svg>
        @break

    @case('decision')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 8h8"></path>
            <path d="M7 8l2 3H5zM19 8l2 3h-4z"></path>
            <path d="M13 8V4h-2"></path>
            <path d="M11 20h2"></path>
            <path d="M7 20h10"></path>
        </svg>
        @break

    @case('upload')
    @case('export')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 14V4"></path>
            <path d="M8 8l4-4 4 4"></path>
            <path d="M4 14v5h16v-5"></path>
        </svg>
        @break

    @case('profile')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M5 20a7 7 0 0 1 14 0"></path>
        </svg>
        @break

    @case('email')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
            <path d="M3 8l9 6 9-6"></path>
        </svg>
        @break

    @case('role')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 21h14"></path>
            <path d="M12 17v4"></path>
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M9 8l2 2 4-4"></path>
        </svg>
        @break

    @case('status')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="7" width="18" height="10" rx="5"></rect>
            <circle cx="9" cy="12" r="3"></circle>
        </svg>
        @break

    @case('create')
    @case('save')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 8v8M8 12h8"></path>
        </svg>
        @break

    @case('edit')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 20l4.5-1 9.5-9.5-3.5-3.5L5 15.5z"></path>
            <path d="M13.5 6.5l3.5 3.5"></path>
        </svg>
        @break

    @case('pin')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M14 4l6 6"></path>
            <path d="M8 10l6-6 6 6-6 6z"></path>
            <path d="M8 16l-4 4"></path>
            <path d="M10 14l-6 6"></path>
        </svg>
        @break

    @case('undo')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 10H4l3-3"></path>
            <path d="M4 10l3 3"></path>
            <path d="M4 10h9a7 7 0 1 1 0 14h-2"></path>
        </svg>
        @break

    @case('redo')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 10h5l-3-3"></path>
            <path d="M20 10l-3 3"></path>
            <path d="M20 10H11a7 7 0 1 0 0 14h2"></path>
        </svg>
        @break

    @case('delete')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 7h16"></path>
            <path d="M8 7V5h8v2"></path>
            <path d="M7 7l1 13h8l1-13"></path>
            <path d="M10 11v6M14 11v6"></path>
        </svg>
        @break

    @case('login')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10 17l5-5-5-5"></path>
            <path d="M15 12H3"></path>
            <path d="M8 4h9a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H8"></path>
        </svg>
        @break

    @case('logout')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M14 17l-5-5 5-5"></path>
            <path d="M9 12h12"></path>
            <path d="M16 4H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9"></path>
        </svg>
        @break

    @case('back')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 18l-6-6 6-6"></path>
            <path d="M9 12h12"></path>
        </svg>
        @break

    @case('password')
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="11" width="16" height="10" rx="2"></rect>
            <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
            <circle cx="12" cy="16" r="1"></circle>
        </svg>
        @break

    @case('info')
    @default
        <svg class="{{ $classes }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 10v6M12 7h.01"></path>
        </svg>
@endswitch
