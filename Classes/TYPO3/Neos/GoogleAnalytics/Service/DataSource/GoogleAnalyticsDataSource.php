<?php
namespace TYPO3\Neos\GoogleAnalytics\Service\DataSource;

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
use TYPO3\Neos\GoogleAnalytics\Exception\Exception;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class GoogleAnalyticsDataSource implements \TYPO3\Neos\Service\DataSource\DataSourceInterface {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\GoogleAnalytics\Service\GoogleAnalytics
	 */
	protected $googleAnalytics;

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * {@inheritdoc}
	 */
	static public function getIdentifier() {
		return 'GoogleAnalytics';
	}

	/**
	 * Get analytics stats for the given node
	 *
	 * {@inheritdoc}
	 */
	public function getData(NodeInterface $node = NULL, array $arguments) {
		if (!isset($arguments['stat'])) {
			throw new \InvalidArgumentException('Missing "stat" argument', 1416864525);
		}

		$startDate = new \DateTime('3 months ago');
		$endDate = new \DateTime();
		$stats = $this->googleAnalytics->getStat($node, $this->controllerContext, $arguments['stat'], $startDate, $endDate);
		$data = array(
			'data' => $stats
		);

		// TODO Convert response to expected data

		return $data;
	}

	/**
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 */
	public function setControllerContext($controllerContext) {
		$this->controllerContext = $controllerContext;
	}

}