<?php

namespace Drupal\os2web_logging\EventSubscriber;

use Drupal\os2web_logging\Form\SettingsForm;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NodeAccessEventSubscriber.
 *
 * Runs on every request.
 */
class NodeAccessEventSubscriber implements EventSubscriberInterface {

  /**
   * Logs node access.
   */
  public function logNodeAccess() {
    /** @var Drupal\node\NodeInterface $node */
    $node = NULL;

    $routeMatch = \Drupal::routeMatch();
    if ($routeMatch->getRouteName() == 'entity.node.canonical' && $node = $routeMatch->getParameter('node')) {
      $config = \Drupal::config(SettingsForm::$configName);

      // Ignore anonymous user.
      $logAnonymUser = $config->get('log_anonymous_user');
      if (!$logAnonymUser && \Drupal::currentUser()->isAnonymous()) {
        return;
      }

      // Filter on node type.
      if ($node_types = $config->get('logged_node_types')) {
        if (in_array($node->getType(), $node_types, TRUE)) {

          // Log entry.
          $logger = \Drupal::logger('os2web_logging.access_log');
          $logger->info('Node loaded', ['sid' => $node->id()]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['logNodeAccess'];
    return $events;
  }

}
