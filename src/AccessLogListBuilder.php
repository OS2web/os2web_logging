<?php

namespace Drupal\os2web_logging;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of OS2Web Logging Access Log entities.
 *
 * @ingroup os2web_logging
 */
class AccessLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('OS2Web Logging Access Log ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\os2web_logging\Entity\AccessLog $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.os2web_logging_access_log.edit_form',
      ['os2web_logging_access_log' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
