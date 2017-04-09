<?php

namespace GeeksAreForLife\TrelloMate;

use KHerGe\JSON\JSON;

class Config
{
    private $config;
    private $configFile;
    private $default;

    public function __construct()
    {
    }

    public function load($configFile, $defaultFile)
    {
        $config = $this->readConfig($configFile);
        $default = $this->readConfig($defaultFile);

        $this->configFile = $configFile;

        if ($default !== false) {
            $this->default = $default;
        }

        if ($config !== false) {
            $this->config = $config;

            return true;
        } else {
            return false;
        }
    }

    public function save()
    {
        $this->saveFile($this->config, $this->configFile);
    }

    public function getValue($key, $module = false)
    {
        $keys = $this->getKeys($key, $module);

        $config = $this->config;
        $default = $this->default;

        foreach ($keys as $key) {
            if (isset($config[$key])) {
                $config = $config[$key];
            } else {
                $config = '';
            }

            if (isset($default[$key])) {
                $default = $default[$key];
            } else {
                $default = '';
            }
        }

        if ($config !== '') {
            return $config;
        } elseif ($default !== '') {
            return $default;
        } else {
            return false;
        }
    }

    public function setValue($key, $value, $module = false)
    {
        $keys = $this->getKeys($key, $module);

        $config = &$this->config;
        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                $config[$key] = [];
            }
            $config = &$config[$key];
        }
        //var_dump($config);
        if (empty($config) || !is_array($config)) {
            $config = $value;
        } else {
            $config[] = $value;
        }
    }

    private function getKeys($key, $module) {
        if ($module) {
            $key = 'modules.' . strtolower($module) . '.' . $key;
        }

        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
        } else {
            $keys = [$key];
        }

        return $keys;
    }

    private function readConfig($file)
    {
        if (file_exists($file)) {
            $configJson = file_get_contents($file);

            $json = new JSON();

            try {
                $json->lint($configJson);

                return $json->decode($configJson, true);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            // create a blank file
            $this->saveFile([], $file);

            return [];
        }
    }

    private function saveFile($content, $file)
    {
        $json = new JSON();

        $json->encodeFile($content, $file, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
    }
}
