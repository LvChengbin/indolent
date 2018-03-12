<?php

namespace NextSeason\Model\SQL;


class Placeholder {
    const JOIN = '##PLACEHOLDER_JOIN##';
    const CROSS_JOIN = '##PLACEHOLDER_CROSS_JOIN##';
    const INNER_JOIN = '##PLACEHOLDER_INNER_JOIN##';
    const OUTER_JOIN = '##PLACEHOLDER_OUTER_JOIN##';
    const LEFT_JOIN = '##PLACEHOLDER_LEFT_JOIN##';
    const RIGHT_JOIN = '##PLACEHOLDER_RIGHT_JOIN##';
    const STRAIGHT_JOIN = '##PLACEHOLDER_STRAIGHT_JOIN##';
    const ON = '##PLACEHOLDER_ON##';
    const USING = '##PLACEHOLDER_USING##';
    const AND = '##PLACEHOLDER_AND##';
    const OR = '##PLACEHOLDER_OR##';
    const COMMA = '##PLACEHOLDER_COMMA##';
}
