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
use TYPO3\Neos\GoogleAnalytics\Domain\Dto\DataResult;
use TYPO3\Neos\GoogleAnalytics\Domain\Model\SiteConfiguration;
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
	 * @Flow\Inject
	 * @var \TYPO3\Neos\GoogleAnalytics\Domain\Repository\SiteConfigurationRepository
	 */
	protected $siteConfigurationRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\LinkingService
	 */
	protected $linkingService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject(setting="authentication", package="TYPO3.Neos.GoogleAnalytics")
	 * @var array
	 */
	protected $authenticationSettings;

	/**
	 * @Flow\Inject(setting="stats", package="TYPO3.Neos.GoogleAnalytics")
	 * @var array
	 */
	protected $statsSettings;

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
			if (!isset($this->authenticationSettings[$key])) {
				throw new MissingConfigurationException(sprintf('Missing setting "TYPO3.Neos.GoogleAnalytics.authentication.%s"', $key), 1415796352);
			}
		}

		$client->setApplicationName($this->authenticationSettings['applicationName']);
		$client->setClientId($this->authenticationSettings['clientId']);
		$client->setClientSecret($this->authenticationSettings['clientSecret']);

		$client->setDeveloperKey($this->authenticationSettings['developerKey']);
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

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @param \DateTime $startDate
	 * @param \DateTime $endDate
	 * @throws AuthenticationRequiredException
	 * @throws MissingConfigurationException
	 * @throws \TYPO3\Neos\Exception
	 * @return array
	 */
	public function getStats(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext, \DateTime $startDate, \DateTime $endDate) {
		$context = $node->getContext();
		if (!$context instanceof \TYPO3\Neos\Domain\Service\ContentContext) {
			throw new \InvalidArgumentException('Expected ContentContext', 1415722633);
		}
		$site = $context->getCurrentSite();
		$siteConfiguration = $this->siteConfigurationRepository->findOneBySite($site);

		if ($siteConfiguration instanceof SiteConfiguration) {
			$analytics = $this->getAnalytics($site);

			$startDateFormatted = $startDate->format('Y-m-d');
			$endDateFormatted = $endDate->format('Y-m-d');

			$contextProperties = $node->getContext()->getProperties();
			$contextProperties['workspaceName'] = 'live';
			$liveContext = $this->contextFactory->create($contextProperties);
			$liveNode = $liveContext->getNodeByIdentifier($node->getIdentifier());


			$nodeUriString = $this->linkingService->createNodeUri($controllerContext, $liveNode, NULL, 'html', TRUE);
			$nodeUri = new \TYPO3\Flow\Http\Uri($nodeUriString);

			// $hostname = $nodeUri->getHost();
			$hostname = 'learn-neos.com';
			// $path = $nodeUri->getPath();
			$path = '/blog.html';

			$filters = 'ga:pagePath==' . $path . ';ga:hostname==' . $hostname;

			$results = array();

			foreach ($this->statsSettings as $statName => $statConfiguration) {
				$results[$statName] = new DataResult($analytics->data_ga->get(
					'ga:' . $siteConfiguration->getProfileId(),
					$startDateFormatted,
					$endDateFormatted,
					$statConfiguration['metrics'],
					array(
						'filters' => $filters,
						'dimensions' => isset($statConfiguration['dimensions']) ? $statConfiguration['dimensions'] : ''
					)
				));
			}

			return $results;
		} else {
			throw new MissingConfigurationException('No profile configured for site', 1415806282);
		}
	}

}