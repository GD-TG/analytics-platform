import BasePage from '../BasePage/BasePage';

const Finance = () => (
  <BasePage
    title="Финансы"
    subtitle="Финансовая аналитика"
    renderContent={() => (
      <div>
        <p>Данные о финансах будут отображаться здесь после настройки API</p>
      </div>
    )}
  />
);

export default Finance;

