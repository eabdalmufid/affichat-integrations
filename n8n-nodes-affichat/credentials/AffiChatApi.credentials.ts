import type {
  IAuthenticateGeneric,
  ICredentialTestRequest,
  ICredentialType,
  INodeProperties,
} from "n8n-workflow";

export class AffiChatApi implements ICredentialType {
  name = "affiChatApi";
  displayName = "AffiChat API";
  documentationUrl = "https://chat.affidev.com/dashboard/api-docs";

  properties: INodeProperties[] = [
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

  authenticate: IAuthenticateGeneric = {
    type: "generic",
    properties: {
      headers: {
        "x-api-key": "={{$credentials.apiKey}}",
      },
    },
  };

  test: ICredentialTestRequest = {
    request: {
      baseURL: "https://chat.affidev.com",
      url: "/api/key/check",
      method: "GET",
    },
  };
}
