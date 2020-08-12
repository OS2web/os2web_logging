<?php

namespace Drupal\os2web_logging\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining OS2Web Logging Access Log entities.
 *
 * @ingroup os2web_logging
 */
interface AccessLogInterface extends ContentEntityInterface {

  /**
   * Gets the OS2Web Logging Access Log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OS2Web Logging Access Log.
   */
  public function getCreatedTime();

  /**
   * Sets the OS2Web Logging Access Log creation timestamp.
   *
   * @param int $timestamp
   *   The OS2Web Logging Access Log creation timestamp.
   *
   * @return \Drupal\os2web_logging\Entity\AccessLogInterface
   *   The called OS2Web Logging Access Log entity.
   */
  public function setCreatedTime($timestamp);

}
