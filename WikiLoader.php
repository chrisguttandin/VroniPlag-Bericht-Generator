<?php

class WikiLoader {
	const API = 'http://de.vroniplag.wikia.com/api.php';
	const PAGES_PER_QUERY = 50;

	// Returns a list of pages with a given prefix in unserialized format.
	// Gracefully resumes the API query if the result limit is exceeded.
	static private function queryPrefixList($prefix,
		$ignoreRedirects = false)
	{
		$url = self::API.'?action=query&prop=&format=php&generator=allpages&gaplimit=500&gapprefix='.urlencode($prefix);
		if ($ignoreRedirects)
			$url .= '&gapfilterredir=nonredirects';

		$s = unserialize(file_get_contents($url));

		while(isset($s['query-continue'])) {
			$url2 = $url.'&gapfrom='.urlencode($s['query-continue']['allpages']['gapfrom']);
			unset($s['query-continue']);
			$s = wikiLoaderMerge($s, unserialize(file_get_contents($url2)));
		}

		return $s;
	}

	// Returns a list of category members in unserialized format.
	// Gracefully resumes the API query if the result limit is exceeded.
	static private function queryCategoryMembers($category, $namespace = false)
	{
		$url = self::API.'?action=query&list=categorymembers&cmtitle='.urlencode($category).'&format=php&cmlimit=500';
		if($namespace !== false)
			$url .= '&cmnamespace='.urlencode($namespace);
		$s = unserialize(file_get_contents($url));

		while(isset($s['query-continue'])) {
			$url2 = $url.'&cmcontinue='.urlencode($s['query-continue']['categorymembers']['cmcontinue']);
			unset($s['query-continue']);
			$s = wikiLoaderMerge($s, unserialize(file_get_contents($url2)));
		}

		return $s;
	}

	// Returns page data (given a list of page IDs) in unserialized format.
	static public function queryEntries($pageids)
	{
		$url = self::API.'?action=query&prop=info%7Crevisions%7Ccategories&rvprop=content&cllimit=max&format=php&pageids='.urlencode(implode('|', $pageids));
		$s = unserialize(file_get_contents($url));

		while(isset($s['query-continue'])) {
			$url2 = $url.'&clcontinue='.urlencode($s['query-continue']['categories']['clcontinue']);
			unset($s['query-continue']);
			$s = wikiLoaderMerge($s, unserialize(file_get_contents($url2)));
		}

		return $s;
	}

	// Returns page data (given page titles) in unserialized format.
	static private function queryEntriesByTitles($titles)
	{
		$url = self::API.'?action=query&prop=info%7Crevisions%7Ccategories&rvprop=content&cllimit=max&format=php&titles='.urlencode(implode('|', $titles));
		$s = unserialize(file_get_contents($url));

		while(isset($s['query-continue'])) {
			$url2 = $url.'&clcontinue='.urlencode($s['query-continue']['categories']['clcontinue']);
			unset($s['query-continue']);
			$s = wikiLoaderMerge($s, unserialize(file_get_contents($url2)));
		}

		return $s;
	}


	// Returns a list of page IDs of pages with a given prefix.
	static public function getPrefixList($prefix, $ignoreRedirects = false)
	{
		$s = self::queryPrefixList($prefix);
		$pageids = array();
		foreach($s['query']['pages'] as $page) {
			$pageids[] = $page['pageid'];
		}
		return $pageids;
	}

	// Returns a list of page IDs of category members.
	static public function getCategoryMembers($category, $namespace = false)
	{
		$s = self::queryCategoryMembers($category, $namespace);
		$pageids = array();
		if(!isset($s['query']['categorymembers'])) {
			echo "getCategoryMembers(): Error, queryCategoryMembers($category, $namespace) returned:\n";
			var_dump($s);
			exit(1);
		}
		foreach($s['query']['categorymembers'] as $member) {
			$pageids[] = $member['pageid'];
		}
		return $pageids;
	}

	// Returns a list of page titles of category members.
	static public function getCategoryMembersTitles($category, $namespace = false)
	{
		$s = self::queryCategoryMembers($category, $namespace);
		$titles = array();
		foreach($s['query']['categorymembers'] as $member) {
			$titles[] = $member['title'];
		}
		return $titles;
	}

	// Returns page data for a single page ID, in unserialized format.
	static public function getEntry($pageid)
	{
		return self::queryEntries(array($pageid));
	}

	// Returns raw Wikitext for a single page ID.
	static public function getRawText($pageid)
	{
		$s = self::queryEntries(array($pageid));
		return $s['query']['pages'][$pageid]['revisions'][0]['*'];
	}

	// Returns raw Wikitext for a single page, given the page title.
	static public function getRawTextByTitle($title)
	{
		$s = self::queryEntriesByTitles(array($title));
		if(!isset($s['query']['pages']))
			return false;
		foreach ($s['query']['pages'] as $page)
			if(isset($page['revisions'][0]['*']))
				return $page['revisions'][0]['*'];
		return false;
	}

	// Returns page data for multiple page IDs, in unserialized format.
	// Optionally cleans up the returned data (removing results from
	// pages that are redirects; sorting results by page title).
	static public function getEntries($pageids,
		$ignoreRedirects = false, $sortByTitle = true)
	{
		$entries = array();
		foreach(array_chunk($pageids, self::PAGES_PER_QUERY) as $chunk) {
			$response = self::queryEntries($chunk);
			if(isset($response['query']['pages']))
				$entries = array_merge($entries, $response['query']['pages']);
		}

		if($ignoreRedirects) {
			$entries = array_filter($entries, 'wikiLoaderIsNonRedirect');
		}

		if($sortByTitle) {
			usort($entries, 'wikiLoaderTitleCmp');
		}

		return $entries;
	}

	// Returns page data for multiple page titles, in unserialized format.
	// Optionally cleans up the returned data (removing results from
	// pages that are redirects; sorting results by page title).
	static public function getEntriesByTitles($titles,
		$ignoreRedirects = false, $sortByTitle = true)
	{
		$entries = array();
		foreach(array_chunk($titles, self::PAGES_PER_QUERY) as $chunk) {
			$response = self::queryEntriesByTitles($chunk);
			if(isset($response['query']['pages']))
				$entries = array_merge($entries, $response['query']['pages']);
		}

		if($ignoreRedirects) {
			$entries = array_filter($entries, 'wikiLoaderIsNonRedirect');
		}

		if($sortByTitle) {
			usort($entries, 'wikiLoaderTitleCmp');
		}

		return $entries;
	}

	// Returns page data for all pages whose title starts with the given
	// prefix, in unserialized format.
	// Optionally cleans up the returned data (removing results from
	// pages that are redirects; sorting results by page title).
	static public function getEntriesWithPrefix($prefix,
		$ignoreRedirects = false, $sortByTitle = true)
	{
		$pageids = self::getPrefixList($prefix, $ignoreRedirects);
		return self::getEntries($pageids, false, $sortByTitle);
	}

	static public function parseSource($rawText, $prefix)
	{
		$rawText = html_entity_decode(html_entity_decode($rawText, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'); // Two times better than once...
		if(preg_match_all('/{{'.$prefix.'(.*?)}}/s', $rawText, $matches) === 1) {
			$text = $matches[1][0];
			preg_match_all('/|\s*(\w+)\s*=\s*([^|]+)/', $text, $matches);
			$i = 0;
			$source = array();
			while(isset($matches[1][$i])) {
				if($matches[1][$i]) {
					$source[$matches[1][$i]] = trim($matches[2][$i]);
				}
				$i++;
			}
			return $source;
		} else {
			return false;
		}
	}
}

// these functions have to be defined outside of the class --
// they are used as callbacks
function wikiLoaderIsNonRedirect($entry) {
	return !isset($entry['redirect']);
}
function wikiLoaderTitleCmp($entry1, $entry2) {
	return strnatcasecmp($entry1['title'], $entry2['title']);
}

// Replacement for array_merge_recursive with one difference:
//   it does not append arrays with numeric indices (this "feature" of
//   array_merge_recursive would break WikiLoader::queryEntries())
function wikiLoaderMerge($obj1, $obj2) {
	if (is_array($obj1) && is_array($obj2)) {
		foreach ($obj2 as $key => $value) {
			if (isset($obj1[$key])) {
				$obj1[$key] = wikiLoaderMerge($obj1[$key], $value);
			} else {
				$obj1[$key] = $value;
			}
		}
		return $obj1;
	} else {
		return $obj2;
	}
}
