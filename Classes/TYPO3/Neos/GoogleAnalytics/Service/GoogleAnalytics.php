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
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\GoogleAnalytics\Exception\AuthenticationRequiredException;
use TYPO3\Neos\GoogleAnalytics\Exception\MissingConfigurationException;

/**
 * @Flow\Scope("singleton")
 */
class GoogleAnalytics {

	/**
	 * @Flow\Inject
	 * @var TokenStorage
	 */
	protected $tokenStorage;

	/**
	 * @Flow\Inject(setting="authentication", package="TYPO3.Neos.GoogleAnalytics")
	 * @var array
	 */
	protected $settings;

	/**
	 * @param Site $site The current site, optional
	 * @param boolean $requireAuthentication
	 * @throws AuthenticationRequiredException
	 * @throws MissingConfigurationException
	 * @return \Google_Service_Analytics
	 */
	public function getAnalytics(Site $site = NULL, $requireAuthentication = TRUE) {
		$client = new \Google_Client();

		$requiredAuthenticationSettings = array(
			'applicationName',
			'clientId',
			'clientSecret',
			'developerKey'
		);
		foreach ($requiredAuthenticationSettings as $key) {
			if (!isset($this->settings[$key])) {
				throw new MissingConfigurationException(sprintf('Missing setting "TYPO3.Neos.GoogleAnalytics.authentication.%s"', $key), 1415796352);
			}
		}

		$client->setApplicationName($this->settings['applicationName']);
		$client->setClientId($this->settings['clientId']);
		$client->setClientSecret($this->settings['clientSecret']);

		$client->setDeveloperKey($this->settings['developerKey']);
		$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
		$client->setAccessType('offline');

		$accessToken = $this->tokenStorage->getAccessToken('global');
		if ($accessToken !== NULL) {
			$client->setAccessToken($accessToken);

			if ($client->isAccessTokenExpired()) {
				$refreshToken = $this->tokenStorage->getRefreshToken('global');
				$client->refreshToken($refreshToken);
			}
		} elseif ($requireAuthentication) {
			throw new AuthenticationRequiredException('No access token', 1415783205, NULL, $site);
		}

		return new \Google_Service_Analytics($client);
	}

}