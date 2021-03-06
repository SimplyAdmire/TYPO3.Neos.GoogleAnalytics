<?php
namespace TYPO3\Neos\GoogleAnalytics;

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

/**
 * Base exception for GoogleAnalytics package
 */
class Exception extends \TYPO3\Flow\Exception {

	/**
	 * @var \TYPO3\Neos\Domain\Model\Site
	 */
	protected $site;

	/**
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previous
	 * @param Site $site The site for the Google Analytics context
	 */
	public function __construct($message = '', $code = 0, \Exception $previous = NULL, Site $site = NULL) {
		parent::__construct($message, $code, $previous);
		$this->site = $site;
	}

	/**
	 * @return Site
	 */
	public function getSite() {
		return $this->site;
	}

}