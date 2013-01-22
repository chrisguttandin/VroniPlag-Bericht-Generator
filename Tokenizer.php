<?php

require_once('Token.php');
require_once('Tokens.php');

class Tokenizer {

	private static function joinSquareBrackets($tokens) {
		$new_tokens = array();

		for ($i = 0; $i < count($tokens); $i++) {

			// current token start with "[" and next token ends with "]"
			if (isset($tokens[$i + 1]) && preg_match('/^\$\[\$/', $tokens[$i]) && preg_match('/\$\]\$$/', $tokens[$i + 1])) {
				$new_tokens[] = $tokens[$i] . ' ' . $tokens[$i + 1];

				// skip next token
				$i++;
			}
			else {
				$new_tokens[] = $tokens[$i];
			}
		}

		return $new_tokens;
	}

	private static function splitAtCharacter($tokens, $string, $regex) {
		$new_tokens = array();

		foreach ($tokens as $token) {
			if (preg_match($regex, $token)) {
				$extra_tokens = preg_split($regex, $token);
				foreach ($extra_tokens as $j => $extra_token) {
					if ($extra_token !== '') {
						$new_tokens[] = $extra_token;
					}
					if ($j < count($extra_tokens) - 1) {
						$new_tokens[] = $string;
					}
				}
			}
	        else {
				$new_tokens[] = $token;
	        }
		}

		return $new_tokens;
	}

	private static function splitAtColons($tokens) {
		return self::splitAtCharacter($tokens, ':', '/:/');
	}

	private static function splitAtComma($tokens) {
		return self::splitAtCharacter($tokens, ',', '/,/');
	}

	private static function splitAtDots($tokens) {
		return self::splitAtCharacter($tokens, '.', '/\./');
	}

	private static function splitAtQuotes($tokens) {
		$tokens = self::splitAtCharacter($tokens, '„', '/„/');
		$tokens = self::splitAtCharacter($tokens, '“', '/“/');
		$tokens = self::splitAtCharacter($tokens, '»', '/»/');
		$tokens = self::splitAtCharacter($tokens, '«', '/«/');

		return $tokens;
	}

	private static function splitAtSemicolons($tokens) {
		return self::splitAtCharacter($tokens, ';', '/;/');
	}

	private static function splitAtWhitespaces($text) {
		// remove duplicate whitespace
		$tokens = preg_split('/\s/', $text);

		// add double whitespace
		$text = implode('  ', $tokens);

		// split text with empty tokens
		$tokens = preg_split('/\s/', $text);

		// add whitespace to empty tokens
		for ($i = 0; $i < count($tokens); $i++) {
			if ($tokens[$i] === '') {
	            $tokens[$i] = ' ';
	        }
		}

		return $tokens;
	}

	public static function tokenize($text) {

		$tokens = self::splitAtWhitespaces($text);

		$tokens = self::splitAtComma($tokens);

		$tokens = self::splitAtDots($tokens);

		$tokens = self::splitAtSemicolons($tokens);

		$tokens = self::splitAtColons($tokens);

		$tokens = self::joinSquareBrackets($tokens);

		$tokens = self::wrapBoldCommand($tokens);

		$tokens = self::wrapTokens($tokens);

		return $tokens;
	}

	public static function wrapBoldCommand($tokens) {
		$wrap = false;
		foreach ($tokens as $i => $token) {
			if (substr($token, 0, 8) === '\textbf{'
					&& substr($token, count($token) - 2, 1) !== '}') {
				$tokens[$i] = $token . '}';
				$wrap = true;
			}
			else if ($wrap
					&& substr($token, count($token) - 2, 1) === '}'
					&& substr($token, count($token) - 3, 2) !== '{}') {
				$tokens[$i] = '\textbf{' . $token;
				$wrap = false;
			}
			else if ($wrap
					&& $token !== ' '
					&& preg_match('/^\\[a-zA-Z]$/', $token)) {
				$tokens[$i] = '\textbf{' . $token . '}';
			}
		}
		return $tokens;
	}

	public static function wrapTokens($tokens) {
		foreach ($tokens as $i => $token) {
			$tokens[$i] = new Token($token);
		}
		return new Tokens($tokens);
	}

}