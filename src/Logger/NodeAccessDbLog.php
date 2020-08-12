<?php

namespace Drupal\os2web_logging\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\os2web_logging\Entity\AccessLog;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the watchdog database table.
 */
class NodeAccessDbLog implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    AccessLog::create([
      'sid' => $context['sid'],
      'uid' => $context['uid'],
      'message' => $message,
      'ip' => mb_substr($context['ip'], 0, 15),
      'request_uri' => $context['request_uri'],
      'timestamp' => $context['timestamp'],
    ])->save();
  }

}
