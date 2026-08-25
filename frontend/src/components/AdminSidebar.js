"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const links = [
  { href: "/admin/agencies", label: "Manage Agencies", icon: "fa-building" },
  { href: "/admin/bookings", label: "Manage Bookings", icon: "fa-calendar-check" },
  { href: "/admin/payments", label: "Manage Payments", icon: "fa-money-bill-wave" },
  { href: "/admin/cancellations", label: "Cancellations", icon: "fa-ban" },
  { href: "/admin/commissions", label: "Commission & Revenue", icon: "fa-chart-line" },
];

export default function AdminSidebar() {
  const pathname = usePathname();

  return (
    <aside className="w-[250px] bg-white rounded-xl p-5 shadow-sm border border-black/5 sticky top-24 self-start">
      <h3 className="mb-5 text-safar-primary font-semibold">Admin Menu</h3>
      <ul className="space-y-2">
        {links.map((link) => {
          const active = pathname === link.href;
          return (
            <li key={link.href}>
              <Link
                href={link.href}
                className={`block px-3 py-2 rounded-lg transition ${
                  active
                    ? "bg-safar-bg text-safar-primary font-semibold"
                    : "text-safar-text hover:bg-safar-bg"
                }`}
              >
                <i className={`fas ${link.icon} w-5 inline-block`}></i> {link.label}
              </Link>
            </li>
          );
        })}
      </ul>
    </aside>
  );
}