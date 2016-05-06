<?php
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf8');

require('irbis64.class.php');

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


/* Пример получения записей по термину словаря  */
	$irbis->set_db('IBIS');
	$ret = $irbis->term_records("K=сказки", 0, 0);
	if ($irbis->error_code != 0) echo $irbis->error() . PHP_EOL;
	print_r($ret);
//	echo implode(',', $ret);


	// Вывод фотографии
/*		foreach ($ret as $field) {
		$field = explode('#', $field, 2);
		if ($field[0] == 953) {
			$field = parse_field($field[1]);
			if (isset($field['B'])) {
				$res = preg_replace_callback('/%([A-F0-9]{2})/', function ($matches) {
					return pack('H2', $matches[1]);
				}, $field['B']);
				header('Content-type: image/jpeg;');
				echo $res;
			}
		}
	}*/
	echo PHP_EOL;

} else {
	echo $irbis->error();
}

/*	function parse_field(&$field) {
	$ret = array();
	preg_match_all("/[\1f\^](.)([^\1f\^]+?)/U", $field, $matches, PREG_SET_ORDER);
	foreach ($matches as $match) {
		$ret[(string)$match[1]] = $match[2];
	}
	return $ret;
}
*/
