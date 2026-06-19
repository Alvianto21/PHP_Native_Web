<?php

/**
 * Flash message helper for setting and displaying session-based alerts.
 */
class Flasher {
	/**
	 * Set a flash message in session.
	 *
	 * @param string $pesan Short message text
	 * @param string $aksi Action or context for the message
	 * @param string $tipe Bootstrap alert type (e.g. 'success','danger')
	 * @return void
	 */
	public static function setFlash($pesan, $aksi, $tipe) {
		$_SESSION['flash'] = [
			'pesan' => $pesan,
			'aksi' => $aksi,
			'tipe' => $tipe
		];
	}

	/**
	 * Render and clear the flash message if present.
	 *
	 * @return void
	 */
	public static function showFlash() {
		if (isset($_SESSION['flash'])) {
			echo '<div class="alert alert-' . $_SESSION['flash']['tipe'] . ' alert-dismissible fade show" \trole="alert">'
				. 'Data <strong>' . $_SESSION['flash']['pesan'] . '</strong> ' . $_SESSION['flash']['aksi'] . '.'
				. '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
				. '</div>';
			unset($_SESSION['flash']);
		}
	}
}