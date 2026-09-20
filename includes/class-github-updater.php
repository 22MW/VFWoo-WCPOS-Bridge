<?php
/**
 * GitHub Releases updater.
 *
 * Shows the plugin in Dashboard > Updates and installs the release ZIP published on GitHub.
 * The repository is shared with another plugin, so only releases whose tag starts with
 * `pos-v` are considered and "latest release" is never used.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Github_Updater {
	private const REPO       = '22MW/VFWoo-WCPOS-Bridge';
	private const TAG_PREFIX = 'pos-v';
	private const ASSET_NAME = 'vfwoo-webkul-pos-bridge.zip';
	private const SLUG       = 'vfwoo-webkul-pos-bridge';
	private const CACHE_KEY  = 'vfwoo_webkul_bridge_github_release';

	public function register_hooks(): void {
		add_filter( 'site_transient_update_plugins', array( $this, 'filter_plugin_updates' ) );
		add_filter( 'plugins_api', array( $this, 'filter_plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
	}

	/**
	 * Newest stable release of this plugin (cached one hour, failures cached briefly).
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_latest_release(): ?array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return empty( $cached ) ? null : $cached;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases?per_page=30',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'VFWoo-Webkul-POS-Bridge',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::CACHE_KEY, array(), 10 * MINUTE_IN_SECONDS );
			return null;
		}

		$releases = json_decode( wp_remote_retrieve_body( $response ), true );
		$best     = array();
		if ( is_array( $releases ) ) {
			foreach ( $releases as $release ) {
				if ( ! is_array( $release ) || ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ) {
					continue;
				}
				$tag = (string) ( $release['tag_name'] ?? '' );
				if ( 0 !== strpos( $tag, self::TAG_PREFIX ) ) {
					continue;
				}
				if ( empty( $best ) || version_compare( $this->get_remote_version( $release ), $this->get_remote_version( $best ), '>' ) ) {
					$best = $release;
				}
			}
		}

		set_transient( self::CACHE_KEY, $best, empty( $best ) ? 10 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS );
		return empty( $best ) ? null : $best;
	}

	/**
	 * @param array<string,mixed> $release Release data.
	 */
	private function get_remote_version( array $release ): string {
		return substr( (string) ( $release['tag_name'] ?? '' ), strlen( self::TAG_PREFIX ) );
	}

	/**
	 * Download URL of the release ZIP: the named asset, or the tag archive as fallback.
	 * Only URLs inside this repository are accepted.
	 *
	 * @param array<string,mixed> $release Release data.
	 */
	private function get_package_url( array $release ): string {
		$base = 'https://github.com/' . self::REPO . '/';

		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( is_array( $asset ) && ( $asset['name'] ?? '' ) === self::ASSET_NAME ) {
					$url = (string) ( $asset['browser_download_url'] ?? '' );
					return 0 === strpos( $url, $base ) ? $url : '';
				}
			}
		}

		$tag = (string) ( $release['tag_name'] ?? '' );
		return '' === $tag ? '' : $base . 'archive/refs/tags/' . rawurlencode( $tag ) . '.zip';
	}

	/**
	 * @param object|false $transient Update transient.
	 * @return object|false
	 */
	public function filter_plugin_updates( $transient ) {
		if ( ! is_object( $transient ) || ! isset( $transient->checked ) || ! is_array( $transient->checked ) ) {
			return $transient;
		}

		$plugin  = plugin_basename( VFWOO_WEBKUL_FILE );
		$release = $this->get_latest_release();
		if ( ! $release || empty( $transient->checked[ $plugin ] ) ) {
			return $transient;
		}

		$remote  = $this->get_remote_version( $release );
		$package = $this->get_package_url( $release );
		if ( '' === $remote || '' === $package || version_compare( $remote, $transient->checked[ $plugin ], '<=' ) ) {
			return $transient;
		}

		$transient->response[ $plugin ] = (object) array(
			'slug'        => self::SLUG,
			'plugin'      => $plugin,
			'new_version' => $remote,
			'url'         => 'https://github.com/' . self::REPO,
			'package'     => $package,
		);

		return $transient;
	}

	/**
	 * Details shown in the "View version x details" window.
	 *
	 * @param false|object|array $result Current result.
	 * @param string             $action API action.
	 * @param object             $args   Request arguments.
	 * @return false|object|array
	 */
	public function filter_plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$info                = new \stdClass();
		$info->name          = 'VFWoo Webkul POS Bridge';
		$info->slug          = self::SLUG;
		$info->version       = $this->get_remote_version( $release );
		$info->author        = 'Veri*Fac*WOO';
		$info->homepage      = 'https://github.com/' . self::REPO;
		$info->requires      = '6.2';
		$info->requires_php  = '7.4';
		$info->download_link = $this->get_package_url( $release );
		$info->sections      = array(
			'description' => 'Integra los datos fiscales de Veri*Fac*WOO en el TPV WooCommerce Point of Sale de Webkul.',
			'changelog'   => $this->format_changelog( $release ),
		);

		return $info;
	}

	/**
	 * @param array<string,mixed> $release Release data.
	 */
	private function format_changelog( array $release ): string {
		$date   = substr( (string) ( $release['published_at'] ?? '' ), 0, 10 );
		$output = '<p><strong>' . esc_html( $this->get_remote_version( $release ) ) . '</strong>' . ( $date ? ' — ' . esc_html( $date ) : '' ) . '</p>';

		$lines = array_filter( array_map( 'trim', explode( "\n", (string) ( $release['body'] ?? '' ) ) ) );
		if ( $lines ) {
			$output .= '<ul>';
			foreach ( $lines as $line ) {
				$output .= '<li>' . esc_html( (string) preg_replace( '/^[-*]\s+/', '', $line ) ) . '</li>';
			}
			$output .= '</ul>';
		}

		return $output;
	}

	/**
	 * GitHub names the extracted folder "{repo}-{tag}": rename it to the plugin folder.
	 *
	 * @param string $source        Extracted folder.
	 * @param string $remote_source Temporary folder.
	 * @param object $upgrader      Upgrader.
	 * @param array  $hook_extra    Context.
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		$plugin = plugin_basename( VFWOO_WEBKUL_FILE );
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $plugin ) {
			return $source;
		}

		$folder = dirname( $plugin );
		if ( basename( untrailingslashit( $source ) ) === $folder ) {
			return $source;
		}

		$corrected = trailingslashit( dirname( untrailingslashit( $source ) ) ) . $folder;
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return @rename( $source, $corrected ) ? trailingslashit( $corrected ) : $source;
	}

	/**
	 * @param object $upgrader Upgrader.
	 * @param array  $options  Context.
	 */
	public function clear_cache( $upgrader, $options ): void {
		if ( isset( $options['type'] ) && 'plugin' === $options['type'] ) {
			delete_transient( self::CACHE_KEY );
		}
	}
}
