<?php

/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

namespace OCA\Drawio;

use OCP\IConfig;
use OCP\ILogger;


class AppConfig {

    private $predefDrawioUrl = "https://embed.diagrams.net";
    private $predefOverrideXML = "yes";
    private $predefOfflineMode = "no";
    private $predefTheme = "kennedy"; //kennedy, minimal, atlas, dark
    private $predefLang = "auto";
    private $predefAutosave = "yes";

    private $appName;

    private $config;

    private $logger;

    // The config keys
    private $_drawioUrl = "DrawioUrl";
    private $_overridexml = "DrawioXml";
    private $_offlinemode = "DrawioOffline";
    private $_theme = "DrawioTheme";
    private $_lang = "DrawioLang";
    private $_autosave = "DrawioAutosave";

    public function __construct($AppName)
    {
        $this->appName = $AppName;

        $this->config = \OC::$server->getConfig();
        $this->logger = \OC::$server->getLogger();
    }

    public function SetDrawioUrl($drawio)
    {
        $drawio = strtolower(rtrim(trim($drawio), "/"));
        if (strlen($drawio) > 0 && !preg_match("/^https?:\/\//i", $drawio)) $drawio = "http://" . $drawio;
        $this->logger->info("SetDrawioUrl: " . $drawio, array("app" => $this->appName));
        $this->config->setAppValue($this->appName, $this->_drawioUrl, $drawio);
    }

    public function GetDrawioUrl()
    {
        $val = $this->config->getAppValue($this->appName, $this->_drawioUrl);
        if (empty($val)) $val = $this->predefDrawioUrl;
        //default URL changed from draw.io to embed.diagrams.net #118
        if (in_array(strtolower($val), array("https://draw.io", "https://www.draw.io", "http://draw.io", "http://www.draw.io") )) $val = $this->predefDrawioUrl;
        return $val;
    }

    public function SetOverrideXML($overridexml)
    {
        $overridexml = (string)$overridexml;
        $this->logger->info("SetOverrideXML: " . $overridexml, array("app" => $this->appName));
        $this->config->setAppValue($this->appName, $this->_overridexml, $overridexml);
    }

    public function GetOverrideXML()
    {
        $val = $this->config->getAppValue($this->appName, $this->_overridexml);
        if (empty($val)) $val = $this->predefOverrideXML;
        return $val;
    }

    public function SetOfflineMode($offlinemode)
    {
        $offlinemode = (string)$offlinemode;
        $this->logger->info("SetOfflineMode: " . $offlinemode, array("app" => $this->appName));
        $this->config->setAppValue($this->appName, $this->_offlinemode, $offlinemode);
    }

    public function GetOfflineMode()
    {
        $val = $this->config->getAppValue($this->appName, $this->_offlinemode);
        if (empty($val)) $val = $this->predefOfflineMode;
        return $val;
    }

    public function SetTheme($theme)
    {
        $this->logger->info("SetTheme: " . $theme, array("app" => $this->appName));
        $this->config->setAppValue($this->appName, $this->_theme, $theme);
    }

    public function GetTheme()
    {
        $val = $this->config->getAppValue($this->appName, $this->_theme);
        if (empty($val)) $val = $this->predefTheme;
        return $val;
    }

    public function SetLang($lang)
    {
        $this->logger->info("SetLang: " . $lang, array("app" => $this->appName));
        $this->config->setAppValue($this->appName, $this->_lang, $lang);
    }

    public function GetLang()
    {
        $val = $this->config->getAppValue($this->appName, $this->_lang);
        if (empty($val)) $val = $this->predefLang;
        return $val;
    }

    public function SetAutosave($autosave)
    {
        $this->logger->info("SetAutosave: " . $autosave, array("app" => $this->appName));
        $this->config->setAppValue($this->appName, $this->_autosave, $autosave);
    }

    public function GetAutosave()
    {
        $val = $this->config->getAppValue($this->appName, $this->_autosave);
        if (empty($val)) $val = $this->predefAutosave;
        return $val;
    }

    public function GetAppName()
    {
        return $this->appName;
    }

     /**
     * Additional data about formats
     *
     * @var array
     */
    public $formats = [
            "xml" => [ "mime" => "application/xml", "type" => "text" ],
            "drawio" => [ "mime" => "application/x-drawio", "type" => "text" ]
        ];

}
