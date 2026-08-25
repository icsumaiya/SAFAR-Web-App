"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

const badgeClass = {
  requested: "bg-yellow-200 text-yellow-800",
  approved: "bg-green-200 text-green-800",
  rejected: "bg-rose-200 text-rose-800",
  completed: "bg-gray-200 text-gray-800",
};

const refundOptions = ["not_applicable", "pending", "processing", "refunded", "rejected"];

export default function CancellationsPage() {
  const [items, setItems] = useState([]);
  const [statusFilter, setStatusFilter] = useState("all");
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [amountDrafts, setAmountDrafts] = useState({});

  async function load() {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({ status: statusFilter, search });
      const data = await apiFetch(`/admin/cancellations.php?${params.toString()}`);
      setItems(data.data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [statusFilter]);

  async function approve(id) {
    const raw = amountDrafts[id];
    const refundable_amount = raw && raw.trim() !== "" ? raw : "";

    try {
      await apiFetch("/admin/cancellations.php", {
        method: "POST",
        body: JSON.stringify({ cancellation_id: id, action: "approve", refundable_amount }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function reject(id) {
    if (!confirm("Reject this cancellation request?")) return;
    try {
      await apiFetch("/admin/cancellations.php", {
        method: "POST",
        body: JSON.stringify({ cancellation_id: id, action: "reject" }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function updateRefund(id, refund_status) {
    try {
      await apiFetch("/admin/cancellations.php", {
        method: "POST",
        body: JSON.stringify({ cancellation_id: id, action: "update_refund", refund_status }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  const tabs = ["all", "requested", "approved", "rejected", "completed"];

  return (
    <AdminLayout title="Cancellations & Refunds">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">{error}</div>
      )}

      <form
        onSubmit={(e) => {
          e.preventDefault();
          load();
        }}
        className="flex gap-2 mb-4"
      >
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Search by traveler or package..."
          className="flex-1 max-w-sm border rounded px-3 py-2"
        />
        <button type="submit" className="bg-safar-primary text-white px-4 py-2 rounded-lg">
          Search
        </button>
      </form>

      <div className="flex gap-2 mb-5 flex-wrap">
        {tabs.map((tab) => (
          <button
            key={tab}
            onClick={() => setStatusFilter(tab)}
            className={`px-4 py-2 rounded-lg text-sm capitalize ${
              statusFilter === tab
                ? "bg-safar-primary text-white"
                : "border-2 border-safar-primary text-safar-primary"
            }`}
          >
            {tab}
          </button>
        ))}
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-black/5 p-6">
        {loading ? (
          <p className="text-safar-muted">Loading...</p>
        ) : items.length === 0 ? (
          <p className="text-safar-muted">No cancellation requests found.</p>
        ) : (
          <div className="space-y-4">
            {items.map((c) => (
              <div key={c.id} className="border rounded-lg p-4">
                <div className="flex justify-between items-start flex-wrap gap-3">
                  <div>
                    <p className="font-semibold">{c.traveler_name} — {c.package_title}</p>
                    <p className="text-sm text-safar-muted mt-1">Reason: {c.reason}</p>
                    <p className="text-xs text-safar-muted mt-1">
                      Requested {new Date(c.requested_at).toLocaleDateString()}
                    </p>
                  </div>
                  <span className={`px-2 py-1 rounded text-xs font-medium h-fit ${badgeClass[c.status]}`}>
                    {c.status}
                  </span>
                </div>

                {c.status === "requested" && (
                  <div className="flex gap-2 items-center mt-3 flex-wrap">
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="Refundable amount (optional)"
                      value={amountDrafts[c.id] || ""}
                      onChange={(e) =>
                        setAmountDrafts((prev) => ({ ...prev, [c.id]: e.target.value }))
                      }
                      className="border rounded px-2 py-1 text-sm w-56"
                    />
                    <button
                      onClick={() => approve(c.id)}
                      className="bg-green-600 text-white px-3 py-1 rounded text-xs"
                    >
                      Approve
                    </button>
                    <button
                      onClick={() => reject(c.id)}
                      className="bg-rose-600 text-white px-3 py-1 rounded text-xs"
                    >
                      Reject
                    </button>
                  </div>
                )}

                {c.status === "approved" && (
                  <div className="flex gap-2 items-center mt-3 flex-wrap">
                    <span className="text-xs text-safar-muted">
                      Refundable: {c.refundable_amount != null ? `$${Number(c.refundable_amount).toFixed(2)}` : "N/A"}
                    </span>
                    <label className="text-xs text-safar-muted">Refund status:</label>
                    <select
                      value={c.refund_status}
                      onChange={(e) => updateRefund(c.id, e.target.value)}
                      className="border rounded px-2 py-1 text-xs"
                    >
                      {refundOptions.map((opt) => (
                        <option key={opt} value={opt}>{opt.replace("_", " ")}</option>
                      ))}
                    </select>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </AdminLayout>
  );
}