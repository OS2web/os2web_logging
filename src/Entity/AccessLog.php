<?php

namespace Drupal\os2web_logging\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the OS2Web Logging Access Log entity.
 *
 * @ingroup os2web_logging
 *
 * @ContentEntityType(
 *   id = "os2web_logging_access_log",
 *   label = @Translation("OS2Web Logging Access Log"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\os2web_logging\AccessLogListBuilder",
 *     "views_data" = "Drupal\os2web_logging\Entity\AccessLogViewsData",
 *   },
 *   base_table = "os2web_logging_access_log",
 *   translatable = FALSE,
 *   admin_permission = "administer os2logging settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "message",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class AccessLog extends ContentEntityBase implements AccessLogInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Subject node ID reference field.
    $fields['sid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('ID of the subject node'))
      ->setDescription(t('Subject node ID.'))
      ->setSettings([
        'target_type' => 'node',
        'not null' => TRUE,
      ])
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // User ID reference field.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user ID.'))
      ->setSettings([
        'target_type' => 'user',
        'not null' => TRUE,
      ])
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // Message of text log field.
    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('Message of the log.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // IP of the visitor fields.
    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User IP'))
      ->setDescription(t('IP of the visitor.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 15,
      ])
      ->setReadOnly(TRUE);

    // Request URI field..
    $fields['request_uri'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Request URI'))
      ->setDescription(t('URI from which the request has been made.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // Created field.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
