import { PrismaClient } from "@prisma/client";
import { hashPassword } from "../src/password.js";

const prisma = new PrismaClient();

async function main() {
  const baseSettings: Array<{ key: string; value: string }> = [
    { key: "companysettings.sitename", value: "Al Mudheer" },
    { key: "companysettings.microsoftAuth.enabled", value: "false" },
    { key: "companysettings.microsoftAuth.allowPublicRegistration", value: "false" },
    { key: "companysettings.microsoftAuth.defaultRole", value: "20" },
    { key: "auth.hideDefaultLogin", value: "off" }
  ];

  for (const item of baseSettings) {
    await prisma.setting.upsert({
      where: { key: item.key },
      update: { value: item.value },
      create: item
    });
  }

  const initialAdminEmail = process.env.INITIAL_ADMIN_EMAIL ?? "admin@example.com";
  const initialAdminPassword = process.env.INITIAL_ADMIN_PASSWORD ?? "ChangeMe123!";

  await prisma.user.upsert({
    where: { email: initialAdminEmail },
    update: {
      firstName: "Yasser",
      lastName: "Rehla",
      role: 50,
      source: "local",
      status: "a",
      passwordHash: hashPassword(initialAdminPassword)
    },
    create: {
      email: initialAdminEmail,
      firstName: "Yasser",
      lastName: "Rehla",
      role: 50,
      source: "local",
      status: "a",
      passwordHash: hashPassword(initialAdminPassword)
    }
  });
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
