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
import Settings from './pages/Settings/Settings';
import Purchases from './pages/Purchases/Purchases';
import Tasks from './pages/Tasks/Tasks';
import Resources from './pages/Resources/Resources';
import Finance from './pages/Finance/Finance';
import Logistics from './pages/Logistics/Logistics';
import Innovation from './pages/Innovation/Innovation';
import Production from './pages/Production/Production';
import Company from './pages/Company/Company';
import Marketing from './pages/Marketing/Marketing';
import Documents from './pages/Documents/Documents';
import Processes from './pages/Processes/Processes';
import Login from './pages/Login/Login';
import Register from './pages/Register/Register';
import YandexCallback from './pages/YandexCallback/YandexCallback';
import ProtectedRoute from './components/ProtectedRoute/ProtectedRoute';
import './App.css';

function App() {
  return (
    <UserProvider>
      <Router>
        <Routes>
          {/* Публичные маршруты (без Header и Sidebar) */}
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/auth/yandex/callback" element={<YandexCallback />} />
          
          {/* Корневой маршрут - перенаправление на login или dashboard */}
          <Route path="/" element={<Navigate to="/login" replace />} />
          
          {/* Защищенные маршруты (с Header и Sidebar) */}
          <Route
            path="/*"
            element={
              <ProtectedRoute>
                <div className="app">
                  <Header />
                  <Sidebar />
                  <main className="app__main">
                    <Routes>
                      <Route path="/dashboard" element={<Dashboard />} />
                      <Route path="/statistics" element={<Statistics />} />
                      <Route path="/visits" element={<Visits />} />
                      <Route path="/sources" element={<Sources />} />
                      <Route path="/age-lead" element={<AgeLead />} />
                      <Route path="/purchases" element={<Purchases />} />
                      <Route path="/tasks" element={<Tasks />} />
                      <Route path="/resources" element={<Resources />} />
                      <Route path="/finance" element={<Finance />} />
                      <Route path="/logistics" element={<Logistics />} />
                      <Route path="/innovation" element={<Innovation />} />
                      <Route path="/production" element={<Production />} />
                      <Route path="/company" element={<Company />} />
                      <Route path="/marketing" element={<Marketing />} />
                      <Route path="/documents" element={<Documents />} />
                      <Route path="/processes" element={<Processes />} />
                      <Route path="/settings" element={<Settings />} />
                      <Route path="*" element={<Navigate to="/dashboard" replace />} />
                    </Routes>
                  </main>
                </div>
              </ProtectedRoute>
            }
          />
        </Routes>
      </Router>
    </UserProvider>
  );
}

export default App;
