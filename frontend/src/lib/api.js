const API_BASE = process.env.NEXT_PUBLIC_API_BASE;

export function getToken() {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("safar_token");
}

export function setToken(token) {
  localStorage.setItem("safar_token", token);
}

export function setUser(user) {
  localStorage.setItem("safar_user", JSON.stringify(user));
}

export function getUser() {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem("safar_user");
  return raw ? JSON.parse(raw) : null;
}

export function logout() {
  localStorage.removeItem("safar_token");
  localStorage.removeItem("safar_user");
}

export async function apiFetch(path, options = {}) {
  const token = getToken();

  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  });

  const data = await res.json();

  if (res.status === 401) {
    logout();
    if (typeof window !== "undefined") {
      window.location.href = "/login";
    }
    throw new Error(data.error || "Session expired. Please log in again.");
  }

  if (!res.ok) {
    throw new Error(data.error || "Something went wrong.");
  }

  return data;
}