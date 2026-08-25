"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

const badgeClass = {
  successful: "bg-green-200 text-green-800",
  failed: "bg-rose-200 text-rose-800",
  pending: "bg-yellow-200 text-yellow-800",
};

export default function PaymentsPage() {
  const [payments, setPayments] = useState([]);
  const [stats, setStats] = useState({});
  const [statusFilter, setStatusFilter] = useState("all");
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  async function loadPayments() {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({ status: statusFilter, search });
      const data = await apiFetch(`/admin/payments.php?${params.toString()}`);
      setPayments(data.data);
      setStats(data.stats || {});
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadPayments();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [statusFilter]);

  const statCards = [
    { label: "Total Payments", value: stats.total_count ?? "—" },
    { label: "Successful", value: stats.successful_count ?? "—" },
    { label: "Pending", value: stats.pending_count ?? "—" },
    { label: "Failed", value: stats.failed_count ?? "—" },
    { label: "Successful Amount", value: stats.successful_amount != null ? `$${Number(stats.successful_amount).toFixed(2)}` : "—" },
  ];

  const tabs = ["all", "pending", "successful", "failed"];

  return (
    <AdminLayout title="Manage Payments">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">{error}</div>
      )}

      {/* Stat cards */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        {statCards.map((card) => (
          <div key={card.label} className="bg-white rounded-xl shadow-sm border border-black/5 p-4 text-center">
            <p className="text-xs text-safar-muted mb-1">{card.label}</p>
            <p className="text-xl font-bold text-safar-primary">{card.value}</p>
          </div>
        ))}
      </div>

      {/* Search */}
      <form
        onSubmit={(e) => {
          e.preventDefault();
          loadPayments();
        }}
        className="flex gap-2 mb-4"
      >
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Search by traveler name or transaction ID..."
          className="flex-1 max-w-sm border rounded px-3 py-2"
        />
        <button type="submit" className="bg-safar-primary text-white px-4 py-2 rounded-lg">
          Search
        </button>
      </form>

      {/* Filter tabs */}
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

      {/* Table */}
      <div className="bg-white rounded-xl shadow-sm border border-black/5 p-6">
        {loading ? (
          <p className="text-safar-muted">Loading...</p>
        ) : payments.length === 0 ? (
          <p className="text-safar-muted">No payments recorded yet.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left border-b">
                  <th className="py-2">Traveler</th>
                  <th className="py-2">Package</th>
                  <th className="py-2">Amount</th>
                  <th className="py-2">Method</th>
                  <th className="py-2">Transaction ID</th>
                  <th className="py-2">Status</th>
                  <th className="py-2">Date</th>
                </tr>
              </thead>
              <tbody>
                {payments.map((p) => (
                  <tr key={p.id} className="border-b last:border-0">
                    <td className="py-2">{p.traveler_name}</td>
                    <td className="py-2">{p.package_title}</td>
                    <td className="py-2">${Number(p.amount).toFixed(2)}</td>
                    <td className="py-2 capitalize">{p.method.replace("_", " ")}</td>
                    <td className="py-2">{p.transaction_id || "—"}</td>
                    <td className="py-2">
                      <span className={`px-2 py-1 rounded text-xs font-medium ${badgeClass[p.status]}`}>
                        {p.status}
                      </span>
                    </td>
                    <td className="py-2">{new Date(p.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}