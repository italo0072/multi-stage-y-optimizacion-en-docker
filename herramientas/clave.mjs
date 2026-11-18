import { randomBytes, randomUUID } from "node:crypto";
import { mkdirSync, readFileSync, renameSync, rmdirSync, unlinkSync, writeFileSync } from "node:fs";
import { parseEnv } from "node:util";

const generators = new Map([
  ["APP_KEY", () => "base64:" + randomBytes(32).toString("base64")],
  ["JWT_SECRET", () => randomBytes(32).toString("hex")],
  ["MYSQL_PASSWORD", () => randomBytes(24).toString("hex")],
  ["MYSQL_ROOT_PASSWORD", () => randomBytes(24).toString("hex")],
]);

function valueFromSource(key) {
  const input = readFileSync(0, "utf8").trim();
  if (input === "NUEVA") return generators.get(key)();
  if (!input) throw Error("Falta el origen. Para un volumen existente, recupera su .env anterior o inspecciona sus contenedores.");
  const prefix = key + "=";
  for (const line of input.split(/\r?\n/)) {
    const variables = JSON.parse(line);
    if (!Array.isArray(variables)) throw Error("La inspección de Docker debe devolver sus variables de entorno.");
    const entry = variables.find(v => typeof v === "string" && v.startsWith(prefix));
    if (entry && entry.length > prefix.length) return entry.slice(prefix.length);
  }
  throw Error("El contenedor anterior no contiene " + key + ". Recupera el valor de tu .env anterior.");
}

function dotenvValue(value) {
  if (/[\r\n\0]/.test(value)) throw Error("La clave contiene un salto de línea o un carácter nulo.");
  if (/^[A-Za-z0-9_:+/=.-]+$/.test(value)) return value;
  return "'" + value.replace(/\\/g, "\\\\").replace(/'/g, "\\'") + "'";
}

function saveKey(key) {
  const bytes = readFileSync(".env");
  const content = bytes[0] === 255 && bytes[1] === 254
    ? bytes.subarray(2).toString("utf16le")
    : bytes.toString("utf8").replace(/^\uFEFF/, "");
  const pattern = new RegExp("^[ \\t]*(?:export[ \\t]+)?" + key + "[ \\t]*=[^\\r\\n]*", "gm");
  const matches = [...content.matchAll(pattern)];
  if (matches.length > 1) throw Error(key + " aparece varias veces en .env. Deja una sola línea para esta variable.");
  if (parseEnv(content)[key]?.trim()) {
    console.log(key + " ya tenía valor en .env.");
    return;
  }
  const assignment = key + "=" + dotenvValue(valueFromSource(key));
  const eol = content.includes("\r\n") ? "\r\n" : "\n";
  const updated = matches.length
    ? content.replace(pattern, () => assignment)
    : content + (content && !content.endsWith("\n") ? eol : "") + assignment + eol;
  const temp = ".env." + randomUUID() + ".tmp";
  try {
    writeFileSync(temp, updated, { mode: 0o600, flag: "wx" });
    renameSync(temp, ".env");
  } finally {
    try { unlinkSync(temp); } catch (error) { if (error.code !== "ENOENT") throw error; }
  }
  console.log(key + " guardada en .env.");
}

const key = process.argv[2];
try {
  if (process.argv.length !== 3 || !generators.has(key)) throw Error("Indica una variable: " + [...generators.keys()].join(", "));
  // Serializa actualizaciones para que dos comandos conserven ambos cambios.
  mkdirSync(".env.clave.lock", { mode: 0o700 });
  try { saveKey(key); } finally { rmdirSync(".env.clave.lock"); }
} catch (error) {
  const message = error.code === "ENOENT" ? "Crea .env junto a docker-compose.yml copiando .env.example."
    : error.code === "EEXIST" ? "Otra escritura tiene el bloqueo .env.clave.lock. Espera a que termine."
    : error instanceof SyntaxError ? "No se pudo leer la inspección de Docker. Vuelve a obtener el origen."
    : error.message;
  console.error(message);
  process.exitCode = 1;
}
