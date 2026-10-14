import React from 'react';
import ReactDOM from 'react-dom/client';
import Dashboard from './components/Dashboard';

const rootElement = document.getElementById('root');
const root = ReactDOM.createRoot(rootElement);

const loggedInUser = { id: 'user123', name: 'ScanCare User' };

root.render(
  <React.StrictMode>
    <Dashboard loggedInUser={loggedInUser} />
  </React.StrictMode>
);
