import type { VercelRequest, VercelResponse } from '@vercel/node';

const BOT_TOKEN = process.env.BOT_TOKEN || 'YOUR_BOT_TOKEN';

// Helper to send messages
async function sendMessage(chat_id: number | string, text: string) {
  const url = `https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`;
  await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ chat_id, text }),
  });
}

export default async function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') {
    return res.status(405).send('Method Not Allowed');
  }

  try {
    // Parse body if it's a string
    const update = typeof req.body === 'string' ? JSON.parse(req.body) : req.body;

    // --- Message handling ---
    if (update.message) {
      const chat_id = update.message.chat.id;
      const text = update.message.text;

      console.log('Received message:', text);

      if (text === '/start') {
        await sendMessage(chat_id, 'Welcome! Your bot is now active.');
      } else if (text?.startsWith('/echo ')) {
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
