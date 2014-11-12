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
use TYPO3\Neos\GoogleAnalytics\Exception\Exception;
use TYPO3\Neos\GoogleAnalytics\Exception\MissingConfigurationException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class DataController extends \TYPO3\Flow\Mvc\Controller\ActionController {

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
	 * @Flow\Inject
	 * @var \TYPO3\Neos\GoogleAnalytics\Service\GoogleAnalytics
	 */
	protected $googleAnalytics;

	protected $supportedMediaTypes = array('application/json');

	protected $viewFormatToObjectNameMap = array('json' => 'TYPO3\Flow\Mvc\View\JsonView');

	/**
	 * @param NodeInterface $node
	 * @return void
	 */
	public function indexAction(NodeInterface $node) {
		$context = $node->getContext();
		if (!$context instanceof \TYPO3\Neos\Domain\Service\ContentContext) {
			throw new \InvalidArgumentException('Expected ContentContext', 1415722633);
		}
		$site = $context->getCurrentSite();
		$siteConfiguration = $this->siteConfigurationRepository->findOneBySite($site);

		if ($siteConfiguration instanceof SiteConfiguration) {
			$startDate = (new \DateTime('3 months ago'))->format('Y-m-d');
			$endDate = (new \DateTime())->format('Y-m-d');

			$contextProperties = $node->getContext()->getProperties();
			$contextProperties['workspaceName'] = 'live';
			$liveContext = $this->contextFactory->create($contextProperties);
			$liveNode = $liveContext->getNodeByIdentifier($node->getIdentifier());

			$nodeUriString = $this->linkingService->createNodeUri($this->controllerContext, $liveNode, NULL, 'html', TRUE);
			$nodeUri = new \TYPO3\Flow\Http\Uri($nodeUriString);

			$filters = 'ga:pagePath==' . $nodeUri->getPath() . ';ga:hostname==' . $nodeUri->getHost();
			// var_dump($filters);
			// ob_flush();

			$analytics = $this->googleAnalytics->getAnalytics($site);
			$results = $analytics->data_ga->get(
				'ga:' . $siteConfiguration->getProfileId(),
				$startDate,
				$endDate,
				// TODO Add more metrics
				'ga:pageviews',
				array(
					'filters' => $filters
				)
			);
			$totals = $results->getTotalsForAllResults();
			$this->view->assign('value', $totals);
		} else {
			// TODO Return different results depending on missing configuration or other errors
			$this->view->assign('value', NULL);
		}
	}

	/**
	 * @return void
	 */
	protected function callActionMethod() {
		try {
			parent::callActionMethod();
		} catch(\TYPO3\Neos\GoogleAnalytics\Exception $exception) {
			$exceptionClassName = get_class($exception);
			$exceptionData = array(
				'error' => array(
					'code' => $exception->getCode(),
					'message' => $exception->getMessage(),
					'type' => substr($exceptionClassName, strrpos($exceptionClassName, '\\') + 1)
				)
			);
			// TODO Check if we actually need the site
			if ($exception->getSite() !== NULL) {
				$exceptionData['error']['siteNodeName'] = $exception->getSite()->getNodeName();
			}
			$this->response->setHeader('Content-Type', 'application/json');
			$this->response->setContent(json_encode($exceptionData));
		}
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