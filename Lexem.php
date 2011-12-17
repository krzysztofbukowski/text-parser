<?php
namespace Parser;
/** 
 * @author kris
 * 
 * 
 */
class Lexem {
	/**
	 * Typ leksemu określający zwykły tekst
	 */
	const LEXEM_TEXT = "TEXT";
	
	/**
	 * Typ leksemu określający spację
	 */
	const LEXEM_WHITESPACE = "WHITESPACE";
	
	/**
	 * Typ leksemu określający nową linię
	 */
	const LEXEM_NEWLINE = "NEWLINE";
	
	/**
	 * Typ leksemu określający emotkę
	 */
	const LEXEM_EMOT = "EMOT";
	
	/**
	 * Typ leksemu określający adres URI
	 */
	const LEXEM_URI = "URI";
	
	/**
	 * Typ leksemu określający zwrot w tekście do użytkownika przy użyciu @
	 * @example @dummy Zobacz jaki fajny filmik
	 */
	const LEXEM_USER = "USER";
	
	protected $_Value;
	protected $_Type;
	
	function __construct($value, $type) {
		$this->_Value = $value;
		$this->_Type = $type;
	}
	
	public function getType(){
		return $this->_Type;
	}
	
	public function getValue(){
		return $this->_Value;		
	}
	
	public function __toString(){
		return $this->_Value;
	}
}