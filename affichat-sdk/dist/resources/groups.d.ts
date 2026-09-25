import { HttpClient } from "../client.js";
import { GroupMetadata, GroupSummary, ListGroupsParams } from "../types/group.js";
export declare class GroupsResource {
    private readonly client;
    constructor(client: HttpClient);
    list(params: string | ListGroupsParams): Promise<{
        status: boolean;
        data: GroupSummary[];
    }>;
    getMetadata(sessionId: string, groupJid: string): Promise<{
        status: boolean;
        data: GroupMetadata;
    }>;
}
//# sourceMappingURL=groups.d.ts.map