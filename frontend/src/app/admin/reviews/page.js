"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

function Stars({ rating }) {
  return (
    <span className="text-safar-primary">
      {"★".repeat(rating)}
      <span className="text-gray-300">{"★".repeat(5 - rating)}</span>
    </span>
  );
}

export default function ReviewsPage() {
  const [reviews, setReviews] = useState([]);
  const [stats, setStats] = useState({});
  const [statusFilter, setStatusFilter] = useState("all");
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  async function load() {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({ status: statusFilter, search });
      const data = await apiFetch(`/admin/reviews.php?${params.toString()}`);
      setReviews(data.data);
      setStats(data.stats || {});
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

  async function toggleVisibility(r) {
    try {
      await apiFetch("/admin/reviews.php", {
        method: "POST",
        body: JSON.stringify({
          id: r.id,
          status: r.status === "visible" ? "hidden" : "visible",
        }),
      });
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  const statCards = [
    { label: "Total Reviews", value: stats.total_count ?? "—" },
    { label: "Visible", value: stats.visible_count ?? "—" },
    { label: "Hidden", value: stats.hidden_count ?? "—" },
    {
      label: "Average Rating",
      value:
        stats.average_rating != null
          ? `${stats.average_rating} ★`
          : "—",
    },
  ];

  const tabs = ["all", "visible", "hidden"];

  return (
    <AdminLayout title="Reviews & Ratings">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">
          {error}
        </div>
      )}

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {statCards.map((card) => (
          <div
            key={card.label}
            className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-4 text-center"
          >
            <p className="text-xs text-safar-muted mb-1">{card.label}</p>
            <p className="text-xl font-bold text-safar-primary">
              {card.value}
            </p>
          </div>
        ))}
      </div>

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
        <button
          type="submit"
          className="bg-safar-primary text-white px-4 py-2 rounded-lg"
        >
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

      <div className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-6">
        {loading ? (
          <p className="text-safar-muted">Loading...</p>
        ) : reviews.length === 0 ? (
          <p className="text-safar-muted">No reviews found.</p>
        ) : (
          <div className="space-y-4">
            {reviews.map((r) => (
              <div key={r.id} className="border rounded-lg p-4">
                <div className="flex justify-between items-start flex-wrap gap-3">
                  <div>
                    <p className="font-semibold">
                      {r.traveler_name} — {r.package_title}
                    </p>
                    <Stars rating={Number(r.rating)} />
                    {r.comment && (
                      <p className="text-sm text-safar-muted mt-1">
                        {r.comment}
                      </p>
                    )}
                    <p className="text-xs text-safar-muted mt-1">
                      {new Date(r.created_at).toLocaleDateString()}
                    </p>
                  </div>
                  <div className="flex flex-col items-end gap-2">
                    <span
                      className={`px-2 py-1 rounded text-xs font-medium h-fit ${
                        r.status === "visible"
                          ? "bg-green-200 text-green-800"
                          : "bg-gray-200 text-gray-800"
                      }`}
                    >
                      {r.status}
                    </span>
                    <button
                      onClick={() => toggleVisibility(r)}
                      className="text-xs text-safar-secondary underline"
                    >
                      {r.status === "visible" ? "Hide" : "Restore"}
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </AdminLayout>
  );
}