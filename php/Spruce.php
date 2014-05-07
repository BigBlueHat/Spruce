<?php
require_once "PHP/CodeSniffer.php";
require_once "PHP/CodeSniffer/Tokenizers/PHP.php";
require_once "PHP/CodeSniffer/Tokenizers/CSS.php";

class Spruce
{
	static protected $tree;
	static protected $tokens;
	static protected $tokenizer;

	static public function up($html, $spruce)
	{
		self::$tokenizer = new PHP_CodeSniffer_Tokenizers_CSS();
		// TODO: parse $spruce to see if it's a path or a full Spruce string
		self::$tokens = self::$tokenizer->tokenizeString(file_get_contents($spruce));
		self::$tree = array();
		//print_r(self::tokens);
		reset(self::$tokens);
		while ($t = current(self::$tokens)) {
			if ($t['type'] != 'T_OPEN_TAG' && $t['type'] != 'T_DOC_COMMENT' && $t['type'] != 'T_COMMENT') {
				if ($t['type'] == 'T_ASPERAND') {
					$temp = next(self::$tokens);
					switch ($temp['content']) {
						case 'import':
							next(self::$tokens);
							self::addImportRule(); //print_r($temp);
							continue;
						case 'media':
							next(self::$tokens);
							self::addMediaRule();
							continue;
					}
				} elseif ($t['type'] == 'T_STRING') {
					self::addStatement();
				}
			}
			next(self::$tokens);
		}
		return self::$tree;
	}

	static private function addImportRule()
	{
		while ($t = current(self::$tokens)) {
			if ($t['type'] != 'T_SEMICOLON') {
				if ($t['type'] == 'T_URL') {
					$url = trim($t['content'], "\"\'");
					self::$tree['@import'][$url] = '';
					end(self::$tree['@import']);
					$k = key(self::$tree['@import']);
				}
				if ($t['type'] == 'T_STRING' && isset($k)) {
					self::$tree['@import'][$k][] = $t['content'];
				}
				next(self::$tokens);
			} else {
				return;
			}
		} 
	}

	static private function addMediaRule()
	{
		while ($t = current(self::$tokens)) {
			if ($t['type'] != 'T_SEMICOLON') {
				if ($t['type'] == 'T_URL') {
					$url = trim($t['content'], "\"\'");
					self::$tree['@import'][$url] = '';
					end(self::$tree['@import']);
					$k = key(self::$tree['@import']);
				}
				if ($t['type'] == 'T_STRING' && isset($k)) {
					self::$tree['@import'][$k][] = $t['content'];
				}
				next(self::$tokens);
			} else {
				return;
			}
		} 
	}

	static private function addStatement()
	{
		$temp = '';
		while ($t = current(self::$tokens)) {
			if ($t['type'] != 'T_OPEN_CURLY_BRACKET' && $t['type'] != 'T_CLOSE_CURLY_BRACKET') {
				$temp .= $t['content'];
				next(self::$tokens);
			} elseif ($t['type'] == 'T_OPEN_CURLY_BRACKET') {
				next(self::$tokens); // advance past curly bracket
				self::$tree[trim($temp)] = self::addProperty();
				$temp = '';
				next(self::$tokens);
			} elseif ($t['type'] == 'T_CLOSE_CURLY_BRACKET') {
				return;
			}
		}

	}

	static private function addProperty()
	{
		$temp = '';
		while ($t = current(self::$tokens)) {
			if ($t['type'] != 'T_COLON') {
				$temp .= $t['content'];
				next(self::$tokens);
			} elseif ($t['type'] == 'T_COLON') {
				next(self::$tokens); // advance past the colon
				return array(trim($temp)=>self::addValues());
			}
		}
	}

	static private function addValues()
	{
		$temp = '';
		while ($t = current(self::$tokens)) {
			if ($t['type'] != 'T_SEMICOLON' && $t['type'] != 'T_CLOSE_CURLY_BRACKET') {
				$temp .= $t['content'];
				next(self::$tokens);
			} elseif ($t['type'] == 'T_SEMICOLON' || $t['type'] == 'T_CLOSE_CURLY_BRACKET') {
				next(self::$tokens); // advance past the colon or curly bracket
				return trim($temp);
			}
		}
	}
}
