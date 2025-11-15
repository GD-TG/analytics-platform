import BasePage from '../BasePage/BasePage';

const Marketing = () => (
  <BasePage
    title="Маркетинг"
    subtitle="Маркетинговая аналитика"
    renderContent={() => (
      <div>
        <p>Данные о маркетинге будут отображаться здесь после настройки API</p>
      </div>
    )}
  />
);

export default Marketing;

