<?php
namespace GDO\Core;
use GDO\Util\Common;
use GDO\User\GDO_Session;
/**
 * The application can control main behaviour settings.
 * 
 * @author gizmore
 * @since 6.00
 * @version 6.10
 */
class Application
{
	const HTML = 'html';
	const JSON = 'json';
	
	public static $TIME;
	public static $MICROTIME;
	
	public static function updateTime()
	{
	    self::$MICROTIME = microtime(true);
	    self::$TIME = (int) self::$MICROTIME;
	}
	
	/**
	 * @return self
	 */
	public static function instance() { return self::$instance; }
	private static $instance;
	
	################
	### Instance ###
	################
	public $loader;
	public function __construct()
	{
		self::$instance = $this;
        ini_set('date.timezone', 'UTC');
		date_default_timezone_set('UTC');
		$this->loader = new ModuleLoader(GDO_PATH . 'GDO/');
	}
	
	public function __destruct()
	{
		Logger::flush();
		GDO_Session::commit();
	}
	
	public function isWindows() { return defined('PHP_WINDOWS_VERSION_MAJOR'); }
	
	/**
	 * @return \GDO\Core\Method
	 */
	public function getMethod() { return method(Common::getGetString('mo', GWF_MODULE), Common::getGetString('me', GWF_METHOD)); }
	
	################
	### Override ###
	################
	public function isCLI() { return PHP_SAPI === 'cli'; }
	public function isInstall() { return false; }
	public function isWebsocket() { return false; }
	public function allowJavascript() { return !isset($_REQUEST['disableJS']); }
	
	##############
	### Format ###
	##############
	public function isAjax() { return isset($_GET['ajax']); }
	public function isHTML() { return $this->getFormat() === self::HTML; }
	public function isJSON() { return $this->getFormat() === self::JSON; }
	public function getFormat() { return Common::getRequestString('fmt', self::HTML); }

	##############
	### Themes ###
	##############
	private $themes = GWF_THEMES;
	public function getThemes() { return is_array($this->themes) ? $this->themes : explode(',', $this->themes); }
	public function setThemes(array $themes) { $this->themes = $themes; return $this; }
}

Application::updateTime();
