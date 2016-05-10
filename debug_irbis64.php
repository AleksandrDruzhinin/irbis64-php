<?php
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf8');

require('class.irbis64.php');

$irbis = new irbis64(); 

$irbis->set_user('1', '1'); // логин, пароль
$irbis->set_arm('C');       // C - Каталогизатор
$irbis->set_id('388623');   // Идентификатор сессии
$irbis->set_server('192.168.0.2', 6666);

if ($irbis->login()) {

/* Пример для поиска книги (ПО АВТОРУ "Иванов..." ИЛИ "Петров...") И (ВИД ДОКУМЕНТА = ОДНОТОМНИК ИЛИ МНОГОТОМНИК) И (КЛЮЧЕВЫЕ СЛОВА СОДЕРЖАТ "фантастика")
	$irbis->set_db('IBIS');
	$search_exp = '("A=Иванов$" +"A=Петров$") * ("V=03" + "V=05") * ("K=фантастика$")';
	$ret = $irbis->records_search($search_exp, 1000, 1, '@brief');
	if ($irbis->error_code != 0) echo $irbis->error() . PHP_EOL;
	print_r($ret);
*/

/* Пример для поиска читателя, имя которого содержит букву "М"
	$irbis->set_db('RDR');
	$search_exp = '"A=$"';
	$ret = $irbis->records_search($search_exp, 1000, 1, '@brief','','',"!if (v11:'М') then '1' else '0' fi");
	if ($irbis->error_code != 0) echo $irbis->error() . PHP_EOL;
	print_r($ret);
*/

/* Пример для получения максимального MFN базы IBIS
	$irbis->set_db('IBIS');
	$ret = $irbis->mfn_max();
	if ($irbis->error_code != 0) echo $irbis->error() . PHP_EOL;
	print_r($ret);
*/

/* Пример получения терминов словаря АВТОРЫ базы IBIS
	$irbis->set_db('IBIS');
	$ret = $irbis->terms_read('A=', 10);
	if ($irbis->error_code != 0) echo $irbis->error() . PHP_EOL;
	
	// формат результата ЧИСЛО ССЫЛОК # ТЕРМИН СЛОВАРЯ
	print_r($ret);
*/


/* Пример получения записей по термину словаря
	$irbis->set_db('IBIS');
	$ret = $irbis->term_records("K=сказки", 0, 0);
	if ($irbis->error_code != 0) echo $irbis->error() . PHP_EOL;
	print_r($ret);
*/


/* Пример чтения записи */
	$irbis->set_db('RDR');
	$reader = $irbis->record_read(137); // mfn = 137
	if ($irbis->error_code != 0) echo $irbis->error() . PHP_EOL;


/* Пример сохранения записи */
	// 1. Читаем запись как есть
	$irbis->set_db('RDR');
	$reader = $irbis->record_read(137);
	if ($irbis->error_code != 0) echo $irbis->error() . PHP_EOL;

//	echo 'Запись до сохранения:' . PHP_EOL;
//	var_dump($reader);

	// 2. Изменяем поля ([0] - номер повторения поля)
	$reader['fields'][920][0] = 'RDR';
	$reader['fields'][10][0] = 'Дружинин';

/* Загрузка фотографии из файла в запись */ 
	$reader['fields'][953][0] = '^Ajpg^B' . $irbis->blob_encode(file_get_contents('test.jpg'));

	// 3. Сохраняем запись. Важно указать актуальный номер версии записи из БД
	$reader = $irbis->record_write($reader);
	if ($irbis->error_code < 0) echo $irbis->error() . PHP_EOL;
//	echo 'Запись после сохранения:' . PHP_EOL;
//	var_dump($reader);

//	echo $reader['fields'][953][0];
//	exit;

	/* Пример вывода фотографии */
	if (isset($reader['fields'][953][0])) {
		$photo = $irbis->parse_field($reader['fields'][953][0]);
		if (isset($photo['A']) && $photo['A'] == 'jpg' && isset($photo['B'])) {
			header('Content-type: image/jpeg;');
			$blob = $irbis->blob_decode($photo['B']);
			echo $blob;
			exit;
		}
	}

} else {
	echo $irbis->error();
}
