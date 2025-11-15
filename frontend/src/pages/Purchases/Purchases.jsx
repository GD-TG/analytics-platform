import BasePage from '../BasePage/BasePage';
import { reportsApi } from '../../api/reports';

const Purchases = () => {
  return (
    <BasePage
      title="Закупки"
      subtitle="Управление закупками и поставками"
      apiEndpoint={() => reportsApi.getStatistics()}
      renderContent={(data) => (
        <div>
          <p>Данные о закупках будут отображаться здесь после настройки API</p>
        </div>
      )}
    />
  );
};

export default Purchases;

