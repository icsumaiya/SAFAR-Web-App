
"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { apiFetch } from "@/lib/api";
import ThemeToggle from "./ThemeToggle";

const links = [
  { href: "/admin", label: "Dashboard", icon: "fa-gauge-high" },
  { href: "/admin/agencies", label: "Manage Agencies", icon: "fa-building" },
  { href: "/admin/packages", label: "Manage Packages", icon: "fa-suitcase" },
  { href: "/admin/bookings", label: "Manage Bookings", icon: "fa-calendar-check" },
  { href: "/admin/payments", label: "Manage Payments", icon: "fa-money-bill-wave" },
  { href: "/admin/cancellations", label: "Cancellations", icon: "fa-ban" },
  { href: "/admin/commissions", label: "Commission & Revenue", icon: "fa-chart-line" },
  { href: "/admin/coupons", label: "Coupons & Discounts", icon: "fa-tags" },
  { href: "/admin/reviews", label: "Reviews & Ratings", icon: "fa-star" },
  { href: "/admin/notifications", label: "Notifications", icon: "fa-bell" },
];

export default function AdminSidebar() {
  const pathname = usePathname();
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    let cancelled = false;

    async function loadUnreadCount() {
      try {
        const data = await apiFetch("/admin/notifications.php");
        if (!cancelled) setUnreadCount(data.unread_count);
      } catch {
        // Sidebar badge is non-critical — fail silently, no error UI here.
      }
    }

    loadUnreadCount();
    const interval = setInterval(loadUnreadCount, 30000);

    return () => {
      cancelled = true;
      clearInterval(interval);
    };
  }, [pathname]);

  return (
    <aside className="w-[250px] bg-safar-card rounded-xl p-5 shadow-sm border border-black/5 sticky top-24 self-start">
      <h3 className="mb-5 text-safar-primary font-semibold">Admin Menu</h3>
      <ul className="space-y-2">
        {links.map((link) => {
          const active = pathname === link.href;
          const showBadge = link.href === "/admin/notifications" && unreadCount > 0;
          return (
            <li key={link.href}>
              <Link
                href={link.href}
                className={`flex items-center justify-between px-3 py-2 rounded-lg transition ${
                  active
                    ? "bg-safar-bg text-safar-primary font-semibold"
                    : "text-safar-text hover:bg-safar-bg"
                }`}
              >
                <span>
                  <i className={`fas ${link.icon} w-5 inline-block`}></i> {link.label}
                </span>
                {showBadge && (
                  <span className="bg-safar-primary text-white text-xs font-semibold rounded-full min-w-[20px] h-5 px-1.5 flex items-center justify-center">
                    {unreadCount > 99 ? "99+" : unreadCount}
                  </span>
                )}
              </Link>
            </li>
          );
        })}
      </ul>

      <div className="mt-6 pt-4 border-t border-black/10">
        <ThemeToggle />
      </div>
    </aside>
  );
}