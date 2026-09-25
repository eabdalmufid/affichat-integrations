import { BroadcastResource } from "./resources/broadcast.js";
import { GroupsResource } from "./resources/groups.js";
import { MessagesResource } from "./resources/messages.js";
import { UtilitiesResource } from "./resources/utilities.js";
import { AffiChatConfig } from "./types/index.js";
export declare class AffiChat {
    readonly messages: MessagesResource;
    readonly broadcast: BroadcastResource;
    readonly groups: GroupsResource;
    readonly utilities: UtilitiesResource;
    private readonly http;
    constructor(config: AffiChatConfig);
}
export * from "./types/index.js";
export * from "./errors.js";
export * from "./client.js";
export * from "./resources/index.js";
export * from "./webhook.js";
export default AffiChat;
//# sourceMappingURL=index.d.ts.map