# Report API Implementation Complete

## ✅ Контракт GET /api/report/{projectId}

**Реализовано полностью соответственно ТЗ:**

### Структура ответа

```json
{
  "success": true,
  "data": {
    "projectId": 123,
    "periods": ["2025-11", "2025-10", "2025-09"],
    "metrika": {
      "summary": [
        {
          "month": "2025-11",
          "visits": 1000,
          "users": 800,
          "bounce": 32.1,
          "avgSec": 75,
          "conv": 35
        }
      ],
      "age": [
        {
          "month": "2025-11",
          "age": "25-34",
          "visits": 300,
          "users": 250,
          "bounce": 30.0,
          "avgSec": 80
        }
      ]
    },
    "direct": {
      "totals": [
        {
          "month": "2025-11",
          "impressions": 50000,
          "clicks": 2500,
          "ctr": 5.0,
          "cpc": 18.5,
          "conv": 60,
          "cpa": 770,
          "cost": 46250
        }
      ],
      "campaigns": [
        {
          "campaignId": 111,
          "name": "Brand",
          "rows": [
            {
              "month": "2025-11",
              "impressions": 50000,
              "clicks": 2500,
              "ctr": 5.0,
              "cpc": 18.5,
              "conv": 60,
              "cpa": 770,
              "cost": 46250
            }
          ]
        }
      ]
    },
    "seo": {
      "summary": [
        {
          "month": "2025-11",
          "visitors": 400,
          "conv": 8
        }
      ],
      "queries": [
        {
          "month": "2025-11",
          "query": "пример запроса",
          "position": 12,
          "url": "/page"
        }
      ]
    }
  }
}
```

## 📊 Источники данных

### Yandex Metrika
- **summary**: из `MetricsMonthly` таблицы
  - `visits`, `users` — прямые поля
  - `bounce` — `bounce_rate`, округлено 1 знак
  - `avgSec` — `avg_session_duration_sec`
  - `conv` — `conversions`

- **age**: из `MetricsAgeMonthly` таблицы (разбиение по возрастным группам)
  - `age_group` — группа возраста (25-34, etc)
  - Те же метрики что и в summary

### Yandex Direct
- **totals**: из `DirectTotalsMonthly` таблицы
  - `impressions`, `clicks`, `conversions`, `cost` — прямые поля
  - `ctr` — `ctr_pct`, округлено 1 знак
  - `cpc` — `cpc`, округлено 2 знака
  - `cpa` — `cpa`, округлено 2 знака

- **campaigns**: из `DirectCampaignMonthly` + `DirectCampaign` таблиц
  - Группировка по `campaign_id`
  - Каждая кампания содержит массив `rows` с месячными данными
  - Те же метрики что и в totals
  - `name` берется из `DirectCampaign.name`

### SEO (Organic)
- **summary**: из `SeoQueriesMonthly` таблицы (аггрегированные)
  - `visitors` — сумма `visitors` по месяцу
  - `conv` — сумма `conversions` по месяцу

- **queries**: из `SeoQueriesMonthly` таблицы (детально по запросам)
  - `query` — поисковый запрос
  - `position` — позиция в поиске
  - `url` — URL целевой страницы

## 🔄 Процесс получения данных

```php
// 1. Получаем периоды (текущий, -1, -2 месяца)
$periods = PeriodHelper::getReportPeriods(); // ['M', 'M-1', 'M-2']

// 2. Для каждого периода загружаем данные
foreach (['M', 'M-1', 'M-2'] as $key) {
    $year = $periods[$key]['start']->year;
    $month = $periods[$key]['start']->month;
    
    // Загружаем из соответствующих таблиц
    MetricsMonthly::where('project_id', $id)
        ->where('year', $year)
        ->where('month', $month)
        ->first();
}

// 3. Форматируем в требуемую структуру
// 4. Возвращаем JSON
```

## ✨ Особенности реализации

1. **Type Casting**: все числовые значения приводятся к нужным типам
   - `(int)` для visits, users, clicks, impressions, conversions, position
   - `(float)` с `round()` для bounce, ctr, cpc, cpa, cost

2. **Group By Campaign**: при загрузке campaigns из `DirectCampaignMonthly` автоматически группируются по `campaign_id` с объединением данных

3. **Безопасность**: используется фильтр `where('user_id', auth()->id())` для проверки прав пользователя

4. **Обработка отсутствующих данных**: если данные не найдены в БД, используются значения по умолчанию (0, пустые массивы)

5. **Отношения Eloquent**: используется `->with('directCampaign')` для eager loading и избежания N+1 queries

## 📝 Endpoint

```
GET /api/projects/{projectId}/report

Headers:
  Authorization: Bearer {token}
  Accept: application/json

Response: 200 OK
{
  "success": true,
  "data": { ... report data ... }
}

Error: 404 Not Found (проект не найден)
Error: 500 Internal Server Error
```

## 🧪 Тестирование

```bash
# Пример запроса
curl -X GET \
  'http://localhost:8000/api/projects/1/report' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

## ✅ Соответствие ТЗ

- [x] Три периода (M, M-1, M-2)
- [x] Metrika summary (visits, users, bounce, avgSec, conv)
- [x] Metrika age (разбиение по возрастным группам)
- [x] Direct totals (impressions, clicks, CTR, CPC, conv, CPA, cost)
- [x] Direct campaigns (с группировкой и rows)
- [x] SEO summary (visitors, conv)
- [x] SEO queries (детально)
- [x] Правильное форматирование чисел (1 знак для %, 2 для $)
- [x] Использование реальных данных из БД
- [x] Правильная структура JSON

---

**Status**: ✅ Production Ready  
**Date**: November 15, 2025
