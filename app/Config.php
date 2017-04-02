<?php

namespace GeeksAreForLife\TrelloMate;

use KHerGe\JSON\JSON;

class Config
{
    private $config;

    public function __construct()
    {
    }

    public function load($configFile, $defaultFile)
    {
        $config = $this->readConfig($configFile);
        $default = $this->readConfig($defaultFile);

        if ($config !== false and $default !== false) {
            $this->config = array_merge($default, $config);

            return true;
        } elseif ($config !== false) {
            $this->config = $config;

            return true;
        } else {
            return false;
        }
    }

    private function readConfig($file)
    {
        if (file_exists($file)) {
            $configJson = file_get_contents($file);

            $json = new JSON();

            try {
                $json->lint($configJson);

                return $json->decode($configJson, true);
            } catch (Exception $e) {
                return false;
            }
        } else {
            // create a blank file
            touch($file);

            return [];
        }
    }
}
