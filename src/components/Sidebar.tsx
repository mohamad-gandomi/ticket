import { useState } from 'react';
import { NavLink, Link } from 'react-router-dom';
import {
  ListIcon,
  Plus,
  Close,
  Setting,
  TicketStar,
  Menu,
  BookOpen,
} from '../icons';
import { getConfig } from '../config';
import type { ReactNode } from 'react';

const USER_NAV = [
  { to: '/tickets',     label: 'لیست تیکت‌ها', icon: <ListIcon size={18} /> },
  { to: '/tickets/new', label: 'تیکت جدید',    icon: <Plus    size={18} /> },
];

const ADMIN_NAV = [
  { to: '/tickets',   label: 'صف تیکت‌ها',  icon: <ListIcon  size={18} /> },
  { to: '/knowledge', label: 'پایگاه دانش', icon: <BookOpen  size={18} /> },
  { to: '/settings',  label: 'تنظیمات',     icon: <Setting   size={18} /> },
];

function NavItems({ items, onClose }: { items: typeof USER_NAV; onClose?: () => void }) {
  return (
    <>
      {items.map((it) => (
        <NavLink
          key={it.to}
          to={it.to}
          end
          onClick={onClose}
          className={({ isActive }) =>
            `flex items-center gap-3 h-11 px-3 rounded-xl text-[13px] transition ${
              isActive ? 'bg-brand-tint text-brand font-medium' : 'text-ink-700 hover:bg-surface-50'
            }`
          }
        >
          <span className="shrink-0">{it.icon}</span>
          <span>{it.label}</span>
        </NavLink>
      ))}
    </>
  );
}

function Sidebar({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const { mode } = getConfig();
  const nav       = mode === 'admin' ? ADMIN_NAV : USER_NAV;
  const homeRoute = '/tickets';

  const brand = (
    <Link to={homeRoute} onClick={onClose} className="flex items-center gap-2 pb-5 mb-1 border-b border-line shrink-0">
      <span className="text-brand"><TicketStar size={32} /></span>
      <div>
        <div className="text-[14px] font-bold leading-tight">WP AI Support</div>
        <div className="text-[11px] text-ink-500">{mode === 'admin' ? 'پنل مدیریت' : 'پنل کاربر'}</div>
      </div>
    </Link>
  );

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && (
        <div className="fixed inset-0 z-40 bg-ink-900/40 lg:hidden" onClick={onClose} />
      )}

      {/* Desktop: stretching white column so background fills full page height */}
      <div className="hidden lg:flex flex-col w-60 shrink-0 bg-white border-l border-line">
        <div className="sticky top-0 h-screen overflow-y-auto p-5 flex flex-col gap-1">
          {brand}
          <NavItems items={nav} />
        </div>
      </div>

      {/* Mobile: fixed slide-in overlay */}
      <aside
        className={`
          fixed top-0 right-0 z-50 h-screen w-60 shrink-0 border-l border-line bg-white p-5
          flex flex-col gap-1 overflow-y-auto transition-transform duration-300 lg:hidden
          ${isOpen ? 'translate-x-0' : 'translate-x-full'}
        `}
      >
        <div className="flex items-center justify-between pb-5 mb-1 border-b border-line shrink-0">
          <button onClick={onClose} className="size-9 grid place-items-center rounded-lg text-ink-500 hover:bg-surface-50">
            <Close size={18} />
          </button>
          <Link to={homeRoute} onClick={onClose} className="flex items-center gap-2">
            <span className="text-brand"><TicketStar size={26} /></span>
            <span className="text-[13px] font-bold">WP AI Support</span>
          </Link>
        </div>
        <NavItems items={nav} onClose={onClose} />
      </aside>
    </>
  );
}

export function Layout({ children }: { children: ReactNode }) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { mode } = getConfig();
  const homeRoute = '/tickets';

  return (
    <div className="min-h-screen flex bg-surface-50 text-ink-900 overflow-x-hidden" dir="rtl">
      <div className="lg:hidden fixed top-0 right-0 left-0 z-30 h-14 bg-white border-b border-line flex items-center justify-between px-4 shrink-0">
        <button
          onClick={() => setSidebarOpen(true)}
          className="size-9 grid place-items-center rounded-lg text-ink-700 hover:bg-surface-50"
        >
          <Menu size={20} />
        </button>
        <Link to={homeRoute} className="flex items-center gap-2">
          <span className="text-brand"><TicketStar size={28} /></span>
          <span className="text-[14px] font-bold">WP AI Support</span>
        </Link>
      </div>

      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <main className="flex-1 min-w-0 pt-14 lg:pt-0">{children}</main>
    </div>
  );
}
