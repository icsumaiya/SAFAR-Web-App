"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

const badgeClass = {
  verified: "bg-green-200 text-green-800",
  rejected: "bg-rose-200 text-rose-800",
  suspended: "bg-rose-200 text-rose-800",
  pending: "bg-yellow-200 text-yellow-800",
};

export default function AgenciesPage() {
  const [agencies, setAgencies] = useState([]);
  const [search, setSearch] = useState("");
  const [filterStatus, setFilterStatus] = useState("all");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  async function loadAgencies() {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({ search, filter_status: filterStatus });
      const data = await apiFetch(`/admin/agencies.php?${params.toString()}`);
      setAgencies(data.data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadAgencies();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function handleAction(agencyId, action) {
    if (action === "reject" && !confirm("Reject this agency?")) return;

    try {
      await apiFetch("/admin/agencies.php", {
        method: "POST",
        body: JSON.stringify({ agency_id: agencyId, action }),
      });
      loadAgencies();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <AdminLayout title="Manage Agencies">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">{error}</div>
      )}

      {/* Search / filter */}
      <div className="bg-white rounded-xl shadow-sm border border-black/5 p-5 mb-5">
        <form
          onSubmit={(e) => {
            e.preventDefault();
            loadAgencies();
          }}
          className="flex flex-wrap gap-4 items-end"
        >
          <div className="flex-[2] min-w-[200px]">
            <label className="block text-sm font-semibold mb-1">Search</label>
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Company, contact name, or email..."
              className="w-full border rounded px-3 py-2"
            />
          </div>
          <div className="flex-1 min-w-[150px]">
            <label className="block text-sm font-semibold mb-1">Status</label>
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="w-full border rounded px-3 py-2"
            >
              <option value="all">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="verified">Verified</option>
              <option value="rejected">Rejected</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="bg-safar-primary text-white px-4 py-2 rounded-lg hover:bg-safar-primary-dark transition">
              Filter
            </button>
            <button
              type="button"
              onClick={() => {
                setSearch("");
                setFilterStatus("all");
                loadAgencies();
              }}
              className="border-2 border-safar-primary text-safar-primary px-4 py-2 rounded-lg"
            >
              Reset
            </button>
          </div>
        </form>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl shadow-sm border border-black/5 p-6">
        <h2 className="text-lg font-semibold mb-4">All Agencies ({agencies.length})</h2>

        {loading ? (
          <p className="text-safar-muted">Loading...</p>
        ) : agencies.length === 0 ? (
          <p className="text-safar-muted">No agencies found.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left border-b">
                  <th className="py-2">Company Name</th>
                  <th className="py-2">Contact Person</th>
                  <th className="py-2">Email</th>
                  <th className="py-2">Status</th>
                  <th className="py-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                {agencies.map((agency) => (
                  <tr key={agency.id} className="border-b last:border-0">
                    <td className="py-2">{agency.company_name}</td>
                    <td className="py-2">{agency.name}</td>
                    <td className="py-2">{agency.email}</td>
                    <td className="py-2">
                      <span className={`px-2 py-1 rounded text-xs font-medium ${badgeClass[agency.status] || badgeClass.pending}`}>
                        {agency.status}
                      </span>
                    </td>
                    <td className="py-2 space-x-2">
                      {agency.status === "pending" && (
                        <>
                          <button
                            onClick={() => handleAction(agency.id, "verify")}
                            className="bg-green-600 text-white px-3 py-1 rounded text-xs"
                          >
                            Verify
                          </button>
                          <button
                            onClick={() => handleAction(agency.id, "reject")}
                            className="bg-rose-600 text-white px-3 py-1 rounded text-xs"
                          >
                            Reject
                          </button>
                        </>
                      )}
                      {agency.status === "verified" && (
                        <button
                          onClick={() => handleAction(agency.id, "suspend")}
                          className="bg-rose-500 text-white px-3 py-1 rounded text-xs"
                        >
                          Suspend
                        </button>
                      )}
                      {agency.status === "suspended" && (
                        <button
                          onClick={() => handleAction(agency.id, "activate")}
                          className="bg-green-600 text-white px-3 py-1 rounded text-xs"
                        >
                          Activate
                        </button>
                      )}
                    </td>
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