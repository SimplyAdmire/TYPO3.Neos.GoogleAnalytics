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
	 * @var \TYPO3\Neos\GoogleAnalytics\Service\GoogleAnalytics
	 */
	protected $googleAnalytics;

	protected $supportedMediaTypes = array('application/json');

	protected $viewFormatToObjectNameMap = array('json' => 'TYPO3\Flow\Mvc\View\JsonView');

	/**
	 * Get analytics stats for the given node
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function indexAction(NodeInterface $node) {
		$startDate = new \DateTime('3 months ago');
		$endDate = new \DateTime();
		$stats = $this->googleAnalytics->getStats($node, $this->controllerContext, $startDate, $endDate);
		$this->response->setHeader('Content-Type', 'application/json');
		$data = array(
			'stats' => $stats,
			'startDate' => $startDate->format(\DateTime::ISO8601),
			'endDate' => $endDate->format(\DateTime::ISO8601),
		);
		return json_encode($data, JSON_PRETTY_PRINT);
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