"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AffiChatApi = void 0;
class AffiChatApi {
    name = "affiChatApi";
    displayName = "AffiChat API";
    documentationUrl = "https://chat.affidev.com/dashboard/api-docs";
    properties = [
        {
            displayName: "API Key",
            name: "apiKey",
            type: "string",
            typeOptions: { password: true },
            default: "",
            required: true,
            description: "API Key generated from AffiChat Dashboard (https://chat.affidev.com/dashboard/api-docs)",
        },
    ];
    authenticate = {
        type: "generic",
        properties: {
            headers: {
                "x-api-key": "={{$credentials.apiKey}}",
            },
        },
    };
    test = {
        request: {
            baseURL: "https://chat.affidev.com",
            url: "/api/key/check",
            method: "GET",
        },
    };
}
exports.AffiChatApi = AffiChatApi;
//# sourceMappingURL=AffiChatApi.credentials.js.map