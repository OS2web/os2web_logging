<?php

namespace Drupal\os2web_logging;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\os2web_logging\Form\SettingsForm;

/**
 * Overrides the monolog.handler.os2web_logging_node_access_file service.
 */
class Os2webLoggingServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $config = \Drupal::config(SettingsForm::$configName);
    $store_period = $config->get('files_store_period');

    // Updating store period for logger.
    if ($store_period) {
      $logger = $container->getDefinition('monolog.handler.os2web_logging_node_access_file');
      $logger->replaceArgument(1, $store_period);
    }
  }

}
