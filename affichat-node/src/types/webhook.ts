export type WebhookEventType = "messages.upsert" | "session.status";

export interface WebhookMediaData {
  image?: string | null;
  video?: string | null;
  document?: string | null;
  audio?: string | null;
}

export interface WebhookMessageData {
  id: string;
  from: string;
  fromMe: boolean;
  isGroup: boolean;
  participant?: string | null;
  pushName?: string | null;
  timestamp: number;
  message: string | null;
  media?: WebhookMediaData;
}

export interface WebhookSessionStatusData {
  session: string;
  status: "connected" | "disconnected" | "connecting" | string;
  phoneNumber?: string | null;
  name?: string | null;
  reason?: string;
  timestamp?: string;
}

export interface WebhookEvent<T = WebhookMessageData | WebhookSessionStatusData | Record<string, any>> {
  event: WebhookEventType | string;
  sessionId: string;
  timestamp: string;
  data: T;
}
