<?php

class Tool {

	const M_ERROR = 'error';

	const M_INFO = 'info';

	const M_SUCCESS = 'success';

	const M_WARNING = 'warning';

	public static function preload() {

		session_start();

		if(!isset($_SESSION['messages']))
			$_SESSION['messages'] = array();

	}

	public static function appendMessage($message, $type) {

		if(!isset($_SESSION['messages'][$type]))
			$_SESSION['messages'][$type] = array();
		$_SESSION['messages'][$type][] = $message;

	}

	public static function readMessages() {

		if(!empty($_SESSION['messages'])) {
			foreach($_SESSION['messages'] as $type => &$messages) {
				foreach($messages as $index => $message) {
					echo '<p class="' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</p>';
					unset($messages[$index]);
				}
			}
		}

	}

	public static function generatePassword($length) {

		$password = '';
		$vowels = 'aeuyAEUY';
		$consonants = 'bdghjmnpqrstvzBDGHJLMNPQRSTVWXZ23456789';

		for($i = 0; $i < $length; $i++) {

			if($i % 2)
				$password .= $consonants[(rand() % strlen($consonants))];
			else
				$password .= $vowels[(rand() % strlen($vowels))];

		}

		return $password;

	}

	public static function formatUser($userObject) {

		if (!empty($userObject)) {

			$userStd = new stdClass();

			$userStd->id = intval($userObject->_id);
			$userStd->isadmin = ($userObject->_admin == 1);
			$userStd->name = $userObject->_name;
			$userStd->email = $userObject->_email;
			$userStd->avatar = ($userObject->_avatar == 1) ? HTTP_ROOT . AVATAR_DIR . $userStd->id . '.png' : HTTP_ROOT . DEFAULT_AVATAR;
			$userStd->islocked = ($userObject->_locked == 1);
			$userStd->theme = $userObject->_theme;
			$userStd->language = $userObject->_language;
			$userStd->favorite_lang = $userObject->_favoriteLang;

			return $userStd;
		} else {
			return false;
		}
	}

	public static function formatSnippet($snippetObject) {

		if (!empty($snippetObject)) {

			$snippetStd = new stdClass();

			$snippetStd->id = intval($snippetObject->_id);
			$snippetStd->name = $snippetObject->_name;
			$snippetStd->idUser = intval($snippetObject->_idUser);
			$snippetStd->lastUpdate = $snippetObject->_lastUpdate;
			$snippetStd->content = $snippetObject->_content;
			$snippetStd->language = intval($snippetObject->_language);		## FIX IT : with Geshi codes.
			$snippetStd->comment = $snippetObject->_comment;
			$snippetStd->category = $snippetObject->_category;
			$snippetStd->tags = array();

			if (mb_strlen($snippetObject->_tags) != 0) {
				$tagsArray = explode(',', preg_replace('# *, *#', ',', strtolower($snippetObject->_tags)));
				foreach($tagsArray AS $tag) {
					$snippetStd->tags[] = $tag;
				}
			}
			
			$snippetStd->privacy = ($snippetObject->_private) ? true : false;

			return $snippetStd;
		} else {
			return false;
		}
	}

	public static function emailExistInDB($email) {

		try {

			$db = PDOSQLite::getDBLink();
			$request = $db->prepare('SELECT * FROM `users` WHERE `email` = :email');
			$request->bindValue(':email', $email, PDO::PARAM_STR);

			return $request->execute();
			
		} catch (Exception$e) {
			return false;
		}
	}

	public static function loadLanguage() {

		global $Theme;
		$langCode = !empty($_SESSION['user']->_language) ? $_SESSION['user']->_language : DEFAULT_LANG;

		if(!empty($_SESSION['lang']->langcode)) {
			if(!isset($_SESSION['user'])
				OR (isset($_SESSION['user']->_language) AND $_SESSION['user']->_language == $_SESSION['lang']->langcode)) {
				return $_SESSION['lang'];
			}
		}

		$_SESSION['lang'] = new stdClass();
		$themeLangFile = THEME_PATH . $Theme->dirname . '/' . LANGUAGE_DIR .  $langCode . '.json';
		$globalLangFile = LANGUAGE_PATH . $langCode . '.json';

		if(file_exists($globalLangFile)) {
			$globalLang = json_decode(file_get_contents($globalLangFile));
			if(file_exists($themeLangFile)) {
				$themeLang = json_decode(file_get_contents($themeLangFile));
				$Composer = new Compositor($globalLang, $themeLang);
				$_SESSION['lang'] = $Composer;
			} else {
				$_SESSION['lang'] = $globalLang;
			}
			$_SESSION['lang']->langcode = $langCode;
		}

		return $_SESSION['lang'];

	}

	public static function loadTheme() {

		$dirname = !empty($_SESSION['user']->_theme) ? $_SESSION['user']->_theme : DEFAULT_THEME;

		if(!empty($_SESSION['theme']->dirname)) {
			if(!isset($_SESSION['user'])
				OR (isset($_SESSION['theme']->dirname) AND $_SESSION['user']->theme == $_SESSION['theme']->dirname)) {
				return $_SESSION['theme'];
			}
		}

		$_SESSION['theme'] = new stdClass();
		$themeFile = THEME_PATH . $dirname . '/theme.json';

		if(file_exists($themeFile)) {
			$_SESSION['theme'] = json_decode(file_get_contents($themeFile));
			$_SESSION['theme']->dirname = $dirname;
			$_SESSION['theme']->location = HTTP_ROOT . THEME_DIR . $_SESSION['theme']->dirname . '/';
		}

		return $_SESSION['theme'];

	}

	public static function getAllThemes() {

		$themesPlace = THEME_PATH;
		$listOfThemes = array();

		foreach(glob($themesPlace . '*', GLOB_ONLYDIR | GLOB_MARK) as $themeDir) {
			foreach(glob($themeDir . '*.json') as $themeFileJSON) {
				$content = file_get_contents($themeFileJSON);
				$decodeContent = json_decode($content);
				$decodeContent->dirname = end(explode('/', substr($themeDir, 0, -1)));
				$listOfThemes[] = $decodeContent;
			}
		}

		return $listOfThemes;

	}

}