#!/usr/bin/env node

const STITCH_URL = 'https://stitch.googleapis.com/mcp';
const API_KEY = process.env.STITCH_API_KEY;
if (!API_KEY) { process.stderr.write('Error: STITCH_API_KEY env var not set\n'); process.exit(1); }

let buffer = '';

process.stdin.setEncoding('utf8');
process.stdin.on('data', async (chunk) => {
  buffer += chunk;
  const lines = buffer.split('\n');
  buffer = lines.pop() ?? '';

  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed) continue;

    let parsed;
    try {
      parsed = JSON.parse(trimmed);
    } catch {
      continue;
    }

    // Skip client→server notifications (no id) — they don't expect a response
    if (parsed.id === undefined && parsed.method) {
      continue;
    }

    try {
      const response = await fetch(STITCH_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json, text/event-stream',
          'X-Goog-Api-Key': API_KEY,
        },
        body: trimmed,
      });

      const text = await response.text();

      // Parse SSE or plain JSON responses
      for (const respLine of text.split('\n')) {
        const rt = respLine.trim();
        if (rt.startsWith('data: ')) {
          const json = rt.slice(6).trim();
          if (json && json !== '[DONE]') process.stdout.write(json + '\n');
        } else if (rt && !rt.startsWith(':') && !rt.startsWith('event:')) {
          process.stdout.write(rt + '\n');
        }
      }
    } catch (err) {
      process.stderr.write(`Error: ${err.message}\n`);
    }
  }
});
