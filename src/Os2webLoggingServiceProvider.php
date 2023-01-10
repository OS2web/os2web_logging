<?php

namespace Drupal\os2web_logging;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\os2web_logging\Form\SettingsForm;
use Drupal\os2web_logging\Form\WatchdogSettingsForm;

/**
 * Overrides the monolog service.
 */
class Os2webLoggingServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Skipping calls when container is not ready.
    // We need the container to be reader in order to fetch module
    // configuration.
    if (!\Drupal::hasContainer() || !\Drupal::hasService('config.factory')) {
      return;
    }

    // Updating OS2Logger Node Access.
    $config = \Drupal::config(SettingsForm::$configName);
    $logger = $container->getDefinition('monolog.handler.os2web_logging_access_log_file');

    // Updating store path for logger.
    $logs_path = $config->get('files_log_path');
    if (!empty($logs_path)) {
      $logs_path .= '/os2web_logging_access_log.log';
      $logger->replaceArgument(0, $logs_path);
    }

    // Updating store period for logger.
    $store_period = $config->get('files_store_period');
    if ($store_period) {
      $logger->replaceArgument(1, $store_period);
    }

    // Updating OS2Logger Watchdog.
    $watchdogConfig = \Drupal::config(WatchdogSettingsForm::$configName);
    $watchdogLogger = $container->getDefinition('monolog.handler.os2web_logging_watchdog');

    // Updating store path for watchdog logger.
    $logs_path = $watchdogConfig->get('watchdog_files_log_path');
    if (!empty($logs_path)) {
      $logs_path .= '/os2web_logging_watchdog.log';
      $watchdogLogger->replaceArgument(0, $logs_path);
    }

    // Updating store period for logger.
    $store_period = $watchdogConfig->get('watchdog_files_store_period');
    if ($store_period) {
      $watchdogLogger->replaceArgument(1, $store_period);
    }

    // Updating monolog.channel_handlers.
    $channel_handlers = $container->getParameter('monolog.channel_handlers');
    $channel_handlers['default'] = [
      'os2web_logging_watchdog',
    ];
    // Only add if DB Log enabled.
    if ($watchdogConfig->get('watchdog_dblog_enabled')) {
      $channel_handlers['default'][] = 'drupal.dblog';
    }
    $container->setParameter('monolog.channel_handlers', $channel_handlers);
  }

}
