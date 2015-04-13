<?php
/**
 * SpriteSheet
 * SpriteSheet Log Formatter
 *
 * @author		Alexia E. Smith
 * @license		LGPL v3.0
 * @package		SpriteSheet
 * @link		https://github.com/CurseStaff/SpriteSheet
 *
 **/

class SpriteSheetLogFormatter extends LogFormatter {
	/**
	 * Handle custom log parameters for SpriteSheet class.
	 *
	 * @access	public
	 * @return	array	Extract and parsed parameters.
	 */
	protected function getMessageParameters() {
		$parameters = parent::getMessageParameters();

		$title = $this->entry->getTarget();
		$spriteSheet = SpriteSheet::newFromTitle($title, true);

		if ($spriteSheet !== false) {
			$lastRevision = $spriteSheet->getPreviousRevision();
			//Handle old revision ID.
			if ($parameters[3] > 0) {
				$links = $spriteSheet->getRevisionLinks($parameters[3]);
				$parameters[3] = ['raw' => implode(" | ", $links)];
			}
		}

		return $parameters;
	}
}
