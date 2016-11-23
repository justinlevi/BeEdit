<?php

namespace Drupal\beedit\Form;

use Drupal\beedit\BeEditFeatureManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BeEditDeleteForm.
 *
 * Form for deleting a feature.
 *
 * @package Drupal\beedit\Form
 */
class BeEditDeleteForm extends ConfirmFormBase {
  protected $featureFile;

  /**
   * Return a Form ID.
   */
  public function getFormId() {
    return 'beedit_delete_form';
  }

  /**
   * Set the delete question.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A translated string with the delete question.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the behat feature %feature_file ?', ['%feature_file' => $this->featureFile]);
  }

  /**
   * Set the redirect URL if user cancels the delete operation.
   *
   * @return \Drupal\Core\Url
   *   A url link to the features list.
   */
  public function getCancelUrl() {
    return new Url('beedit.list');
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $feature_name_subpath = '') {
    $this->featureFile = str_replace('--', '/', $feature_name_subpath);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fm = new BeEditFeatureManager($this->featureFile);
    $status = $fm->delete();

    if ($status) {
      drupal_set_message($this->t('Deleted the feature %feature_file', ['%feature_file' => $this->featureFile]));
      $form_state->setRedirect('beedit.list');
    }
    else {
      drupal_set_message($this->t('An error occurred. The feature %feature_file was not deleted', ['%feature_file' => $this->featureFile]), 'error');
    }
  }

}
