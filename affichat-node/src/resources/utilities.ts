import { HttpClient } from "../client.js";
import { ApiKeyCheckData, CheckProfileParams, HealthStatus, ProfileData } from "../types/utility.js";

export class UtilitiesResource {
  constructor(private readonly client: HttpClient) {}

  public async healthCheck(): Promise<HealthStatus> {
    return this.client.request<HealthStatus>("GET", "/api/health");
  }

  public async checkApiKey(): Promise<{ status: boolean; message: string; data: ApiKeyCheckData }> {
    return this.client.request("GET", "/api/key/check");
  }

  public async checkProfile(params: CheckProfileParams): Promise<{ status: boolean; data: ProfileData }> {
    return this.client.request("GET", "/api/profile", {
      params: {
        sessionId: params.sessionId,
        phoneNumber: params.phoneNumber,
      },
    });
  }
}
