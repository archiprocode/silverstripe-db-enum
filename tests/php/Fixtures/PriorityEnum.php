<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests\Fixtures;

/**
 * Test enum for priority values.
 */
enum PriorityEnum: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;
}
