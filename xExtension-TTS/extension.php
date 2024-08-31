<?php

declare(strict_types=1);

final class TTSExtension extends Minz_Extension {
	#[\Override]
	public function init(): void {
		$this->registerController('TTS');
		Minz_View::appendScript($this->getFileUrl('tts.js', 'js'));
		spl_autoload_register(array($this, 'loader'));
	}

	public function loader(string $class_name): void {
		if (strpos($class_name, 'TTS') === 0) {
			$class_name = substr($class_name, 4);
			$base_path = $this->getPath() . '/';
			include($base_path . str_replace('\\', '/', $class_name) . '.php');
		}
	}
}
