"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.AffiChatTrigger = void 0;
const crypto_1 = __importDefault(require("crypto"));
class AffiChatTrigger {
    description = {
        displayName: "AffiChat Trigger",
        name: "affiChatTrigger",
        icon: "file:AffiChat.svg",
        group: ["trigger"],
        version: 1,
        description: "Receive incoming WhatsApp messages and session status events from AffiChat Gateway",
        defaults: {
            name: "AffiChat Trigger",
        },
        inputs: [],
        outputs: ["main"],
        webhooks: [
            {
                name: "default",
                httpMethod: "POST",
                responseMode: "onReceived",
                path: "webhook",
            },
        ],
        properties: [
            {
                displayName: "Event",
                name: "event",
                type: "options",
                options: [
                    {
                        name: "All Events",
                        value: "*",
                        description: "Receive all events (messages and session status)",
                    },
                    {
                        name: "Incoming Messages (messages.upsert)",
                        value: "messages.upsert",
                        description: "Triggers when an incoming WhatsApp message is received",
                    },
                    {
                        name: "Session Status (session.status)",
                        value: "session.status",
                        description: "Triggers when a WhatsApp device connects or disconnects",
                    },
                ],
                default: "messages.upsert",
                description: "The event type that will trigger this workflow",
            },
            {
                displayName: "Webhook Secret",
                name: "webhookSecret",
                type: "string",
                typeOptions: { password: true },
                default: "",
                description: "Optional secret key to verify HMAC SHA-256 signature from X-AffiChat-Signature header",
            },
            {
                displayName: "Sender Filter (Phone Number)",
                name: "senderFilter",
                type: "string",
                default: "",
                placeholder: "6281234567890",
                description: "Only process messages from this phone number (leave blank for all)",
                displayOptions: {
                    show: {
                        event: ["*", "messages.upsert"],
                    },
                },
            },
            {
                displayName: "Ignore Self Messages",
                name: "ignoreSelf",
                type: "boolean",
                default: true,
                description: "Whether to ignore outgoing messages sent by the device itself",
                displayOptions: {
                    show: {
                        event: ["*", "messages.upsert"],
                    },
                },
            },
        ],
    };
    async webhook() {
        const req = this.getRequestObject();
        const headers = this.getHeaderData();
        const body = this.getBodyData();
        const getHeader = (name) => {
            const lower = name.toLowerCase();
            return String(headers?.[lower] || headers?.[name] || "");
        };
        const event = String(body?.event || getHeader("x-affichat-event"));
        const sessionId = String(body?.sessionId || getHeader("x-affichat-session"));
        const webhookSecret = this.getNodeParameter("webhookSecret", "");
        // Verify HMAC-SHA256 signature if secret is configured.
        if (webhookSecret) {
            const incomingSignature = getHeader("x-affichat-signature").trim();
            let rawPayload = req.rawBody;
            if (Buffer.isBuffer(rawPayload)) {
                rawPayload = rawPayload.toString("utf8");
            }
            else if (typeof rawPayload !== "string") {
                rawPayload = JSON.stringify(body);
            }
            const computedSignature = crypto_1.default
                .createHmac("sha256", webhookSecret)
                .update(rawPayload)
                .digest("hex");
            if (!incomingSignature || incomingSignature !== computedSignature) {
                return {
                    webhookResponse: {
                        status: "error",
                        message: "Invalid AffiChat webhook signature",
                    },
                };
            }
        }
        const eventData = (body?.data || {});
        // Filter event type (allow probe_test from AffiChat dashboard test button).
        const selectedEvent = this.getNodeParameter("event", "*");
        const isProbeTest = Boolean(eventData?.event === "probe_test");
        if (!isProbeTest && selectedEvent !== "*" && event !== selectedEvent) {
            return {
                webhookResponse: {
                    status: "ignored",
                    reason: "Event type not subscribed",
                },
            };
        }
        if (event === "messages.upsert") {
            const ignoreSelf = this.getNodeParameter("ignoreSelf", true);
            if (ignoreSelf && Boolean(eventData?.fromMe)) {
                return {
                    webhookResponse: {
                        status: "ignored",
                        reason: "Ignored self outgoing message",
                    },
                };
            }
            const senderFilter = String(this.getNodeParameter("senderFilter", "") || "").trim();
            if (senderFilter) {
                const fromJid = String(eventData?.from || eventData?.sender || "");
                const cleanSender = senderFilter.replace(/[^0-9]/g, "");
                const cleanFrom = fromJid.replace(/[^0-9]/g, "");
                if (!cleanFrom.includes(cleanSender)) {
                    return {
                        webhookResponse: {
                            status: "ignored",
                            reason: "Sender does not match filter",
                        },
                    };
                }
            }
        }
        const outputItem = {
            event,
            sessionId,
            timestamp: body?.timestamp || new Date().toISOString(),
            data: eventData,
            raw: body,
        };
        if (event === "messages.upsert") {
            outputItem.messageId = eventData?.id;
            outputItem.sender = eventData?.from;
            outputItem.senderNumber = typeof eventData?.from === "string" ? eventData.from.replace(/@.*$/, "") : "";
            outputItem.text = eventData?.message;
            outputItem.pushName = eventData?.pushName;
            outputItem.isGroup = eventData?.isGroup;
            outputItem.media = eventData?.media;
        }
        else if (event === "session.status") {
            outputItem.status = eventData?.status;
            outputItem.phoneNumber = eventData?.phoneNumber;
            outputItem.name = eventData?.name;
            outputItem.reason = eventData?.reason;
        }
        return {
            workflowData: [this.helpers.returnJsonArray([outputItem])],
            webhookResponse: {
                status: "success",
            },
        };
    }
}
exports.AffiChatTrigger = AffiChatTrigger;
//# sourceMappingURL=AffiChatTrigger.node.js.map