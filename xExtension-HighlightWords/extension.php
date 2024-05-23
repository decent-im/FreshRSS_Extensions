<?php

declare(strict_types=1);

final class HighlightWordsExtension extends Minz_Extension {
	public string $words_primary;
	public string $words_secondary;
	public string $permission_problem = '';

	#[\Override]
	public function init(): void {
		$this->registerTranslates();
		
		$current_user = Minz_Session::paramString('currentUser');
		$filename = 'words.' . $current_user . '.js';
		$filepath = join_path($this->getPath(), 'static', $filename);

		if (file_exists($filepath)) {
			Minz_View::appendScript($this->getFileUrl($filename, 'js'));
		}

		Minz_View::appendScript($this->getFileUrl('mark.es6.min.js','js'));
		Minz_View::appendScript($this->getFileUrl('highlightWords.js', 'js'));
		Minz_View::appendStyle($this->getFileUrl('highlightWords.css', 'css'));
		
		
	}

	#[\Override]
	public function handleConfigureAction(): void {
		$this->registerTranslates();

		$current_user = Minz_Session::paramString('currentUser');
		$filename = 'words.' . $current_user . '.js';
		$staticPath = join_path($this->getPath(), 'static');
		$filepath = join_path($staticPath, $filename);

		if (!file_exists($filepath) && !is_writable($staticPath)) {
			$tmpPath = explode(EXTENSIONS_PATH . '/', $staticPath);
			$this->permission_problem = $tmpPath[1] . '/';
		} elseif (file_exists($filepath) && !is_writable($filepath)) {
			$tmpPath = explode(EXTENSIONS_PATH . '/', $filepath);
			$this->permission_problem = $tmpPath[1];
		} elseif (Minz_Request::isPost()) {
			$words_primary = html_entity_decode(Minz_Request::paramString('words_primary'));
			$words_secondary = html_entity_decode(Minz_Request::paramString('words_secondary'));
			$enableInArticle = Minz_Request::paramString('enableInArticle')?'true':'false';
			
			file_put_contents($filepath, 'const HIGHLIGHT_WORDS_PRIMARY = ['.$words_primary.'];const HIGHLIGHT_WORDS_SECONDARY = ['.$words_secondary.'];/*enableInArticle ['. $enableInArticle .']*/const enableInArticle = ' . $enableInArticle.';');
		}

		$this->words_primary = '';
		$this->words_secondary = '';
		
		if (file_exists($filepath)) {
			
			$filecontent = file_get_contents($filepath);
			preg_match_all("/\[([^\]]*)\]/", $filecontent, $match);
			$this->words_primary = htmlentities($filecontent ? $match[1][0] : '');
			$this->words_secondary = htmlentities($filecontent ? $match[1][1]: '');

			$this->enableInArticle = $match[1][2];
		}
	}
}
