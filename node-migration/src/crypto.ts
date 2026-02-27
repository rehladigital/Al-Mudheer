import crypto from "node:crypto";
import { env } from "./config.js";

function normalizeKey(secret: string): Buffer {
  const hash = crypto.createHash("sha256").update(secret).digest();
  return hash;
}

export function encryptText(value: string): string {
  const iv = crypto.randomBytes(16);
  const key = normalizeKey(env.ENCRYPTION_KEY);
  const cipher = crypto.createCipheriv("aes-256-cbc", key, iv);
  const encrypted = Buffer.concat([cipher.update(value, "utf8"), cipher.final()]);
  return `enc::${iv.toString("hex")}:${encrypted.toString("hex")}`;
}

export function decryptText(value: string): string {
  if (!value.startsWith("enc::")) {
    return value;
  }

  const payload = value.slice(5);
  const [ivHex, dataHex] = payload.split(":");
  if (!ivHex || !dataHex) {
    return "";
  }

  const key = normalizeKey(env.ENCRYPTION_KEY);
  const decipher = crypto.createDecipheriv("aes-256-cbc", key, Buffer.from(ivHex, "hex"));
  const decrypted = Buffer.concat([decipher.update(Buffer.from(dataHex, "hex")), decipher.final()]);
  return decrypted.toString("utf8");
}
