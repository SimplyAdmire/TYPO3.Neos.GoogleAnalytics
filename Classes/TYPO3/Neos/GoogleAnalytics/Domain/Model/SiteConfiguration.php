<?php
namespace TYPO3\Neos\GoogleAnalytics\Domain\Model;

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
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class SiteConfiguration {

	/**
	 * @ORM\ManyToOne
	 * @var \TYPO3\Neos\Domain\Model\Site
	 */
	protected $site;

	/**
	 * @var string
	 */
	protected $refreshToken = '';

	/**
	 * @var string
	 */
	protected $profileId;

	/**
	 * @return string
	 */
	public function getProfileId() {
		return $this->profileId;
	}

	/**
	 * @param string $profileId
	 */
	public function setProfileId($profileId) {
		$this->profileId = $profileId;
	}

	/**
	 * @return string
	 */
	public function getRefreshToken() {
		return $this->refreshToken;
	}

	/**
	 * @param string $refreshToken
	 */
	public function setRefreshToken($refreshToken) {
		$this->refreshToken = $refreshToken;
	}

	/**
	 * @return \TYPO3\Neos\Domain\Model\Site
	 */
	public function getSite() {
		return $this->site;
	}

	/**
	 * @param \TYPO3\Neos\Domain\Model\Site $site
	 */
	public function setSite($site) {
		$this->site = $site;
	}

}