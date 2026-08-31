"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { getToken, getUser } from "@/lib/api";

export default function Home() {
  const router = useRouter();

  useEffect(() => {
    const token = getToken();
    const user = getUser();

    if (token && user?.role === "admin") {
      router.replace("/admin");
    } else {
      router.replace("/login");
    }
  }, [router]);

  return <div className="min-h-screen flex items-center justify-center text-safar-muted">Loading...</div>;
}