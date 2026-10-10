import axios from "axios";

/**
 * Shape of a failed API call, normalised so call sites can react to *why* it
 * failed instead of just printing whatever string came back (or a hardcoded
 * fallback that hides the real reason).
 */
export type ApiErrorType =
  | "upgrade_required" // 403, tenant plan cap reached
  | "validation" // 422
  | "unauthenticated" // 401
  | "forbidden" // other 403
  | "timeout" // request aborted client-side
  | "network" // never reached the server
  | "server" // 5xx
  | "unknown";

export interface ApiErrorInfo {
  type: ApiErrorType;
  status?: number;
  message: string;
  /** Only set when type === "upgrade_required". */
  limitKey?: string;
  limit?: number;
  used?: number;
  /** Only set when type === "validation". */
  fieldErrors?: Record<string, string[]>;
}

interface ApiErrorBody {
  status?: string;
  error?: string;
  message?: string;
  error_type?: string;
  errors?: Record<string, string[]>;
  data?: {
    upgrade_required?: boolean;
    limit_key?: string;
    limit?: number;
    used?: number;
  };
}

const DEFAULT_FALLBACK = "Something went wrong. Please try again.";

export function parseApiError(err: unknown, fallback: string = DEFAULT_FALLBACK): ApiErrorInfo {
  if (!axios.isAxiosError(err)) {
    return {
      type: "unknown",
      message: err instanceof Error && err.message ? err.message : fallback,
    };
  }

  // Tenants with an expired subscription are deliberately stalled server-side,
  // which outlasts the production axios timeout. Say the request timed out
  // rather than blaming whatever the user just clicked.
  if (err.code === "ECONNABORTED" || /timeout/i.test(err.message)) {
    return {
      type: "timeout",
      message: "The server took too long to respond. Please try again in a moment.",
    };
  }

  if (!err.response) {
    return {
      type: "network",
      message: "We couldn't reach the server. Check your connection and try again.",
    };
  }

  const status = err.response.status;
  const body = (err.response.data ?? {}) as ApiErrorBody;
  const serverMessage = body.message || body.error;

  if (status === 401) {
    // axios.ts already clears the session and redirects on 401 -- don't do it twice.
    return {
      type: "unauthenticated",
      status,
      message: serverMessage || "Your session has expired. Please sign in again.",
    };
  }

  if (status === 403 && (body.error_type === "upgrade_required" || body.data?.upgrade_required)) {
    return {
      type: "upgrade_required",
      status,
      message: serverMessage || "This action isn't available on your current plan.",
      limitKey: body.data?.limit_key,
      limit: typeof body.data?.limit === "number" ? body.data.limit : undefined,
      used: typeof body.data?.used === "number" ? body.data.used : undefined,
    };
  }

  if (status === 403) {
    return {
      type: "forbidden",
      status,
      message: serverMessage || "You don't have permission to do that.",
    };
  }

  if (status === 422) {
    const first = body.errors ? Object.values(body.errors)[0]?.[0] : undefined;
    return {
      type: "validation",
      status,
      message: first || serverMessage || "Please check the details and try again.",
      fieldErrors: body.errors,
    };
  }

  if (status >= 500) {
    return {
      type: "server",
      status,
      message: "The server ran into a problem. Please try again shortly.",
    };
  }

  return { type: "unknown", status, message: serverMessage || fallback };
}

/** For call sites that only need a string to toast. */
export function getApiErrorMessage(err: unknown, fallback?: string): string {
  return parseApiError(err, fallback).message;
}
