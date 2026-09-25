export interface HealthStatus {
    status: boolean;
    message?: string;
    uptime?: number;
    timestamp?: string;
}
export interface ApiKeyCheckData {
    active: boolean;
    keyName: string;
    user: {
        name: string;
        email: string;
    };
    plan: string;
    remainingMessages: number;
    maxDevices: number;
}
export interface CheckProfileParams {
    sessionId: string;
    phoneNumber: string;
}
export interface ProfileData {
    exists: boolean;
    jid?: string;
    name?: string;
    status?: string;
    pictureUrl?: string;
}
//# sourceMappingURL=utility.d.ts.map