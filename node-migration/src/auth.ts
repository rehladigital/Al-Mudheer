import { randomBytes, createHash } from "node:crypto";
import { createRemoteJWKSet, jwtVerify } from "jose";
import type { Request, Response } from "express";
import { env } from "./config.js";
import { prisma } from "./db.js";
import { getDecryptedSetting, getSetting, toBool } from "./settings.js";
import { verifyPassword } from "./password.js";

function fallback<T>(primary: T | undefined | "", backup: T): T {
  return primary === undefined || primary === "" ? backup : primary;
}

async function oidcConfig() {
  const enabledFromDb = await getSetting("companysettings.microsoftAuth.enabled", String(env.SSO_ENABLED));
  const enabled = toBool(enabledFromDb);

  const issuer = fallback(
    await getDecryptedSetting("companysettings.microsoftAuth.issuer", ""),
    env.OIDC_ISSUER ?? ""
  );
  const clientId = fallback(
    await getDecryptedSetting("companysettings.microsoftAuth.clientId", ""),
    env.OIDC_CLIENT_ID ?? ""
  );
  const clientSecret = fallback(
    await getDecryptedSetting("companysettings.microsoftAuth.clientSecret", ""),
    env.OIDC_CLIENT_SECRET ?? ""
  );
  const allowPublicRegistration = toBool(
    await getSetting("companysettings.microsoftAuth.allowPublicRegistration", "false")
  );
  const defaultRole = Number(await getSetting("companysettings.microsoftAuth.defaultRole", "20"));

  return { enabled, issuer, clientId, clientSecret, allowPublicRegistration, defaultRole };
}

function base64Url(input: Buffer): string {
  return input
    .toString("base64")
    .replace(/\+/g, "-")
    .replace(/\//g, "_")
    .replace(/=+$/g, "");
}

function randomString(size = 32): string {
  return base64Url(randomBytes(size));
}

function createCodeChallenge(verifier: string): string {
  const digest = createHash("sha256").update(verifier).digest();
  return base64Url(digest);
}

async function discover(issuer: string) {
  const url = `${issuer.replace(/\/$/, "")}/.well-known/openid-configuration`;
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error("Failed to load OIDC discovery document");
  }
  return (await response.json()) as {
    authorization_endpoint: string;
    token_endpoint: string;
    jwks_uri: string;
  };
}

export async function startOidc(req: Request, res: Response) {
  const config = await oidcConfig();
  if (!config.enabled || !config.issuer || !config.clientId) {
    return res.status(400).json({ error: "SSO is not configured" });
  }

  const endpoints = await discover(config.issuer);
  const state = randomString(24);
  const nonce = randomString(24);
  const codeVerifier = randomString(32);
  const codeChallenge = createCodeChallenge(codeVerifier);
  req.session.oidc = { state, nonce, codeVerifier, issuer: config.issuer };

  const authUrl = new URL(endpoints.authorization_endpoint);
  authUrl.searchParams.set("client_id", config.clientId);
  authUrl.searchParams.set("redirect_uri", `${env.APP_URL}${env.OIDC_REDIRECT_PATH}`);
  authUrl.searchParams.set("response_type", "code");
  authUrl.searchParams.set("scope", env.OIDC_SCOPES);
  authUrl.searchParams.set("state", state);
  authUrl.searchParams.set("nonce", nonce);
  authUrl.searchParams.set("code_challenge", codeChallenge);
  authUrl.searchParams.set("code_challenge_method", "S256");

  return res.redirect(authUrl.toString());
}

export async function oidcCallback(req: Request, res: Response) {
  const config = await oidcConfig();
  if (!config.enabled || !config.issuer || !config.clientId) {
    return res.status(400).json({ error: "SSO is not configured" });
  }

  const endpoints = await discover(config.issuer);
  const state = req.session.oidc?.state;
  const nonce = req.session.oidc?.nonce;
  const codeVerifier = req.session.oidc?.codeVerifier;
  const returnedState = String(req.query.state ?? "");
  const code = String(req.query.code ?? "");
  if (!state || !nonce || !codeVerifier || !code || returnedState !== state) {
    return res.status(400).json({ error: "Invalid OIDC session state" });
  }

  const tokenResponse = await fetch(endpoints.token_endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      grant_type: "authorization_code",
      code,
      client_id: config.clientId,
      client_secret: config.clientSecret,
      redirect_uri: `${env.APP_URL}${env.OIDC_REDIRECT_PATH}`,
      code_verifier: codeVerifier
    })
  });

  if (!tokenResponse.ok) {
    return res.status(400).json({ error: "Token exchange failed" });
  }

  const tokenJson = (await tokenResponse.json()) as { id_token?: string };
  if (!tokenJson.id_token) {
    return res.status(400).json({ error: "Missing id_token in token response" });
  }

  const jwks = createRemoteJWKSet(new URL(endpoints.jwks_uri));
  const { payload: claims } = await jwtVerify(tokenJson.id_token, jwks, {
    issuer: config.issuer,
    audience: config.clientId
  });

  if (String(claims.nonce ?? "") !== nonce) {
    return res.status(400).json({ error: "OIDC nonce mismatch" });
  }

  const email = String(claims.email ?? claims.preferred_username ?? "");
  if (!email) {
    return res.status(400).json({ error: "Email not found in OIDC claims" });
  }

  let user = await prisma.user.findUnique({ where: { email } });
  if (!user) {
    if (!config.allowPublicRegistration) {
      return res.status(403).json({ error: "User does not exist and registration is disabled" });
    }
    user = await prisma.user.create({
      data: {
        email,
        firstName: String(claims.given_name ?? ""),
        lastName: String(claims.family_name ?? ""),
        role: Number.isFinite(config.defaultRole) ? config.defaultRole : 20,
        source: "oidc",
        status: "a"
      }
    });
  } else {
    user = await prisma.user.update({
      where: { id: user.id },
      data: {
        firstName: String(claims.given_name ?? user.firstName ?? ""),
        lastName: String(claims.family_name ?? user.lastName ?? "")
      }
    });
  }

  req.session.userId = user.id;
  req.session.userRole = user.role;
  delete req.session.oidc;
  return res.redirect("/dashboard");
}

export async function localLogin(req: Request, res: Response) {
  const { email, password } = req.body as { email?: string; password?: string };
  if (!email || !password) return res.status(400).json({ error: "Email and password are required" });

  const user = await prisma.user.findUnique({ where: { email } });
  if (!user) return res.status(401).json({ error: "User not found" });
  if (!user.passwordHash || !verifyPassword(password, user.passwordHash)) {
    return res.status(401).json({ error: "Invalid email or password" });
  }

  req.session.userId = user.id;
  req.session.userRole = user.role;
  return res.json({ ok: true, redirect: "/dashboard" });
}

export function logout(req: Request, res: Response) {
  req.session.destroy(() => {
    res.redirect("/login");
  });
}

export function requireAuth(req: Request, res: Response, next: () => void) {
  if (!req.session.userId) {
    return res.redirect("/login");
  }
  return next();
}

export function ssoOnly(oidcEnabled: boolean, advanced: boolean): boolean {
  return oidcEnabled && !advanced;
}
