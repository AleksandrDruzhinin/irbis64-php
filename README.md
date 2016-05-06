# irbis64-php
Класс PHP для работы с АБИС ИРБИС64 по протоколу TCP

Обсуждение http://irbis.gpntb.ru/read.php?29,101880

Возможости:

1. Подключаться и авторизоваться
2. Завершать сессию (автоматически)
3. Запрос максимального MFN в выбранной
4. Получение списка терминов словаря (с количеством ссылок)
5. Получение списка MFN записей, соответствующих терминам словаря
6. Чтение конкретной записи без форматирования
7. Прямой / Последовательный поиск. (По запросу и по unifor-условию отбора) 

Планирую добавить:

1. Функцию сохранения записи
2. Экспорт/Импорт бинарных ресурсов (фотографии читателей)
3. Вывод библиографического описания в виде HTML или RTF
