<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The bracket cannot be built because the class does not have enough
 * schedules (jadwal) to hold its partai.
 */
class BracketNeedsSchedule extends RuntimeException
{
}
