<?php

namespace Drupal\os2web_logging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\os2web_logging\Form\SettingsForm;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Provides status page.
 */
class LoggingController extends ControllerBase {

  /**
   * Builds status page.
   *
   * @return array
   *   Renderable array.
   */
  public function statusPage() {
    $checkedRequirements = LoggingController::getCheckedRequirements();

    $groupedRequirements = [];

    if (!empty($checkedRequirements['error'])) {
      $groupedRequirements['error'] = [
        'title' => $this->t('Errors found'),
        'type' => 'error',
        'items' => $checkedRequirements['error'],
      ];
    }

    if (!empty($checkedRequirements['warning'])) {
      $groupedRequirements['warning'] = [
        'title' => $this->t('Warnings found'),
        'type' => 'warning',
        'items' => $checkedRequirements['warning'],
      ];
    }

    if (!empty($checkedRequirements['checked'])) {
      $groupedRequirements['checked'] = [
        'title' => $this->t('Checked'),
        'type' => 'checked',
        'items' => $checkedRequirements['checked'],
      ];
    }

    return [
      '#theme' => 'status_report_grouped',
      '#grouped_requirements' => $groupedRequirements,
    ];
  }

  /**
   * Checks the module requirement.
   *
   * @return array
   *   Array of requirements as
   *   [
   *     'error' => [
   *       0 => [
   *         'title' => 'Requirement title',
   *         'value' => 'Requirement value',
   *         'description' => 'Additional info'
   *       ]
   *     ]
   *     'warning' => [
   *       ...
   *     ]
   *     'checked' => [
   *       ...
   *     ]
   *   ]
   */
  public static function getCheckedRequirements() {
    $requirements = [
      'error' => [],
      'warning' => [],
      'checked' => [],
    ];

    $config = \Drupal::config(SettingsForm::$configName);

    // 1. Module enabled.
    $moduleHandler = \Drupal::service('module_handler');
    $module_enabled = $moduleHandler->moduleExists('os2web_logging');

    $req = [
      'title' => t('Module enabled'),
      'description' => t('Check if module is enabled'),
    ];

    if ($module_enabled) {
      $req['value'] = t('Enabled');
      $requirements['checked'][] = $req;
    }
    else {
      $req['value'] = t('No');
      $requirements['error'][] = $req;
    }

    // 2. Node types.
    $node_types = $config->get('logged_node_types');
    $node_types = array_filter($node_types);

    $node_types_build = [
      '#theme' => 'item_list',
      '#title' => t('Node type'),
      '#items' => $node_types,
    ];
    $node_types_rendered = \Drupal::service('renderer')->renderPlain($node_types_build);

    $req = [
      'title' => t('Selected node types'),
      'description' => t('These node types will be logged'),
    ];

    if (!empty($node_types)) {
      $req['value'] = $node_types_rendered;
      $requirements['checked'][] = $req;
    }
    else {
      $req['value'] = t('No');
      $requirements['error'][] = $req;
    }

    // 3. NemID fields.
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('os2forms_nemid')) {
      $webform_elements = $config->get('logged_webform_elements');
      $webform_elements = array_filter($webform_elements);

      $webform_elements_build = [
        '#theme' => 'item_list',
        '#title' => t('NemID fields type'),
        '#items' => $webform_elements,
      ];
      $webform_elements_rendered = \Drupal::service('renderer')->renderPlain($webform_elements_build);

      $req = [
        'title' => t('Selected NemID fields'),
        'description' => t('These NemID fields access will be logged'),
      ];

      $req['value'] = !empty($webform_elements) ? $webform_elements_rendered : t('Empty');
      $requirements['checked'][] = $req;
    }

    // 4. Number of days to store DB logs:
    $dblogStorePeriod = $config->get('dblogs_store_period');

    $req = [
      'title' => t('Days to store DB logs'),
      'description' => t('Number of days to store database logs'),
    ];

    if ($dblogStorePeriod) {
      $req['value'] = $dblogStorePeriod;
      $requirements['checked'][] = $req;
    }
    else {
      $req['value'] = t('No');
      $requirements['error'][] = $req;
    }

    // 5. Number of days to store file logs.
    $fileLogsStorePeriod = $config->get('files_store_period');

    $req = [
      'title' => t('Days to store log file'),
      'description' => t('Number of days to store log files'),
    ];

    if ($fileLogsStorePeriod) {
      $req['value'] = $fileLogsStorePeriod;
      $requirements['checked'][] = $req;
    }
    else {
      $req['value'] = t('No');
      $requirements['error'][] = $req;
    }

    // 6. File directory is writable.
    $logger = \Drupal::service('monolog.handler.os2web_logging_access_log_file');
    $logsDir = dirname($logger->getUrl());
    $exists = \Drupal::service('file_system')->prepareDirectory($logsDir, FileSystemInterface::MODIFY_PERMISSIONS);

    $req = [
      'title' => t('File logs directory'),
      'description' => t('If directory exists and is writable'),
    ];

    if ($exists) {
      $req['value'] = t('Directory exists and is writable %dir', ['%dir' => $logsDir]);
      $requirements['checked'][] = $req;
    }
    else {
      $req['value'] = t('Directory does not exist or is not writable %dir', ['%dir' => $logsDir]);
      $requirements['error'][] = $req;
    }

    return $requirements;
  }

  /**
   * Download file.
   *
   * @param string $filename
   *   The filename.
   */
  public function logfileExport($filename) {
    $config = $this->config(SettingsForm::$configName);

    // Do some file validation here, like checking for extension.

    // File lives in /files/downloads.
    $logPath = $config->get('files_log_path');

    $uri = $logPath . '/'. $filename;

    $headers = [
      'Content-Type' => 'text/csv', // Would want a condition to check for extension and set Content-Type dynamically
      'Content-Description' => 'File Download',
      'Content-Disposition' => 'attachment; filename=' . $filename
    ];

    // Return and trigger file donwload.
    return new BinaryFileResponse($uri, 200, $headers, true );

  }

}
