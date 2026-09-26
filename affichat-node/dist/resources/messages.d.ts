import { HttpClient } from "../client.js";
import { MessageResponse, SendContactParams, SendDocumentParams, SendImageParams, SendLocationParams, SendPollParams, SendStickerParams, SendTextParams, SendVideoParams } from "../types/message.js";
export declare class MessagesResource {
    private readonly client;
    constructor(client: HttpClient);
    sendText(params: SendTextParams): Promise<MessageResponse>;
    sendImage(params: SendImageParams): Promise<MessageResponse>;
    sendDocument(params: SendDocumentParams): Promise<MessageResponse>;
    sendVideo(params: SendVideoParams): Promise<MessageResponse>;
    sendLocation(params: SendLocationParams): Promise<MessageResponse>;
    sendContact(params: SendContactParams): Promise<MessageResponse>;
    sendSticker(params: SendStickerParams): Promise<MessageResponse>;
    sendPoll(params: SendPollParams): Promise<MessageResponse>;
}
//# sourceMappingURL=messages.d.ts.map