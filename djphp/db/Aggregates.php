<?php
class Aggregator{
    public $type;
    public $field;
    public $alias = NULL;

    function __construct($type, $field){
        $this->type = $type;
        $this->field = $field;
    }
}

function AggSum($args) {
    return new Aggregator("sum",$args);
}

function AggMax($args) {
    return new Aggregator("max",$args);
}

function AggMin($args) {
    return new Aggregator("min",$args);
}

function AggCount($args) {
    return new Aggregator("count",$args);
}