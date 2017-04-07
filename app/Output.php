<?php

namespace GeeksAreForLife\TrelloMate;

use cli\Colors;

class Output
{
    private $debug = false;

    private $outputColors = [
        'eol'       => '%n',
        'info'      => '',
        'warning'   => '%Y',
        'error'     => '%R',
        'debug'     => '',
    ];

    const MSG_INFO = 0;
    const MSG_WARN = 1;
    const MSG_ERR = 2;
    const MSG_DEBUG = 3;

    public function __construct($debug = false)
    {
        $this->debug = $debug;

        $this->outputColors['debug'] = Colors::color(['color' => 'white', 'style' => 'bright', 'background' => 'blue']);
    }

    public function debug($msg)
    {
        if ($this->debug) {
            $this->msg($msg, self::MSG_DEBUG);
        }
    }

    public function msg($msg, $type = self::MSG_INFO)
    {
        $output = '';

        if ($type == self::MSG_INFO) {
            $output .= $this->outputColors['info'];
        } elseif ($type == self::MSG_WARN) {
            $output .= $this->outputColors['warning'];
        } elseif ($type == self::MSG_ERR) {
            $output .= $this->outputColors['error'];
        } elseif ($type == self::MSG_DEBUG) {
            $output .= $this->outputColors['debug'];
        }

        $output .= $msg;

        $output .= $this->outputColors['eol'];

        \cli\line($output);
    }

    public function question($question, $default = false)
    {
        return \cli\prompt($question, $default);
    }

    public function yesno($question, $default = 'y')
    {
        $choice = \cli\choose($question, 'yn', $default);
        if ($choice == 'y') {
            return true;
        } else {
            return false;
        }
    }

    public function selectFromList($list, $msg)
    {
        $choice = \cli\menu($list, null, $msg);

        return $choice;
    }

    public function progress($msg, $total)
    {
        return $this->progressBar = new \cli\progress\Bar($msg, $total);
    }
}
