<?php

namespace App\Services\GoogleDrive;

use RuntimeException;

/**
 * Thrown when a company has no usable Drive connection — never linked, disconnected, or
 * consent withdrawn in Google.
 *
 * Distinct from a transient API failure on purpose: this one is terminal and a queued job
 * should fail rather than retry, because only a human reconnecting can fix it.
 */
class GoogleDriveNotConnected extends RuntimeException
{
}
