<?php

namespace Drupal\os2web_logging\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\os2web_logging\Entity\AccessLog;

/**
 * Logging settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Name of the config.
   *
   * @var string
   */
  public static $configName = 'os2web_logging.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [SettingsForm::$configName];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2web_logging_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(SettingsForm::$configName);

    // Set up the link.
    $url = Url::fromUri('internal:/admin/reports/os2web-logging-access-logs');
    $link = Link::fromTextAndUrl('reports', $url);

    $form[] = [
      '#markup' => $this->t('Logs messages can be seen on the %reports page', ['%reports' => $link->toString()]),
    ];

    $node_types = NodeType::loadMultiple();

    // Node types.
    $nodeTypeOptions = [];
    foreach ($node_types as $node_type) {
      $nodeTypeOptions[$node_type->id()] = $node_type->label();
    }
    asort($nodeTypeOptions);

    $form['logged_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node types access to keep log of:'),
      '#options' => $nodeTypeOptions,
      '#default_value' => $config->get('logged_node_types') ? $config->get('logged_node_types') : [],
    ];

    // Webform elements.
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('os2forms_nemid')) {
      $webformElementOptions = [];
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $webformElements = $element_manager->getDefinitions();
      foreach ($webformElements as $el) {
        if ($el['provider'] == 'os2forms_nemid') {
          $webformElementOptions[$el['id']] = $el['label'];
        }
      }
      asort($webformElementOptions);

      $form['logged_webform_elements'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Webform fields access to keep log of:'),
        '#options' => $webformElementOptions,
        '#default_value' => $config->get('logged_webform_elements') ? $config->get('logged_webform_elements') : [],
      ];
    }

    $form['log_anonymous_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log anonymous user actions'),
      '#description' => $this->t('If anonymous user actions are being logged'),
      '#default_value' => $config->get('log_anonymous_user'),
    ];

    $form['dblogs_detail'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Database logs'),
      '#open' => TRUE,
    ];

    $form['dblogs_detail']['dblogs_store_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Store database logs for this period'),
      '#field_suffix' => $this->t('days'),
      '#size' => 5,
      '#description' => $this->t('Database logs will be stored for the selected number of days, after that they will be automatically deleted (cleanup is done daily)'),
      '#default_value' => $config->get('dblogs_store_period') ? $config->get('dblogs_store_period') : 180,
    ];

    $form['file_logs_detail'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('File logs'),
      '#open' => TRUE,
    ];

    $form['file_logs_detail']['files_store_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Store log files for this period'),
      '#field_suffix' => $this->t('days'),
      '#size' => 5,
      '#min' => 180,
      '#description' => $this->t('Log file will be stored for the selected number of days, after that they will be automatically deleted'),
      '#default_value' => $config->get('files_store_period') ? $config->get('files_store_period') : 180,
    ];

    $form['file_logs_detail']['files_log_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store log files directory'),
      '#description' => $this->t('Log file will be stored for the selected number of days, after that they will be automatically deleted'),
      '#default_value' => $config->get('files_log_path') ? $config->get('files_log_path') : '../logs',
    ];

    $form['logs_import_file_detail'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Logs import'),
    ];

    $options = [];
    if ($config->get('files_log_path')) {
      /** @var FileSystemInterface $fileSystem */
      $fileSystem = \Drupal::service('file_system');
      $storedLogFiles = $fileSystem->scanDirectory($config->get('files_log_path'), '/os2web_logging_node_access-\d{4}-\d{2}-\d{2}\.(log|gz)/');

      foreach ($storedLogFiles as $file) {
        $url = Url::fromRoute('os2web_logging.logfile.download', ['filename' => $file->filename]);
        $link = Link::fromTextAndUrl(t('[Download]'), $url);
        $options[$file->uri] = $file->filename . ' ' . $link->toString();
      }
      arsort($options);
    }

    $form['logs_import_file_detail']['logs_import_files_select'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Import from existing files'),
      '#description' => $this->t('Archived log files will be automatically extracted'),
    ];

    $form['logs_import_file_detail']['logs_import_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Import logs from uploaded file'),
      '#description' => $this->t('Logs will be imported form the uploaded file (.log or .json)'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $files_log_path = $form_state->getValue('files_log_path');

    $exists = \Drupal::service('file_system')->prepareDirectory($files_log_path, FileSystemInterface::MODIFY_PERMISSIONS);
    if (!$exists) {
      $form_state->setErrorByName('files_log_path', t('Directory does not exist or is not writable %dir', ['%dir' => $files_log_path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Storing files to import.
    $importFileUris = [];

    // Checking file if we have an uploaded file to import from.
    $uploadedFile = $this->uploadImportFile();

    if ($uploadedFile) {
      \Drupal::messenger()
        ->addMessage(t('File uploaded successfully: @file', ['@file' => $uploadedFile->getFileUri()]));
      $importFileUris[] = $uploadedFile->getFileUri();
    }

    // Checking if we have any files selected to import from.
    if ($selectedFiles = $form_state->getValue('logs_import_files_select')) {
      foreach ($selectedFiles as $uri) {
        if ($uri) {
          $importFileUris[] = $uri;
        }
      }
      $form_state->unsetValue('logs_import_files_select');
    }

    // Import logs from the files.
    if (!empty($importFileUris)) {
      $importedLogs = $this->importLogsFromUris($importFileUris);
      if ($importedLogs) {
        // To prevent old logs from being deleted on next cron (might happen at
        // any time), update the last os2web_logging.last_cleanup_run.
        //
        // This will make logs stored in the database for the next 24h.
        \Drupal::state()
          ->set('os2web_logging.last_cleanup_run', time());
      }
    }

    // Saving values.
    $config = $this->config(SettingsForm::$configName);
    $old_files_store_period = $config->get('files_store_period');
    $old_files_store_path = $config->get('files_log_path');

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    // Rebuilding cache only if 'files_store_period' or 'files_log_path'
    // changed. New setting requires cache to be rebuilt.
    if ($old_files_store_period != $config->get('files_store_period') ||
      $old_files_store_path != $config->get('files_log_path')) {
      // Rebuild module and theme data.
      $module_data = \Drupal::service('extension.list.module')->getList();

      $files = [];
      foreach ($module_data as $name => $extension) {
        if ($extension->status) {
          $files[$name] = $extension;
        }
      }
      \Drupal::service('kernel')
        ->updateModules(\Drupal::moduleHandler()
          ->getModuleList(), $files);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Uploads file from field logs_import_file.
   *
   * @return \Drupal\file\Entity\File|null
   *   Uploaded file or NULL.
   */
  protected function uploadImportFile() {
    // Add validator for your file type etc.
    $validators = ['file_validate_extensions' => ['log', 'json']];

    /** @var \Drupal\file\Entity\File $file */
    $file = file_save_upload('logs_import_file', $validators, FALSE, 0);

    return $file;
  }

  /**
   * Imports logs from file.
   *
   * Reads the files from provided URIs and creates log entries.
   *
   * @param array $uris
   *   Array of files uris.
   *
   * @return int
   *   Number of imported logs entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function importLogsFromUris(array $uris) {
    $lines_counter = 0;
    $imported_items_counter = 0;

    foreach ($uris as $uri) {
      $handle = gzopen($uri, "r");
      if ($handle) {
        while (($line = gzgets($handle)) !== FALSE) {
          $lines_counter++;
          $lineJson = Json::decode($line);

          // Discarding messages that do not follow format.
          if (empty($lineJson) ||
            !array_key_exists('context', $lineJson) ||
            !array_key_exists('extra', $lineJson) ||
            !array_key_exists('message', $lineJson) ||
            !array_key_exists('datetime', $lineJson)) {
            continue;
          }

          // Create new log entry.
          $saved = AccessLog::create([
            'sid' => $lineJson['context']['sid'],
            'uid' => $lineJson['extra']['uid'],
            'message' => $lineJson['message'],
            'ip' => $lineJson['extra']['ip'],
            'request_uri' => $lineJson['extra']['request_uri'],
            'created' => strtotime($lineJson['datetime']['date']),
          ])->save();
          if ($saved) {
            $imported_items_counter++;
          }
        }
        fclose($handle);

        \Drupal::messenger()
          ->addMessage(t('Log messages @imported out of @total were imported', [
            '@imported' => $imported_items_counter,
            '@total' => $lines_counter,
          ]));
      }
      else {
        \Drupal::messenger()
          ->addError(t('Could not import from file @file', ['@file' => $uri]));
      }
    }

    return $imported_items_counter;
  }

}
