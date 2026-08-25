"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getToken, getUser } from "@/lib/api";
import AdminSidebar from "./AdminSidebar";

export default function AdminLayout({ title, children }) {
  const router = useRouter();
  const [checked, setChecked] = useState(false);

  useEffect(() => {
    const token = getToken();
    const user = getUser();

    if (!token || !user || user.role !== "admin") {
      router.push("/login");
      return;
    }

    setChecked(true);
  }, [router]);

  if (!checked) {
    return <div className="p-10 text-safar-muted">Checking access...</div>;
  }

  return (
    <div className="min-h-screen bg-safar-bg">
      <div className="max-w-6xl mx-auto py-8 px-4 flex gap-8 items-start">
        <AdminSidebar />
        <div className="flex-1 min-w-0 w-full">
          <h1 className="text-2xl font-bold mb-6 text-safar-text">{title}</h1>
          {children}
        </div>
      </div>
    </div>
  );
}