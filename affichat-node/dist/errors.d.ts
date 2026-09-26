export declare class AffiChatError extends Error {
    readonly status: number;
    readonly code?: string;
    readonly responseBody?: unknown;
    constructor(message: string, status?: number, code?: string, responseBody?: unknown);
}
export declare class AuthenticationError extends AffiChatError {
    constructor(message?: string, responseBody?: unknown);
}
export declare class ForbiddenError extends AffiChatError {
    constructor(message?: string, responseBody?: unknown);
}
export declare class QuotaExceededError extends AffiChatError {
    constructor(message?: string, responseBody?: unknown);
}
export declare class NotFoundError extends AffiChatError {
    constructor(message?: string, responseBody?: unknown);
}
export declare class ValidationError extends AffiChatError {
    constructor(message?: string, responseBody?: unknown);
}
//# sourceMappingURL=errors.d.ts.map