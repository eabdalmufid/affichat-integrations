export class BroadcastResource {
    client;
    constructor(client) {
        this.client = client;
    }
    async sendBulk(params) {
        return this.client.request("POST", "/api/send-bulk", { body: params });
    }
    async listCampaigns(params = {}) {
        return this.client.request("GET", "/api/campaigns", {
            params: {
                sessionId: params.sessionId,
                limit: params.limit,
            },
        });
    }
    async getCampaign(id) {
        return this.client.request("GET", `/api/campaigns/${encodeURIComponent(id)}`);
    }
    async pauseCampaign(id) {
        return this.client.request("POST", `/api/campaigns/${encodeURIComponent(id)}/pause`);
    }
    async resumeCampaign(id) {
        return this.client.request("POST", `/api/campaigns/${encodeURIComponent(id)}/resume`);
    }
    async cancelCampaign(id) {
        return this.client.request("POST", `/api/campaigns/${encodeURIComponent(id)}/cancel`);
    }
}
//# sourceMappingURL=broadcast.js.map