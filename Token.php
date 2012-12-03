<?php

class Token {

	private $token;

	private $tokenToCompare;

	public function Token($token) {
		$this->token = $token;
	}

	public function asString() {
		return $this->token;
	}

	public function asStringToCompare() {
		if (!isset($this->tokenToCompare)) {
			$tokenToCompare = $this->token;
			$tokenToCompare = preg_replace(array(
			'/\$\[\$\.\.\.\$\]\$/', // [...] looks like this $[$...$]$
			'/\$\[\$FN 1\$\]\$/',
			'/\$\[\$/',
			'/\$\]\$/',
			'/\\\[a-z]+\{([^}]*)}/', // LaTeX commands
			'/\\\[a-z]+\{/',
			'/}/',
			'/â€ž/', // „
			'/â€œ/', // “
			'/„/',
			'/“/',
			'/»/',
			'/«/',
			'/,/',
			'/‘/',
			'/\'\'/',
			'/^\s$/',
			'/\./'
			), array(
			'',
			'',
			'',
			'',
			'$1',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			''
			), $tokenToCompare);
			$this->tokenToCompare = strtolower($tokenToCompare);
		} 
		return $this->tokenToCompare;
	}

}