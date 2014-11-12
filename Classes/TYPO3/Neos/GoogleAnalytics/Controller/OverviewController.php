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
use TYPO3\Neos\GoogleAnalytics\Exception\AuthenticationRequiredException;
use TYPO3\Neos\GoogleAnalytics\Exception\MissingConfigurationException;

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
	 * @Flow\Inject
	 * @var \TYPO3\Neos\GoogleAnalytics\Service\GoogleAnalytics
	 */
	protected $googleAnalytics;

	/**
	 * @param string $accountId
	 * @param string $webpropertyId
	 * @param string $profileId
	 * @return void
	 */
	public function indexAction($accountId = '', $webpropertyId = '', $profileId = '') {
		$site = $this->siteRepository->findFirst();
		$this->view->assign('site', $site);

		$analytics = $this->googleAnalytics->getAnalytics();

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
		$analytics = $this->googleAnalytics->getAnalytics(NULL, FALSE);
		$client = $analytics->getClient();

		$redirectUri = $this->uriBuilder->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor('authenticate');
		$client->setRedirectUri($this->removeUriQueryArguments($redirectUri));

		// We have to get the "code" query argument without a module prefix
		$code = $this->request->getHttpRequest()->getArgument('code');
		if (!empty($code)) {
			$client->authenticate($code);

			$this->tokenStorage->storeAccessToken('global', $client->getAccessToken());
			$this->tokenStorage->storeRefreshToken('global', $client->getRefreshToken());

			$indexUri = $this->uriBuilder->reset()
				->setCreateAbsoluteUri(TRUE)
				->uriFor('index');
			$this->redirectToUri($this->removeUriQueryArguments($indexUri));
		}

		// If we don't have a refresh token, require an approval prompt to receive a refresh token
		$refreshToken = $this->tokenStorage->getRefreshToken('global');
		if ($refreshToken === NULL) {
			$client->setApprovalPrompt('force');
		}

		$authUrl = $client->createAuthUrl();
		$this->view->assign('authUrl', $authUrl);
	}

	/**
	 * @return void
	 */
	public function errorMessageAction() {
		// TODO Add some way to reauthenticate / delete access tokens
	}

	/**
	 * Catch Google service exceptions and forward to the "apiError" action to show
	 * an error message.
	 *
	 * @return void
	 */
	protected function callActionMethod() {
		try {
			parent::callActionMethod();
		} catch (\Google_Service_Exception $exception) {
			$this->addFlashMessage('%1$s', 'Google API error', \TYPO3\Flow\Error\Message::SEVERITY_ERROR, array('message' => $exception->getMessage(), 1415797974));
			$this->forward('errorMessage');
		} catch (MissingConfigurationException $exception) {
			$this->addFlashMessage('%1$s', 'Missing configuration', \TYPO3\Flow\Error\Message::SEVERITY_ERROR, array('message' => $exception->getMessage(), 1415797974));
			$this->forward('errorMessage');
		} catch (AuthenticationRequiredException $exception) {
			$this->redirect('authenticate');
		}
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