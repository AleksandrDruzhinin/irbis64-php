<?php
class irbis64 {
private $ip = '', $port = '', $sock;
private $login = '', $pass = '';
private $id = '', $seq = 1;

public $arm = 'C'; // Каталогизатор
public $db = 'IBIS'; // 
public $server_timeout = 30;
public $server_ver     = '';
public $error_code = 0;

function __construct() { }
function __destruct() { $this->logout(); }

function set_server($ip, $port = 6666) { $this->ip = $ip; $this->port = (int)$port; }
function set_user($login, $pass) { $this->login = $login; $this->pass = $pass; }
function set_arm($arm) { $this->arm = $arm; }
function set_db($db) { $this->db = $db; }
function set_id($id) { $this->id = $id; }

function error($code = '') {
	if ($code == '') $code = $this->error_code;
	
	switch ($code) {
	case '0': return 'Ошибки нет';
	case '1': return 'Подключение к серверу не удалось';

	case '-3333': return 'Пользователь не существует'; 
	case '-3337': return 'Пользователь уже зарегистрирован'; 
	case '-4444': return 'Пароль не подходит'; 

	case '-140':  return 'MFN за пределами базы'; 
	case '-5555': return 'База не существует'; 
	case '-400': 	return 'Ошибка при открытии файла mst или xrf'; 
	case '-603': 	return 'Запись логически удалена'; 
	case '-601': 	return 'Запись удалена'; 

	case '-202': 	return 'Термин не существует'; 
	case '-203': 	return 'TERM_LAST_IN_LIST'; 
	case '-204': 	return 'TERM_FIRST_IN_LIST'; 

	case '-608': 	return 'Не совпадает номер версии у сохраняемой записи'; 
	}
	return 'Неизвестная ошибка: ' . $code;
}

function connect() {
	$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($this->sock === false) return false;
	return (@socket_connect($this->sock, $this->ip, $this->port));
}

// Авторизация
function login() {
	$packet = implode("\n", array('A', $this->arm, 'A', $this->id, $this->seq, '', '', '', '', '', $this->login, $this->pass));
	$packet = strlen($packet)."\n".$packet;
	$answer = $this->send($packet);
	if ($answer === false) {
		$this->error_code = 1;
		return false;
	}
	
	$this->error_code = $answer[10];
	if ($this->error_code != 0) return false;
	$this->server_timeout = $answer[11];
	$this->server_ver     = $answer[4];
	return true;
}

// Завершение сессии
function logout() {
	$packet = implode("\n", array('B', $this->arm, 'B', $this->id, $this->seq, '', '', '', '', '', $this->login));
	$packet = strlen($packet) . "\n" . $packet;
	$answer = $this->send($packet);
	if ($answer === false) return false;

	$this->error_code = $answer[10];
	if ($this->error_code != 0) return false;
	return true;
}

// Получить максимальный MFN в базе
function mfn_max() {
	$packet = implode("\n", array('O', $this->arm, 'O', $this->id, $this->seq, '', '', '', '', '', $this->db));
	$packet = strlen($packet) . "\n" . $packet;
	$answer = $this->send($packet);
	if ($answer === false) return false;

	$this->error_code = $answer[10];
	if ($this->error_code > 0) {
		$this->error_code = 0;
		return $answer[10];
	} else {
		return false;
	}
}

// Чтение словаря
function terms_read($term, $num_terms = '', $format = '') {
	// см. инструкцию сервера "7.8	Функции работы со словарем базы данных"
	// если указан формат, по в результат добавляются по одной записи для каждой строки словаря
	$packet = implode("\n", array('H', $this->arm, 'H', $this->id, $this->seq, '', '', '', '', '', $this->db, $term, $num_terms, $format));
	$packet = strlen($packet) . "\n" . $packet;

	$answer = $this->send($packet);
	if ($answer === false) return false;
	
	$this->error_code = $answer[10];
	
	if ($this->error_code == 0) {
		// массив $terms
		$terms = array();
		$c = count($answer) - 1;
		for ($i = 11; $i < $c; $i++) {
			$terms[] = $answer[$i]; // формат ЧИСЛО_ССЫЛОК#ТЕРМИН=ЗНАЧЕНИЕ
		}
		return $terms;
	} else return false;
}

// Получить список ссылок термина 
function term_records($term, $num_postings = '', $first_posting = '') {
	// $term - список терминов для поиска. формат: "K=фантастика\nK=природа" = вывести список соответствующих хотя бы одному из терминов
	// $num_postings = количество возвращаемых записей из списка, если = 0 то возвращается MAX_POSTINGS_IN_PACKET записей
	// если $first_posting = 0 - возвращается только количество записей, если больше - указывает смещение первой возвращаемой записи из списка
	$first_posting = (int)$first_posting;

	$packet = implode("\n", array('I', $this->arm, 'I', $this->id, $this->seq, '', '', '', '', '', $this->db, $num_postings, $first_posting, '', $term));
	$packet = strlen($packet) . "\n" . $packet;

	$answer = $this->send($packet, true);
	if ($answer === false) return false;
	
	$this->error_code = $answer[10];
	
	/* основной формат для результатов поиска
			MFN#TAG#OCC#CNT (см. инструкцию к серверу "6.5.3.1	Обыкновенный формат записи IFP")
				MFN – номер записи;
				TAG – идентификатор поля назначенный при отборе терминов в словарь;
				OCC – номер повторения;
				CNT – номер термина в поле.
	*/
	
	if ($this->error_code == 0) {
		$records = array();
		
		$c = count($answer) - 1;
		for ($i = 11; $i < $c; $i++) {
			$ret = explode('#', $answer[$i]);
			// для упрощения возвращаем только список MFN 
			// или количество найденных записей (при $first_posting == 0)
			$records[] = $ret[0];
		}
		return $records;
	} else return false;
}

// Получить запись
function record_read($mfn, $lock = false) {
	/* 
		record (
			mfn
			status - состояние записи
			ver - версия записи
			fileds - массив полей записи в формате [номер][повторение] = значение
		)	
	*/
	$packet = implode("\n", array('C', $this->arm, 'C', $this->id, $this->seq, '', '', '', '', '', $this->db, $mfn, $lock ? 1 : 0));
	$packet = strlen($packet) . "\n" . $packet;

	$answer = $this->send($packet);
	if ($answer === false) return false;

	$this->error_code = $answer[10];
	$mfn_status  = explode('#', $answer[11]);
	$rec_version = explode('#', $answer[12]);
	$record = array(
		'mfn' => $mfn_status[0],
		'status' => (isset($mfn_status[1]) && $mfn_status[1] != '') ? $mfn_status[1] : 0,
		'ver' => isset($rec_version[1]) ? $rec_version[1] : 0
	);
	if ($this->error_code != 0) return false;

	$record['fields'] = array();
	$c = count($answer) - 1;
	for ($i = 13; $i < $c; $i++) {
		preg_match("/(\d+?)#(.*?)/U", $answer[$i], $matches);
		$field_num = (int)$matches[1];
		$field_val = $matches[2];
		$record['fields'][$field_num][] = $field_val;
	}
	return $record;
}

// Сохранить запись
function record_write($record, $lock = false, $if_update = true) { // $lock  = блокировать запись , $if_update = актуализировать запись
	$fields = array();
	foreach ($record['fields'] as $field_num => $field_values) {
		foreach ($field_values as $field_value) {
			$fields[] = $field_num . '#' . $field_value;
		}
	}
	$status = 0;
	$records = $record['mfn'] . '#' . $record['status'] . "\x1f\x1e" . '0#' . $record['ver'] . "\x1f\x1e" . implode("\x1f\x1e", $fields) . "\x1f\x1e";

	$packet = implode("\n", array('D', $this->arm, 'D', $this->id, $this->seq, '', '', '', '', '', $this->db, $lock ? 1 : 0, $if_update ? 1 : 0, $records));
	$packet = strlen($packet) . "\n" . $packet;

	$answer = $this->send($packet);
	if ($answer === false) return false;
	
	$ret = explode('#', $answer[11]);

	$this->error_code = $ret[0];
	return ($this->record_read($record['mfn']));
}

// Поиск записей по запросу
function records_search($search_exp, $num_records = 1, $first_record = 0, $format = '@brief', $min = '', $max = '', $expression = '') {
	// $search_exp = выражение для прямого поиска
	//		IBIS "I=шифр документа"
	//		IBIS "MHR=место хранения экз-ра"
	//		IBIS "K=ключевые слова"
	//		RDR "A=фио читателя"

	// $num_records = ограничение количества выдаваемых записей
	// 0 - возвращается количество записей не больше MAX_POSTINGS_IN_PACKET 

	// $first_record = задает смещение с какой записи возвращать результаты
	// 0 - возвращается только количество найденных записей

	// $format = @ - оптимизированный (см. описание сервера "7.9.1 Поиск записей по заданному поисковому выражению (K)")
	// $format = '@brief' - оптимизированный сокращенный формат (см. BRIEF.PFT - выводится в список записей в окне каталогизатора)
	
	// $min, $max, $expression - для последовательного поиска. $expression = условие отбора

	$packet = implode("\n", array('K', $this->arm, 'K', $this->id, $this->seq, '', '', '', '', '', $this->db, $search_exp, $num_records, $first_record, $format, $min, $max, $expression));
	$packet = strlen($packet) . "\n" . $packet;

	// <debug>
	echo 'Поиск записей по ключу ' . $search_exp . PHP_EOL;
	if ($expression != '') echo 'Уточняющее условие ' . $expression . PHP_EOL;
	// </debug>

	$answer = $this->send($packet);
	if ($answer === false) return false;

	$this->error_code = $answer[10];
	if ($this->error_code != 0) return false;

	$ret['found'] = $answer[11]; // количество найденных записей

	$c = count($answer) - 1;
	for ($i = 12; $i < $c; $i++) {
		$ret['records'][] = $answer[$i];
	}
	return $ret;
}

function send($packet, $debug = false) {
	if ($this->sock === false) return false;
	if (!$this->connect()) return false;
	$this->seq++;

	if ($debug) echo PHP_EOL . '>> ' . str_replace("\n", '\n ', $packet) . PHP_EOL;
	socket_write($this->sock, $packet, strlen($packet));
	$answer = '';
	while ($buf = @socket_read($this->sock, 2048, PHP_NORMAL_READ)) {
		$answer .= $buf;
	}
	socket_close($this->sock);
	if ($debug) echo '<< ' . str_replace("\r\n", '\r\n ', $answer) . PHP_EOL;
	return explode("\r\n", $answer);
}

// Раскодировать строку поля на ассоциированный массив с подполями
function parse_field(&$field) {
	$ret = array();
	$matches = explode('^', $field);
	if (count($matches) == 1) { 
		$matches = explode("\x1f", $field);
	}
	foreach ($matches as $match) {
		$ret[(string)substr($match, 0, 1)] = substr($match, 1);
	}
	return $ret;
}

// Раскодировать бинарную строку
function blob_decode($blob) {
	return preg_replace_callback('/%([A-Fa-f0-9]{2})/', function ($matches) { return pack('H2', $matches[1]); }, $blob);
}

// Закодировать бинарную строку
function blob_encode($binary) {
	if (strlen($binary) > 100000) return false; // защита от дурака. размер файла не больше 100Кб

	$c = strlen($binary);
	$ret = '';
	// цифры и латинские цифры можно не кодировать в формат %HEX, а выводить напрямую в blob, сокращает на 25% объем данных
	for ($i = 0; $i < $c; $i++) {
		$n = ord(substr($binary, $i, 1));
		if (($n >= 0x30 && $n <= 0x39) || ($n >= 0x41 && $n <= 0x5A) || ($n >= 0x61 && $n <= 0x7A)) {
			$ret .= substr($binary, $i, 1);
		} else {
			$ret .= '%' . ($n < 16 ? '0' : '') . strtoupper(dechex($n));
		}
	}
	return $ret;
}

} // class
