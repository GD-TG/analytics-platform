// Динамические импорты для PDF-генерации
let jsPDF = null;
let html2canvas = null;

const loadPDFLibraries = async () => {
  if (!jsPDF) {
    try {
      jsPDF = (await import('jspdf')).default;
      html2canvas = (await import('html2canvas')).default;
    } catch (error) {
      throw new Error('PDF libraries not installed. Please run: npm install jspdf html2canvas');
    }
  }
  return { jsPDF, html2canvas };
};

/**
 * Генерация PDF отчета в стиле OWOX/Roistat
 */
export const generatePDFReport = async (elementId, filename = 'report.pdf', options = {}) => {
  try {
    // Загружаем библиотеки
    const { jsPDF: PDF, html2canvas: h2c } = await loadPDFLibraries();
    
    const element = document.getElementById(elementId);
    if (!element) {
      throw new Error(`Element with id "${elementId}" not found`);
    }

    // Опции для html2canvas
    const canvasOptions = {
      scale: 2,
      useCORS: true,
      logging: false,
      backgroundColor: '#ffffff',
      ...options.canvas,
    };

    // Создаем canvas из элемента
    const canvas = await h2c(element, canvasOptions);
    const imgData = canvas.toDataURL('image/png');

    // Создаем PDF
    const pdf = new PDF({
      orientation: options.orientation || 'portrait',
      unit: 'mm',
      format: options.format || 'a4',
    });

    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = pdf.internal.pageSize.getHeight();
    const imgWidth = canvas.width;
    const imgHeight = canvas.height;
    const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
    const imgScaledWidth = imgWidth * ratio;
    const imgScaledHeight = imgHeight * ratio;

    // Добавляем изображение
    pdf.addImage(imgData, 'PNG', 0, 0, imgScaledWidth, imgScaledHeight);

    // Если контент не помещается на одну страницу, добавляем новые страницы
    let heightLeft = imgScaledHeight;
    let position = 0;

    while (heightLeft > 0) {
      position = heightLeft - pdfHeight;
      pdf.addPage();
      pdf.addImage(imgData, 'PNG', 0, position, imgScaledWidth, imgScaledHeight);
      heightLeft -= pdfHeight;
    }

    // Сохраняем PDF
    pdf.save(filename);
    
    return pdf;
  } catch (error) {
    console.error('Error generating PDF:', error);
    throw error;
  }
};

/**
 * Генерация PDF отчета с кастомным контентом
 */
export const generateCustomPDFReport = async (data, filename = 'report.pdf', options = {}) => {
  // Загружаем библиотеки
  const { jsPDF: PDF } = await loadPDFLibraries();
  
  const pdf = new PDF({
    orientation: options.orientation || 'portrait',
    unit: 'mm',
    format: options.format || 'a4',
  });

  // Заголовок
  pdf.setFontSize(20);
  pdf.setTextColor(40, 40, 40);
  pdf.text(options.title || 'Отчет', 20, 20);

  // Дата
  pdf.setFontSize(10);
  pdf.setTextColor(100, 100, 100);
  const date = new Date().toLocaleDateString('ru-RU');
  pdf.text(`Дата: ${date}`, 20, 30);

  // Контент
  let yPosition = 40;
  pdf.setFontSize(12);
  pdf.setTextColor(40, 40, 40);

  if (data && Array.isArray(data)) {
    data.forEach((item, index) => {
      if (yPosition > 270) {
        pdf.addPage();
        yPosition = 20;
      }
      pdf.text(`${index + 1}. ${JSON.stringify(item)}`, 20, yPosition);
      yPosition += 10;
    });
  }

  // Сохраняем
  pdf.save(filename);
  
  return pdf;
};

