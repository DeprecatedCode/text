<?php

namespace Evolution\Text;

/**
 * Text processing bundle
 */
class _Bundle {
	
	public function __get($var) {
		
		// Expose features
		switch($var) {
			
			case 'lexer':
				return new Lexer;
				break;
		}
		
		// Loading an invalid feature
		throw new \Exception("No `$var` feature in Text bundle");
	}
	
}