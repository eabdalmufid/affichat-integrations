import { HttpClient } from "../client.js";
import { Campaign, ListCampaignsParams, SendBulkParams } from "../types/broadcast.js";
export declare class BroadcastResource {
    private readonly client;
    constructor(client: HttpClient);
    sendBulk(params: SendBulkParams): Promise<{
        status: boolean;
        message: string;
        data?: Campaign;
    }>;
    listCampaigns(params?: ListCampaignsParams): Promise<{
        status: boolean;
        data: Campaign[];
    }>;
    getCampaign(id: string): Promise<{
        status: boolean;
        data: Campaign;
    }>;
    pauseCampaign(id: string): Promise<{
        status: boolean;
        message: string;
        data?: Campaign;
    }>;
    resumeCampaign(id: string): Promise<{
        status: boolean;
        message: string;
        data?: Campaign;
    }>;
    cancelCampaign(id: string): Promise<{
        status: boolean;
        message: string;
        data?: Campaign;
    }>;
}
//# sourceMappingURL=broadcast.d.ts.map