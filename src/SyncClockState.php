<?php

namespace Dynamophp\VectorClock;

enum SyncClockState: string
{
    case IDLE = 'idle';
    case COMMUNICATING = 'communicating';
}
