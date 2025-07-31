/**
 * Extract single item for live notifications.
 */
async function fetchSingleItem() {
	let attResp;

	try {
		attResp = await wp.apiFetch({
			path: '/0-day/v1/live/get_last_item',
			method: 'GET',
			cache: 'no-cache'
		});

		if (attResp.event) {
			
			jQuery("style").append( attResp.style );

			jQuery('.aadvan-live-notif-item').addClass(attResp.classes);
			jQuery('#wp-admin-bar-aadvan-menu .ab-item').html('<b><i>' + attResp.in + '</i></b> ' + attResp.event.message);
		} else if (attResp.message) {
			jQuery('#wp-admin-bar-aadvan-menu .ab-item').html('<b><i>' + attResp.message + '</i></b>');
		}

	} catch (error) {
		throw error;
	}
}

/**
 * Passkey Registration Handler.
 */
wp.domReady(() => {
	fetchSingleItem();
});
