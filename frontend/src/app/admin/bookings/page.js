"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

const badgeClass = {
  approved: "bg-green-200 text-green-800",
  rejected: "bg-rose-200 text-rose-800",
  pending: "bg-yellow-200 text-yellow-800",
};

export default function BookingsPage() {
  const [bookings, setBookings] = useState([]);
  const [counts, setCounts] = useState({});
  const [pagination, setPagination] = useState({ page: 1, total_pages: 1 });
  const [statusFilter, setStatusFilter] = useState("all");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  async function loadBookings(targetPage = page) {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({
        status: statusFilter,
        search,
        page: targetPage,
      });
      const data = await apiFetch(`/admin/bookings.php?${params.toString()}`);
      setBookings(data.data);
      setCounts(data.counts || {});
      setPagination(data.pagination);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadBookings(1);
    setPage(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [statusFilter]);

  async function handleAction(bookingId, action) {
    try {
      await apiFetch("/admin/bookings.php", {
        method: "POST",
        body: JSON.stringify({ booking_id: bookingId, booking_action: action }),
      });
      loadBookings(page);
    } catch (err) {
      setError(err.message);
    }
  }

  function goToPage(p) {
    setPage(p);
    loadBookings(p);
  }

  const tabs = [
    { key: "all", label: "All" },
    { key: "pending", label: "Pending" },
    { key: "approved", label: "Approved" },
    { key: "rejected", label: "Rejected" },
  ];

  return (
    <AdminLayout title="Manage Bookings">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">{error}</div>
      )}

      {/* Search */}
      <form
        onSubmit={(e) => {
          e.preventDefault();
          loadBookings(1);
          setPage(1);
        }}
        className="flex gap-2 mb-4"
      >
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Search by traveler, package, or agency..."
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
            key={tab.key}
            onClick={() => setStatusFilter(tab.key)}
            className={`px-4 py-2 rounded-lg text-sm ${
              statusFilter === tab.key
                ? "bg-safar-primary text-white"
                : "border-2 border-safar-primary text-safar-primary"
            }`}
          >
            {tab.label} {tab.key !== "all" && `(${counts[tab.key] || 0})`}
          </button>
        ))}
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl shadow-sm border border-black/5 p-6">
        {loading ? (
          <p className="text-safar-muted">Loading...</p>
        ) : bookings.length === 0 ? (
          <p className="text-safar-muted">No bookings found.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left border-b">
                  <th className="py-2">Traveler</th>
                  <th className="py-2">Package</th>
                  <th className="py-2">Agency</th>
                  <th className="py-2">Date</th>
                  <th className="py-2">Status</th>
                  <th className="py-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                {bookings.map((b) => (
                  <tr key={b.id} className="border-b last:border-0">
                    <td className="py-2">{b.traveler_name}</td>
                    <td className="py-2">{b.package_title}</td>
                    <td className="py-2">{b.company_name}</td>
                    <td className="py-2">{new Date(b.booking_date).toLocaleDateString()}</td>
                    <td className="py-2">
                      <span className={`px-2 py-1 rounded text-xs font-medium ${badgeClass[b.status]}`}>
                        {b.status}
                      </span>
                    </td>
                    <td className="py-2 space-x-2">
                      {b.status === "pending" && (
                        <>
                          <button
                            onClick={() => handleAction(b.id, "approve")}
                            className="bg-green-600 text-white px-3 py-1 rounded text-xs"
                          >
                            Approve
                          </button>
                          <button
                            onClick={() => handleAction(b.id, "reject")}
                            className="bg-rose-600 text-white px-3 py-1 rounded text-xs"
                          >
                            Reject
                          </button>
                        </>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {pagination.total_pages > 1 && (
          <div className="flex gap-2 justify-center mt-5">
            {Array.from({ length: pagination.total_pages }, (_, i) => i + 1).map((p) => (
              <button
                key={p}
                onClick={() => goToPage(p)}
                className={`px-3 py-1 rounded text-sm ${
                  p === page
                    ? "bg-safar-primary text-white"
                    : "border-2 border-safar-primary text-safar-primary"
                }`}
              >
                {p}
              </button>
            ))}
          </div>
        )}
      </div>
    </AdminLayout>
  );
}