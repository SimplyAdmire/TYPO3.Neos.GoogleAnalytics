<?php
namespace TYPO3\Neos\GoogleAnalytics\Service;

/*                                                                            *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos.GoogleAnalytics" *
 *                                                                            *
 * It is free software; you can redistribute it and/or modify it under        *
 * the terms of the GNU General Public License, either version 3 of the       *
 * License, or (at your option) any later version.                            *
 *                                                                            *
 * The TYPO3 project - inspiring people to share!                             *
 *                                                                            */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class TokenStorage {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $cache;

	/**
	 * @param string $identifier
	 * @param string $accessToken
	 * @return void
	 */
	public function storeAccessToken($identifier, $accessToken) {
		$this->cache->set('AccessToken-' . md5($identifier), $accessToken);
	}

	/**
	 * @param string $identifier
	 * @param string $refreshToken
	 * @return void
	 */
	public function storeRefreshToken($identifier, $refreshToken) {
		$this->cache->set('RefreshToken-' . md5($identifier), $refreshToken);
	}

	/**
	 * @param string $identifier
	 * @return string
	 */
	public function getAccessToken($identifier) {
		$accessToken = $this->cache->get('AccessToken-' . md5($identifier));
		if ($accessToken === FALSE) {
			return NULL;
		}
		return $accessToken;
	}

	/**
	 * @param string $identifier
	 * @return string
	 */
	public function getRefreshToken($identifier) {
		$accessToken = $this->cache->get('RefreshToken-' . md5($identifier));
		if ($accessToken === FALSE) {
			return NULL;
		}
		return $accessToken;
	}

}