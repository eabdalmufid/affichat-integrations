import { HttpClient } from "../client.js";
import { ApiKeyCheckData, CheckProfileParams, HealthStatus, ProfileData } from "../types/utility.js";
export declare class UtilitiesResource {
    private readonly client;
    constructor(client: HttpClient);
    healthCheck(): Promise<HealthStatus>;
    checkApiKey(): Promise<{
        status: boolean;
        message: string;
        data: ApiKeyCheckData;
    }>;
    checkProfile(params: CheckProfileParams): Promise<{
        status: boolean;
        data: ProfileData;
    }>;
}
//# sourceMappingURL=utilities.d.ts.map