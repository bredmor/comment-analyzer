<?php
namespace bredmor\CommentAnalyzer;

class SpanScore {
    private $value;
    private $type;
    private $begin;
    private $end;

    public function __construct($value, $type, $begin, $end) {
        $this->value = $value;
        $this->type = $type;
    }

    public function __get($name) {
        switch($name) {
            case 'value':
                return $this->value;
                break;

            case 'type':
                return $this->type;
                break;

            case 'begin':
                return $this->begin;
                break;

            case 'end':
                return $this->end;
                break;
        }
    }
}