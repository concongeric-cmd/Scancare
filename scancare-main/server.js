// server.js
require('dotenv').config();
const crypto = require('crypto');
const express = require('express');
const cors = require('cors');
const Pusher = require('pusher');

const pusherAppId = process.env.PUSHER_APP_ID;
const pusherKey = process.env.PUSHER_KEY;
// Keep PUSHER_SECRET in the backend environment variables and never expose it to the frontend client.
const pusherSecret = process.env.PUSHER_SECRET;
const pusherCluster = process.env.PUSHER_CLUSTER || 'ap1';
const appUrl = process.env.APP_URL;

if (!pusherAppId || !pusherKey || !pusherSecret || !appUrl) {
  throw new Error('PUSHER_APP_ID, PUSHER_KEY, PUSHER_SECRET, and APP_URL are required');
}

const pendingLogins = new Map();

console.log('Using Pusher config:', {
  appId: pusherAppId,
  key: pusherKey,
  secretPresent: Boolean(pusherSecret),
  cluster: pusherCluster
});

const app = express();
app.use(cors({ origin: appUrl }));
app.use(express.json());

const pusher = new Pusher({
  appId: pusherAppId,
  key: pusherKey,
  secret: pusherSecret,
  cluster: pusherCluster,
  useTLS: true
});

app.post('/api/auth/request-login-approval', async (req, res) => {
  try {
    const { userId, deviceId, location } = req.body;
    if (!userId || !deviceId) {
      return res.status(400).json({ error: 'userId and deviceId are required' });
    }

    const tempToken = crypto.randomBytes(32).toString('hex');
    const pendingLogin = {
      userId: String(userId),
      deviceId: String(deviceId),
      location: location ? String(location) : 'unknown location',
      tempToken,
      status: 'pending',
      requestedAt: new Date().toISOString()
    };

    pendingLogins.set(tempToken, pendingLogin);

    await pusher.trigger(`user-${userId}`, 'login-request', {
      deviceId: pendingLogin.deviceId,
      location: pendingLogin.location,
      tempToken,
      requestedAt: pendingLogin.requestedAt
    });

    return res.json({ ok: true, tempToken, status: 'pending' });
  } catch (error) {
    console.error('request-login-approval error', error);
    return res.status(500).json({ error: 'unable_to_request_approval' });
  }
});

app.post('/api/auth/approve-login', async (req, res) => {
  try {
    const { userId, tempToken } = req.body;
    if (!userId || !tempToken) {
      return res.status(400).json({ error: 'userId and tempToken are required' });
    }

    const pendingLogin = pendingLogins.get(tempToken);
    if (!pendingLogin || pendingLogin.userId !== String(userId)) {
      return res.status(404).json({ error: 'pending_login_not_found' });
    }

    pendingLogin.status = 'approved';
    pendingLogin.approvedAt = new Date().toISOString();
    pendingLogins.set(tempToken, pendingLogin);

    await pusher.trigger(`user-${userId}`, 'login-approved', {
      tempToken,
      approvedAt: pendingLogin.approvedAt
    });

    return res.json({ ok: true, approved: true, tempToken, status: 'approved' });
  } catch (error) {
    console.error('approve-login error', error);
    return res.status(500).json({ error: 'unable_to_process_approval' });
  }
});

app.post('/api/auth/reject-login', async (req, res) => {
  try {
    const { userId, tempToken } = req.body;
    if (!userId || !tempToken) {
      return res.status(400).json({ error: 'userId and tempToken are required' });
    }

    const pendingLogin = pendingLogins.get(tempToken);
    if (!pendingLogin || pendingLogin.userId !== String(userId)) {
      return res.status(404).json({ error: 'pending_login_not_found' });
    }

    pendingLogin.status = 'rejected';
    pendingLogin.rejectedAt = new Date().toISOString();
    pendingLogins.set(tempToken, pendingLogin);

    await pusher.trigger(`user-${userId}`, 'login-rejected', {
      tempToken,
      rejectedAt: pendingLogin.rejectedAt
    });

    return res.json({ ok: true, rejected: true, tempToken, status: 'rejected' });
  } catch (error) {
    console.error('reject-login error', error);
    return res.status(500).json({ error: 'unable_to_process_rejection' });
  }
});

app.post('/api/auth/complete-login', async (req, res) => {
  try {
    const { tempToken } = req.body;
    if (!tempToken) {
      return res.status(400).json({ error: 'tempToken is required' });
    }

    const pendingLogin = pendingLogins.get(tempToken);
    if (!pendingLogin || pendingLogin.status !== 'approved') {
      return res.status(401).json({ authorized: false, error: 'login_not_approved' });
    }

    pendingLogins.delete(tempToken);
    return res.json({
      authorized: true,
      userId: pendingLogin.userId,
      deviceId: pendingLogin.deviceId,
      tempToken
    });
  } catch (error) {
    console.error('complete-login error', error);
    return res.status(500).json({ error: 'unable_to_complete_login' });
  }
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => console.log(`Auth backend listening on port ${PORT}`));
