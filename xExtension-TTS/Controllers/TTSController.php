<?php

declare(strict_types=1);

final class FreshExtension_TTS_Controller extends Minz_ActionController {
	public ?Minz_Extension $extension;

	#[\Override]
	public function init(): void {
		$this->extension = Minz_ExtensionManager::findExtension('TTS');
	}

	public function playAction(): void {
		if (!FreshRSS_Auth::hasAccess()) {
			Minz_Error::error(403);
		}

		$id = Minz_Request::paramString('id');
		if ($id === '') {
			Minz_Error::error(404);
		}

		$entryDAO = FreshRSS_Factory::createEntryDao();
		$entry = $entryDAO->searchById($id);
		if ($entry === null) {
			Minz_Error::error(404);
			return;
		}

		if (!FreshRSS_Context::hasSystemConf()) {
			throw new FreshRSS_Context_Exception('System configuration not initialised!');
		}

		$username = Minz_Session::paramString('currentUser');
		assert($username);

		// save locally for caching and cheap repeat replay
		$localFilePath = '/tmp/' . $id . '.mp3';
		$localFile = @fopen($localFilePath, 'r');
		if ($localFile) {
			error_log("TTS: User '$username', entry id $id, streaming from existing audiofile");
			$localFile = fopen($localFilePath, 'r');
			fpassthru($localFile);
			exit(0);
			// TODO determine if the file has been finalized, and report back Content-Length.
			// Possibly support bytes-range then.
		}

		file_put_contents('/tmp/' . $id . '.who', $username);

		$cmd = 'w3m -v -F -T text/html -dump -cols 9999 - ';
		$cmd .= '| grep -v "^[[]https[]]$" '; // w3m replaces images with "[https]" lines
		$cmd .= '| time -o /tmp/' . $id . '.piper.time  /usr/share/piper/piper --model /piper-voices/en/en_US/lessac/high/en_US-lessac-high.onnx --output_raw ';
		$cmd .= '| time -o /tmp/' . $id . '.ffmpeg.time ffmpeg -ar 22050 -ac 1 -f s16le -i - -f mp3 -';

		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("file", '/tmp/' . $id . '.err', "x") // stderr is a file to write to
		);
		$process = proc_open($cmd, $descriptorspec, $pipes/*, $cwd, $env*/);
		assert($process);
		$entry->loadCompleteContent();
		$htmlContent = $entry->title() . "\n\n" . $entry->content(/*withEnclosures=*/false);
		$contentStrlen = strlen($htmlContent);
		error_log("TTS: User '$username', entry id $id, running TTS on $contentStrlen bytes of text input");
		$cc = fopen('/tmp/' . $id . '.html', 'wb');
		fwrite($cc, $htmlContent);
		fclose($cc);
		fwrite($pipes[0], $htmlContent);
		fclose($pipes[0]);

		// stream the output, present it as audio encoded content
		header("Content-Type: audio/mpeg");
		//header("Cache-Control: max-age=60");

		if (/* just stream it? */false) {
			fpassthru($pipes[1]);
			exit(0);
		}

		// save locally for caching, cheap replay, and analysis
		$localFile = fopen($localFilePath, /* open for writing, fail if exists */'x');
		if ($localFile) {
			while ($chunk = fread($pipes[1], 512)) {
				$chunkBytes = strlen($chunk);
				$writtenToLocal = fwrite($localFile, $chunk);
				assert($writtenToLocal === $chunkBytes);
				echo($chunk);
				//$writtenToRemove = fwrite(STDOUT, $chunk); // doesn't work
				// no assert - if the user disconnects, we would prefer to finalize the file
			}
		}
		// TODO record duration and audio stream size for usage accounting
		// TODO per-feed configuration of language and voice to use
		exit(0); // don't try to load any view
	}
}
