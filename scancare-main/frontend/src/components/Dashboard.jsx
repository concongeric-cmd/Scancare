import React, { useEffect, useState, useCallback } from 'react';
import Pusher from 'pusher-js';

const dashboardShellStyle = {
  position: 'relative',
  minHeight: '100vh',
  overflow: 'visible'
};

const modalOverlayStyle = {
  position: 'fixed',
  inset: 0,
  backgroundColor: 'rgba(0, 0, 0, 0.65)',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  zIndex: 999999,
  padding: 24
};

const modalCardStyle = {
  width: 420,
  maxWidth: '100%',
  background: '#fff',
  borderRadius: 12,
  padding: 24,
  boxShadow: '0 16px 40px rgba(0,0,0,0.25)',
  position: 'relative',
  zIndex: 1000000
};

export default function Dashboard({ loggedInUser }) {
  const [showModal, setShowModal] = useState(false);
  const [loginRequestData, setLoginRequestData] = useState(null);
  const [statusMessage, setStatusMessage] = useState('');

  const userId = loggedInUser?.id ?? null;

  const handleApprove = useCallback(async () => {
    if (!userId || !loginRequestData?.tempToken) {
      setShowModal(false);
      return;
    }

    try {
      const response = await fetch('/api/auth/approve-login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId,
          tempToken: loginRequestData.tempToken
        })
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data?.error || 'Unable to approve login');
      }

      setStatusMessage('Login approved successfully.');
    } catch (error) {
      console.error('Approve login failed', error);
      setStatusMessage('Unable to approve login right now.');
    } finally {
      setShowModal(false);
      setLoginRequestData(null);
    }
  }, [userId, loginRequestData]);

  const handleReject = useCallback(async () => {
    if (!userId || !loginRequestData?.tempToken) {
      setShowModal(false);
      return;
    }

    try {
      const response = await fetch('/api/auth/reject-login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId,
          tempToken: loginRequestData.tempToken
        })
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data?.error || 'Unable to reject login');
      }

      setStatusMessage('Login rejected.');
    } catch (error) {
      console.error('Reject login failed', error);
      setStatusMessage('Unable to reject login right now.');
    } finally {
      setShowModal(false);
      setLoginRequestData(null);
    }
  }, [userId, loginRequestData]);

  useEffect(() => {
    if (!userId) return;

    const key = process.env.REACT_APP_PUSHER_KEY;
    const cluster = process.env.REACT_APP_PUSHER_CLUSTER || 'ap1';
    const channelName = `user-${userId}`;

    if (!key) return;

    const pusher = new Pusher(key, {
      cluster,
      forceTLS: true,
      disableStats: true,
      enableStats: false,
      enabledTransports: ['ws', 'wss'],
      activityTimeout: 120000,
      pongTimeout: 30000,
      unavailableTimeout: 10000
    });

    pusher.connection.bind('state_change', (states) => {
      console.log('Pusher state change', states);
    });

    pusher.connection.bind('error', (err) => {
      console.error('Pusher connection error', err);
    });

    const channel = pusher.subscribe(channelName);

    channel.bind('pusher:subscription_succeeded', () => {
      console.log('Pusher channel subscription succeeded', channelName);
    });

    const onLoginRequest = (data) => {
      console.log('login-request event received', data);
      alert('Pusher Connected & Alert Received!');
      const safeData = {
        deviceId: data?.deviceId ? String(data.deviceId) : 'unknown device',
        location: data?.location ? String(data.location) : 'unknown location',
        tempToken: data?.tempToken ? String(data.tempToken) : null,
        requestedAt: data?.requestedAt ? String(data.requestedAt) : new Date().toISOString()
      };
      setStatusMessage('');
      setLoginRequestData(safeData);
      setShowModal(true);
    };

    channel.bind('login-request', onLoginRequest);

    return () => {
      try {
        channel.unbind('login-request', onLoginRequest);
        channel.unbind_all();
        channel.unsubscribe(channelName);
      } catch (error) {
        console.warn('Channel cleanup warning', error);
      }

      try {
        pusher.disconnect();
      } catch (error) {
        console.warn('Pusher disconnect warning', error);
      }
    };
  }, [userId]);

  return (
    <div style={dashboardShellStyle}>
      <h1>ScanCare Dashboard</h1>
      <p>Logged in as: {loggedInUser?.name ?? 'Unknown user'}</p>

      {statusMessage ? (
        <p
          role="status"
          style={{
            marginTop: 12,
            padding: '10px 12px',
            background: '#eef7ee',
            border: '1px solid #b7d7b0',
            borderRadius: 6,
            color: '#235b26'
          }}
        >
          {statusMessage}
        </p>
      ) : null}

      {showModal && loginRequestData && (
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="login-request-title"
          style={modalOverlayStyle}
        >
          <div style={modalCardStyle}>
            <h2 id="login-request-title">Is this you?</h2>
            <p>
              A login request came from device <strong>{loginRequestData.deviceId}</strong>.
            </p>
            <p>Location: {loginRequestData.location}</p>
            <p style={{ color: '#555', fontSize: 12 }}>
              Requested at: {loginRequestData.requestedAt}
            </p>

            <div style={{ marginTop: 20, display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
              <button
                onClick={handleReject}
                style={{
                  padding: '10px 16px',
                  background: '#f3f3f3',
                  border: '1px solid #ccc',
                  borderRadius: 6,
                  cursor: 'pointer'
                }}
              >
                Reject
              </button>
              <button
                onClick={handleApprove}
                style={{
                  padding: '10px 16px',
                  background: '#1d72b8',
                  color: '#fff',
                  border: 'none',
                  borderRadius: 6,
                  cursor: 'pointer'
                }}
              >
                Approve
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
