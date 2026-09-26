export class AffiChatError extends Error {
  public readonly status: number;
  public readonly code?: string;
  public readonly responseBody?: unknown;

  constructor(message: string, status = 500, code?: string, responseBody?: unknown) {
    super(message);
    this.name = "AffiChatError";
    this.status = status;
    this.code = code;
    this.responseBody = responseBody;
  }
}

export class AuthenticationError extends AffiChatError {
  constructor(message = "Unauthorized - Invalid or missing API Key", responseBody?: unknown) {
    super(message, 401, "UNAUTHORIZED", responseBody);
    this.name = "AuthenticationError";
  }
}

export class ForbiddenError extends AffiChatError {
  constructor(message = "Forbidden access", responseBody?: unknown) {
    super(message, 403, "FORBIDDEN", responseBody);
    this.name = "ForbiddenError";
  }
}

export class QuotaExceededError extends AffiChatError {
  constructor(message = "Monthly message quota exceeded", responseBody?: unknown) {
    super(message, 403, "QUOTA_EXCEEDED", responseBody);
    this.name = "QuotaExceededError";
  }
}

export class NotFoundError extends AffiChatError {
  constructor(message = "Resource not found", responseBody?: unknown) {
    super(message, 404, "NOT_FOUND", responseBody);
    this.name = "NotFoundError";
  }
}

export class ValidationError extends AffiChatError {
  constructor(message = "Validation error", responseBody?: unknown) {
    super(message, 400, "VALIDATION_ERROR", responseBody);
    this.name = "ValidationError";
  }
}
