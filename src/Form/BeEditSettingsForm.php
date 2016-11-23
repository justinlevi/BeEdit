<?php

namespace Drupal\beedit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure BeEdit settings.
 */
class BeEditSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'beedit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'beedit.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('beedit.settings');

    $form['beedit_behat_bin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Behat Executable File'),
      '#description' => $this->t('The absolute path where the Behat binary file is located.
        i.e. /usr/local/bin/behat'),
      '#default_value' => $config->get('beedit_behat_bin'),
    ];

    $form['beedit_behat_config'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional Config Settings'),
      '#description' => $this->t('Specify additional paramaters Example: `--config=local.yml -p local`'),
      '#default_value' => $config->get('beedit_behat_config'),
    ];

    $form['beedit_behat_project_root'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Behat Project Folder'),
      '#description' => $this->t('The absolute path of the Behat project folder. 
        This is where the project behat.yml file and the features folder would
        be located.'),
      '#default_value' => $config->get('beedit_behat_project_root'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    if (!file_exists($input['beedit_behat_bin'])) {
      $form_state->setErrorByName('beedit_behat_bin', $this->t('Behat executable file does not exist.'));
      return;
    }

    if (!file_exists($input['beedit_behat_project_root'])) {
      $form_state->setErrorByName('beedit_behat_project_root', $this->t('Behat project folder does not exist.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('beedit.settings');

    $config->set('beedit_behat_bin', $form_state->getValue('beedit_behat_bin'))
      ->save();

    $config->set('beedit_behat_project_root', $form_state->getValue('beedit_behat_project_root'))
      ->save();

    $config->set('beedit_behat_config', $form_state->getValue('beedit_behat_config'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
