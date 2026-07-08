document.addEventListener('DOMContentLoaded', () => {

	const dataEl = document.getElementById('wpsd-ttfb-data');

	if(!dataEl) return;

	try {

		const data = JSON.parse(dataEl.textContent);

		const el = document.getElementById('wpsd-ttfb-value');

		if(el && data.ttfb){

			el.textContent = data.ttfb + ' s';

		}

	} catch(e){}

});

(function(){

	window.addEventListener('wpsd_enqueued_ready', function(){

		var container = document.getElementById('wpsd-enqueued-resources');

		if(!container)return;

		container.innerHTML = window.wpsd_enqueued_markup;

	});

})();
