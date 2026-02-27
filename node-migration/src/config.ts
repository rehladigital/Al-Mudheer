import dotenv from "dotenv";
import { z } from "zod";

dotenv.config();

const envSchema = z.object({
  NODE_ENV: z.string().default("development"),
  PORT: z.coerce.number().default(3000),
  APP_URL: z.string().url(),
  DATABASE_PROVIDER: z.enum(["sqlite", "mysql"]),
  DATABASE_URL: z.string().min(1),
  SESSION_SECRET: z.string().min(12),
  ENCRYPTION_KEY: z.string().min(16),
  SSO_ENABLED: z
    .string()
    .default("false")
    .transform((v) => ["1", "true", "on", "yes"].includes(v.toLowerCase())),
  OIDC_ISSUER: z.string().url().optional().or(z.literal("")),
  OIDC_CLIENT_ID: z.string().optional().or(z.literal("")),
  OIDC_CLIENT_SECRET: z.string().optional().or(z.literal("")),
  OIDC_REDIRECT_PATH: z.string().default("/auth/oidc/callback"),
  OIDC_SCOPES: z.string().default("openid profile email")
});

export const env = envSchema.parse(process.env);
