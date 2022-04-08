<?php

use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;

class SpriteSheetRemote extends SpriteSheet {
	/**
	 * Last API Error Message
	 *
	 * @var		string
	 */
	private $lastApiErrorMessage = false;

	/**
	 * Image storage from the remote repository.
	 *
	 * @var		object
	 */
	private $image = null;

	/**
	 * Create a new instance of this class from a Title object.
	 *
	 * @access	public
	 * @param	object	Title
	 * @param	boolean	[Optional] Stash the object to quick retrieval.
	 * @return	mixed	SpriteSheet or false on error.
	 */
	static public function newFromTitle( LinkTarget $title, $useMemoryCache = false ) {
		if ($title->getNamespace() != NS_FILE || !$title->getDBkey()) {
			return false;
		}

		if ($useMemoryCache && isset(self::$spriteSheets[$title->getDBkey()])) {
			return self::$spriteSheets[$title->getDBkey()];
		}

		$spriteSheet = new self();
		$spriteSheet->setTitle($title);

		$spriteSheet->newFrom = 'remote';

		$success = $spriteSheet->load();

		if ($success) {
			self::$spriteSheets[$title->getDBkey()] = $spriteSheet;

			return $spriteSheet;
		}
		return false;
	}

	/**
	 * Load from the remote API.
	 *
	 * @access	public
	 * @param	array	[Unused]
	 * @return	boolean	Success
	 */
	public function load($row = null) {
		if (!$this->isLoaded) {
			$this->image = MediaWikiServices::getInstance()
				->getRepoGroup()
				->findFile( $this->getTitle() );

			if ($this->image !== false && $this->image->exists() && !$this->image->isLocal() && $this->image->getRepo() instanceof ForeignAPIRepo) {
				$query = [
					'action'	=> 'spritesheet',
					'do'		=> 'getSpriteSheet',
					'title'		=> $this->getTitle()->getDBkey(), //DO NOT MOVE THIS TO THE BOTTOM.  NEVER.  Mediawiki has a dumb as fuck bug called "class IEUrlExtension" which will block all requests if the file name is at the end of the parameter list.
					'format'	=> 'json'
				];

				//Make sure to change this cache piece back to 300 seconds once this extension is out of development.
				$data = $this->image->getRepo()->httpGetCached('SpriteSheet', $query, 0);

				if ($data) {
					$spriteData = FormatJson::decode($data, true);
					if ($spriteData['success'] === true && is_array($spriteData['data']) && $spriteData['data']['title'] == $this->getTitle()->getDBkey()) {
						$this->setColumns($spriteData['data']['columns']);
						$this->setRows($spriteData['data']['rows']);
						$this->setInset($spriteData['data']['inset']);
						$this->setTitle($this->getTitle());

						$this->isLoaded = true;

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Return the last error message from the remote API if produced.
	 *
	 * @access	public
	 * @return	mixed	String error message or false if none has been set.
	 */
	public function getLastApiErrorMessage() {
		return $this->lastApiErrorMessage;
	}

	/**
	 * Dummy function to prevent attempts to save the remote SpriteSheet locally.
	 *
	 * @access	public
	 * @return	boolean	Success
	 */
	public function save() {
		return true;
	}

	/**
	 * Return if this is a local SpriteSheet.
	 *
	 * @access	public
	 * @return	boolean	False
	 */
	public function isLocal() {
		return false;
	}

	/**
	 * Return all named sprites/slices for thie sprite sheet.
	 *
	 * @access	public
	 * @return	array	Named Sprite Cache
	 */
	public function getAllSpriteNames() {
		if ($this->image !== false && $this->image->exists() && !$this->image->isLocal()) {
			$query = [
				'action'	=> 'spritesheet',
				'do'		=> 'getAllSpriteNames',
				'title'		=> $this->getTitle()->getDBkey(), //DO NOT MOVE THIS TO THE BOTTOM.  NEVER.  Mediawiki has a dumb as fuck bug called "class IEUrlExtension" which will block all requests if the file name is at the end of the parameter list.
				'format'	=> 'json'
			];

			//Make sure to change this cache piece back to 300 seconds once this extension is out of development.
			$this->image->getRepo()->httpGetCached('SpriteSheet', $query, 0);
			return;
		}

		while ($row = $result->fetchRow()) {
			$spriteName = SpriteName::newFromRow($row, $this);
			if ($spriteName->exists()) {
				$this->spriteNameCache[$spriteName->getName()] = $spriteName;
			}
		}
		return $this->spriteNameCache;
	}
}