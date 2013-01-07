\documentclass[ngerman,final,fontsize=12pt,paper=a4,twoside,bibliography=totocnumbered,BCOR=8mm,draft=false]{scrartcl}

\usepackage[LGRx,T1]{fontenc}
\usepackage[ngerman]{babel}
\usepackage[utf8]{inputenx}
\usepackage[sort&compress,square]{natbib}
\usepackage[babel]{csquotes}
\usepackage[hyphens]{url}
\usepackage[draft=false,final,plainpages=false,pdftex]{hyperref}
\usepackage{eso-pic}
\usepackage{graphicx}
\usepackage{color}
\usepackage{xcolor}
\usepackage{pdflscape}
\usepackage{longtable}
\usepackage{framed}
\usepackage{textcomp}
\usepackage{textgreek}

\usepackage[charter,sfscaled]{mathdesign}

%\usepackage[spacing=true,tracking=true,kerning=true,babel]{microtype}
\usepackage[spacing=true,kerning=true,babel]{microtype}

\usepackage{colortbl}

\usepackage{pdfcolparallel}

%\setparsizes{1em}{.5\baselineskip}{0pt plus 1fil}

\usepackage{float} % for floating figures

\author{VroniPlag} 

<?php
require 'config.php';
require 'loadParameters.php';
?>
\title{<?php print $TITEL1;?>}
\subtitle{<?php print $TITEL2;?>}
\publishers{\url{<?php print 'http://de.vroniplag.wikia.com/wiki/'.BERICHT_SEITE;?>}}

\hypersetup{%
        pdfauthor={VroniPlag},%
	pdftitle={<?php print $TITEL1.' -- '.$TITEL2;?>},%
        pdflang={en},%
        pdfduplex={DuplexFlipLongEdge},%
        pdfprintscaling={None},%
	linktoc=all,%
<?php
if($abLinks === 'color' || $abLinks === 'color+underline' || $abLinks === 'color+box') {
	print "\t".'colorlinks,%'."\n";
} else if($abLinks === 'underline') {
	print "\t".'colorlinks=false,%'."\n";
	print "\t".'pdfborderstyle={/S/U/W 1},%'."\n";
	print "\t".'pdfborder=0 0 1,%'."\n";
} else if($abLinks === 'box') {
	// nothing to do
} else if($abLinks === 'none') {
	print "\t".'draft,%'."\n";
}
if($abEnableLinkColors === 'yes') {
	print "\t".'linkcolor='.$abInternalLinkColor.',%'."\n";
	print "\t".'citecolor='.$abSourceLinkColor.',%'."\n";
	print "\t".'filecolor='.$abExternalLinkColor.',%'."\n";
	print "\t".'urlcolor='.$abExternalLinkColor.',%'."\n";
	print "\t".'linkbordercolor={'.$abInternalLinkBorderColor.'},%'."\n";
	print "\t".'citebordercolor={'.$abSourceLinkBorderColor.'},%'."\n";
	print "\t".'filebordercolor={'.$abExternalLinkBorderColor.'},%'."\n";
	print "\t".'urlbordercolor={'.$abExternalLinkBorderColor.'},%'."\n";
} else {
	print "\t".'linkcolor=black,'."\n";
	print "\t".'citecolor=black,'."\n";
	print "\t".'filecolor=black,'."\n";
	print "\t".'urlcolor=black,'."\n";
	print "\t".'linkbordercolor={0 0 0},'."\n";
	print "\t".'citebordercolor={0 0 0},'."\n";
	print "\t".'filebordercolor={0 0 0},'."\n";
	print "\t".'urlbordercolor={0 0 0},'."\n";
}

?>
}

\definecolor{shadecolor}{rgb}{0.95,0.95,0.95} 
<?php

require_once('TextMarker.php');

foreach (TextMarker::getTextColours() as $colourName => $colourValues) {

?>
\definecolor{<?= $colourName ?>}{RGB}{<?= $colourValues[0] ?>,<?= $colourValues[1] ?>,<?= $colourValues[2] ?>}
<?php

}

?>
\newenvironment{fragment}
	{\begin{snugshade}}
	{\end{snugshade}
	 \penalty-200
	 \vskip 0pt plus 10mm minus 5mm}
\newenvironment{fragmentpart}[1]
	{\noindent\textbf{#1}\par\penalty500}
	{\par}
\newcommand{\BackgroundPic}
	{\put(0,0){\parbox[b][\paperheight]{\paperwidth}{%
		\vfill%
		\centering%
		\includegraphics[width=\paperwidth,height=\paperheight,%
			keepaspectratio]{background.png}%
		\vfill%
	}}}

%\setkomafont{chapter}{\Large}
\setkomafont{section}{\large}
\addtokomafont{disposition}{\normalfont\boldmath\bfseries}
\urlstyle{rm}

\begin{document}

<?php
# color+underline und color+box muessen nach \begin{document} behandelt werden
if($abLinks === 'color+underline') {
	print "\hypersetup{%\n";
	print "\t".'pdfborderstyle={/S/U/W 1},%'."\n";
	print "\t".'pdfborder=0 0 1,%'."\n";
	print "}\n";
} else if($abLinks === 'color+box') {
	print "\hypersetup{%\n";
	print "\t".'pdfborderstyle={/S/S/W 1},%'."\n";
	print "\t".'pdfborder=0 0 1,%'."\n";
	print "}\n";
}
?>

<?php require_once('korrekturen.php'); ?>
\vbox{\huge <?php print korrString($titelaufnahme_title); ?>}
\vspace*{10mm}
\vbox{\large <?php print korrStringWithLinks($titelaufnahme_subtitle, true, STUFFINTOFOOTNOTES, false); ?>}
\vspace*{10mm}
<?php

	$parameters = json_decode(file_get_contents('http://de.vroniplag.wikia.com/api.php?action=query&titles=' . NAME_PREFIX . '/AutoBarcodeParameter&format=json&cllimit=max&rvprop=content&prop=revisions'), true);
	$parameters = reset($parameters['query']['pages']);
	$parameters = $parameters['revisions'][0]['*'];
	$parameters = preg_replace('/\[\[Kategorie:([0-9a-zA-Z]+)\]\]/', '', $parameters);
	$parameters = json_decode($parameters, true);
	$parameters = $parameters['AutoBarcodeParameter'];

	$height = 300;
	$width = ($parameters['to'] - $parameters['from']) * 10;

	$barcode = imagecreate($width, $height);

	$white = imagecolorallocate($barcode, 255, 255, 255);
	$blue = imagecolorallocate($barcode, 0, 0, 255);
	$dark_red = imagecolorallocate($barcode, 153, 0, 0);
	$black = imagecolorallocate($barcode, 0, 0, 0);
	$red = imagecolorallocate($barcode, 255, 0, 0);

	imagefill($barcode, 0, 0, $white);
	
	require_once 'Logger.php';	
	$pages = json_decode(file_get_contents('http://de.vroniplag.wikia.com/api.php?action=query&generator=allpages&gaplimit=500&gapprefix=' . $parameters['prefix'] . '/0&prop=categories&cllimit=max&format=json'), true);
	$pages = $pages['query']['pages'];
	$number_of_plag_pages = 0;
	$number_of_relevant_pages = $parameters['range']['to'] - $parameters['range']['from'];

	foreach ($pages as $page) {
		$index = intval(substr($page['title'], count($parameters['prefix']) + 2));
		$categories = $page['categories'];
		$percent_of_plagiarism = 0;
		$color = $white;
		foreach ($categories as $category) {
			if ($category['title'] === 'Kategorie:Plagiatsseite' && $percent_of_plagiarism < 1) {
				$percent_of_plagiarism = 1;
				$color = $black;
			}
			if ($category['title'] === 'Kategorie:Gt50' && $percent_of_plagiarism < 50) {
				$percent_of_plagiarism = 50;
				$color = $dark_red;
			}
			if ($category['title'] === 'Kategorie:Gt75' && $percent_of_plagiarism < 75) {
				$percent_of_plagiarism = 75;
				$color = $red;
			}
		}
		if ($percent_of_plagiarism > 0) {
			imagefilledrectangle($barcode, $index * 10, 0, ($index + 1) * 10 - 1, $height - 1, $color);
			$number_of_plag_pages++;
		}
	}

	imagefilledrectangle($barcode, 0, 0, ($parameters['range']['from'] - 1) * 10 - 1, $height - 1, $blue);
	imagefilledrectangle($barcode, ($parameters['range']['to'] - 1) * 10, 0, $width, $height - 1, $blue);

	imagepng($barcode, 'img/barcode.png');

	imagecolordeallocate($barcode, $white);
	imagecolordeallocate($barcode, $blue);
	imagecolordeallocate($barcode, $dark_red);
	imagecolordeallocate($barcode, $black);
	imagecolordeallocate($barcode, $red);

	imagedestroy($barcode);

	$caption = $parameters['reference'] . ' Barcode';

	$percentage_of_plagiarism = round(($number_of_plag_pages / $number_of_relevant_pages) * 100, 2);
?>
\begin{figure}[h!]
	\begin{minipage}{\textwidth}
		\includegraphics[width=\textwidth]{img/barcode.png}
	\end{minipage}
\end{figure}
\definecolor{blue}{rgb}{0,0,1}
\definecolor{black}{rgb}{0,0,0}
\definecolor{dark_red}{rgb}{0.6,0,0}
\definecolor{red}{rgb}{1,0,0}

Legende: \textcolor{blue}{nicht einberechnete Seiten}, \textcolor{black}{Seite enthält Plagiat}, \textcolor{dark_red}{mehr als 50 \% der Seite plagiiert}, \textcolor{red}{mehr als 75 \% der Seite plagiiert}
\newline
\newline
Plagiatsfunde nach Seiten. Anzahl Seiten mit Plagiaten in <?php echo $parameters['reference']; ?>: <?php echo $number_of_plag_pages; ?>, d.h. <?php echo $percentage_of_plagiarism; ?> \%

\newpage

%\AddToShipoutPicture*{\BackgroundPic}
\maketitle\thispagestyle{empty}
%\ClearShipoutPicture

\tableofcontents
\newpage

<?php require_once('importWiki.php'); ?>

\appendix
\newpage
\section{Textnachweise}

<?php require_once('importFragmente.php'); ?>
\bibliographystyle{dinat-custom}
\renewcommand{\refname}{Quellenverzeichnis}
\bibliography{ab}
\section{Abbildungsverzeichnis}
\renewcommand{\listfigurename}{}
\listoffigures
\end{document}
