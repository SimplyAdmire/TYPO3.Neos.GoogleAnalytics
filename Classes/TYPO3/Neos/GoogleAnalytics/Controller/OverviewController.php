<?php
namespace TYPO3\Neos\GoogleAnalytics\Controller;

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
use TYPO3\Neos\GoogleAnalytics\Domain\Model\SiteConfiguration;

class OverviewController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\GoogleAnalytics\Domain\Repository\SiteConfigurationRepository
	 */
	protected $siteConfigurationRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\GoogleAnalytics\Service\TokenStorage
	 */
	protected $tokenStorage;

	/**
	 * @param string $accountId
	 * @param string $webpropertyId
	 * @param string $profileId
	 * @return void
	 */
	public function indexAction($accountId = '', $webpropertyId = '', $profileId = '') {
		$site = $this->siteRepository->findFirst();
		$this->view->assign('site', $site);

		$analytics = $this->getAnalytics();
		$client = $analytics->getClient();

		if (!$client->getAccessToken()) {
			$this->redirect('authenticate');
		}

		$accounts = $this->getAccounts($analytics);
		$this->view->assign('accounts', $accounts);

		if ($accountId !== '') {
			$webproperties = $this->getWebproperties($analytics, $accountId);
			$this->view->assign('webproperties', $webproperties);
			$this->view->assign('accountId', $accountId);
		}

		if ($accountId !== '' && $webpropertyId !== '') {
			$profiles = $this->getProfiles($analytics, $accountId, $webpropertyId);
			$this->view->assign('profiles', $profiles);
			$this->view->assign('webpropertyId', $webpropertyId);
		}

		if ($accountId !== '' && $webpropertyId !== '' && $profileId !== '') {
			$this->view->assign('profileId', $profileId);
		}
	}

	/**
	 * @param Site $site
	 * @param string $profileId
	 * @return void
	 */
	public function updateAction(Site $site, $profileId) {
		$siteConfiguration = $this->siteConfigurationRepository->findOneBySite($site);
		if ($siteConfiguration instanceof SiteConfiguration) {
			$siteConfiguration->setProfileId($profileId);
			$this->siteConfigurationRepository->update($siteConfiguration);
		} else {
			$siteConfiguration = new SiteConfiguration();
			$siteConfiguration->setSite($site);
			$siteConfiguration->setProfileId($profileId);
			$this->siteConfigurationRepository->add($siteConfiguration);
		}

		$this->redirect('index');
	}

	/**
	 * @return void
	 */
	public function authenticateAction() {
		$analytics = $this->getAnalytics();
		$client = $analytics->getClient();

		// We have to get the "code" query argument without a module prefix
		$code = $this->request->getHttpRequest()->getArgument('code');
		if (!empty($code)) {
			$client->authenticate($code);
			$this->tokenStorage->storeAccessToken('global', $client->getAccessToken());
			$this->tokenStorage->storeRefreshToken('global', $client->getRefreshToken());

			$indexUri = $this->uriBuilder->reset()
				->setCreateAbsoluteUri(TRUE)
				->uriFor('index');
			$this->redirectToUri($indexUri);
		}

		$authUrl = $client->createAuthUrl();
		$this->view->assign('authUrl', $authUrl);
	}

	/**
	 * @param \Google_Service_Analytics $analytics
	 * @return array
	 */
	protected function getAccounts($analytics) {
		// TODO Handle "(403) User does not have any Google Analytics account."
		$accounts = $analytics->management_accounts->listManagementAccounts();

		return $accounts->getItems();
	}

	/**
	 * @param string $accountId
	 * @param \Google_Service_Analytics $analytics
	 * @return array
	 */
	protected function getWebproperties($analytics, $accountId) {
		$webwebproperties = $analytics->management_webproperties->listManagementWebproperties($accountId);
		return $webwebproperties->getItems();
	}

	/**
	 * @param string $accountId
	 * @param string $webpropertyId
	 * @param \Google_Service_Analytics $analytics
	 * @return array
	 */
	protected function getProfiles($analytics, $accountId, $webpropertyId) {
			$profiles = $analytics->management_profiles->listManagementProfiles($accountId, $webpropertyId);
			return $profiles;
	}

	/**
	 * @return \Google_Service_Analytics
	 */
	protected function getAnalytics() {
		$client = new \Google_Client();
		// TODO Move that to settings, show information in module if not configured
		$client->setApplicationName('TYPO3 Neos');
		$client->setClientId('488706292022-pdmhjba7tc9lnie3uh5t9ji9uakbp55a.apps.googleusercontent.com');
		$client->setClientSecret('ERJQNB5zdfSV0YIzePh2Y_SZ');

		$redirectUri = $this->uriBuilder->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor('authenticate');
		$client->setRedirectUri($this->removeUriQueryArguments($redirectUri));

		$client->setDeveloperKey('AIzaSyA79k9lgMYYb1cpgdLLY0KvnE5OK0x593g');
		$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
		$client->setAccessType('offline');

		$accessToken = $this->tokenStorage->getAccessToken('global');
		if ($accessToken !== NULL) {
			$client->setAccessToken($accessToken);

			if ($client->isAccessTokenExpired()) {
				$refreshToken = $this->tokenStorage->getRefreshToken('global');
				$client->refreshToken($refreshToken);
			}
		}

		return new \Google_Service_Analytics($client);
	}

	/**
	 * @param $redirectUri
	 * @return string
	 */
	protected function removeUriQueryArguments($redirectUri) {
		$uri = new \TYPO3\Flow\Http\Uri($redirectUri);
		$uri->setQuery(NULL);
		$redirectUri = (string)$uri;
		return $redirectUri;
	}
}