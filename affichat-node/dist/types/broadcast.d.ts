export interface SendBulkParams {
    sessionId: string;
    recipients: string[];
    message?: string;
    templateId?: string;
    delayMs?: number;
    scheduledAt?: string;
}
export interface CampaignItem {
    id: string;
    campaignId: string;
    recipient: string;
    status: "PENDING" | "SENT" | "FAILED";
    sentAt?: string;
    error?: string;
}
export interface Campaign {
    id: string;
    sessionId: string;
    status: "PENDING" | "RUNNING" | "PAUSED" | "COMPLETED" | "CANCELLED";
    totalCount: number;
    sentCount: number;
    failedCount: number;
    createdAt: string;
    updatedAt: string;
    recent_items?: CampaignItem[];
}
export interface ListCampaignsParams {
    sessionId?: string;
    limit?: number;
}
//# sourceMappingURL=broadcast.d.ts.map