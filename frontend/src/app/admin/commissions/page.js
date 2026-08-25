"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";

export default function CommissionsPage() {
  const [data, setData] = useState(null);
  const [search, setSearch] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);
  const [percentageDraft, setPercentageDraft] = useState("");
  const [savingPercentage, setSavingPercentage] = useState(false);

  async function load() {
    setLoading(true);
    setError("");
    try {
      const params = new URLSearchParams({ search });
      const result = await apiFetch(`/admin/commissions.php?${params.toString()}`);
      setData(result);
      setPercentageDraft(String(result.commission_percentage));
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function savePercentage(e) {
    e.preventDefault();
    setSavingPercentage(true);
    setError("");
    try {
      await apiFetch("/admin/commissions.php", {
        method: "POST",
        body: JSON.stringify({ commission_percentage: percentageDraft }),
      });
      load();
    } catch (err) {
      setError(err.message);
    } finally {
      setSavingPercentage(false);
    }
  }

  const summary = data?.summary || {};
  const statCards = [
    { label: "Total Sales", value: `$${Number(summary.total_sales || 0).toFixed(2)}` },
    { label: "Platform Commission", value: `$${Number(summary.total_commission || 0).toFixed(2)}` },
    { label: "Agency Earnings", value: `$${Number(summary.total_agency_earnings || 0).toFixed(2)}` },
  ];

  return (
    <AdminLayout title="Commission & Revenue">
      {error && (
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded mb-4">{error}</div>
      )}

      {loading ? (
        <p className="text-safar-muted">Loading...</p>
      ) : (
        <>
          {data?.synced > 0 && (
            <div className="bg-green-100 text-green-800 text-sm p-3 rounded mb-4">
              {data.synced} new successful payment(s) processed into commission records.
            </div>
          )}

          {/* Stat cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {statCards.map((card) => (
              <div key={card.label} className="bg-white rounded-xl shadow-sm border border-black/5 p-5 text-center">
                <p className="text-xs text-safar-muted mb-1">{card.label}</p>
                <p className="text-xl font-bold text-safar-primary">{card.value}</p>
              </div>
            ))}
          </div>

          {/* Commission percentage settings */}
          <div className="bg-white rounded-xl shadow-sm border border-black/5 p-5 mb-6">
            <h2 className="font-semibold mb-3">Platform Commission Rate</h2>
            <form onSubmit={savePercentage} className="flex gap-2 items-center">
              <input
                type="number"
                step="0.01"
                min="0"
                max="100"
                value={percentageDraft}
                onChange={(e) => setPercentageDraft(e.target.value)}
                className="border rounded px-3 py-2 w-32"
              />
              <span className="text-safar-muted">%</span>
              <button
                type="submit"
                disabled={savingPercentage}
                className="bg-safar-primary text-white px-4 py-2 rounded-lg disabled:opacity-50"
              >
                {savingPercentage ? "Saving..." : "Save"}
              </button>
            </form>
            <p className="text-xs text-safar-muted mt-2">
              Applies to new commission records going forward — past records keep the rate that was active when they were created.
            </p>
          </div>

          {/* By agency */}
          <div className="bg-white rounded-xl shadow-sm border border-black/5 p-5 mb-6">
            <h2 className="font-semibold mb-3">Revenue by Agency</h2>
            {data?.by_agency?.length ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left border-b">
                    <th className="py-2">Agency</th>
                    <th className="py-2">Gross</th>
                    <th className="py-2">Commission</th>
                    <th className="py-2">Agency Earning</th>
                  </tr>
                </thead>
                <tbody>
                  {data.by_agency.map((row) => (
                    <tr key={row.company_name} className="border-b last:border-0">
                      <td className="py-2">{row.company_name}</td>
                      <td className="py-2">${Number(row.gross).toFixed(2)}</td>
                      <td className="py-2">${Number(row.commission).toFixed(2)}</td>
                      <td className="py-2">${Number(row.earning).toFixed(2)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <p className="text-safar-muted text-sm">No commission data yet.</p>
            )}
          </div>

          {/* Commission history */}
          <div className="bg-white rounded-xl shadow-sm border border-black/5 p-6">
            <div className="flex justify-between items-center mb-4 flex-wrap gap-3">
              <h2 className="font-semibold">Commission History</h2>
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
                  placeholder="Search by agency..."
                  className="border rounded px-3 py-2 text-sm"
                />
                <button type="submit" className="bg-safar-primary text-white px-4 py-2 rounded-lg text-sm">
                  Search
                </button>
              </form>
            </div>

            {data?.data?.length ? (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left border-b">
                      <th className="py-2">Agency</th>
                      <th className="py-2">Package</th>
                      <th className="py-2">Gross</th>
                      <th className="py-2">Rate</th>
                      <th className="py-2">Commission</th>
                      <th className="py-2">Agency Earning</th>
                      <th className="py-2">Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.data.map((row) => (
                      <tr key={row.id} className="border-b last:border-0">
                        <td className="py-2">{row.company_name}</td>
                        <td className="py-2">{row.package_title}</td>
                        <td className="py-2">${Number(row.gross_amount).toFixed(2)}</td>
                        <td className="py-2">{Number(row.commission_percentage).toFixed(2)}%</td>
                        <td className="py-2">${Number(row.commission_amount).toFixed(2)}</td>
                        <td className="py-2">${Number(row.agency_earning).toFixed(2)}</td>
                        <td className="py-2">{new Date(row.created_at).toLocaleDateString()}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <p className="text-safar-muted text-sm">No commission records yet — these are created automatically once a payment is marked successful.</p>
            )}
          </div>
        </>
      )}
    </AdminLayout>
  );
}