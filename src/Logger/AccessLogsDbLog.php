<?php

namespace Drupal\os2web_logging\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2web_logging\Entity\AccessLog;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the database table.
 */
class AccessLogsDbLog implements LoggerInterface {
  use RfcLoggerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if (strlen($context['request_uri']) > AccessLog::REQUEST_URI_FIELD_MAX_LENGTH) {
      $request_uri = $context['request_uri'];
      $context['request_uri'] = substr($request_uri, 0, AccessLog::REQUEST_URI_FIELD_MAX_LENGTH);
      $message .= PHP_EOL;
      $message .= $this->t('Request URI has been truncated due to max_length restriction.') . PHP_EOL;
    }
    AccessLog::create([
      'sid' => $context['sid'],
      'uid' => $context['uid'],
      'message' => $message,
      'ip' => mb_substr($context['ip'], 0, 15),
      'request_uri' => $context['request_uri'],
      'created' => $context['timestamp'],
    ])->save();
  }

}
