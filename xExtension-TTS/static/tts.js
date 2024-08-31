(function tts_player() {
	'use strict';

	const tts_player = {
		flux_list: null,
		flux: null,
		tts_player: null,

		init: function () {
			const flux_list = document.querySelectorAll('[id^="flux_"]');

			for (let i = 0; i < flux_list.length; i++) {
				if ('player' in flux_list[i].dataset) {
					continue;
				}

				tts_player.flux = flux_list[i];
				const entry_id = tts_player.flux.id.replace(/^flux_/, '');

				const player = document.createElement('audio');
				player.setAttribute('class', 'tts-player');
				player.setAttribute('controls', '');
				player.setAttribute('preload', 'none');
				player.setAttribute('src', '/i/?c=TTS&a=play&id=' + entry_id);

				const entry_div = document.getElementById(tts_player.flux.id);
				const header_tag = entry_div.getElementsByTagName('header')[0];
				header_tag.insertBefore(player, header_tag.children[0]);
				flux_list[i].dataset.player = true;
			}
		},
	};

	function add_load_more_listener() {
		tts_player.init();
		document.body.addEventListener('freshrss:load-more', function (e) {
			tts_player.init();
		});
	}

	if (document.readyState && document.readyState !== 'loading') {
		add_load_more_listener();
	} else if (document.addEventListener) {
		document.addEventListener('DOMContentLoaded', add_load_more_listener, false);
	}
}());
