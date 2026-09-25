export * from "./message.js";
export * from "./broadcast.js";
export * from "./group.js";
export * from "./utility.js";
export * from "./webhook.js";

export interface AffiChatConfig {
  apiKey: string;
  baseUrl?: string;
  timeoutMs?: number;
}
