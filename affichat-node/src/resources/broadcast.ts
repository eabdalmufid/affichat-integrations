import { HttpClient } from "../client.js";
import { Campaign, ListCampaignsParams, SendBulkParams } from "../types/broadcast.js";

export class BroadcastResource {
  constructor(private readonly client: HttpClient) {}

  public async sendBulk(params: SendBulkParams): Promise<{ status: boolean; message: string; data?: Campaign }> {
    return this.client.request("POST", "/api/send-bulk", { body: params });
  }

  public async listCampaigns(params: ListCampaignsParams = {}): Promise<{ status: boolean; data: Campaign[] }> {
    return this.client.request("GET", "/api/campaigns", {
      params: {
        sessionId: params.sessionId,
        limit: params.limit,
      },
    });
  }

  public async getCampaign(id: string): Promise<{ status: boolean; data: Campaign }> {
    return this.client.request("GET", `/api/campaigns/${encodeURIComponent(id)}`);
  }

  public async pauseCampaign(id: string): Promise<{ status: boolean; message: string; data?: Campaign }> {
    return this.client.request("POST", `/api/campaigns/${encodeURIComponent(id)}/pause`);
  }

  public async resumeCampaign(id: string): Promise<{ status: boolean; message: string; data?: Campaign }> {
    return this.client.request("POST", `/api/campaigns/${encodeURIComponent(id)}/resume`);
  }

  public async cancelCampaign(id: string): Promise<{ status: boolean; message: string; data?: Campaign }> {
    return this.client.request("POST", `/api/campaigns/${encodeURIComponent(id)}/cancel`);
  }
}
