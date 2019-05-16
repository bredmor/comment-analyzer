<?php
namespace bredmor\CommentAnalyzer;

class SummaryScore {
    private $value;
    private $type;

    public function __construct($value, $type) {
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
        }
    }
}