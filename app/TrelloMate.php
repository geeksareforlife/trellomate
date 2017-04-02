<?php

namespace GeeksAreForLife\TrelloMate;

class TrelloMate
{
    private $defaultConfig = __DIR__.'/defaultConfig.json';
    private $localConfig = __DIR__.'/config.json';

    private $config;

    private $moduleDir = __DIR__.'/modules';

    private $commands = [];

    public function __construct()
    {
        $this->loadConfig();
        $this->loadModules();
    }

    private function loadConfig()
    {
        $this->config = new Config();
        $this->config->load($this->localConfig, $this->defaultConfig);
    }

    private function loadModules()
    {
        foreach (new \DirectoryIterator($this->moduleDir) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            } else {
                $this->commands = array_merge($this->commands, $this->loadModule($fileInfo->getBasename('.php')));
            }
        }
    }

    private function loadModule($module)
    {
        require_once $this->moduleDir.'/'.$module.'.php';

        if (class_exists($module)) {
            return $module::getCommands();
        } else {
            return [];
        }
    }
}
