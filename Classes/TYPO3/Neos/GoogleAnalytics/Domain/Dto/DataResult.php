<?php
namespace TYPO3\Neos\GoogleAnalytics\Domain\Dto;

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

class DataResult implements \JsonSerializable {

	/**
	 * @var \Google_Service_Analytics_GaData
	 */
	protected $result;

	public function __construct($result) {
		$this->result = $result;
	}

	/**
	 * {@inheritdoc}
	 */
	function jsonSerialize() {
		$totals = $this->result->getTotalsForAllResults();
		$sanitizedTotals = array();
		foreach ($totals as $key => $value) {
			$replacedKey = str_replace(':', '_', $key);
			$sanitizedTotals[$replacedKey] = $value;
		}
		$columnHeaders = $this->result->getColumnHeaders();
		foreach ($columnHeaders as &$columnHeader) {
			$columnHeader['name'] = str_replace(':', '_', $columnHeader['name']);
		}
		$rows = $this->result->getRows();
		$sanitizedRows = array();
		foreach ($rows as $rowIndex => $row) {
			foreach ($row as $columnIndex => $columnValue) {
				$columnName = $columnHeaders[$columnIndex]['name'];
				$sanitizedRows[$rowIndex][$columnName] = $columnValue;
			}
			if (count($columnHeaders) == 2 && $columnHeaders[0]['columnType'] === 'DIMENSION' && $columnHeaders[1]['columnType'] === 'METRIC') {
				$sanitizedRows[$rowIndex]['percent'] = $row[1] / $sanitizedTotals[$columnHeaders[1]['name']] * 100;
			}
		}
		return array(
			'totals' => $sanitizedTotals,
			'rows' => $sanitizedRows
		);
	}
}