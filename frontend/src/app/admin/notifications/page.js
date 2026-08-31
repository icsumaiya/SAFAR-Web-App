"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

const typeLabels = {
  agency_registration: "New Agency",
  new_booking: "New Booking",
  payment_successful: "Payment",
  cancellation_request: "Cancellation",
  refund_update: "Refund",
  new_review: "Review",
};

export default function NotificationsPage() {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  async function load() {
    setLoading(true);
    setError("");
    try {
      const data = await apiFetch("/admin/notifications.php");
      setNotifications(data.data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function markRead(id) {
    try {
      await apiFetch("/admin/notifications.php", {
        method: "POST",
        body: JSON.stringify({ action: "mark_read", id }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function markAllRead() {
    try {
      await apiFetch("/admin/notifications.php", {
        method: "POST",
        body: JSON.stringify({ action: "mark_all_read" }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  const unreadCount = notifications.filter((n) => !n.is_read).length;

  return (
    <AdminLayout title="Notifications">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">
          {error}
        </div>
      )}

      <div className="flex justify-between items-center mb-4">
        <p className="text-sm text-safar-muted">{unreadCount} unread</p>
        {unreadCount > 0 && (
          <button
            onClick={markAllRead}
            className="text-sm border-2 border-safar-primary text-safar-primary px-4 py-2 rounded-lg"
          >
            Mark all as read
          </button>
        )}
      </div>

      <div className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-4">
        {loading ? (
          <p className="text-safar-muted p-4">Loading...</p>
        ) : notifications.length === 0 ? (
          <p className="text-safar-muted p-4">No notifications yet.</p>
        ) : (
          <div className="divide-y">
            {notifications.map((n) => (
              <div
                key={n.id}
                className={`flex items-start justify-between gap-4 p-4 ${
                  !n.is_read ? "bg-safar-bg" : ""
                }`}
              >
                <div>
                  <span className="inline-block text-xs font-semibold text-safar-primary mb-1">
                    {typeLabels[n.type] || n.type}
                  </span>
                  <p className="text-sm">{n.message}</p>
                  <p className="text-xs text-safar-muted mt-1">
                    {new Date(n.created_at).toLocaleString()}
                  </p>
                </div>
                {!n.is_read && (
                  <button
                    onClick={() => markRead(n.id)}
                    className="text-xs border-2 border-safar-primary text-safar-primary px-3 py-1 rounded-lg whitespace-nowrap"
                  >
                    Mark read
                  </button>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </AdminLayout>
  );
}