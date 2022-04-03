<?php
namespace bredmor\CommentAnalyzer;

class SpanScore {
    private float $value;
    private string $type;
    private int $begin;
    private int $end;

    public function __construct($value, $type, $begin, $end) {
        $this->value    = $value;
        $this->type     = $type;
        $this->begin    = $begin;
        $this->end      = $end;
    }

    public function __get($name) {
        return match($name) {
            'value' => $this->value,
            'type'  => $this->type,
            'begin' => $this->begin,
            'end'   => $this->end
        };
    }
}