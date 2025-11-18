import type { VercelRequest, VercelResponse } from '@vercel/node';
import fetch from 'node-fetch';

// Telegram bot token from environment
const BOT_TOKEN = process.env.BOT_TOKEN;
if (!BOT_TOKEN) {
  console.error('BOT_TOKEN is not defined in environment variables.');
}

// Helper to send messages
async function sendMessage(chat_id: number, text: string) {
  const url = `https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`;
  await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ chat_id, text }),
  });
}

// Main webhook handler
export default async function handler(req: VercelRequest, res: VercelResponse) {
  // Allow GET for quick test in browser
  if (req.method === 'GET') {
    return res.status(200).send('Telegram webhook is live!');
  }

  // Only accept POST for actual webhook calls
  if (req.method !== 'POST') {
    return res.status(405).send('Method Not Allowed');
  }

  try {
    const update = req.body;

    // --- Message handling ---
    if (update.message) {
      const chat_id = update.message.chat.id;
      const text = update.message.text || '';

      console.log('Received message:', text);

      if (text === '/start') {
        await sendMessage(chat_id, 'Welcome! Your bot is now active.');
      } else if (text.startsWith('/echo ')) {
        const reply = text.replace('/echo ', '');
        await sendMessage(chat_id, `You said: ${reply}`);
      } else {
        await sendMessage(chat_id, `I received: ${text}`);
      }
    }

    // --- Callback query handling ---
    if (update.callback_query) {
      const chat_id = update.callback_query.message.chat.id;
      const data = update.callback_query.data;
      console.log('Callback query:', data);
      await sendMessage(chat_id, `You clicked: ${data}`);
    }

    res.status(200).send('OK');
  } catch (err) {
    console.error('Error handling Telegram webhook:', err);
    res.status(500).send('Internal Server Error');
  }
}
