export class GroupsResource {
    client;
    constructor(client) {
        this.client = client;
    }
    async list(params) {
        const sessionId = typeof params === "string" ? params : params.sessionId;
        const includeParticipants = typeof params === "object" ? params.includeParticipants : false;
        return this.client.request("GET", "/api/groups", {
            params: {
                sessionId,
                include_participants: includeParticipants,
            },
        });
    }
    async getMetadata(sessionId, groupJid) {
        return this.client.request("GET", `/api/groups/${encodeURIComponent(groupJid)}`, {
            params: { sessionId },
        });
    }
}
//# sourceMappingURL=groups.js.map