<?php

namespace Dynamophp\VectorClock;

enum ClockOrder: string
{
    case NOT_COMPARABLE = 'not-comparable';
    case IDENTICAL = 'identical';
    case HAPPEN_BEFORE = 'before';
    case HAPPEN_AFTER = 'after';
    case CONCURRENT = 'concurrent';
}
