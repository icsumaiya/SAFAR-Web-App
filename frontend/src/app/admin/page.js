"use client";

import { useEffect, useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { apiFetch } from "@/lib/api";
import {
  ResponsiveContainer,
  LineChart,
  Line,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
} from "recharts";

const COLORS = ["#FF7D4B", "#009688", "#E66A3D", "#3B82F6", "#A855F7"];

export default function AdminDashboardPage() {
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiFetch("/admin/analytics.php")
      .then(setData)
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <AdminLayout title="Dashboard">
        <p className="text-safar-muted">Loading...</p>
      </AdminLayout>
    );
  }

  if (error) {
    return (
      <AdminLayout title="Dashboard">
        <div className="bg-red-100 text-red-700 text-sm p-3 rounded">
          {error}
        </div>
      </AdminLayout>
    );
  }

  const { summary, charts } = data;

  const statCards = [
    { label: "Total Users", value: summary.total_users },
    { label: "Total Agencies", value: summary.total_agencies },
    { label: "Verified Agencies", value: summary.verified_agencies },
    { label: "Total Packages", value: summary.total_packages },
    { label: "Total Hotels", value: summary.total_hotels },
    { label: "Total Bookings", value: summary.total_bookings },
    { label: "Pending Bookings", value: summary.pending_bookings },
    { label: "Confirmed Bookings", value: summary.confirmed_bookings },
    { label: "Cancelled Bookings", value: summary.cancelled_bookings },
    {
      label: "Total Revenue",
      value: `$${Number(summary.total_revenue).toFixed(2)}`,
    },
    {
      label: "Platform Commission",
      value: `$${Number(summary.platform_commission).toFixed(2)}`,
    },
    {
      label: "Agency Earnings",
      value: `$${Number(summary.agency_earnings).toFixed(2)}`,
    },
    { label: "Successful Payments", value: summary.successful_payments },
    { label: "Pending Payments", value: summary.pending_payments },
  ];

  return (
    <AdminLayout title="Admin Dashboard">
      {/* Stat cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {statCards.map((c) => (
          <div
            key={c.label}
            className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-4 text-center"
          >
            <p className="text-xs text-safar-muted mb-1">{c.label}</p>
            <p className="text-lg font-bold text-safar-primary">{c.value}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Monthly bookings */}
        <ChartCard title="Monthly Bookings">
          <ResponsiveContainer width="100%" height={250}>
            <LineChart data={charts.monthly_bookings}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" fontSize={12} />
              <YAxis fontSize={12} />
              <Tooltip />
              <Line
                type="monotone"
                dataKey="total"
                stroke="#FF7D4B"
                strokeWidth={2}
              />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>

        {/* Monthly revenue */}
        <ChartCard title="Monthly Revenue">
          <ResponsiveContainer width="100%" height={250}>
            <LineChart data={charts.monthly_revenue}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" fontSize={12} />
              <YAxis fontSize={12} />
              <Tooltip />
              <Line
                type="monotone"
                dataKey="total"
                stroke="#009688"
                strokeWidth={2}
              />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>

        {/* Booking status distribution */}
        <ChartCard title="Booking Status Distribution">
          <ResponsiveContainer width="100%" height={250}>
            <PieChart>
              <Pie
                data={charts.booking_status_distribution}
                dataKey="total"
                nameKey="status"
                cx="50%"
                cy="50%"
                outerRadius={80}
                label
              >
                {charts.booking_status_distribution.map((_, i) => (
                  <Cell key={i} fill={COLORS[i % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </ChartCard>

        {/* User growth */}
        <ChartCard title="User Growth">
          <ResponsiveContainer width="100%" height={250}>
            <BarChart data={charts.user_growth}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" fontSize={12} />
              <YAxis fontSize={12} />
              <Tooltip />
              <Bar dataKey="total" fill="#FF7D4B" />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>

        {/* Popular packages */}
        <ChartCard title="Popular Packages">
          <ResponsiveContainer width="100%" height={250}>
            <BarChart data={charts.popular_packages} layout="vertical">
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis type="number" fontSize={12} />
              <YAxis
                dataKey="title"
                type="category"
                width={100}
                fontSize={11}
              />
              <Tooltip />
              <Bar dataKey="bookings_count" fill="#009688" />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>

        {/* Popular destinations */}
        <ChartCard title="Popular Destinations">
          <ResponsiveContainer width="100%" height={250}>
            <BarChart data={charts.popular_destinations} layout="vertical">
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis type="number" fontSize={12} />
              <YAxis
                dataKey="location"
                type="category"
                width={100}
                fontSize={11}
              />
              <Tooltip />
              <Bar dataKey="bookings_count" fill="#E66A3D" />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>

        {/* Top agencies */}
        <ChartCard title="Top Agencies by Revenue">
          <ResponsiveContainer width="100%" height={250}>
            <BarChart data={charts.top_agencies} layout="vertical">
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis type="number" fontSize={12} />
              <YAxis
                dataKey="company_name"
                type="category"
                width={100}
                fontSize={11}
              />
              <Tooltip />
              <Bar dataKey="revenue" fill="#3B82F6" />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>

        {/* Revenue trend */}
        <ChartCard title="Revenue Trend (30 days)">
          <ResponsiveContainer width="100%" height={250}>
            <LineChart data={charts.revenue_trend}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="day" fontSize={10} />
              <YAxis fontSize={12} />
              <Tooltip />
              <Line
                type="monotone"
                dataKey="total"
                stroke="#A855F7"
                strokeWidth={2}
              />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>
    </AdminLayout>
  );
}

function ChartCard({ title, children }) {
  return (
    <div className="bg-safar-card rounded-xl shadow-sm border border-black/5 p-5">
      <h2 className="font-semibold mb-3">{title}</h2>
      {children}
    </div>
  );
}