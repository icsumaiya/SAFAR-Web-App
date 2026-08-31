"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

const badgeClass = {
  verified: "bg-green-200 text-green-800",
  rejected: "bg-rose-200 text-rose-800",
  suspended: "bg-rose-200 text-rose-800",
  pending: "bg-yellow-200 text-yellow-800",
};

const emptyForm = {
  logo_url: "",
  cover_image_url: "",
  description: "",
  address: "",
  website: "",
  facebook_url: "",
  instagram_url: "",
};

export default function AgencyProfilePage() {
  const { id } = useParams();
  const router = useRouter();

  const [profile, setProfile] = useState(null);
  const [form, setForm] = useState(emptyForm);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState("");

  async function load() {
    setLoading(true);
    setError("");
    try {
      const data = await apiFetch(`/admin/agency-profile.php?agency_id=${id}`);
      setProfile(data);
      setForm({
        logo_url: data.agency.logo_url || "",
        cover_image_url: data.agency.cover_image_url || "",
        description: data.agency.description || "",
        address: data.agency.address || "",
        website: data.agency.website || "",
        facebook_url: data.agency.facebook_url || "",
        instagram_url: data.agency.instagram_url || "",
      });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (id) load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  async function saveProfile(e) {
    e.preventDefault();
    setSaving(true);
    setSaveError("");
    try {
      await apiFetch("/admin/agency-profile.php", {
        method: "POST",
        body: JSON.stringify({ agency_id: Number(id), ...form }),
      });
      setEditing(false);
      load();
    } catch (err) {
      setSaveError(err.message);
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return (
      <AdminLayout title="Agency Profile">
        <p className="text-safar-muted">Loading...</p>
      </AdminLayout>
    );
  }

  if (error || !profile) {
    return (
      <AdminLayout title="Agency Profile">
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded">
          {error || "Agency not found."}
        </div>
      </AdminLayout>
    );
  }

  const { agency, booking_stats, package_count, revenue, rating } = profile;

  return (
    <AdminLayout title={agency.company_name}>
      <button
        onClick={() => router.push("/admin/agencies")}
        className="text-sm text-safar-primary mb-4 hover:underline"
      >
        <i className="fas fa-arrow-left mr-1"></i> Back to all agencies
      </button>

      {/* Cover + logo header */}
      <div className="bg-white rounded-xl shadow-sm border border-black/5 overflow-hidden mb-6">
        <div
          className="h-40 bg-safar-secondary/20 bg-cover bg-center"
          style={agency.cover_image_url ? { backgroundImage: `url(${agency.cover_image_url})` } : {}}
        />
        <div className="p-6 flex flex-wrap gap-4 items-center -mt-12">
          <div className="w-24 h-24 rounded-xl bg-white border-4 border-white shadow-sm overflow-hidden shrink-0">
            {agency.logo_url ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={agency.logo_url} alt={agency.company_name} className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full flex items-center justify-center bg-safar-bg text-safar-primary text-2xl font-bold">
                {agency.company_name?.charAt(0) || "?"}
              </div>
            )}
          </div>

          <div className="flex-1 min-w-[200px]">
            <div className="flex items-center gap-2 flex-wrap">
              <h2 className="text-xl font-bold text-safar-text">{agency.company_name}</h2>
              <span className={`px-2 py-1 rounded text-xs font-medium ${badgeClass[agency.status] || badgeClass.pending}`}>
                {agency.status}
              </span>
            </div>
            <p className="text-sm text-safar-muted">{agency.name} · {agency.email}</p>
            {agency.address && <p className="text-sm text-safar-muted mt-1"><i className="fas fa-location-dot mr-1"></i>{agency.address}</p>}
          </div>

          <button
            onClick={() => setEditing((v) => !v)}
            className="border-2 border-safar-primary text-safar-primary px-4 py-2 rounded-lg text-sm shrink-0"
          >
            {editing ? "Cancel" : "Edit Profile"}
          </button>
        </div>

        {agency.description && !editing && (
          <p className="px-6 pb-6 text-sm text-safar-text">{agency.description}</p>
        )}

        {(agency.website || agency.facebook_url || agency.instagram_url) && !editing && (
          <div className="px-6 pb-6 flex gap-4 text-sm text-safar-primary flex-wrap">
            {agency.website && <a href={agency.website} target="_blank" rel="noreferrer"><i className="fas fa-globe mr-1"></i>Website</a>}
            {agency.facebook_url && <a href={agency.facebook_url} target="_blank" rel="noreferrer"><i className="fab fa-facebook mr-1"></i>Facebook</a>}
            {agency.instagram_url && <a href={agency.instagram_url} target="_blank" rel="noreferrer"><i className="fab fa-instagram mr-1"></i>Instagram</a>}
          </div>
        )}
      </div>

      {/* Edit form */}
      {editing && (
        <div className="bg-white rounded-xl shadow-sm border border-black/5 p-6 mb-6">
          <h3 className="font-semibold mb-4">Edit Profile</h3>

          {saveError && (
            <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">{saveError}</div>
          )}

          <form onSubmit={saveProfile} className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs text-safar-muted mb-1">Logo URL</label>
              <input
                type="text"
                value={form.logo_url}
                onChange={(e) => setForm({ ...form, logo_url: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
                placeholder="https://..."
              />
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">Cover Image URL</label>
              <input
                type="text"
                value={form.cover_image_url}
                onChange={(e) => setForm({ ...form, cover_image_url: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
                placeholder="https://..."
              />
            </div>

            <div className="md:col-span-2">
              <label className="block text-xs text-safar-muted mb-1">Description</label>
              <textarea
                value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })}
                rows={4}
                maxLength={2000}
                className="w-full border rounded px-3 py-2 text-sm"
                placeholder="What does this agency do..."
              />
            </div>

            <div className="md:col-span-2">
              <label className="block text-xs text-safar-muted mb-1">Address</label>
              <input
                type="text"
                value={form.address}
                onChange={(e) => setForm({ ...form, address: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
                maxLength={255}
              />
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">Website</label>
              <input
                type="text"
                value={form.website}
                onChange={(e) => setForm({ ...form, website: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
                placeholder="https://..."
              />
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">Facebook URL</label>
              <input
                type="text"
                value={form.facebook_url}
                onChange={(e) => setForm({ ...form, facebook_url: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
                placeholder="https://facebook.com/..."
              />
            </div>

            <div>
              <label className="block text-xs text-safar-muted mb-1">Instagram URL</label>
              <input
                type="text"
                value={form.instagram_url}
                onChange={(e) => setForm({ ...form, instagram_url: e.target.value })}
                className="w-full border rounded px-3 py-2 text-sm"
                placeholder="https://instagram.com/..."
              />
            </div>

            <div className="md:col-span-2">
              <button
                type="submit"
                disabled={saving}
                className="bg-safar-primary text-white px-4 py-2 rounded-lg text-sm disabled:opacity-60"
              >
                {saving ? "Saving..." : "Save Profile"}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Stat cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {[
          { label: "Packages", value: package_count },
          { label: "Bookings", value: booking_stats.total },
          { label: "Revenue", value: `$${Number(revenue).toFixed(2)}` },
          { label: "Rating", value: rating.review_count > 0 ? `${rating.average_rating} ★ (${rating.review_count})` : "No reviews yet" },
        ].map((card) => (
          <div key={card.label} className="bg-white rounded-xl shadow-sm border border-black/5 p-4 text-center">
            <p className="text-xs text-safar-muted mb-1">{card.label}</p>
            <p className="text-lg font-bold text-safar-primary">{card.value}</p>
          </div>
        ))}
      </div>

      {/* Booking status breakdown */}
      <div className="bg-white rounded-xl shadow-sm border border-black/5 p-6">
        <h3 className="font-semibold mb-4">Booking Status Breakdown</h3>
        <div className="grid grid-cols-3 gap-4 text-center">
          <div>
            <p className="text-xs text-safar-muted">Pending</p>
            <p className="text-lg font-bold text-yellow-600">{booking_stats.pending}</p>
          </div>
          <div>
            <p className="text-xs text-safar-muted">Approved</p>
            <p className="text-lg font-bold text-green-600">{booking_stats.approved}</p>
          </div>
          <div>
            <p className="text-xs text-safar-muted">Rejected</p>
            <p className="text-lg font-bold text-rose-600">{booking_stats.rejected}</p>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}