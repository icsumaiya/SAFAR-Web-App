"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

const emptyForm = {
  id: null,
  type: "tour",
  agency_id: "",
  title: "",
  location: "",
  price: "",
  image_url: "",
  description: "",
};

const sortOptions = [
  { value: "newest", label: "Newest" },
  { value: "price_asc", label: "Price: Low to High" },
  { value: "price_desc", label: "Price: High to Low" },
  { value: "rating", label: "Highest Rated" },
  { value: "popularity", label: "Most Popular" },
];

export default function PackagesPage() {
  const [packages, setPackages] = useState([]);
  const [pagination, setPagination] = useState({ page: 1, total_pages: 1 });
  const [allAgencies, setAllAgencies] = useState([]);
  const [verifiedAgencies, setVerifiedAgencies] = useState([]);

  const [search, setSearch] = useState("");
  const [filterType, setFilterType] = useState("all");
  const [filterAgency, setFilterAgency] = useState("all");
  const [filterStatus, setFilterStatus] = useState("all");
  const [statusCounts, setStatusCounts] = useState({
    all: 0,
    pending: 0,
    approved: 0,
    rejected: 0,
  });
  const [sort, setSort] = useState("newest");
  const [page, setPage] = useState(1);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [formError, setFormError] = useState("");
  const [saving, setSaving] = useState(false);

  async function load() {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({
        search,
        filter_type: filterType,
        filter_agency: filterAgency,
        filter_status: filterStatus,
        sort,
        page: String(page),
      });
      const data = await apiFetch(`/admin/packages.php?${params.toString()}`);
      setPackages(data.data);
      setPagination(data.pagination);
      setAllAgencies(data.all_agencies);
      setVerifiedAgencies(data.verified_agencies);
      if (data.status_counts) setStatusCounts(data.status_counts);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filterType, filterAgency, filterStatus, sort, page]);

  function openCreateForm(type) {
    setForm({ ...emptyForm, type });
    setFormError("");
    setShowForm(true);
  }

  function openEditForm(pkg) {
    setForm({
      id: pkg.id,
      type: pkg.type,
      agency_id: pkg.agency_id,
      title: pkg.title,
      location: pkg.location,
      price: pkg.price,
      image_url: pkg.image_url || "",
      description: pkg.description,
    });
    setFormError("");
    setShowForm(true);
  }

  async function savePackage(e) {
    e.preventDefault();
    setSaving(true);
    setFormError("");
    try {
      await apiFetch("/admin/packages.php", {
        method: "POST",
        body: JSON.stringify({ action: form.id ? "update" : "create", ...form }),
      });
      setShowForm(false);
      load();
    } catch (err) {
      setFormError(err.message);
    } finally {
      setSaving(false);
    }
  }

  async function deletePackage(pkg) {
    if (!confirm(`Delete "${pkg.title}"? This can't be undone.`)) return;
    try {
      await apiFetch("/admin/packages.php", {
        method: "POST",
        body: JSON.stringify({ action: "delete", id: pkg.id }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function approvePackage(pkg) {
    try {
      await apiFetch("/admin/packages.php", {
        method: "POST",
        body: JSON.stringify({ action: "approve", id: pkg.id }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function rejectPackage(pkg) {
    if (!confirm(`Reject "${pkg.title}"? The agency will be notified.`)) return;
    try {
      await apiFetch("/admin/packages.php", {
        method: "POST",
        body: JSON.stringify({ action: "reject", id: pkg.id }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <AdminLayout title="Manage Packages">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">
          {error}
        </div>
      )}

      <div className="flex justify-between items-center mb-5 flex-wrap gap-3">
        <h1 className="text-xl font-bold text-safar-text">Manage Packages</h1>
        <div className="flex gap-2">
          <button
            onClick={() => openCreateForm("tour")}
            className="bg-safar-primary text-white px-4 py-2 rounded-lg text-sm font-medium"
          >
            <i className="fas fa-plus mr-1"></i> Create Tour
          </button>
          <button
            onClick={() => openCreateForm("hotel")}
            className="bg-safar-secondary text-white px-4 py-2 rounded-lg text-sm font-medium"
          >
            <i className="fas fa-plus mr-1"></i> Create Hotel
          </button>
        </div>
      </div>

      <div className="flex gap-2 mb-5 flex-wrap">
        {[
          { value: "all", label: "All" },
          { value: "pending", label: "Pending" },
          { value: "approved", label: "Approved" },
          { value: "rejected", label: "Rejected" },
        ].map((tab) => (
          <button
            key={tab.value}
            onClick={() => {
              setFilterStatus(tab.value);
              setPage(1);
            }}
            className={`px-4 py-2 rounded-lg text-sm ${
              filterStatus === tab.value
                ? "bg-safar-primary text-white"
                : "border-2 border-safar-primary text-safar-primary"
            }`}
          >
            {tab.label} ({statusCounts[tab.value] ?? 0})
          </button>
        ))}
      </div>

      <div className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-5 mb-6">
        <form
          onSubmit={(e) => {
            e.preventDefault();
            setPage(1);
            load();
          }}
          className="flex flex-wrap gap-4 items-end"
        >
          <div className="flex-1 min-w-[200px]">
            <label className="block text-xs text-safar-muted mb-1">Search</label>
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Title or location..."
              className="w-full border rounded px-3 py-2 text-sm"
            />
          </div>

          <div className="min-w-[140px]">
            <label className="block text-xs text-safar-muted mb-1">Type</label>
            <select
              value={filterType}
              onChange={(e) => {
                setFilterType(e.target.value);
                setPage(1);
              }}
              className="w-full border rounded px-3 py-2 text-sm"
            >
              <option value="all">All Types</option>
              <option value="tour">Tour</option>
              <option value="hotel">Hotel</option>
            </select>
          </div>

          <div className="min-w-[180px]">
            <label className="block text-xs text-safar-muted mb-1">Agency</label>
            <select
              value={filterAgency}
              onChange={(e) => {
                setFilterAgency(e.target.value);
                setPage(1);
              }}
              className="w-full border rounded px-3 py-2 text-sm"
            >
              <option value="all">All Agencies</option>
              {allAgencies.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.company_name}
                </option>
              ))}
            </select>
          </div>

          <div className="min-w-[180px]">
            <label className="block text-xs text-safar-muted mb-1">Sort By</label>
            <select
              value={sort}
              onChange={(e) => {
                setSort(e.target.value);
                setPage(1);
              }}
              className="w-full border rounded px-3 py-2 text-sm"
            >
              {sortOptions.map((s) => (
                <option key={s.value} value={s.value}>
                  {s.label}
                </option>
              ))}
            </select>
          </div>

          <button
            type="submit"
            className="bg-safar-primary text-white px-4 py-2 rounded-lg text-sm"
          >
            Filter
          </button>
        </form>
      </div>

      {showForm && (
        <div className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-6 mb-6">
          <h2 className="font-semibold text-safar-text mb-4">
            {form.id
              ? `Edit Package — ${form.title}`
              : `Create New ${form.type === "hotel" ? "Hotel" : "Tour"}`}
          </h2>

          {formError && (
            <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">
              {formError}
            </div>
          )}

          <form
            onSubmit={savePackage}
            className="grid grid-cols-1 md:grid-cols-2 gap-4"
          >
            <div>
              <label className="block text-xs text-safar-muted mb-1">
                Package Type
              </label>
              <select
                value={form.type}
                onChange={(e) => setForm({ ...form, type: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
              >
                <option value="tour">Tour Package</option>
                <option value="hotel">Hotel Listing</option>
              </select>
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">
                Assign to Agency
              </label>
              <select
                required
                value={form.agency_id}
                onChange={(e) =>
                  setForm({ ...form, agency_id: e.target.value })
                }
                className="w-full border rounded px-3 py-2 text-sm"
              >
                <option value="">-- Select Agency --</option>
                {verifiedAgencies.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.company_name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">
                Title
              </label>
              <input
                type="text"
                required
                value={form.title}
                onChange={(e) => setForm({ ...form, title: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">
                Location
              </label>
              <input
                type="text"
                required
                value={form.location}
                onChange={(e) =>
                  setForm({ ...form, location: e.target.value })
                }
                className="w-full border rounded px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">
                Price ($)
              </label>
              <input
                type="number"
                step="0.01"
                min="0"
                required
                value={form.price}
                onChange={(e) => setForm({ ...form, price: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">
                Image URL (Optional)
              </label>
              <input
                type="url"
                value={form.image_url}
                onChange={(e) =>
                  setForm({ ...form, image_url: e.target.value })
                }
                placeholder="https://..."
                className="w-full border rounded px-3 py-2 text-sm"
              />
            </div>

            <div className="md:col-span-2">
              <label className="block text-xs text-safar-muted mb-1">
                Description
              </label>
              <textarea
                required
                rows={4}
                value={form.description}
                onChange={(e) =>
                  setForm({ ...form, description: e.target.value })
                }
                className="w-full border rounded px-3 py-2 text-sm"
              />
            </div>

            <div className="md:col-span-2 flex gap-2">
              <button
                type="submit"
                disabled={saving}
                className="bg-safar-primary text-white px-4 py-2 rounded-lg text-sm disabled:opacity-60"
              >
                {saving
                  ? "Saving..."
                  : form.id
                  ? "Update Package"
                  : "Create Package"}
              </button>
              <button
                type="button"
                onClick={() => setShowForm(false)}
                className="border-2 border-safar-primary text-safar-primary px-4 py-2 rounded-lg text-sm"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-6">
        <h2 className="font-semibold mb-4">
          All Packages ({pagination.total ?? packages.length})
        </h2>

        {loading ? (
          <p className="text-safar-muted">Loading...</p>
        ) : packages.length === 0 ? (
          <p className="text-safar-muted">
            No packages found matching your filters.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left border-b">
                  <th className="py-2">Image</th>
                  <th className="py-2">Title</th>
                  <th className="py-2">Type</th>
                  <th className="py-2">Agency</th>
                  <th className="py-2">Location</th>
                  <th className="py-2">Price</th>
                  <th className="py-2">Status</th>
                  <th className="py-2">Rating</th>
                  <th className="py-2">Bookings</th>
                  <th className="py-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                {packages.map((pkg) => (
                  <tr key={pkg.id} className="border-b last:border-0">
                    <td className="py-2">
                      <div
                        className="w-14 h-10 rounded bg-gray-200 bg-cover bg-center"
                        style={
                          pkg.image_url
                            ? {
                                backgroundImage: `url(${pkg.image_url})`,
                              }
                            : {}
                        }
                      />
                    </td>
                    <td className="py-2">{pkg.title}</td>
                    <td className="py-2">
                      <span className="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 capitalize">
                        {pkg.type}
                      </span>
                    </td>
                    <td className="py-2">{pkg.company_name}</td>
                    <td className="py-2">{pkg.location}</td>
                    <td className="py-2">
                      ${Number(pkg.price).toFixed(2)}
                    </td>
                    <td className="py-2">
                      <span
                        className={`px-2 py-1 rounded text-xs font-medium capitalize ${
                          pkg.status === "approved"
                            ? "bg-green-100 text-green-800"
                            : pkg.status === "rejected"
                            ? "bg-rose-100 text-rose-800"
                            : "bg-amber-100 text-amber-800"
                        }`}
                      >
                        {pkg.status || "pending"}
                      </span>
                    </td>
                    <td className="py-2">
                      {Number(pkg.avg_rating) > 0
                        ? `${Number(pkg.avg_rating).toFixed(1)} ★`
                        : "—"}
                    </td>
                    <td className="py-2">{pkg.bookings_count}</td>
                    <td className="py-2">
                      <div className="flex gap-2 flex-wrap">
                        {pkg.status === "pending" && (
                          <>
                            <button
                              onClick={() => approvePackage(pkg)}
                              className="text-xs px-2 py-1 rounded bg-green-600 text-white"
                            >
                              Approve
                            </button>
                            <button
                              onClick={() => rejectPackage(pkg)}
                              className="text-xs px-2 py-1 rounded bg-amber-600 text-white"
                            >
                              Reject
                            </button>
                          </>
                        )}
                        <button
                          onClick={() => openEditForm(pkg)}
                          className="text-xs px-2 py-1 rounded border border-safar-primary text-safar-primary"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => deletePackage(pkg)}
                          className="text-xs px-2 py-1 rounded bg-rose-600 text-white"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {pagination.total_pages > 1 && (
          <div className="flex justify-center gap-2 mt-5">
            <button
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className="px-3 py-1 rounded border text-sm disabled:opacity-40"
            >
              Prev
            </button>
            <span className="text-sm text-safar-muted px-2 py-1">
              Page {pagination.page} of {pagination.total_pages}
            </span>
            <button
              disabled={page >= pagination.total_pages}
              onClick={() => setPage((p) => p + 1)}
              className="px-3 py-1 rounded border text-sm disabled:opacity-40"
            >
              Next
            </button>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}