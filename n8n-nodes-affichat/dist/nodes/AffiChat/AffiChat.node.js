"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AffiChat = void 0;
class AffiChat {
    description = {
        displayName: "AffiChat",
        name: "affiChat",
        icon: "file:AffiChat.svg",
        group: ["transform"],
        version: 1,
        subtitle: '={{$parameter["operation"] + ": " + $parameter["resource"]}}',
        description: "Interact with WhatsApp Gateway via AffiChat REST API",
        defaults: {
            name: "AffiChat",
        },
        inputs: ["main"],
        outputs: ["main"],
        credentials: [
            {
                name: "affiChatApi",
                required: true,
            },
        ],
        properties: [
            {
                displayName: "Resource",
                name: "resource",
                type: "options",
                noDataExpression: true,
                options: [
                    { name: "Message", value: "message" },
                    { name: "Broadcast", value: "broadcast" },
                    { name: "Group", value: "group" },
                    { name: "Utility", value: "utility" },
                ],
                default: "message",
            },
            // ── Operations for Message ──
            {
                displayName: "Operation",
                name: "operation",
                type: "options",
                noDataExpression: true,
                displayOptions: {
                    show: { resource: ["message"] },
                },
                options: [
                    { name: "Send Text", value: "sendText", action: "Send a text message" },
                    { name: "Send Image", value: "sendImage", action: "Send an image via URL" },
                    { name: "Send Document", value: "sendDocument", action: "Send a document/PDF via URL" },
                    { name: "Send Video", value: "sendVideo", action: "Send a video via URL" },
                    { name: "Send Location", value: "sendLocation", action: "Send GPS coordinates" },
                    { name: "Send Contact", value: "sendContact", action: "Send a contact vCard" },
                    { name: "Send Sticker", value: "sendSticker", action: "Send a WebP sticker via URL" },
                    { name: "Send Poll", value: "sendPoll", action: "Send an interactive poll" },
                ],
                default: "sendText",
            },
            // ── Operations for Broadcast ──
            {
                displayName: "Operation",
                name: "operation",
                type: "options",
                noDataExpression: true,
                displayOptions: {
                    show: { resource: ["broadcast"] },
                },
                options: [
                    { name: "Send Bulk Messages", value: "sendBulk", action: "Queue a bulk broadcast campaign" },
                    { name: "List Campaigns", value: "listCampaigns", action: "List broadcast campaigns" },
                    { name: "Get Campaign Details", value: "getCampaign", action: "Get broadcast campaign status" },
                    { name: "Pause Campaign", value: "pauseCampaign", action: "Pause a running campaign" },
                    { name: "Resume Campaign", value: "resumeCampaign", action: "Resume a paused campaign" },
                    { name: "Cancel Campaign", value: "cancelCampaign", action: "Cancel a campaign" },
                ],
                default: "sendBulk",
            },
            // ── Operations for Group ──
            {
                displayName: "Operation",
                name: "operation",
                type: "options",
                noDataExpression: true,
                displayOptions: {
                    show: { resource: ["group"] },
                },
                options: [
                    { name: "List Groups", value: "listGroups", action: "List WhatsApp groups" },
                    { name: "Get Group Metadata", value: "getMetadata", action: "Get group info and participants" },
                ],
                default: "listGroups",
            },
            // ── Operations for Utility ──
            {
                displayName: "Operation",
                name: "operation",
                type: "options",
                noDataExpression: true,
                displayOptions: {
                    show: { resource: ["utility"] },
                },
                options: [
                    { name: "Check API Key & Quota", value: "checkApiKey", action: "Verify API key and remaining quota" },
                    { name: "Check Phone Profile", value: "checkProfile", action: "Check if a phone number exists on WhatsApp" },
                    { name: "Health Check", value: "healthCheck", action: "Check gateway service availability" },
                ],
                default: "checkApiKey",
            },
            // ── Common Session ID ──
            {
                displayName: "Session ID",
                name: "sessionId",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: {
                        resource: ["message", "group"],
                    },
                },
                description: "The connected WhatsApp session ID",
            },
            {
                displayName: "Session ID",
                name: "sessionId",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: {
                        resource: ["broadcast"],
                        operation: ["sendBulk"],
                    },
                },
                description: "The connected WhatsApp session ID",
            },
            {
                displayName: "Session ID",
                name: "sessionId",
                type: "string",
                default: "",
                required: false,
                displayOptions: {
                    show: {
                        resource: ["broadcast"],
                        operation: ["listCampaigns"],
                    },
                },
                description: "Filter campaigns by WhatsApp session ID (optional)",
            },
            {
                displayName: "Session ID",
                name: "sessionId",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: {
                        resource: ["utility"],
                        operation: ["checkProfile"],
                    },
                },
                description: "The connected WhatsApp session ID to perform profile lookup",
            },
            // ── Common Recipient / To ──
            {
                displayName: "To (Phone Number or Group JID)",
                name: "to",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"] },
                },
                description: "Phone number with country code (e.g. 6281234567890) or group JID",
            },
            // ── Message: Text ──
            {
                displayName: "Message Text",
                name: "text",
                type: "string",
                typeOptions: { rows: 4 },
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendText"] },
                },
            },
            // ── Message: Image ──
            {
                displayName: "Image URL",
                name: "imageUrl",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendImage"] },
                },
            },
            {
                displayName: "Caption",
                name: "caption",
                type: "string",
                default: "",
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendImage", "sendDocument", "sendVideo"] },
                },
            },
            // ── Message: Document ──
            {
                displayName: "Document URL",
                name: "documentUrl",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendDocument"] },
                },
            },
            {
                displayName: "Filename",
                name: "filename",
                type: "string",
                default: "document.pdf",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendDocument"] },
                },
            },
            // ── Message: Sticker ──
            {
                displayName: "Sticker URL",
                name: "stickerUrl",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendSticker"] },
                },
                description: "URL of the WebP sticker file",
            },
            // ── Message: Video ──
            {
                displayName: "Video URL",
                name: "videoUrl",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendVideo"] },
                },
            },
            // ── Message: Location ──
            {
                displayName: "Latitude",
                name: "latitude",
                type: "number",
                default: 0,
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendLocation"] },
                },
            },
            {
                displayName: "Longitude",
                name: "longitude",
                type: "number",
                default: 0,
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendLocation"] },
                },
            },
            {
                displayName: "Location Name",
                name: "locationName",
                type: "string",
                default: "",
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendLocation"] },
                },
            },
            {
                displayName: "Address",
                name: "address",
                type: "string",
                default: "",
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendLocation"] },
                },
            },
            // ── Message: Contact ──
            {
                displayName: "Contact Name",
                name: "contactName",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendContact"] },
                },
            },
            {
                displayName: "Contact Phone Number",
                name: "contactNumber",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendContact"] },
                },
            },
            // ── Message: Poll ──
            {
                displayName: "Question",
                name: "question",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendPoll"] },
                },
            },
            {
                displayName: "Poll Options (Comma-Separated)",
                name: "pollOptions",
                type: "string",
                default: "Option 1, Option 2",
                required: true,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendPoll"] },
                },
            },
            {
                displayName: "Allow Multiple Answers",
                name: "multipleAnswers",
                type: "boolean",
                default: false,
                displayOptions: {
                    show: { resource: ["message"], operation: ["sendPoll"] },
                },
                description: "Whether participants can select multiple options",
            },
            // ── Broadcast: Send Bulk ──
            {
                displayName: "Recipients (Comma-Separated)",
                name: "recipients",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["broadcast"], operation: ["sendBulk"] },
                },
                description: "List of phone numbers separated by comma (e.g. 6281234567890, 6289876543210)",
            },
            {
                displayName: "Broadcast Message",
                name: "broadcastMessage",
                type: "string",
                typeOptions: { rows: 4 },
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["broadcast"], operation: ["sendBulk"] },
                },
            },
            {
                displayName: "Delay Between Messages (ms)",
                name: "delayMs",
                type: "number",
                default: 2000,
                displayOptions: {
                    show: { resource: ["broadcast"], operation: ["sendBulk"] },
                },
            },
            // ── Broadcast: Campaign ID ──
            {
                displayName: "Campaign ID",
                name: "campaignId",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: {
                        resource: ["broadcast"],
                        operation: ["getCampaign", "pauseCampaign", "resumeCampaign", "cancelCampaign"],
                    },
                },
            },
            {
                displayName: "Limit",
                name: "limit",
                type: "number",
                default: 50,
                displayOptions: {
                    show: { resource: ["broadcast"], operation: ["listCampaigns"] },
                },
            },
            // ── Group: JID & Include Participants ──
            {
                displayName: "Group JID",
                name: "groupJid",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["group"], operation: ["getMetadata"] },
                },
                description: "WhatsApp group ID (e.g. 120363012345678901@g.us)",
            },
            {
                displayName: "Include Participants",
                name: "includeParticipants",
                type: "boolean",
                default: false,
                displayOptions: {
                    show: { resource: ["group"], operation: ["listGroups"] },
                },
            },
            // ── Utility: Check Profile Phone Number ──
            {
                displayName: "Phone Number to Check",
                name: "phoneNumber",
                type: "string",
                default: "",
                required: true,
                displayOptions: {
                    show: { resource: ["utility"], operation: ["checkProfile"] },
                },
            },
        ],
    };
    async execute() {
        const items = this.getInputData();
        const returnData = [];
        const credentials = await this.getCredentials("affiChatApi");
        const baseUrl = "https://chat.affidev.com";
        for (let i = 0; i < items.length; i++) {
            try {
                const resource = this.getNodeParameter("resource", i);
                const operation = this.getNodeParameter("operation", i);
                let method = "GET";
                let endpoint = "";
                let body;
                let qs;
                if (resource === "message") {
                    method = "POST";
                    const sessionId = this.getNodeParameter("sessionId", i);
                    const to = this.getNodeParameter("to", i);
                    if (operation === "sendText") {
                        endpoint = "/api/send-text";
                        body = {
                            sessionId,
                            to,
                            text: this.getNodeParameter("text", i),
                        };
                    }
                    else if (operation === "sendImage") {
                        endpoint = "/api/send-image";
                        body = {
                            sessionId,
                            to,
                            imageUrl: this.getNodeParameter("imageUrl", i),
                            caption: this.getNodeParameter("caption", i, ""),
                        };
                    }
                    else if (operation === "sendDocument") {
                        endpoint = "/api/send-document";
                        body = {
                            sessionId,
                            to,
                            documentUrl: this.getNodeParameter("documentUrl", i),
                            filename: this.getNodeParameter("filename", i),
                            caption: this.getNodeParameter("caption", i, ""),
                        };
                    }
                    else if (operation === "sendSticker") {
                        endpoint = "/api/send-sticker";
                        body = {
                            sessionId,
                            to,
                            stickerUrl: this.getNodeParameter("stickerUrl", i),
                        };
                    }
                    else if (operation === "sendVideo") {
                        endpoint = "/api/send-video";
                        body = {
                            sessionId,
                            to,
                            videoUrl: this.getNodeParameter("videoUrl", i),
                            caption: this.getNodeParameter("caption", i, ""),
                        };
                    }
                    else if (operation === "sendLocation") {
                        endpoint = "/api/send-location";
                        body = {
                            sessionId,
                            to,
                            latitude: this.getNodeParameter("latitude", i),
                            longitude: this.getNodeParameter("longitude", i),
                            name: this.getNodeParameter("locationName", i, ""),
                            address: this.getNodeParameter("address", i, ""),
                        };
                    }
                    else if (operation === "sendContact") {
                        endpoint = "/api/send-contact";
                        body = {
                            sessionId,
                            to,
                            contactName: this.getNodeParameter("contactName", i),
                            contactNumber: this.getNodeParameter("contactNumber", i),
                        };
                    }
                    else if (operation === "sendPoll") {
                        endpoint = "/api/send-poll";
                        const rawOptions = this.getNodeParameter("pollOptions", i);
                        const options = Array.isArray(rawOptions)
                            ? rawOptions.map((o) => String(o).trim()).filter(Boolean)
                            : typeof rawOptions === "string"
                                ? rawOptions.split(",").map((o) => o.trim()).filter(Boolean)
                                : [];
                        body = {
                            sessionId,
                            to,
                            question: this.getNodeParameter("question", i),
                            options,
                            multipleAnswers: this.getNodeParameter("multipleAnswers", i, false),
                        };
                    }
                }
                else if (resource === "broadcast") {
                    if (operation === "sendBulk") {
                        method = "POST";
                        endpoint = "/api/send-bulk";
                        const rawRecipients = this.getNodeParameter("recipients", i);
                        const recipients = Array.isArray(rawRecipients)
                            ? rawRecipients.map((r) => String(r).trim()).filter(Boolean)
                            : typeof rawRecipients === "string"
                                ? rawRecipients.split(",").map((r) => r.trim()).filter(Boolean)
                                : [];
                        body = {
                            sessionId: this.getNodeParameter("sessionId", i),
                            recipients,
                            message: this.getNodeParameter("broadcastMessage", i),
                            delayMs: this.getNodeParameter("delayMs", i, 2000),
                        };
                    }
                    else if (operation === "listCampaigns") {
                        method = "GET";
                        endpoint = "/api/campaigns";
                        qs = {
                            sessionId: this.getNodeParameter("sessionId", i, ""),
                            limit: this.getNodeParameter("limit", i, 50),
                        };
                    }
                    else if (operation === "getCampaign") {
                        method = "GET";
                        const id = this.getNodeParameter("campaignId", i);
                        endpoint = `/api/campaigns/${encodeURIComponent(id)}`;
                    }
                    else if (operation === "pauseCampaign") {
                        method = "POST";
                        const id = this.getNodeParameter("campaignId", i);
                        endpoint = `/api/campaigns/${encodeURIComponent(id)}/pause`;
                    }
                    else if (operation === "resumeCampaign") {
                        method = "POST";
                        const id = this.getNodeParameter("campaignId", i);
                        endpoint = `/api/campaigns/${encodeURIComponent(id)}/resume`;
                    }
                    else if (operation === "cancelCampaign") {
                        method = "POST";
                        const id = this.getNodeParameter("campaignId", i);
                        endpoint = `/api/campaigns/${encodeURIComponent(id)}/cancel`;
                    }
                }
                else if (resource === "group") {
                    method = "GET";
                    const sessionId = this.getNodeParameter("sessionId", i);
                    if (operation === "listGroups") {
                        endpoint = "/api/groups";
                        qs = {
                            sessionId,
                            include_participants: this.getNodeParameter("includeParticipants", i, false),
                        };
                    }
                    else if (operation === "getMetadata") {
                        const groupJid = this.getNodeParameter("groupJid", i);
                        endpoint = `/api/groups/${encodeURIComponent(groupJid)}`;
                        qs = { sessionId };
                    }
                }
                else if (resource === "utility") {
                    method = "GET";
                    if (operation === "checkApiKey") {
                        endpoint = "/api/key/check";
                    }
                    else if (operation === "checkProfile") {
                        endpoint = "/api/profile";
                        qs = {
                            sessionId: this.getNodeParameter("sessionId", i),
                            phoneNumber: this.getNodeParameter("phoneNumber", i),
                        };
                    }
                    else if (operation === "healthCheck") {
                        endpoint = "/api/health";
                    }
                }
                const requestOptions = {
                    method,
                    url: `${baseUrl}${endpoint}`,
                    json: true,
                    body,
                    qs,
                };
                const responseData = await this.helpers.httpRequestWithAuthentication.call(this, "affiChatApi", requestOptions);
                returnData.push({
                    json: (responseData && typeof responseData === "object" ? responseData : { response: responseData }),
                    pairedItem: { item: i },
                });
            }
            catch (error) {
                if (this.continueOnFail()) {
                    returnData.push({
                        json: { error: error.message },
                        pairedItem: { item: i },
                    });
                    continue;
                }
                throw error;
            }
        }
        return [returnData];
    }
}
exports.AffiChat = AffiChat;
//# sourceMappingURL=AffiChat.node.js.map