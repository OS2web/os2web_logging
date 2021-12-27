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
 * Watchdog Logging settings form.
 */
class WatchdogSettingsForm extends ConfigFormBase {

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
    return [WatchdogSettingsForm::$configName];
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
    $config = $this->config(WatchdogSettingsForm::$configName);

    $form['watchdog_db_details'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Watchdog DB logs'),
      '#open' => TRUE,
    ];

    $form['watchdog_db_details']['watchdog_dblog_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('DB Log enabled'),
      '#description' => $this->t('If logs are to be stored in the database as dblog entries.'),
      '#default_value' => $config->get('watchdog_dblog_enabled') ?? TRUE,
    ];

    // Set up the link.
    $url = Url::fromUri('internal:/admin/reports/dblog');
    $link = Link::fromTextAndUrl('Recent log messages', $url);

    $form['watchdog_db_details'][] = [
      '#markup' => $this->t('DB Logs messages can be seen on the %reports page', ['%reports' => $link->toString()]),
    ];

    $form['watchdog_files_details'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Watchdog File logs'),
    ];

    $form['watchdog_files_details']['watchdog_files_store_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Store log files for this period'),
      '#field_suffix' => $this->t('days'),
      '#size' => 5,
      '#min' => 180,
      '#description' => $this->t('Log file will be stored for the selected number of days, after that they will be automatically deleted'),
      '#default_value' => $config->get('watchdog_files_store_period') ?? 180,
    ];

    $form['watchdog_files_details']['watchdog_files_log_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store log files directory'),
      '#description' => $this->t('Log file will be stored for the selected number of days, after that they will be automatically deleted'),
      '#default_value' => $config->get('watchdog_files_log_path') ?? '../logs',
      '#field_suffix' => '<em>/os2web_logging_watchdog-YYYY-MM-DD.log</em>',
    ];

    $options = [];
    if ($config->get('watchdog_files_log_path')) {
      /** @var FileSystemInterface $fileSystem */
      $fileSystem = \Drupal::service('file_system');
      $storedLogFiles = $fileSystem->scanDirectory($config->get('watchdog_files_log_path'), '/os2web_logging_watchdog-\d{4}-\d{2}-\d{2}\.(log|gz)/');

      foreach ($storedLogFiles as $file) {
        $url = Url::fromRoute('os2web_logging.logfile.download', ['filename' => $file->filename]);
        $link = Link::fromTextAndUrl($file->filename, $url);
        $options[$file->uri] = $link;
      }
      arsort($options);
    }

    $watchdog_logs_build = [
      '#theme' => 'item_list',
      '#title' => t('Watchdog logs'),
      '#items' => $options,
    ];
    $watchlog_logs_rendered = \Drupal::service('renderer')->renderPlain($watchdog_logs_build);

    $form['watchdog_files_details'][] = [
      '#markup' => $watchlog_logs_rendered,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $files_log_path = $form_state->getValue('watchdog_files_log_path');

    $exists = \Drupal::service('file_system')->prepareDirectory($files_log_path, FileSystemInterface::MODIFY_PERMISSIONS);
    if (!$exists) {
      $form_state->setErrorByName('watchdog_files_log_path', t('Directory does not exist or is not writable %dir', ['%dir' => $files_log_path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Saving values.
    $config = $this->config(WatchdogSettingsForm::$configName);
    $old_files_store_period = $config->get('watchdog_files_store_period');
    $old_files_store_path = $config->get('watchdog_files_log_path');
    $old_dblog_enabled = $config->get('watchdog_dblog_enabled');

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    // Rebuilding cache only if 'files_store_period' or 'files_log_path'
    // changed. New setting requires cache to be rebuilt.
    if ($old_files_store_period != $config->get('watchdog_files_store_period') ||
      $old_files_store_path != $config->get('watchdog_files_log_path') ||
      $old_dblog_enabled != $config->get('watchdog_dblog_enabled')) {

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


}
