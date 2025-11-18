import type { VercelRequest, VercelResponse } from '@vercel/node';
import { Readable } from 'stream';

// Helper to get raw body
async function getRawBody(req: VercelRequest): Promise<Buffer> {
  const chunks: Buffer[] = [];
  for await (const chunk of req) {
    chunks.push(typeof chunk === 'string' ? Buffer.from(chunk) : chunk);
  }
  return Buffer.concat(chunks);
}

export default async function handler(req: VercelRequest, res: VercelResponse) {
  try {
    const targetUrl = 'https://api.ephpic.org/hooks/telegram';

    // Get raw request body
    const bodyBuffer = await getRawBody(req);

    // Forward headers except host
    const headers: Record<string, string> = {};
    for (const [key, value] of Object.entries(req.headers)) {
      if (value && key !== 'host') headers[key] = Array.isArray(value) ? value[0] : value;
    }

    // Forward request to real server
    const response = await fetch(targetUrl, {
      method: req.method,
      headers,
      body: ['GET', 'HEAD'].includes(req.method || '') ? undefined : bodyBuffer,
    });

    // Forward response status, headers, and body
    res.status(response.status);
    response.headers.forEach((value, key) => res.setHeader(key, value));
    const responseBody = await response.arrayBuffer();
    res.send(Buffer.from(responseBody));
    
  } catch (err) {
    console.error('Proxy error:', err);
    res.status(500).send('Internal Server Error');
  }
}
