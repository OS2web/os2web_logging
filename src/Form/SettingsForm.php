<?php

namespace Drupal\os2web_logging\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(SettingsForm::$configName);

    $old_files_store_period = $config->get('files_store_period');

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);

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
  }

}
