"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

const emptyForm = {
  id: null,
  code: "",
  discount_type: "percentage",
  discount_value: "",
  min_booking_amount: "",
  max_discount_amount: "",
  start_date: "",
  expiry_date: "",
  usage_limit: "",
  per_user_limit: "",
};

export default function CouponsPage() {
  const [items, setItems] = useState([]);
  const [statusFilter, setStatusFilter] = useState("all");
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState(emptyForm);

  async function load() {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({ status: statusFilter, search });
      const data = await apiFetch(`/admin/coupons.php?${params.toString()}`);
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

  function openCreate() {
    setForm(emptyForm);
    setShowForm(true);
  }

  function openEdit(c) {
    setForm({
      id: c.id,
      code: c.code,
      discount_type: c.discount_type,
      discount_value: c.discount_value,
      min_booking_amount: c.min_booking_amount ?? "",
      max_discount_amount: c.max_discount_amount ?? "",
      start_date: c.start_date ? c.start_date.slice(0, 10) : "",
      expiry_date: c.expiry_date ? c.expiry_date.slice(0, 10) : "",
      usage_limit: c.usage_limit ?? "",
      per_user_limit: c.per_user_limit ?? "",
    });
    setShowForm(true);
  }

  async function saveCoupon(e) {
    e.preventDefault();
    setError("");
    try {
      await apiFetch("/admin/coupons.php", {
        method: "POST",
        body: JSON.stringify({
          ...form,
          action: form.id ? "update" : "create",
        }),
      });
      setShowForm(false);
      setForm(emptyForm);
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function toggleActive(c) {
    try {
      await apiFetch("/admin/coupons.php", {
        method: "POST",
        body: JSON.stringify({
          id: c.id,
          action: c.is_active ? "deactivate" : "activate",
        }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function deleteCoupon(id) {
    if (!confirm("Delete this coupon? This cannot be undone.")) return;
    try {
      await apiFetch("/admin/coupons.php", {
        method: "POST",
        body: JSON.stringify({ id, action: "delete" }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  const tabs = ["all", "active", "inactive"];

  return (
    <AdminLayout title="Coupons & Discounts">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">
          {error}
        </div>
      )}

      <div className="flex justify-between items-center mb-4 flex-wrap gap-3">
        <form
          onSubmit={(e) => {
            e.preventDefault();
            load();
          }}
          className="flex gap-2"
        >
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by coupon code..."
            className="flex-1 max-w-sm border rounded px-3 py-2"
          />
          <button
            type="submit"
            className="bg-safar-primary text-white px-4 py-2 rounded-lg"
          >
            Search
          </button>
        </form>

        <button
          onClick={openCreate}
          className="bg-safar-secondary text-white px-4 py-2 rounded-lg text-sm"
        >
          <i className="fas fa-plus mr-1"></i> New Coupon
        </button>
      </div>

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

      {showForm && (
        <div className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-6 mb-6">
          <h2 className="font-semibold mb-4">
            {form.id ? "Edit Coupon" : "New Coupon"}
          </h2>
          <form
            onSubmit={saveCoupon}
            className="grid grid-cols-1 md:grid-cols-2 gap-4"
          >
            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Coupon Code
              </label>
              <input
                type="text"
                value={form.code}
                onChange={(e) => setForm({ ...form, code: e.target.value })}
                className="w-full border rounded px-3 py-2"
                placeholder="e.g. SUMMER20"
                required
              />
            </div>

            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Discount Type
              </label>
              <select
                value={form.discount_type}
                onChange={(e) =>
                  setForm({ ...form, discount_type: e.target.value })
                }
                className="w-full border rounded px-3 py-2"
              >
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed Amount</option>
              </select>
            </div>

            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Discount Value{" "}
                {form.discount_type === "percentage" ? "(%)" : "($)"}
              </label>
              <input
                type="number"
                step="0.01"
                min="0"
                value={form.discount_value}
                onChange={(e) =>
                  setForm({ ...form, discount_value: e.target.value })
                }
                className="w-full border rounded px-3 py-2"
                required
              />
            </div>

            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Min Booking Amount
              </label>
              <input
                type="number"
                step="0.01"
                min="0"
                value={form.min_booking_amount}
                onChange={(e) =>
                  setForm({ ...form, min_booking_amount: e.target.value })
                }
                className="w-full border rounded px-3 py-2"
                placeholder="0 (no minimum)"
              />
            </div>

            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Max Discount Amount
              </label>
              <input
                type="number"
                step="0.01"
                min="0"
                value={form.max_discount_amount}
                onChange={(e) =>
                  setForm({ ...form, max_discount_amount: e.target.value })
                }
                className="w-full border rounded px-3 py-2"
                placeholder="No cap"
              />
            </div>

            <div></div>

            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Start Date
              </label>
              <input
                type="date"
                value={form.start_date}
                onChange={(e) =>
                  setForm({ ...form, start_date: e.target.value })
                }
                className="w-full border rounded px-3 py-2"
                required
              />
            </div>

            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Expiry Date
              </label>
              <input
                type="date"
                value={form.expiry_date}
                onChange={(e) =>
                  setForm({ ...form, expiry_date: e.target.value })
                }
                className="w-full border rounded px-3 py-2"
                required
              />
            </div>

            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Total Usage Limit
              </label>
              <input
                type="number"
                min="1"
                step="1"
                value={form.usage_limit}
                onChange={(e) =>
                  setForm({ ...form, usage_limit: e.target.value })
                }
                className="w-full border rounded px-3 py-2"
                placeholder="Unlimited"
              />
            </div>

            <div>
              <label className="block text-sm text-safar-muted mb-1">
                Per-User Limit
              </label>
              <input
                type="number"
                min="1"
                step="1"
                value={form.per_user_limit}
                onChange={(e) =>
                  setForm({ ...form, per_user_limit: e.target.value })
                }
                className="w-full border rounded px-3 py-2"
                placeholder="Unlimited"
              />
            </div>

            <div className="md:col-span-2 flex gap-2 mt-2">
              <button
                type="submit"
                className="bg-safar-primary text-white px-4 py-2 rounded-lg"
              >
                {form.id ? "Save Changes" : "Create Coupon"}
              </button>
              <button
                type="button"
                onClick={() => setShowForm(false)}
                className="border-2 border-safar-primary text-safar-primary px-4 py-2 rounded-lg"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-6">
        {loading ? (
          <p className="text-safar-muted">Loading...</p>
        ) : items.length === 0 ? (
          <p className="text-safar-muted">No coupons found.</p>
        ) : (
          <div className="space-y-4">
            {items.map((c) => (
              <div key={c.id} className="border rounded-lg p-4">
                <div className="flex justify-between items-start flex-wrap gap-3">
                  <div>
                    <p className="font-semibold">
                      {c.code}{" "}
                      <span className="text-safar-muted font-normal text-sm">
                        —{" "}
                        {c.discount_type === "percentage"
                          ? `${c.discount_value}% off`
                          : `$${c.discount_value} off`}
                      </span>
                    </p>
                    <p className="text-sm text-safar-muted mt-1">
                      Valid {c.start_date?.slice(0, 10)} to{" "}
                      {c.expiry_date?.slice(0, 10)}
                      {c.min_booking_amount
                        ? ` · Min booking $${c.min_booking_amount}`
                        : ""}
                      {c.max_discount_amount
                        ? ` · Max discount $${c.max_discount_amount}`
                        : ""}
                    </p>
                    <p className="text-xs text-safar-muted mt-1">
                      Usage limit: {c.usage_limit ?? "Unlimited"} total
                      {c.per_user_limit
                        ? `, ${c.per_user_limit} per user`
                        : ""}
                    </p>
                  </div>
                  <span
                    className={`px-2 py-1 rounded text-xs font-medium h-fit ${
                      c.is_active
                        ? "bg-green-200 text-green-800"
                        : "bg-gray-200 text-gray-800"
                    }`}
                  >
                    {c.is_active ? "Active" : "Inactive"}
                  </span>
                </div>

                <div className="flex gap-2 items-center mt-3 flex-wrap">
                  <button
                    onClick={() => openEdit(c)}
                    className="border-2 border-safar-primary text-safar-primary px-3 py-1 rounded text-xs"
                  >
                    Edit
                  </button>
                  <button
                    onClick={() => toggleActive(c)}
                    className={`px-3 py-1 rounded text-xs text-white ${
                      c.is_active ? "bg-gray-500" : "bg-green-600"
                    }`}
                  >
                    {c.is_active ? "Deactivate" : "Activate"}
                  </button>
                  <button
                    onClick={() => deleteCoupon(c.id)}
                    className="bg-rose-600 text-white px-3 py-1 rounded text-xs"
                  >
                    Delete
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </AdminLayout>
  );
}