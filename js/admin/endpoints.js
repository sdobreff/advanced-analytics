/**
 * Extract single item for live notifications.
 */
async function fetchSingleItem() {
	let attResp;

	try {
        const nonce = 'nonce value';
        const apiFetch = wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( nonce ) );
		const response = await apiFetch( {
			path: '/wp-control/v1/live/get_last_item',
			method: 'POST',
		} );

		attResp = await startRegistration( response );
	} catch ( error ) {
		throw error;
	}

}

/**
 * Passkey Registration Handler.
 */
wp.domReady( () => {
	fetchSingleItem();
} );
