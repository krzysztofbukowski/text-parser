<?php

/**
 *
 *
 * 			[0-9]			[^0-9]		sepa
 * START		NUMBER	UNDEFINED	SEPARATOR
 * NUMBER	NUMBER	UNDEFINED 	SEPARATOR
 * SEPARATOR	NUMBER	UNDEFINED 	SEPARATOR
 *
 * @author kris
 *
 */

namespace Parser;

require_once 'Zend/Debug.php';
require_once 'Lexem.php';

/**
 * @author kris
 * @package Parser
 *
 */
class Parser {
    const WHITESPACE = " ";
    const RN = "\r\n";
    const N = "\n";

    const STATE_START = 0;
    const STATE_SEPARATOR = 1;
    const STATE_TEXT = 2;
    const STATE_NEWLINE = 3;
    const STATE_EMOT_BEGIN = 4;
    const STATE_EMOT = 5;
    const STATE_EMOT_END = 6;
    const STATE_LOGIN_START = 7;
    const STATE_LOGIN = 8;
    const STATE_URI_H = 9;
    const STATE_URI_T1 = 10;
    const STATE_URI_T2 = 11;
    const STATE_URI_P = 12;
    const STATE_URI_S = 13;
    const STATE_URI_COLLON = 14;
    const STATE_URI_SLASH1 = 15;
    const STATE_URI_SLASH2 = 16;
    const STATE_URI = 17;
    const STATE_UNDEFINED = 18;

    const CHAR_LETTER_SEPARATOR = 0;
    const CHAR_N = 1;
    const CHAR_R = 2;
    const CHAR_COLLON = 3;
    const CHAR_AT = 4;
    const CHAR_DIGIT = 5;
    const CHAR_LETTER = 6;
    const CHAR_LETTER_H = 7;
    const CHAR_LETTER_T = 8;
    const CHAR_LETTER_P = 9;
    const CHAR_LETTER_S = 10;
    const CHAR_SLASH = 11;
    const CHAR_OTHER = 12;

    //tylko dla wyświetlania czytelnego formatu tabeli przejść. parser korzysta z powyżej zdefiniowanych stałych
    private $_States = array(
	self::STATE_START => 'START',
	self::STATE_UNDEFINED => 'UNDEFINED',
	self::STATE_SEPARATOR => 'SEPARATOR',
	self::STATE_TEXT => 'CHARACTER',
	self::STATE_NEWLINE => 'NEWLINE',
	self::STATE_EMOT_BEGIN => 'EMOT_BEGIN',
	self::STATE_EMOT => 'EMOT',
	self::STATE_EMOT_END => 'EMOT_END',
	self::STATE_LOGIN_START => 'STATE_LOGIN_START',
	self::STATE_LOGIN => 'STATE_LOGIN',
	self::STATE_URI_H => 'STATE_URI_H',
	self::STATE_URI_T1 => 'STATE_URI_T1',
	self::STATE_URI_T2 => 'STATE_URI_T2',
	self::STATE_URI_P => 'STATE_URI_P',
	self::STATE_URI_S => 'STATE_URI_S',
	self::STATE_URI_COLLON => 'STATE_URI_COLLON',
	self::STATE_URI_SLASH1 => 'STATE_URI_SLASH1',
	self::STATE_URI_SLASH2 => 'STATE_URI_SLASH2',
	self::STATE_URI => 'STATE_URI',
    );
    private $_Chars = array(
	self::CHAR_LETTER_SEPARATOR => '\w',
	self::CHAR_OTHER => 'znak',
	self::CHAR_N => '\n',
	self::CHAR_R => '\r',
	self::CHAR_COLLON => ':',
	self::CHAR_AT => '@',
	self::CHAR_DIGIT => 'cyfra',
	self::CHAR_LETTER => 'litera',
	self::CHAR_LETTER_H => 'h',
	self::CHAR_LETTER_T => 't',
	self::CHAR_LETTER_P => 'p',
	self::CHAR_LETTER_S => 's',
	self::CHAR_SLASH => '/',
    );

    /**
     * Tablica przejść stanów maszyny stanów. Dwuwymiarowa tablica postaci [stan][znak] = stan następny
     * @var array
     */
    protected $_SM = array();

    /**
     *
     * @var array 
     */
    protected $_Lexems = array();

    public function __construct() {

	$MS = array();
	//'wyzerowanie' tablicy - z każdego stanu przez każdy znak trafiamy do stanu nieokreślonego
	for ($state = 0; $state <= self::STATE_UNDEFINED; $state++) {
	    for ($char = 0; $char <= self::CHAR_OTHER; $char++) {
		$MS[$state][$char] = self::STATE_UNDEFINED;
	    }
	}

	//inicjalizacja tablicy
	$MS[self::STATE_START][self::CHAR_LETTER_SEPARATOR] = self::STATE_SEPARATOR;
	$MS[self::STATE_START][self::CHAR_OTHER] = self::STATE_TEXT;
	$MS[self::STATE_START][self::CHAR_N] = self::STATE_NEWLINE;
	$MS[self::STATE_START][self::CHAR_R] = self::STATE_NEWLINE;
	$MS[self::STATE_START][self::CHAR_COLLON] = self::STATE_EMOT_BEGIN;
	$MS[self::STATE_START][self::CHAR_AT] = self::STATE_LOGIN_START;
	$MS[self::STATE_START][self::CHAR_DIGIT] = self::STATE_TEXT;
	$MS[self::STATE_START][self::CHAR_LETTER] = self::STATE_TEXT;
	$MS[self::STATE_START][self::CHAR_LETTER_H] = self::STATE_URI_H;
	$MS[self::STATE_START][self::CHAR_LETTER_T] = self::STATE_TEXT;
	$MS[self::STATE_START][self::CHAR_LETTER_P] = self::STATE_TEXT;
	$MS[self::STATE_START][self::CHAR_LETTER_S] = self::STATE_TEXT;


	$MS[self::STATE_SEPARATOR][self::CHAR_LETTER_SEPARATOR] = self::STATE_SEPARATOR;
	$MS[self::STATE_TEXT][self::CHAR_OTHER] = self::STATE_TEXT;
	$MS[self::STATE_TEXT][self::CHAR_LETTER] = self::STATE_TEXT;
	$MS[self::STATE_TEXT][self::CHAR_DIGIT] = self::STATE_TEXT;
	$MS[self::STATE_TEXT][self::CHAR_LETTER_T] = self::STATE_TEXT;
	$MS[self::STATE_TEXT][self::CHAR_LETTER_S] = self::STATE_TEXT;
	$MS[self::STATE_TEXT][self::CHAR_LETTER_P] = self::STATE_TEXT;

	//nowa linia
	$MS[self::STATE_NEWLINE][self::CHAR_R] = self::STATE_NEWLINE;
	$MS[self::STATE_NEWLINE][self::CHAR_N] = self::STATE_NEWLINE;

	//emotki
	$MS[self::STATE_EMOT_BEGIN][self::CHAR_OTHER] = self::STATE_EMOT;
	$MS[self::STATE_EMOT_BEGIN][self::CHAR_LETTER] = self::STATE_EMOT;
	$MS[self::STATE_EMOT_BEGIN][self::CHAR_LETTER_H] = self::STATE_EMOT;
	$MS[self::STATE_EMOT_BEGIN][self::CHAR_LETTER_T] = self::STATE_EMOT;
	$MS[self::STATE_EMOT_BEGIN][self::CHAR_LETTER_P] = self::STATE_EMOT;
	$MS[self::STATE_EMOT_BEGIN][self::CHAR_LETTER_S] = self::STATE_EMOT;
	$MS[self::STATE_EMOT_BEGIN][self::CHAR_DIGIT] = self::STATE_EMOT;

	$MS[self::STATE_EMOT][self::CHAR_LETTER] = self::STATE_EMOT;
	$MS[self::STATE_EMOT][self::CHAR_LETTER_H] = self::STATE_EMOT;
	$MS[self::STATE_EMOT][self::CHAR_LETTER_T] = self::STATE_EMOT;
	$MS[self::STATE_EMOT][self::CHAR_LETTER_P] = self::STATE_EMOT;
	$MS[self::STATE_EMOT][self::CHAR_LETTER_S] = self::STATE_EMOT;
	$MS[self::STATE_EMOT][self::CHAR_DIGIT] = self::STATE_EMOT;
	$MS[self::STATE_EMOT][self::CHAR_COLLON] = self::STATE_EMOT_END;

	//@login
	$MS[self::STATE_LOGIN_START][self::CHAR_DIGIT] = self::STATE_TEXT;
	$MS[self::STATE_LOGIN_START][self::CHAR_LETTER] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN_START][self::CHAR_LETTER_T] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN_START][self::CHAR_LETTER_H] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN_START][self::CHAR_LETTER_P] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN_START][self::CHAR_LETTER_S] = self::STATE_LOGIN;

	$MS[self::STATE_LOGIN][self::CHAR_DIGIT] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN][self::CHAR_LETTER] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN][self::CHAR_LETTER_T] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN][self::CHAR_LETTER_H] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN][self::CHAR_LETTER_P] = self::STATE_LOGIN;
	$MS[self::STATE_LOGIN][self::CHAR_LETTER_S] = self::STATE_LOGIN;

	//linki
	$MS[self::STATE_URI_H][self::CHAR_COLLON] = self::STATE_TEXT;
	$MS[self::STATE_URI_H][self::CHAR_SLASH] = self::STATE_TEXT;
	$MS[self::STATE_URI_H][self::CHAR_LETTER_H] = self::STATE_TEXT;
	$MS[self::STATE_URI_H][self::CHAR_LETTER_T] = self::STATE_URI_T1;
	$MS[self::STATE_URI_H][self::CHAR_LETTER_P] = self::STATE_TEXT;
	$MS[self::STATE_URI_H][self::CHAR_LETTER_S] = self::STATE_TEXT;
	$MS[self::STATE_URI_H][self::CHAR_OTHER] = self::STATE_TEXT;
	$MS[self::STATE_URI_H][self::CHAR_DIGIT] = self::STATE_TEXT;

	$MS[self::STATE_URI_T1][self::CHAR_COLLON] = self::STATE_TEXT;
	$MS[self::STATE_URI_T1][self::CHAR_SLASH] = self::STATE_TEXT;
	$MS[self::STATE_URI_T1][self::CHAR_LETTER_H] = self::STATE_TEXT;
	$MS[self::STATE_URI_T1][self::CHAR_LETTER_T] = self::STATE_URI_T2;
	$MS[self::STATE_URI_T1][self::CHAR_LETTER_P] = self::STATE_TEXT;
	$MS[self::STATE_URI_T1][self::CHAR_LETTER_S] = self::STATE_TEXT;
	$MS[self::STATE_URI_T1][self::CHAR_OTHER] = self::STATE_TEXT;

	$MS[self::STATE_URI_T2][self::CHAR_COLLON] = self::STATE_TEXT;
	$MS[self::STATE_URI_T2][self::CHAR_SLASH] = self::STATE_TEXT;
	$MS[self::STATE_URI_T2][self::CHAR_LETTER_H] = self::STATE_TEXT;
	$MS[self::STATE_URI_T2][self::CHAR_LETTER_T] = self::STATE_TEXT;
	$MS[self::STATE_URI_T2][self::CHAR_LETTER_P] = self::STATE_URI_P;
	$MS[self::STATE_URI_T2][self::CHAR_LETTER_S] = self::STATE_TEXT;
	$MS[self::STATE_URI_T2][self::CHAR_OTHER] = self::STATE_TEXT;

	$MS[self::STATE_URI_P][self::CHAR_COLLON] = self::STATE_URI_COLLON;
	$MS[self::STATE_URI_P][self::CHAR_SLASH] = self::STATE_TEXT;
	$MS[self::STATE_URI_P][self::CHAR_LETTER_H] = self::STATE_TEXT;
	$MS[self::STATE_URI_P][self::CHAR_LETTER_T] = self::STATE_TEXT;
	$MS[self::STATE_URI_P][self::CHAR_LETTER_P] = self::STATE_TEXT;
	$MS[self::STATE_URI_P][self::CHAR_LETTER_S] = self::STATE_URI_S;
	$MS[self::STATE_URI_P][self::CHAR_OTHER] = self::STATE_TEXT;

	$MS[self::STATE_URI_S][self::CHAR_COLLON] = self::STATE_URI_COLLON;
	$MS[self::STATE_URI_S][self::CHAR_SLASH] = self::STATE_TEXT;
	$MS[self::STATE_URI_S][self::CHAR_LETTER_H] = self::STATE_TEXT;
	$MS[self::STATE_URI_S][self::CHAR_LETTER_T] = self::STATE_TEXT;
	$MS[self::STATE_URI_S][self::CHAR_LETTER_P] = self::STATE_TEXT;
	$MS[self::STATE_URI_S][self::CHAR_LETTER_S] = self::STATE_TEXT;
	$MS[self::STATE_URI_S][self::CHAR_OTHER] = self::STATE_TEXT;

	$MS[self::STATE_URI_COLLON][self::CHAR_SLASH] = self::STATE_URI_SLASH1;
	$MS[self::STATE_URI_COLLON][self::CHAR_LETTER_H] = self::STATE_TEXT;
	$MS[self::STATE_URI_COLLON][self::CHAR_LETTER_T] = self::STATE_TEXT;
	$MS[self::STATE_URI_COLLON][self::CHAR_LETTER_P] = self::STATE_TEXT;
	$MS[self::STATE_URI_COLLON][self::CHAR_LETTER_S] = self::STATE_TEXT;
	$MS[self::STATE_URI_COLLON][self::CHAR_OTHER] = self::STATE_TEXT;

	$MS[self::STATE_URI_SLASH1][self::CHAR_SLASH] = self::STATE_URI_SLASH2;
	$MS[self::STATE_URI_SLASH1][self::CHAR_LETTER_H] = self::STATE_TEXT;
	$MS[self::STATE_URI_SLASH1][self::CHAR_LETTER_T] = self::STATE_TEXT;
	$MS[self::STATE_URI_SLASH1][self::CHAR_LETTER_P] = self::STATE_TEXT;
	$MS[self::STATE_URI_SLASH1][self::CHAR_LETTER_S] = self::STATE_TEXT;
	$MS[self::STATE_URI_SLASH1][self::CHAR_OTHER] = self::STATE_TEXT;

	$MS[self::STATE_URI_SLASH2][self::CHAR_SLASH] = self::STATE_TEXT;
	$MS[self::STATE_URI_SLASH2][self::CHAR_LETTER_H] = self::STATE_URI;
	$MS[self::STATE_URI_SLASH2][self::CHAR_LETTER_T] = self::STATE_URI;
	$MS[self::STATE_URI_SLASH2][self::CHAR_LETTER_P] = self::STATE_URI;
	$MS[self::STATE_URI_SLASH2][self::CHAR_LETTER_S] = self::STATE_URI;
	$MS[self::STATE_URI_SLASH2][self::CHAR_LETTER] = self::STATE_URI;
	$MS[self::STATE_URI_SLASH2][self::CHAR_OTHER] = self::STATE_URI;

	$MS[self::STATE_URI][self::CHAR_SLASH] = self::STATE_URI;
	$MS[self::STATE_URI][self::CHAR_LETTER_H] = self::STATE_URI;
	$MS[self::STATE_URI][self::CHAR_LETTER_T] = self::STATE_URI;
	$MS[self::STATE_URI][self::CHAR_LETTER_P] = self::STATE_URI;
	$MS[self::STATE_URI][self::CHAR_LETTER_S] = self::STATE_URI;
	$MS[self::STATE_URI][self::CHAR_LETTER] = self::STATE_URI;
	$MS[self::STATE_URI][self::CHAR_DIGIT] = self::STATE_URI;
	$MS[self::STATE_URI][self::CHAR_OTHER] = self::STATE_URI;
	$MS[self::STATE_URI][self::CHAR_COLLON] = self::STATE_URI;

	$this->_MS = $MS;
    }

    /**
     * Dokonuje rozkładu tekstu na leksemy
     * 
     * @param String $input Tekst do rozbioru
     * @return array 
     */
    public function parse($input) {
	$length = \mb_strlen($input, "utf-8");
	$index = 0;
	do {
	    $Q1 = self::STATE_START;
	    $buffer = "";
	    do {
		$char = \mb_substr($input, $index, 1, "utf-8");
		$type = $this->_ClassifyChar($char);

		//$nextChar = \mb_substr($input, $index + 1, 1, "utf-8");
		if ($type == self::CHAR_R) {
		    $index++; //omijamy \n
		}
		$Q = $Q1;
		//nowy stan

		$Q1 = $this->_MS[$Q][$type];
		if ($Q1 == self::STATE_UNDEFINED) {
		    $Q1 = $Q;
		    break;
//				} else if ($Q1 == self::STATE_SEPARATOR && $nextChar != " ") {
//					$buffer.=$char;
//					$index++;
//					break;
		} else {
		    $buffer.=$char;
		}

		$index++;
		if ($index >= $length) {//koniec
		    break;
		}
	    } while (true);

	    // Na podstawie stanu w jakim znajduje się parser ustalany jest typ danego leksemu.
	    switch ($Q1) {
		case self::STATE_UNDEFINED:
		    break;
		case self::STATE_TEXT:
		case self::STATE_EMOT:
		case self::STATE_EMOT_BEGIN:
		case self::STATE_LOGIN_START:
		case self::STATE_URI_H:
		case self::STATE_URI_T1:
		case self::STATE_URI_T2:
		case self::STATE_URI_P:
		case self::STATE_URI_S:
		case self::STATE_URI_SLASH1:
		case self::STATE_URI_SLASH2:
		case self::STATE_URI_COLLON:
		    $this->_Lexems[] = new \Parser\Lexem($buffer, Lexem::LEXEM_TEXT);
		    break;
		case self::STATE_LOGIN:
		    $this->_Lexems[] = new \Parser\Lexem($buffer, Lexem::LEXEM_USER);
		    break;
		case self::STATE_URI:
		    $this->_Lexems[] = new \Parser\Lexem($buffer, Lexem::LEXEM_URI);
		    break;
		case self::STATE_SEPARATOR:
		    $this->_Lexems[] = new \Parser\Lexem($buffer, Lexem::LEXEM_WHITESPACE);
		    break;
		case self::STATE_NEWLINE:
		    $this->_Lexems[] = new \Parser\Lexem($buffer, Lexem::LEXEM_NEWLINE);
		    break;
		case self::STATE_EMOT_END:
		    $this->_Lexems[] = new \Parser\Lexem($buffer, Lexem::LEXEM_EMOT);
		    break;
	    }

	    // Jeśli wskaźnik indeksu znaku jest większy niż długość wejściowego łańcucha znaków
	    // to przerywamy działanie pętli i zwracamy uzyskaną tablicę leksemów
	    if ($index >= $length) {
		break;
	    }
	} while (true);

	return $this->_Lexems;
    }

    public function getLexems($type = null) {
	if ($type == null) {
	    return $this->_Lexems;
	}
    }

    /**
     *
     * Przypisuje podany znak do odpowiedniej grupy znaków
     * @param char $char
     * @return int
     */
    private function _ClassifyChar($char) {
	if ($char >= 'a' && $char <= 'z' || $char >= 'A' && $char <= 'Z') {
	    if ($char == 'h' || $char == 'H') {
		return self::CHAR_LETTER_H;
	    } else if ($char == 't' || $char == 'T') {
		return self::CHAR_LETTER_T;
	    } else if ($char == 'p' || $char == 'P') {
		return self::CHAR_LETTER_P;
	    } else if ($char == 's' || $char == 'S') {
		return self::CHAR_LETTER_P;
	    } else {
		return self::CHAR_LETTER;
	    }
	} else if ($char == "\n") {
	    return self::CHAR_N;
	} else if ($char == "\r") {
	    return self::CHAR_R;
	} else if ($char == " ") {
	    return self::CHAR_LETTER_SEPARATOR;
	} else if ($char == ":") {
	    return self::CHAR_COLLON;
	} else if ($char == '@') {
	    return self::CHAR_AT;
	} else if ($char == '/') {
	    return self::CHAR_SLASH;
	} else if (is_numeric($char)) {
	    return self::CHAR_DIGIT;
	} else {
	    return self::CHAR_OTHER;
	}
    }

    /**
     *
     * Drukuje tablicę przejść stanów
     * @return string
     *
     */
    public function __toString() {
	$html = '<h1>Tablica przejść stanów</h1>';
	$html .= '<table style="font-size:11px;">';
	$html .= '<tbody>';

	//naglowki
	$html .= '<tr id="header"><td></td>';
	for ($char = 0; $char <= self::CHAR_OTHER; $char++) {
	    $html .= '<td>' . $this->_Chars[$char] . '</td>';
	}
	$html .= '</tr>';

	foreach ($this->_MS as $state => $chars) {
	    $html .= '<tr>';
	    $html .= '<td>' . $this->_States[$state] . '</td>';
	    foreach ($chars as $char => $newState) {
		if ($newState == self::STATE_UNDEFINED) {
		    $html .= '<td style="color:#aaa;">' . $this->_States[$newState] . '</td>';
		} else {
		    $html .= '<td>' . $this->_States[$newState] . '</td>';
		}
	    }
	    $html .= '</tr>';
	}

	$html .= '</tbody>';
	$html .= '</table>';

	return $html;
    }

}