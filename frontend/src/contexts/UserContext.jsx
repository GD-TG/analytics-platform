import React, { createContext, useState, useEffect } from 'react';

const UserContext = createContext();

const defaultUser = {
  id: null,
  firstName: 'Айдар',
  lastName: '',
  email: '',
  avatar: 'А',
  role: 'user'
};

export const UserProvider = ({ children }) => {
  const [user, setUser] = useState(() => {
    const savedUser = localStorage.getItem('user');
    if (savedUser) {
      try {
        const parsed = JSON.parse(savedUser);
        return {
          ...defaultUser,
          ...parsed,
          firstName: parsed.first_name || parsed.firstName || defaultUser.firstName,
          lastName: parsed.last_name || parsed.lastName || defaultUser.lastName,
        };
      } catch {
        return defaultUser;
      }
    }
    return defaultUser;
  });

  // Синхронизируем с localStorage при изменении
  useEffect(() => {
    const savedUser = localStorage.getItem('user');
    if (savedUser) {
      try {
        const parsed = JSON.parse(savedUser);
        setUser({
          ...defaultUser,
          ...parsed,
          firstName: parsed.first_name || parsed.firstName || defaultUser.firstName,
          lastName: parsed.last_name || parsed.lastName || defaultUser.lastName,
        });
      } catch {
        // ignore
      }
    }
  }, []);

  return (
    <UserContext.Provider value={{ user, setUser }}>
      {children}
    </UserContext.Provider>
  );
};

export { UserContext };