<?php

namespace Evolution\Text;

/**
 * Text processing lexer class
 */
class Lexer {
		
	private $grammar;
	
	private $initialToken;
	
	private $source;
	
	private $file;
	
	/**
	 * Define grammar
	 */
	public function grammar($grammar, $initialToken = 'default') {
		
		// Ensure grammar is an array
		if(!is_array($grammar))
			throw new \InvalidArgumentException("Lexer grammar must be defined in an array");
			
		// Ensure initialToken is a string
		if(!is_string($initialToken))
			throw new \InvalidArgumentException("Lexer initial token name must be a string");
		
		// Store the grammar and initialToken
		$this->grammar = $grammar;
		$this->initialToken = $initialToken;
		
		// Allow chaining
		return $this;
	}
	
	/**
	 * Set source
	 */
	public function sourceString($source) {
		
		// Ensure source is an string
		if(!is_string($source))
			throw new \InvalidArgumentException("Lexer source string must be a string");
		
		// Store the file and source
		$this->file = '{string}';
		$this->source = $source;
		
		// Allow chaining
		return $this;
	}
	
	/**
	 * Load source from a file
	 */
	public function sourceFile($file) {
		
		// Ensure source is a string
		if(!is_file($file))
			throw new \InvalidArgumentException("Lexer source file `$file` does not exist");
		
		// Store the file and source
		$this->file = $file;
		$this->source = file_get_contents($file);
		
		// Allow chaining
		return $this;
	}
	
	/**
	 * Get the tokens for the loaded configuration
	 */
	public function tokenize() {
		
		if(is_null($this->grammar))
			throw new \LogicException("Lexer grammar must be loaded before using `tokenize`");
			
		if(is_null($this->source))
			throw new \LogicException("Lexer source must be loaded before using `tokenize`");
		
		// Reset line number
		$lineNumber = 1;
		$colNumber = 0;
		
		// Token start positions
		$tokenLine = 1;
		$tokenCol = 0;
		
		// Go through the code one char at a time, starting with default token
		$length = strlen($source);
		$tokens = array();
		$queue = '';
		$processImmediately = false;
		for($pointer = 0; $pointer <= $length; true) {
			
			// Check if processing a forwarded $char
			if($processImmediately) {
				
				// Shut off process flag
				$processImmediately = false;
			}
			
			// Else get a new $char
			else {
				
				// Get char at pointer
				$char = substr($source, $pointer, 1);
				
				// Step ahead after we have the char
				$pointer++;
				
				// Increment line count
				if($char == "\n" || $char == "\r") {
					$lineNumber++;
					$colNumber = -1;
				}
				
				// Increment column count
				$colNumber++;
			}
			
			// Check that the current token is defined
			if(!isset($rules[$token]))
				throw new LexerSyntaxException("The tokenizer has encountered an invalid <i>$token</i> token", $tokenLine, $tokenCol);
			
			// Use the token
			$xtoken = $rules[$token];
			
			// Check for special token types
			if(isset($xtoken['type'])) {
				switch($xtoken['type']) {
					
					// Check if the token is conditional, which means that there's a choice of
					// which token rules to follow, depending on the conditions specified.
					case 'conditional':
						$last = count($tokens);
						$last = $tokens[$last - 1];
						
						// Loop through all possible conditions
						foreach($xtoken as $key => $condtoken) {
						
							// Skip the type
							if($key === 'type')
								continue;
						
							// Check that the token matches the condition, if set
							if(isset($condtoken['token']) && $condtoken['token'] !== $last->name)
								continue;
								
							// Check for matching value or catch-all condition
							if(!isset($condtoken['value']) || $condtoken['value'] === $last->value) {
								
								// Switch to this version of the token
								$xtoken = $condtoken;
								break 2;
							}
						}
						
						// If no conditional match found, throw exception
						throw new Exception("LTML Tokenize Error: The tokenizer has encountered a conditional token <i>$token</i> ".
							"that has no valid match for the last token <i>$last[token]</i> and value <code>$last[value]</code>");
						
					default:
						throw new Exception("LTML Tokenize Error: The tokenizer has encountered an invalid token type <code>".
							$xtoken['type']."</code> for token <i>$token</i>");
				
				}
			}
			
			// Whether to check for the ' ' space token, matches all whitespace
			if($char === "\n" || $char === "\r" || $char === "\t")
				$checkchar = ' ';
			else
				$checkchar = $char;
			
			// Check if the current token has an action for this char, both literal and *
			$literal = isset($xtoken[$checkchar]);
			$star = isset($xtoken['*']);
			
			// If no match, char is part of token and continue
			if(!$literal && !$star) {
				$queue .= $char;
				continue;
			}
			
			// Load the next token
			$ntoken = $xtoken[$literal ? $checkchar : '*'];
			
			// Handle '#drop' token
			if($ntoken === '#drop') {
				continue;	
			}
			
			// Handle '#self' token
			if($ntoken === '#self') {
				$queue .= $char;
				continue;	
			}
			
			// Handle '#error' token
			if($ntoken === '#error') {
				//var_dump($tokens);
				//var_dump(array('token' => $token, 'queue' => $queue));
				return $tokens;
				throw new Exception("Unexpected <code><b>'$char'</b></code>
					after <i>$token</i> on line $lineNumber at column $colNumber, code: <code>$queue$char</code>");
			}
			
			// Add the current token to the stack and handle queue
			$tokens[] = (object) array('name' => $token, 'value' => $queue,
				'line' => $tokenLine, 'col' => $tokenCol);
			
			// Update line and column for next token
			$tokenLine = $lineNumber;
			$tokenCol = $colNumber;
			
			// Handle &tokens by immediately queueing the same char on the new token
			if(substr($ntoken, 0, 1) === '&') {
				$token = substr($ntoken, 1);
				$processImmediately = true;
				$queue = '';
			}
			
			// Normal tokens will start queue on next char
			else {
				$token = $ntoken;
				$queue = $char;
			}
		}
		
		// Return tokens
		return $tokens;
	}
	
/**
 * Lexer Syntax Exception
 */
class LexerSyntaxException extends Exception {
	
	/**
	 * Save relevant information
	 */
	public function __construct($message, $
}