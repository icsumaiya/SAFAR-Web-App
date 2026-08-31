"use client";

import { useEffect, useState } from "react";

export default function ThemeToggle() {
  const [isDark, setIsDark] = useState(false);

  useEffect(() => {
    const saved = localStorage.getItem("safar_theme");
    const shouldBeDark = saved === "dark";
    setIsDark(shouldBeDark);
    document.documentElement.classList.toggle("dark", shouldBeDark);
  }, []);

  function toggleTheme() {
    const next = !isDark;
    setIsDark(next);
    document.documentElement.classList.toggle("dark", next);
    localStorage.setItem("safar_theme", next ? "dark" : "light");
  }

  return (
    <button
      onClick={toggleTheme}
      className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-safar-text hover:bg-safar-bg transition"
      title={isDark ? "Switch to light mode" : "Switch to dark mode"}
    >
      <i className={`fas ${isDark ? "fa-sun" : "fa-moon"}`}></i>
      {isDark ? "Light Mode" : "Dark Mode"}
    </button>
  );
}