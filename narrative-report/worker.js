/**
 * Narrative Report Maker — AI server (Cloudflare Worker)
 *
 * Keeps your Anthropic API key secret and lets the GitHub page ask Claude
 * to write the report. No key is stored in this file.
 *
 * Setup (Cloudflare dashboard):
 *   1. Workers & Pages → Create → Worker → name it "narrative-ai" → Deploy.
 *   2. Edit code → replace everything with this file → Deploy.
 *   3. Settings → Variables and Secrets → Add:
 *        Type: Secret   Name: ANTHROPIC_API_KEY   Value: your key (sk-ant-...)
 *   4. Copy the worker's URL (https://narrative-ai.<you>.workers.dev).
 *
 * Optional variables (Settings → Variables and Secrets, type Text):
 *   MODEL            default "claude-sonnet-5-5"  (use "claude-haiku-5-5" to spend less)
 *   ALLOWED_ORIGINS  default "https://jaijr711.github.io"  (comma-separated)
 *   MAX_PER_10MIN    default 20  (requests per visitor every 10 minutes)
 *
 * Also set a monthly spend limit in the Anthropic Console so the link can
 * never cost more than you planned.
 */

const PREFIX = 'You are helping a Filipino college student write the narrative report';
const MAX_PROMPT_CHARS = 20000;
const hits = new Map(); // per-visitor request times (best effort, per server instance)

export default {
  async fetch(request, env) {
    const allowed = String(env.ALLOWED_ORIGINS || 'https://jaijr711.github.io')
      .split(',').map(s => s.trim()).filter(Boolean);
    const origin = request.headers.get('Origin') || '';
    const cors = {
      'Access-Control-Allow-Origin': allowed.includes(origin) ? origin : allowed[0],
      'Access-Control-Allow-Methods': 'POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type',
      'Access-Control-Max-Age': '86400',
      'Vary': 'Origin',
    };
    const reply = (obj, status = 200) =>
      new Response(JSON.stringify(obj), { status, headers: { ...cors, 'Content-Type': 'application/json' } });

    if (request.method === 'OPTIONS') return new Response(null, { status: 204, headers: cors });
    if (request.method === 'GET') return reply({ ok: true, service: 'narrative-ai', keySet: !!env.ANTHROPIC_API_KEY });
    if (request.method !== 'POST') return reply({ error: 'Use POST' }, 405);
    if (!allowed.includes(origin)) return reply({ error: 'This page is not allowed to use the AI server.' }, 403);
    if (!env.ANTHROPIC_API_KEY) return reply({ error: 'The AI server has no ANTHROPIC_API_KEY yet.' }, 500);

    // Simple per-visitor limit
    const ip = request.headers.get('CF-Connecting-IP') || 'unknown';
    const now = Date.now(), windowMs = 10 * 60 * 1000;
    const max = Math.max(1, Number(env.MAX_PER_10MIN) || 20);
    const recent = (hits.get(ip) || []).filter(t => now - t < windowMs);
    if (recent.length >= max) return reply({ error: 'Too many requests. Wait a few minutes.' }, 429);
    recent.push(now); hits.set(ip, recent);
    if (hits.size > 5000) hits.clear();

    let body;
    try { body = await request.json(); } catch { return reply({ error: 'Bad request' }, 400); }
    const prompt = typeof body?.prompt === 'string' ? body.prompt : '';
    if (!prompt.startsWith(PREFIX) || prompt.length > MAX_PROMPT_CHARS) return reply({ error: 'Invalid request' }, 400);

    let res;
    try {
      res = await fetch('https://api.anthropic.com/v1/messages', {
        method: 'POST',
        headers: {
          'x-api-key': env.ANTHROPIC_API_KEY,
          'anthropic-version': '2023-06-01',
          'content-type': 'application/json',
        },
        body: JSON.stringify({
          model: env.MODEL || 'claude-sonnet-5-5',
          max_tokens: 3000,
          messages: [{ role: 'user', content: prompt }],
        }),
      });
    } catch {
      return reply({ error: 'Could not reach the AI. Try again.' }, 502);
    }

    if (res.status === 429 || res.status === 529) return reply({ error: 'The AI is busy. Try again in a minute.' }, 429);
    if (!res.ok) {
      const detail = (await res.text()).slice(0, 300);
      const noCredit = /credit|billing|balance|spend/i.test(detail);
      return reply({ error: noCredit ? 'The AI account is out of credits or reached its spend limit.' : 'The AI could not answer.', status: res.status }, noCredit ? 402 : 502);
    }

    const data = await res.json();
    const text = (data.content || []).filter(c => c.type === 'text').map(c => c.text).join('');
    if (!text.trim()) return reply({ error: 'The AI gave an empty answer.' }, 502);
    return reply({ text, truncated: data.stop_reason === 'max_tokens' });
  },
};
