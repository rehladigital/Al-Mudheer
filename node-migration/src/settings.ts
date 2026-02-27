import { prisma } from "./db.js";
import { decryptText, encryptText } from "./crypto.js";

export async function getSetting(key: string, fallback = ""): Promise<string> {
  const row = await prisma.setting.findUnique({ where: { key } });
  return row?.value ?? fallback;
}

export async function saveSetting(key: string, value: string): Promise<void> {
  await prisma.setting.upsert({
    where: { key },
    update: { value },
    create: { key, value }
  });
}

export async function saveEncryptedSetting(key: string, value: string): Promise<void> {
  await saveSetting(key, encryptText(value));
}

export async function getDecryptedSetting(key: string, fallback = ""): Promise<string> {
  const value = await getSetting(key, fallback);
  if (!value) return fallback;
  try {
    return decryptText(value);
  } catch {
    return fallback;
  }
}

export function toBool(value: string): boolean {
  return ["1", "true", "on", "yes"].includes(value.toLowerCase());
}
