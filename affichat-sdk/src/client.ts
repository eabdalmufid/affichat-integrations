import { AffiChatConfig } from "./types/index.js";
import {
  AffiChatError,
  AuthenticationError,
  ForbiddenError,
  NotFoundError,
  QuotaExceededError,
  ValidationError,
} from "./errors.js";

export class HttpClient {
  private readonly apiKey: string;
  private readonly baseUrl: string;
  private readonly timeoutMs: number;

  constructor(config: AffiChatConfig) {
    if (!config.apiKey || typeof config.apiKey !== "string") {
      throw new ValidationError("AffiChat apiKey is required and must be a string");
    }

    this.apiKey = config.apiKey.trim();
    this.baseUrl = (config.baseUrl || "https://chat.affidev.com").replace(/\/+$/, "");
    this.timeoutMs = config.timeoutMs || 30000;
  }

  public async request<T = unknown>(
    method: "GET" | "POST" | "PUT" | "DELETE",
    path: string,
    options: {
      body?: unknown;
      params?: Record<string, string | number | boolean | undefined>;
    } = {}
  ): Promise<T> {
    const url = new URL(path.startsWith("/") ? `${this.baseUrl}${path}` : `${this.baseUrl}/${path}`);

    if (options.params) {
      for (const [key, val] of Object.entries(options.params)) {
        if (val !== undefined && val !== null) {
          url.searchParams.set(key, String(val));
        }
      }
    }

    const headers: Record<string, string> = {
      "x-api-key": this.apiKey,
      Accept: "application/json",
    };

    if (options.body !== undefined) {
      headers["Content-Type"] = "application/json";
    }

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), this.timeoutMs);

    try {
      const response = await fetch(url.toString(), {
        method,
        headers,
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
        signal: controller.signal,
      });

      const text = await response.text();
      let data: any = null;
      try {
        data = text ? JSON.parse(text) : null;
      } catch {
        data = { raw: text };
      }

      if (!response.ok) {
        const errorMsg = data?.message || data?.error || `HTTP ${response.status} ${response.statusText}`;

        if (response.status === 401) {
          throw new AuthenticationError(errorMsg, data);
        }
        if (response.status === 403) {
          if (data?.code === "QUOTA_EXCEEDED") {
            throw new QuotaExceededError(errorMsg, data);
          }
          throw new ForbiddenError(errorMsg, data);
        }
        if (response.status === 404) {
          throw new NotFoundError(errorMsg, data);
        }
        if (response.status === 400) {
          throw new ValidationError(errorMsg, data);
        }

        throw new AffiChatError(errorMsg, response.status, data?.code, data);
      }

      return data as T;
    } catch (err: any) {
      if (err.name === "AbortError") {
        throw new AffiChatError(`Request timeout after ${this.timeoutMs}ms`, 408, "TIMEOUT");
      }
      if (err instanceof AffiChatError) {
        throw err;
      }
      throw new AffiChatError(err?.message || "Network request failed", 500, "NETWORK_ERROR", err);
    } finally {
      clearTimeout(timer);
    }
  }
}
