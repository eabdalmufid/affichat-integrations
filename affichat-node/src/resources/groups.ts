import { HttpClient } from "../client.js";
import { GroupMetadata, GroupSummary, ListGroupsParams } from "../types/group.js";

export class GroupsResource {
  constructor(private readonly client: HttpClient) {}

  public async list(params: string | ListGroupsParams): Promise<{ status: boolean; data: GroupSummary[] }> {
    const sessionId = typeof params === "string" ? params : params.sessionId;
    const includeParticipants = typeof params === "object" ? params.includeParticipants : false;

    return this.client.request("GET", "/api/groups", {
      params: {
        sessionId,
        include_participants: includeParticipants,
      },
    });
  }

  public async getMetadata(sessionId: string, groupJid: string): Promise<{ status: boolean; data: GroupMetadata }> {
    return this.client.request("GET", `/api/groups/${encodeURIComponent(groupJid)}`, {
      params: { sessionId },
    });
  }
}
