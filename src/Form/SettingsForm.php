<?php

namespace Drupal\os2web_logging\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\os2web_logging\Entity\AccessLog;

/**
 * Class SettingsForm.
 *
 * @package Drupal\os2web_logging\Form
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

    $node_types = NodeType::loadMultiple();

    // Node types.
    $options = [];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    asort($options);

    $form['logged_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node types to keep log of:'),
      '#options' => $options,
      '#default_value' => $config->get('logged_node_types') ? $config->get('logged_node_types') : [],
    ];

    $form['dblogs_store_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Store database logs for this period'),
      '#field_suffix' => $this->t('days'),
      '#size' => 5,
      '#description' => $this->t('Database logs will be stored for the selected number of days, after that they will be automatically deleted (cleanup is done daily)'),
      '#default_value' => $config->get('dblogs_store_period') ? $config->get('dblogs_store_period') : 180,
    ];

    $form['files_store_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Store log files for this period'),
      '#field_suffix' => $this->t('days'),
      '#size' => 5,
      '#description' => $this->t('Log file will be stored for the selected number of days, after that they will be automatically deleted'),
      '#default_value' => $config->get('files_store_period') ? $config->get('files_store_period') : 180,
    ];

    $form['logs_import_file_detail'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Logs import'),
    ];

    $form['logs_import_file_detail']['logs_import_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Import logs from file'),
      '#description' => $this->t('Logs will be imported form the uploaded file (.log or .json)'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Checking file if we have import file.
    $importedLogs = $this->importLogsFromFile();

    if (!$importedLogs) {
      // To prevent old logs from being deleted on next cron (might happen at
      // any time), update the last os2web_logging.last_cleanup_run.
      //
      // This will make logs stored in the database for the next 24h.
      \Drupal::state()
        ->set('os2web_logging.last_cleanup_run', time());
    }

    // Saving values.
    $config = $this->config(SettingsForm::$configName);
    $old_files_store_period = $config->get('files_store_period');

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    // Rebuilding cache only if 'files_store_period' changed.
    // New setting requires cache to be rebuilt.
    if ($old_files_store_period != $config->get('files_store_period')) {
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
   * Import logs from file.
   *
   * Reads the uploaded file to 'logs_import_file' form fields.
   * Creates logs entries.
   *
   * @return int
   *   Number of imported logs entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function importLogsFromFile() {
    $lines_counter = 0;
    $imported_items_counter = 0;

    // Add validator for your file type etc.
    $validators = ['file_validate_extensions' => ['log', 'json']];

    /** @var \Drupal\file\Entity\File $file */
    $file = file_save_upload('logs_import_file', $validators, FALSE, 0);

    if (!$file) {
      return $imported_items_counter;
    }

    \Drupal::messenger()->addMessage(t('File uploaded successfully: @file', ['@file' => $file->getFileUri()]));

    $handle = fopen($file->getFileUri(), "r");
    if ($handle) {
      while (($line = fgets($handle)) !== FALSE) {
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

      \Drupal::messenger()->addMessage(t('Log messages @imported out of @total were imported', ['@imported' => $imported_items_counter, '@total' => $lines_counter]));
    }
    else {
      \Drupal::messenger()->addError(t('Could not import from file @file', ['@file' => $file->getFileUri()]));
    }

    return $imported_items_counter;
  }
}
