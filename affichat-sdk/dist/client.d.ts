import { AffiChatConfig } from "./types/index.js";
export declare class HttpClient {
    private readonly apiKey;
    private readonly baseUrl;
    private readonly timeoutMs;
    constructor(config: AffiChatConfig);
    request<T = unknown>(method: "GET" | "POST" | "PUT" | "DELETE", path: string, options?: {
        body?: unknown;
        params?: Record<string, string | number | boolean | undefined>;
    }): Promise<T>;
}
//# sourceMappingURL=client.d.ts.map