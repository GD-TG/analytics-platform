# ✅ Sprint 2.I: Metrics Dashboard

## Реализовано

### 1. **Laravel API Endpoints**
**Файл:** `app/Http/Controllers/DashboardController.php`

Три основных endpoint'а:

#### **GET /api/dashboard/sync-status**
Возвращает полный статус синхронизации для текущего пользователя:

```json
{
  "accounts": [
    {
      "id": 1,
      "revoked": false,
      "counters": [
        {
          "id": 1,
          "counter_id": 12345,
          "name": "My Website",
          "active": true,
          "last_fetched_at": "2025-11-15T10:30:00Z",
          "status": "synced"
        }
      ]
    }
  ],
  "summary": {
    "total_accounts": 2,
    "active_accounts": 1,
    "total_counters": 5,
    "synced_counters": 4,
    "pending_counters": 1,
    "overdue_counters": 0,
    "sync_percentage": 80,
    "sync_interval_minutes": 60
  }
}
```

**Counter Status:**
- `synced` — недавно синхронизирован
- `pending` — никогда не синхронизировался
- `overdue` — пора синхронизировать
- `inactive` — счётчик отключен

#### **GET /api/dashboard/stats**
Возвращает статистику по метрикам:

```json
{
  "total_records": 1500,
  "counters_with_data": 3,
  "latest_date": "2025-11-15",
  "earliest_date": "2025-05-15",
  "total_visits": 125000,
  "total_users": 45000
}
```

#### **GET /api/dashboard/recent-syncs?limit=5**
Возвращает последние синхронизации:

```json
{
  "syncs": [
    {
      "counter_id": 12345,
      "synced_at": "2025-11-15T10:30:00Z",
      "time_ago": "5m ago"
    },
    {
      "counter_id": 67890,
      "synced_at": "2025-11-15T09:45:00Z",
      "time_ago": "1h ago"
    }
  ]
}
```

### 2. **React Dashboard Component**
**Файл:** `frontend/src/pages/Dashboard/Dashboard.jsx`

Полнофункциональный React компонент с несколькими подкомпонентами:

#### **Функциональность:**
- ✅ Автоматическое обновление каждые 30 секунд
- ✅ Загрузка данных с трёх endpoint'ов параллельно
- ✅ Обработка ошибок и retry
- ✅ Responsive дизайн

#### **Подкомпоненты:**

**SyncStatusSection**
- Summary cards (Accounts, Counters, Sync %, Interval)
- Progress bar визуализация
- Alerts для pending и overdue счётчиков
- Развёртываемые account cards

**AccountCard**
- Развертывание/схлопывание счётчиков
- Статус аккаунта (ACTIVE/REVOKED)
- Количество счётчиков

**CounterItem**
- Статус с цветовой кодировкой
- Время последней синхронизации
- Badge со статусом

**StatsSection**
- Grid из 4 cards (Total Records, Counters, Visits, Users)
- Дата-диапазон (earliest/latest)

**RecentSyncsSection**
- Список последних синхронизаций
- Relative time ("5m ago", "1h ago")

### 3. **CSS Стилизация**
**Файл:** `frontend/src/pages/Dashboard/Dashboard.css`

Современный, responsive дизайн:

#### **Особенности:**
- ✅ Gradient backgrounds (purple/blue)
- ✅ Smooth transitions и hover effects
- ✅ Color-coded status (green/yellow/red)
- ✅ Responsive grid layout
- ✅ Mobile optimizations
- ✅ Spinner анимация для loading

#### **Цветовая схема:**
- **Success** (✅ Synced): зелёный (#4caf50)
- **Warning** (⏳ Pending): жёлтый (#ffc107)
- **Danger** (🔴 Overdue): красный (#f44336)
- **Disabled** (⏹️ Inactive): серый (#ccc)
- **Primary**: фиолетовый (#667eea)

### 4. **API Routes**
**Файл:** `routes/api.php`

Добавлены три защищённые маршруты (require auth:sanctum):

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard/sync-status', [DashboardController::class, 'getSyncStatus']);
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/recent-syncs', [DashboardController::class, 'getRecentSyncs']);
});
```

## Примеры использования

### В React:
```jsx
import Dashboard from './pages/Dashboard/Dashboard';

function App() {
  return <Dashboard />;
}
```

### Fetch напрямую:
```javascript
const token = localStorage.getItem('auth_token');

// Get sync status
const response = await fetch('/api/dashboard/sync-status', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const data = await response.json();
console.log(data.summary.sync_percentage);
```

## Database Queries

Dashboard использует efficient queries:

**Sync Status:**
```sql
SELECT * FROM yandex_accounts 
WHERE user_id = ? AND deleted_at IS NULL
WITH counters
```

**Stats:**
```sql
SELECT COUNT(*), MAX(date), SUM(visits), SUM(users)
FROM metrics_monthly
JOIN yandex_counters ON metrics_monthly.counter_id = yandex_counters.id
JOIN projects ON yandex_counters.project_id = projects.id
WHERE projects.user_id = ?
```

**Recent Syncs:**
```sql
SELECT counter_id, last_fetched_at
FROM yandex_counters
WHERE project_id IN (SELECT id FROM projects WHERE user_id = ?)
ORDER BY last_fetched_at DESC
LIMIT 5
```

## Features

✅ **Real-time Updates**
- Auto-refresh every 30 seconds
- Manual refresh button

✅ **Comprehensive Status**
- Per-account status
- Per-counter status
- Overall sync percentage

✅ **Visual Feedback**
- Color-coded status badges
- Progress bars
- Status icons (✅ 🔴 ⏳ ⏹️)
- Expandable account sections

✅ **Statistics**
- Total records in database
- Latest/earliest dates
- Aggregate visits & users

✅ **Recent Activity**
- Last 5 syncs
- Relative timestamps ("5m ago")

✅ **Error Handling**
- Network error display
- Retry button
- Graceful fallbacks

✅ **Responsive**
- Desktop (1400px+)
- Tablet (768px-1399px)
- Mobile (< 768px)

## Логирование

Dashboard логирует:
- Fetches в console (dev)
- Errors в console.error
- API errors в JSON format

## Интеграция с других компонентов

Dashboard можно встроить в:
1. **Main navigation** — отдельная страница
2. **Sidebar widget** — компактный синопсис
3. **Home page** — вторая карточка на главной

Например:
```jsx
<Link to="/dashboard">📊 Dashboard</Link>
```

## Статус

✅ **COMPLETED** — Dashboard готов к production использованию

**Дальше:**
- Sprint 2.J: PDF export (отчёты)
- Sprint 2.K: AI insights (анализ трендов)
- Sprint 2.L: Admin panel (управление пользователями)

