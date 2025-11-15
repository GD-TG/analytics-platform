const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

class ApiClient {
  constructor(baseURL = API_BASE_URL) {
    this.baseURL = baseURL;
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    
    // Получаем токен из localStorage
    const token = localStorage.getItem('token');
    
    const config = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(token && { 'Authorization': `Bearer ${token}` }),
        ...options.headers,
      },
      ...options,
    };

    try {
      const response = await fetch(url, config);
      
      // Проверяем, есть ли ответ вообще
      if (!response) {
        throw new Error('Network error: No response from server. Make sure backend is running on http://localhost:8000');
      }
      
      // Пытаемся получить JSON, даже если статус не OK
      let data;
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        data = await response.json();
      } else {
        const text = await response.text();
        throw new Error(`Server error: ${text || `HTTP ${response.status}`}`);
      }
      
      if (!response.ok) {
        // Обработка ошибок валидации
        if (response.status === 422 && data.errors) {
          const errorMessages = Object.values(data.errors).flat().join(', ');
          throw new Error(errorMessages || data.message || 'Validation error');
        }
        
        throw new Error(data.message || data.error || `HTTP error! status: ${response.status}`);
      }

      return data;
    } catch (error) {
      // Если это уже наша ошибка, просто пробрасываем
      if (error.message && !error.message.includes('Failed to fetch')) {
        throw error;
      }
      
      // Обработка сетевых ошибок
      if (error.message === 'Failed to fetch' || error.name === 'TypeError' || error.message.includes('NetworkError')) {
        console.error('Network error:', error);
        // Для ProtectedRoute не показываем ошибку, чтобы не блокировать работу
        if (endpoint.includes('/auth/me')) {
          throw new Error('SERVER_OFFLINE');
        }
        throw new Error('Не удалось подключиться к серверу. Убедитесь, что backend запущен на http://localhost:8000');
      }
      
      console.error('API request failed:', error);
      throw error;
    }
  }

  get(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;
    return this.request(url, { method: 'GET' });
  }

  post(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  put(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }
}

export default new ApiClient();

