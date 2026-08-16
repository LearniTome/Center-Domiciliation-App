#!/usr/bin/env node
// ==========================================
//  Wrapper MCP MySQL — lit la connexion depuis .env
//  Usage (opencode.json) :
//    "mysql-dev": {
//      "type": "local",
//      "command": ["node", "scripts/mysql-mcp.mjs"],
//      "enabled": true
//    }
//  Construit l'URL mysql://user:pass@host:port/db depuis
//  DB_HOST / DB_PORT / DB_NAME / DB_USERNAME / DB_PASSWORD
// ==========================================
import { spawn } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

function parseEnvFile(file) {
  const result = {};
  if (!existsSync(file)) return result;
  const raw = readFileSync(file, 'utf8');
  for (const line of raw.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#') || !trimmed.includes('=')) continue;
    const idx = trimmed.indexOf('=');
    const key = trimmed.slice(0, idx).trim();
    let value = trimmed.slice(idx + 1).trim();
    value = value.replace(/^["']|["']$/g, '');
    result[key] = value;
  }
  return result;
}

const env = { ...parseEnvFile(path.join(projectRoot, '.env')), ...parseEnvFile(path.join(projectRoot, '.env.local')) };

const host = env.DB_HOST || '127.0.0.1';
const port = env.DB_PORT || '3306';
const db = env.DB_NAME || 'center_domiciliation';
const user = env.DB_USERNAME || 'root';
const pass = env.DB_PASSWORD || '';
const auth = pass ? `${user}:${pass}` : user;
const url = `mysql://${auth}@${host}:${port}/${db}`;

const permissions = process.argv[2] || 'list,read,create,update,delete,ddl,transaction';
const child = spawn('npx', ['-y', '@berthojoris/mcp-mysql-server', url, permissions], {
  stdio: 'inherit',
  shell: process.platform === 'win32',
});

child.on('error', (err) => {
  console.error('[mysql-mcp] Erreur de lancement:', err.message);
  process.exit(1);
});
child.on('exit', (code) => process.exit(code ?? 1));
