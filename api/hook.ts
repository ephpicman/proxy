import type { VercelRequest, VercelResponse } from '@vercel/node';
import fetch from 'node-fetch';

// Get bot token from environment
const BOT_TOKEN = process.env.BOT_TOKEN;

// Fail fast if BOT_TOKEN not set
if (!BOT_TOKEN) {
  console.error('BOT_TOKEN is not defined!');
}

// Helper to send messages
async function sendMessage(chat_id: number, text: string) {
  if (!BOT_TOKEN) return;
  const url = `https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`;
  await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ chat_id, text }),
  });
}

// Main webhook handler
export default async function handler(req: VercelRequest, res: VercelResponse) {
  try {
    // Allow GET for testing in browser
    if (req.method === 'GET') {
      return res.status(200).send('Telegram webhook is live!');
    }

    // Only accept POST for actual Telegram updates
    if (req.method !== 'POST') {
      return res.status(405).send('Method Not Allowed');
    }

    const update = req.body;

    // Make sure we have a valid update object
    if (!update) {
      return res.status(400).send('Bad Request: Empty body');
    }

    // Handle messages
    if (update.message) {
      const chat_id = update.message.chat?.id;
      const text = update.message.text || '';

      if (chat_id) {
        if (text === '/start') {
          await sendMessage(chat_id, 'Welcome! Your bot is active.');
        } else if (text.startsWith('/echo ')) {
          const reply = text.slice(6);
          await sendMessage(chat_id, `You said: ${reply}`);
        } else {
          await sendMessage(chat_id, `I received: ${text}`);
        }
      }
    }

    // Handle callback queries
    if (update.callback_query) {
      const chat_id = update.callback_query.message?.chat?.id;
      const data = update.callback_query.data || '';
      if (chat_id) {
        await sendMessage(chat_id, `You clicked: ${data}`);
      }
    }

    res.status(200).send('OK');
  } catch (err) {
    console.error('Error in Telegram webhook:', err);
    res.status(500).send('Internal Server Error');
  }
}
