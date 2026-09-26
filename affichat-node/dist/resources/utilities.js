export class UtilitiesResource {
    client;
    constructor(client) {
        this.client = client;
    }
    async healthCheck() {
        return this.client.request("GET", "/api/health");
    }
    async checkApiKey() {
        return this.client.request("GET", "/api/key/check");
    }
    async checkProfile(params) {
        return this.client.request("GET", "/api/profile", {
            params: {
                sessionId: params.sessionId,
                phoneNumber: params.phoneNumber,
            },
        });
    }
}
//# sourceMappingURL=utilities.js.map