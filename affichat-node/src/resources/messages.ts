import { HttpClient } from "../client.js";
import {
  MessageResponse,
  SendContactParams,
  SendDocumentParams,
  SendImageParams,
  SendLocationParams,
  SendPollParams,
  SendStickerParams,
  SendTextParams,
  SendVideoParams,
} from "../types/message.js";

export class MessagesResource {
  constructor(private readonly client: HttpClient) {}

  public async sendText(params: SendTextParams): Promise<MessageResponse> {
    return this.client.request<MessageResponse>("POST", "/api/send-text", { body: params });
  }

  public async sendImage(params: SendImageParams): Promise<MessageResponse> {
    return this.client.request<MessageResponse>("POST", "/api/send-image", { body: params });
  }

  public async sendDocument(params: SendDocumentParams): Promise<MessageResponse> {
    return this.client.request<MessageResponse>("POST", "/api/send-document", { body: params });
  }

  public async sendVideo(params: SendVideoParams): Promise<MessageResponse> {
    return this.client.request<MessageResponse>("POST", "/api/send-video", { body: params });
  }

  public async sendLocation(params: SendLocationParams): Promise<MessageResponse> {
    return this.client.request<MessageResponse>("POST", "/api/send-location", { body: params });
  }

  public async sendContact(params: SendContactParams): Promise<MessageResponse> {
    return this.client.request<MessageResponse>("POST", "/api/send-contact", { body: params });
  }

  public async sendSticker(params: SendStickerParams): Promise<MessageResponse> {
    return this.client.request<MessageResponse>("POST", "/api/send-sticker", { body: params });
  }

  public async sendPoll(params: SendPollParams): Promise<MessageResponse> {
    return this.client.request<MessageResponse>("POST", "/api/send-poll", { body: params });
  }
}
