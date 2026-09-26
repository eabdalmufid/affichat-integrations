import { HttpClient } from "./client.js";
import { BroadcastResource } from "./resources/broadcast.js";
import { GroupsResource } from "./resources/groups.js";
import { MessagesResource } from "./resources/messages.js";
import { UtilitiesResource } from "./resources/utilities.js";
export class AffiChat {
    messages;
    broadcast;
    groups;
    utilities;
    http;
    constructor(config) {
        this.http = new HttpClient(config);
        this.messages = new MessagesResource(this.http);
        this.broadcast = new BroadcastResource(this.http);
        this.groups = new GroupsResource(this.http);
        this.utilities = new UtilitiesResource(this.http);
    }
}
export * from "./types/index.js";
export * from "./errors.js";
export * from "./client.js";
export * from "./resources/index.js";
export * from "./webhook.js";
export default AffiChat;
//# sourceMappingURL=index.js.map