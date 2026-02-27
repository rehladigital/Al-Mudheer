import cors from "cors";
import express from "express";
import session from "express-session";
import helmet from "helmet";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { env } from "./config.js";
import { localLogin, logout, oidcCallback, requireAuth, ssoOnly, startOidc } from "./auth.js";
import { getDecryptedSetting, getSetting, saveEncryptedSetting, saveSetting, toBool } from "./settings.js";

const app = express();
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, "..");
const legacyPublicPath = path.join(projectRoot, "legacy-php", "public");

app.use(helmet());
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use("/assets", express.static(path.join(legacyPublicPath, "assets")));
app.use("/dist", express.static(path.join(legacyPublicPath, "dist")));
app.use("/images", express.static(path.join(legacyPublicPath, "images")));
app.use("/theme", express.static(path.join(legacyPublicPath, "theme")));
app.get("/favicon.ico", (_req, res) => {
  res.sendFile(path.join(legacyPublicPath, "favicon.ico"));
});
app.use(
  session({
    secret: env.SESSION_SECRET,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      sameSite: "lax",
      secure: env.NODE_ENV === "production"
    }
  })
);

app.get("/health", (_req, res) => {
  res.json({ ok: true });
});

app.get("/login", async (req, res) => {
  const fromDb = await getSetting("companysettings.microsoftAuth.enabled", String(env.SSO_ENABLED));
  const oidcEnabled = toBool(fromDb);
  const advanced = ["1", "true", "on", "yes"].includes(String(req.query.advanced ?? "").toLowerCase());
  const noLoginForm = ssoOnly(oidcEnabled, advanced);
  const companyName = await getSetting("companysettings.sitename", "Al Mudheer");

  if (noLoginForm) {
    return res.send(
      themedPage(
        companyName,
        `
        <div class="loginpanelinner" style="max-width:450px;margin:50px auto;">
          <div class="login-logo text-center"><img src="/assets/images/logo-login.png" alt="Logo" style="max-height:72px"></div>
          <div class="maincontentinner">
            <h4 style="margin-bottom:8px;">Sign in with Microsoft</h4>
            <p>SSO-only mode is enabled for your company.</p>
            <p><a class="btn btn-primary" href="/auth/oidc/start">Continue with Microsoft</a></p>
            <p style="margin-top:12px;"><small>Use <code>?advanced=1</code> to show local login.</small></p>
          </div>
        </div>
        `
      )
    );
  }

  return res.send(
    themedPage(
      companyName,
      `
      <div class="loginpanelinner" style="max-width:450px;margin:50px auto;">
        <div class="login-logo text-center"><img src="/assets/images/logo-login.png" alt="Logo" style="max-height:72px"></div>
        <form method="post" action="/auth/login" class="maincontentinner">
          <h4 style="margin-bottom:8px;">Login</h4>
          <p style="margin-bottom:16px;">Use your company account.</p>
          <div class="form-group" style="margin-bottom:10px;">
            <input name="email" type="email" placeholder="Email" class="form-control" required />
          </div>
          <div class="form-group" style="margin-bottom:14px;">
            <input name="password" type="password" placeholder="Password" class="form-control" required />
          </div>
          <button type="submit" class="btn btn-primary">Sign in</button>
          <a href="/auth/oidc/start" class="btn btn-default" style="margin-left:8px;">Continue with Microsoft</a>
        </form>
      </div>
      `
    )
  );
});

app.post("/auth/login", localLogin);
app.get("/auth/logout", logout);
app.get("/auth/oidc/start", startOidc);
app.get(env.OIDC_REDIRECT_PATH, oidcCallback);

app.get("/dashboard", requireAuth, async (req, res) => {
  const companyName = await getSetting("companysettings.sitename", "Al Mudheer");
  res.send(
    themedPage(
      companyName,
      `
      <div class="maincontent" style="padding:30px;">
        <div class="pageheader">
          <div class="pageicon"><span class="fa fa-dashboard"></span></div>
          <div class="pagetitle">
            <h5>${companyName}</h5>
            <h1>Dashboard</h1>
          </div>
        </div>
        <div class="maincontentinner">
          <p>Welcome. Session user: <strong>${req.session.userId}</strong></p>
          <p><a class="btn btn-default" href="/auth/logout">Logout</a></p>
        </div>
      </div>
      `
    )
  );
});

app.get("/settings/company", requireAuth, async (_req, res) => {
  const payload = {
    siteName: await getSetting("companysettings.sitename", "Al Mudheer"),
    microsoftAuth: {
      enabled: toBool(await getSetting("companysettings.microsoftAuth.enabled", "false")),
      issuer: await getDecryptedSetting("companysettings.microsoftAuth.issuer", ""),
      clientId: await getDecryptedSetting("companysettings.microsoftAuth.clientId", ""),
      hasClientSecret: Boolean(await getDecryptedSetting("companysettings.microsoftAuth.clientSecret", "")),
      allowPublicRegistration: toBool(
        await getSetting("companysettings.microsoftAuth.allowPublicRegistration", "false")
      ),
      defaultRole: Number(await getSetting("companysettings.microsoftAuth.defaultRole", "20"))
    }
  };
  res.json(payload);
});

app.post("/settings/company", requireAuth, async (req, res) => {
  const body = req.body as Record<string, string | undefined>;

  await saveSetting("companysettings.sitename", body.name?.trim() || "Al Mudheer");
  await saveSetting("companysettings.microsoftAuth.enabled", body.microsoftAuthEnabled ? "true" : "false");
  await saveSetting(
    "companysettings.microsoftAuth.allowPublicRegistration",
    body.microsoftAuthAllowPublicRegistration ? "true" : "false"
  );
  await saveSetting("companysettings.microsoftAuth.defaultRole", body.microsoftAuthDefaultRole || "20");
  await saveEncryptedSetting("companysettings.microsoftAuth.issuer", body.microsoftAuthIssuer?.trim() || "");
  await saveEncryptedSetting("companysettings.microsoftAuth.clientId", body.microsoftAuthClientId?.trim() || "");
  if (body.microsoftAuthClientSecret && body.microsoftAuthClientSecret.trim() !== "") {
    await saveEncryptedSetting("companysettings.microsoftAuth.clientSecret", body.microsoftAuthClientSecret.trim());
  }

  res.json({ ok: true });
});

app.listen(env.PORT, () => {
  console.log(`Server running at ${env.APP_URL} on port ${env.PORT}`);
});

function themedPage(title: string, body: string): string {
  return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>${title}</title>
  <link rel="icon" href="/favicon.ico" />
  <link rel="stylesheet" href="/dist/css/main.3.7.1.min.css" />
  <link rel="stylesheet" href="/dist/css/app.3.7.1.min.css" />
</head>
<body class="loginpage">
  ${body}
</body>
</html>`;
}
