Известные проблемы:
1) При редактировании custom_fields  amocrmapi вызывает 2 раза хук update и интеграция на него реагирует дважды
Не знаю это баг или логика , о которой я не подозреваю
2) Редактирование стандартных полей не передает в хуке информацию об изменениях в полях. Не нашел способа вывести их названия и новые значения
Нашел откуда можно вытащить - events, но опять таки не понятно как связывать хук и событие

При написании было 2 варианта: написать всё самому, либо использовать библиотеку api. Пошел по первому пути, так как во втором случае все написали за меня (ну или я не правильно понял цель задания)