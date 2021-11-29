<?php
namespace bredmor\CommentAnalyzer;

class SummaryScore {
    private float $value;
    private string $type;

    public function __construct($value, $type) {
        $this->value = $value;
        $this->type = $type;
    }

    public function __get($name) {
        return match($name) {
            'value'     => $this->value,
            'type'      => $this->type
        };
    }
}