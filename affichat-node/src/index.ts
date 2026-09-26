import { HttpClient } from "./client.js";
import { BroadcastResource } from "./resources/broadcast.js";
import { GroupsResource } from "./resources/groups.js";
import { MessagesResource } from "./resources/messages.js";
import { UtilitiesResource } from "./resources/utilities.js";
import { AffiChatConfig } from "./types/index.js";

export class AffiChat {
  public readonly messages: MessagesResource;
  public readonly broadcast: BroadcastResource;
  public readonly groups: GroupsResource;
  public readonly utilities: UtilitiesResource;
  private readonly http: HttpClient;

  constructor(config: AffiChatConfig) {
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
