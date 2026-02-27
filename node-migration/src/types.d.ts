import "express-session";

declare module "express-session" {
  interface SessionData {
    userId?: number;
    userRole?: number;
    oidc?: {
      state: string;
      nonce: string;
      codeVerifier: string;
      issuer: string;
    };
  }
}
