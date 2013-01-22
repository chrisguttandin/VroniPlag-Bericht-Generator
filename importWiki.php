<?php

require_once('config.php');

require_once('korrekturen.php');

function getCommonPrefix($s1, $s2) {
	$max = min(strlen($s1), strlen($s2));
	for ($i = 0; $i < $max && $s1[$i] == $s2[$i]; ++$i)
		;
	return substr($s1, 0, $i);
}

setlocale(LC_ALL, 'de_DE');

if(!file_exists('cache')) {
	print "Fehler: Cache existiert nicht! 'make cache' ausgefuehrt?\n";
	exit(1);
}
$cache = unserialize(file_get_contents('cache'));

$content = $cache['static'];

// Bericht Box entfernen
$content = preg_replace('/{{Bericht[^}]*}}/', '', $content);
// Infobox entfernen
$content = preg_replace('/{{Infobox[^}]*}}/', '', $content);
// Alles bevor BEGIN_BERICHT entfernen
$content = preg_replace('/.*BEGIN_BERICHT/s', '', $content);
// Kategorien am Ende entfernen
$content = preg_replace('/\[\[Kategorie:[^]|]*\]\]/', '', $content);

preg_match('/{\|[^}]*\|}/', $content, $tables, PREG_OFFSET_CAPTURE);

require_once('Table.php');

for ($i = 0; $i < count($tables); $i++) {
    $tables[$i] = new Table($tables[$i][0], $tables[$i][1]);
}

$offset = 0;
foreach ($tables as $table) {
	$replacement = $table->asLatexSyntax();
	$start = $table->getWikiaPosition() + $offset;
	$length = $table->getWikiaLength();
	$content = substr_replace($content, $replacement, $start, $length);
	$offset += strlen($replacement) - $length;
}

//preg_match('/\[\[([^\]]*)\]\]/', $content, $images, PREG_OFFSET_CAPTURE);
preg_match_all('/(\[\[Datei:([0-9a-zA-Z\-\s,\.]+)\|([0-9a-zA-Z\-\s,\.\|]*)\|([0-9a-zA-Z\-\s,\.\|]*)\]\][\s<>td\/]*)+(\[\[Datei:([0-9a-zA-Z\-\s,\.]+)\|([0-9a-zA-Z\-\s,\.\|]*)\|([0-9a-zA-Z\-\s,\.\|]*)\]\])/', $content, $images, PREG_OFFSET_CAPTURE);

$offset = 0;
for ($i = 0; $i < count($images[0]); $i++) {
	$start = $images[0][$i][1] + $offset;
	$length = strlen($images[0][$i][0]);

	preg_match_all('/(\[\[Datei:([0-9a-zA-Z\-\s,\.]+)\|([0-9a-zA-Z\-\s,\.\|]*)\|([0-9a-zA-Z\-\s,\.\|]*)\]\])/', $images[0][$i][0], $inner_images);

	$replacement = '\begin{figure}[h!]';
	for ($i = 0; $i < count($inner_images[0]); $i++) {
		$replacement .= ' \begin{minipage}{0.31\textwidth} \includegraphics[width=\textwidth]{img/' . $inner_images[2][$i] . '} \caption{' . $inner_images[4][$i] . '} \end{minipage}';
		if ($i + 1 < count($inner_images[0])) {
			$replacement .= ' \hfill';
		}

		// query the image url
		$all_images = unserialize(file_get_contents('http://de.vroniplag.wikia.com/api.php?action=query&list=allimages&format=php&aifrom=' . urlencode($inner_images[2][$i])));
		$image_url = $all_images['query']['allimages'][0]['url'];

		// download the image
		exec('curl -L ' . $image_url . ' > "img/' . $inner_images[2][$i] . '"');
	}
	$replacement .= ' \end{figure}';

	$content = substr_replace($content, $replacement, $start, $length);
	$offset += strlen($replacement) - $length;
}

preg_match_all('/(\[\[Datei:([0-9a-zA-Z\-\s,\.]+)\|([0-9a-zA-Z\-\s,\.\|]*)\|([0-9a-zA-Z\-\s,\.\|]*)\]\])/', $content, $images, PREG_OFFSET_CAPTURE);

$offset = 0;
for ($i = 0; $i < count($images[0]); $i++) {
	$replacement = '\begin{figure}[h!] \begin{minipage}{\textwidth} \includegraphics[width=\textwidth]{img/' . $images[2][$i][0] . '} \caption{'. $images[4][$i][0] . '} \end{minipage} \end{figure}';
	$start = $images[0][$i][1] + $offset;
	$length = strlen($images[0][$i][0]);
	$content = substr_replace($content, $replacement, $start, $length);
	$offset += strlen($replacement) - $length;

	// query the image url
	$all_images = unserialize(file_get_contents('http://de.vroniplag.wikia.com/api.php?action=query&list=allimages&format=php&aifrom=' . urlencode($images[2][$i][0])));
	$image_url = $all_images['query']['allimages'][0]['url'];

	// download the image
	exec('curl -L ' . $image_url . ' > "img/' . $images[2][$i][0] . '"');
}

// references
$content = korrStringWithLinks($content, true, STUFFINTOFOOTNOTES, false);

$content = preg_replace('/===\s*([^=]+?)\s*===/s', '\subsection{$1}', $content);

$content = preg_replace('/==\s*([^=]+?)\s*==/s', '\section{$1}', $content);

$content = korrWikiFontStylesWithoutLineBreaks($content);

$arr = explode("\n", $content);
$arr[] = ''; // for ensuring itemize/enumerate are closed properly

$i = 0;
$inEnum = '';
foreach($arr as $a) {
	$new[$i] = '';
	preg_match('/^([\*#]*)(.*)$/', $a, $match);
	$enumPrefix = $match[1];
	$enumSuffix = $match[2];

	$commonEnumPrefix = getCommonPrefix($enumPrefix, $inEnum);
	while(strlen($inEnum) > strlen($commonEnumPrefix)) {
		if($inEnum[strlen($inEnum)-1] == '#')
			$new[$i] .= '\end{enumerate}'."\n";
		else
			$new[$i] .= '\end{itemize}'."\n";
		$inEnum = substr($inEnum, 0, strlen($inEnum)-1);
	}
	while(strlen($inEnum) < strlen($enumPrefix)) {
		if($enumPrefix[strlen($inEnum)] == '#')
			$new[$i] .= '\begin{enumerate}'."\n";
		else
			$new[$i] .= '\begin{itemize}'."\n";
		$inEnum .= $enumPrefix[strlen($inEnum)];
	}

	if(!empty($enumPrefix))
		$new[$i] .= '\item ';
	$new[$i] .= $enumSuffix."\n";
	$i++;
}

$content = implode("\n", $new);

if (STUFFINTOFOOTNOTES) {
	// display URL's in fixed-width fonts
	$content = preg_replace('/\\\url{(.*?)}/s', '\texttt{\url{$1}}', $content);

	// display Links as footnotes
	$content = preg_replace('/\\\href{(.*?)}{(.*?)}/s', '$2\footnote{\url{$1}}', $content);
}

print($content);
