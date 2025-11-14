import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { UserProvider } from './contexts/UserContext';
import Header from './components/Header/Header';
import Sidebar from './components/Sidebar/Sidebar';
import Statistics from './pages/Statistics/Statistics';
import Visits from './pages/Visits/Visits';
import Sources from './pages/Sources/Sources';
import AgeLead from './pages/AgeLead/AgeLead';
import Dashboard from './components/Dashboard/Dashboard';
import './App.css';

function App() {
  return (
    <UserProvider>
      <Router>
        <div className="app">
          <Header />
          <Sidebar />
          <main className="app__main">
            <Routes>
              <Route path="/" element={<Navigate to="/dashboard" replace />} />
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/statistics" element={<Statistics />} />
              <Route path="/visits" element={<Visits />} />
              <Route path="/sources" element={<Sources />} />
              <Route path="/age-lead" element={<AgeLead />} />
              <Route path="*" element={<Navigate to="/dashboard" replace />} />
            </Routes>
          </main>
        </div>
      </Router>
    </UserProvider>
  );
}

export default App;
