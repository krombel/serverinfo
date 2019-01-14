<?php
/**
 * @copyright Copyright (c) 2018 Matthias Kesler <krombel@krombel.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\ServerInfo;

use OCP\AppFramework\Http\Response;

class PrometheusResponse extends Response {
	public function __construct($data) {
		$this->data = $data;
		$this->addHeader('Content-Type', "text/plain; charset=UTF-8");
	}

	public function render() {
		$output = $this->transformArrayToPromOutput("ocs", $this->data);
		return implode("\r\n", $output);
	}

	/**
	 * Recursive function which flattens an array to a list of _ separated entries
	 *
	 * @return array
	 */
	private function transformArrayToPromOutput($prefix, $arr) {
		$result = [];
		foreach($arr as $key => $value) {
			if (is_array($value)) {
				$arr2 = $this->transformArrayToPromOutput($prefix."_".$key, $value);
				foreach ($arr2 as $key2 => $value2) {
					array_push($result, $value2);
				}
			} else {
				if (strlen($value) == 0) {
					// omit empty values
					continue;
				} else if (strcasecmp($value, "yes") == 0) {
					$value = true;
				} else if (strcasecmp($value, "no") == 0 || strcmp($value, "none") == 0) {
					$value = false;
				}
				if (is_bool($value)) {
					continue;
					$value = (boolval($value)? '1' : '0');
				}
				$pKey = $prefix . "_" . $key;
				if (!is_numeric($value)) {
					// omit non-numeric values as prometheus cannot handle them
					continue;
				}
				if (is_integer($value)) {
					// somehow prometheus expects floats
					array_push($result, $pKey . " " . $value . ".0");
				} else {
					array_push($result, $pKey . " " . $value);
				}
			}
		}
		return $result;
	}
}