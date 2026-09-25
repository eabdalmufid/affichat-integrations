export class AffiChatError extends Error {
    status;
    code;
    responseBody;
    constructor(message, status = 500, code, responseBody) {
        super(message);
        this.name = "AffiChatError";
        this.status = status;
        this.code = code;
        this.responseBody = responseBody;
    }
}
export class AuthenticationError extends AffiChatError {
    constructor(message = "Unauthorized - Invalid or missing API Key", responseBody) {
        super(message, 401, "UNAUTHORIZED", responseBody);
        this.name = "AuthenticationError";
    }
}
export class ForbiddenError extends AffiChatError {
    constructor(message = "Forbidden access", responseBody) {
        super(message, 403, "FORBIDDEN", responseBody);
        this.name = "ForbiddenError";
    }
}
export class QuotaExceededError extends AffiChatError {
    constructor(message = "Monthly message quota exceeded", responseBody) {
        super(message, 403, "QUOTA_EXCEEDED", responseBody);
        this.name = "QuotaExceededError";
    }
}
export class NotFoundError extends AffiChatError {
    constructor(message = "Resource not found", responseBody) {
        super(message, 404, "NOT_FOUND", responseBody);
        this.name = "NotFoundError";
    }
}
export class ValidationError extends AffiChatError {
    constructor(message = "Validation error", responseBody) {
        super(message, 400, "VALIDATION_ERROR", responseBody);
        this.name = "ValidationError";
    }
}
//# sourceMappingURL=errors.js.map