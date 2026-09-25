export class MessagesResource {
    client;
    constructor(client) {
        this.client = client;
    }
    async sendText(params) {
        return this.client.request("POST", "/api/send-text", { body: params });
    }
    async sendImage(params) {
        return this.client.request("POST", "/api/send-image", { body: params });
    }
    async sendDocument(params) {
        return this.client.request("POST", "/api/send-document", { body: params });
    }
    async sendVideo(params) {
        return this.client.request("POST", "/api/send-video", { body: params });
    }
    async sendLocation(params) {
        return this.client.request("POST", "/api/send-location", { body: params });
    }
    async sendContact(params) {
        return this.client.request("POST", "/api/send-contact", { body: params });
    }
    async sendSticker(params) {
        return this.client.request("POST", "/api/send-sticker", { body: params });
    }
    async sendPoll(params) {
        return this.client.request("POST", "/api/send-poll", { body: params });
    }
}
//# sourceMappingURL=messages.js.map