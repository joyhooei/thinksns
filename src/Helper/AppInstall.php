<?php

namespace Ts\Helper;

use Exception;
use Medz\Component\Filesystem\Filesystem;
use Ts;

/**
 * åºç¨å®è£
 * å¨.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class AppInstall
{
    /**
     * éè¦å®è£
     * çåºç¨åç§°.
     *
     * @var string
     **/
    protected static $appName;

    /**
     * å¨å­å½åå®ä¾åå¯¹è±¡
     *
     * @var self
     **/
    protected static $instances = array();

    /**
     * åºç¨ç®å½.
     *
     * @var string
     **/
    protected static $applicationDir;

    /**
     * åºç¨è¯¦æ
     * .
     *
     * @var array
     **/
    protected static $appInfo;

    /**
     * è·ååºç¨å®ä¾åçåä¾.
     *
     * @return self
     *
     * @author Seven Du <lovevipdswoutlook.com>
     **/
    public static function getInstance($appName)
    {
        self::$appName = strtolower($appName);
        if (
            !isset(self::$instances[self::$appName]) ||
            !(self::$instances[self::$appName] instanceof self)
        ) {
            self::$instances[self::$appName] = new self();
        }

        return self::$instances[self::$appName];
    }

    /**
     * æé æ¹æ³.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    protected function __construct()
    {
        if (!self::$appName) {
            throw new Exception('æ²¡æä¼ ééè¦åå§åçåºç¨ï¼', 1);
        }
        $handle = opendir(TS_APPLICATION);
        while (($file = readdir($handle)) !== false) {
            if (strtolower($file) == self::$appName) {
                self::$applicationDir = TS_APPLICATION.Ts::DS.$file;
                break;
            }
        }
        closedir($handle);
        $manageFile = self::$applicationDir.Ts::DS.'manage.json';
        if (!self::$applicationDir) {
            throw new Exception('åºç¨ï¼â'.self::$appName.'âä¸å­å¨ï¼', 1);
        } elseif (!Filesystem::exists($manageFile)) {
            throw new Exception(sprintf('ä¸å­å¨åºç¨éç½®æä»¶ï¼â%sâ', $manageFile));
        }
        self::$appInfo = file_get_contents($manageFile);
        self::$appInfo = json_decode(self::$appInfo, true);
        if (!isset(self::$appInfo['resource'])) {
            self::$appInfo['resource'] = '_static';
        }
    }

    /**
     * å¤å¶åºç¨çå
     * ¬å¼éæèµæº.
     *
     * @return self
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function moveResources()
    {
        Filesystem::mirror(
            sprintf('%s%s%s', self::$applicationDir, Ts::DS, self::$appInfo['resource']), // åå§ç®å½
            sprintf('%s%s/app/%s', TS_ROOT, TS_STORAGE, self::$appName)                   // ç®æ ç®å½
        );

        return $this;
    }

    /**
     * ç§»å¨Tsä¸­åºç¨ä¸­ææçéæèµæº.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public static function moveAllApplicationResources()
    {
        $handle = opendir(TS_APPLICATION);
        while (($file = readdir($handle)) !== false) {
            if (in_array($file, array('.', '..'))) {
                continue;
            }
            $manageFile = sprintf('%s/%s/manage.json', TS_APPLICATION, $file);
            if (
                !file_exists($manageFile) ||
                !($manageInfo = json_decode(file_get_contents($manageFile), true))
            ) {
                continue;
            } elseif (!isset($manageInfo['resource'])) {
                $manageInfo['resource'] = '_static';
            }
            Filesystem::mirror(
                sprintf('%s/%s/%s', TS_APPLICATION, $file, $manageInfo['resource']),
                sprintf('%s/%s/app/%s', TS_ROOT, TS_STORAGE, strtolower($file))
            );
        }
        closedir($handle);
    }
} // END class AppInstall
