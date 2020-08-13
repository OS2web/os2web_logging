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
    // Skipping calls when container is not ready.
    // We need the container to be reader in order to fetch module
    // configuration.
    if (!\Drupal::hasContainer()) {
      return;
    }

    $config = \Drupal::config(SettingsForm::$configName);
    $store_period = $config->get('files_store_period');

    // Updating store period for logger.
    if ($store_period) {
      $logger = $container->getDefinition('monolog.handler.os2web_logging_node_access_file');
      $logger->replaceArgument(1, $store_period);
    }
  }

}
