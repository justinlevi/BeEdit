<?php

namespace Drupal\beedit\Form;

use Drupal\beedit\BeEditFeatureManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BeEditFeatureForm.
 *
 * @package Drupal\beedit\Form
 */
class BeEditFeatureForm extends FormBase {

  /**
   * Return a Form ID.
   */
  public function getFormId() {
    return 'beedit_feature_form';
  }

  /**
   * Build the feature form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $feature_name_subpath = '') {
    $fm = new BeEditFeatureManager($feature_name_subpath);

    if (empty($feature_name_subpath)) {
      $form['#title'] = $this->t('Add Feature');
    }
    else {
      $form['#title'] = $this->t('Edit Feature: %feature_file', ['%feature_file' => $fm->featureName]);
    }

    $form['#attached']['library'][] = 'beedit/beedit.feature.edit';

    $form['feature_name_subpath_old'] = [
      '#type' => 'hidden',
      '#value' => $feature_name_subpath,
    ];

    $form['main'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['beedit-edit-form-main'],
      ],
    ];

    $form['secondary'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['beedit-edit-form-secondary'],
      ],
    ];

    // Define Folder field type. This field changes between textfield and select
    // depending on New Folder checkbox value.
    if ($form_state->getValue('new_folder') == TRUE) {
      $form['main']['feature_folder'] = [
        '#type' => 'textfield',
        '#default_value' => '',
      ];
    }
    else {
      $form['main']['feature_folder'] = [
        '#type' => 'select',
        '#options' => $fm->folders(),
        '#default_value' => str_replace('/', '--', $fm->featureFolder),
      ];
    }

    // Folder field common attributes.
    $form['main']['feature_folder']['#title'] = $this->t('Folder');
    $form['main']['feature_folder']['#required'] = TRUE;
    $form['main']['feature_folder']['#prefix'] = '<div id="beedit-feature-folder-wrapper">';
    $form['main']['feature_folder']['#suffix'] = '</div>';

    // New Folder checkbox.
    $form['main']['new_folder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('New Folder'),
      '#default_value' => 0,
      '#ajax' => [
        'callback' => [$this, 'ajaxNewFolderCallback'],
        'wrapper' => 'beedit-feature-folder-wrapper',
      ],
    ];

    $form['main']['feature_basename'] = [
      '#type' => 'textfield',
      '#title' => t('Feature Name'),
      '#default_value' => $fm->featureBasename,
      '#required' => TRUE,
      '#field_suffix' => '.feature',
    ];

    $form['main']['feature_content'] = [
      '#type' => 'textarea',
      '#title' => t('Feature File Content'),
      '#default_value' => $fm->read(),
      '#rows' => 15,
    ];

    $form['secondary']['help'] = [
      '#type' => 'vertical_tabs',
      '#title' => t('Help'),
    ];

    $form['secondary']['basic_feature'] = [
      '#type' => 'details',
      '#title' => t('Basic Feature'),
      '#group' => 'help',
    ];

    $form['secondary']['basic_feature']['content'] = [
      '#theme' => 'basic_feature',
    ];

    $form['secondary']['scenario_outline'] = [
      '#type' => 'details',
      '#title' => t('Scenario Outline'),
      '#group' => 'help',
    ];

    $form['secondary']['scenario_outline']['content'] = [
      '#theme' => 'scenario_outline',
    ];

    $form['secondary']['definitions'] = [
      '#type' => 'details',
      '#title' => t('Step Definitions'),
      '#group' => 'help',
    ];

    $form['secondary']['definitions']['filter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter'),
      '#field_suffix' => '&times;',
    ];

    $definition_list = $fm->definitionList();
    $form['secondary']['definitions']['steps'] = [
      '#markup' => $definition_list,
      '#prefix' => '<div id="beedit-definition-list">',
      '#suffix' => '</div>',
    ];

    $form['main']['actions'] = [
      '#type' => 'actions',
    ];

    $form['main']['actions']['feature_save'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#dropbutton' => 'feature_action',
    ];

    $form['main']['actions']['feature_save_run'] = [
      '#type' => 'submit',
      '#value' => t('Save and run test'),
      '#dropbutton' => 'feature_action',
    ];

    $form['main']['actions']['feature_save_exit'] = [
      '#type' => 'submit',
      '#value' => t('Save and exit to features list'),
      '#dropbutton' => 'feature_action',
    ];

    $form['main']['actions']['feature_exit'] = [
      '#type' => 'submit',
      '#value' => t('Exit without saving'),
      '#dropbutton' => 'feature_action',
    ];

    return $form;
  }

  /**
   * Validate feature edit form submission.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    // Check folder name for valid characters
    // Allow word characters, spaces, periods, hyphens and forward slashes.
    if ($input['new_folder'] && !preg_match("/^[\w\s\.\-\/]+$/", $input['feature_folder'])) {
      $form_state->setErrorByName('feature_folder', $this->t('Invalid characters in folder name.'));
      return;
    }

    // Check feature name for valid characters
    // Allow word characters, spaces, periods and hyphens but no slashes.
    if (!preg_match("/^[\w\s\.\-]+$/", $input['feature_basename'])) {
      $form_state->setErrorByName('feature_basename', $this->t('Invalid characters in feature name.'));
      return;
    }

    // Make sure feature name does not contain consecutive hyphens.
    if (strpos($input['feature_basename'], '--') !== FALSE) {
      $form_state->setErrorByName('feature_basename', $this->t('Feature name cannot have consecutive hyphens (--).'));
    }

    // Check for duplicate feature name.
    $basename = $input['feature_basename'];
    $folder = $input['feature_folder'];
    $feature_name_subpath = ($folder == '<root>') ? $basename : "$folder--$basename";

    $fm = new BeEditFeatureManager();
    if ($fm->featureNameExist($feature_name_subpath) && $feature_name_subpath != $input['feature_name_subpath_old']) {
      $form_state->setErrorByName('feature_basename', $this->t('Feature name already exists.'));
      return;
    }
  }

  /**
   * Submit feature edit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get triggering action.
    $trigger = $form_state->getTriggeringElement();

    // Exit without saving and redirect to feature list.
    if ($trigger['#id'] == 'edit-feature-exit') {
      $form_state->setRedirect('beedit.list');
      return;
    }

    // Get form values.
    $input = $form_state->getUserInput();
    $basename = $input['feature_basename'];
    $folder = $input['feature_folder'];

    // Remove slashes at front and end of $folder, then replace remaining
    // slashes with double hyphens.
    $folder = trim($folder, '/');
    $folder = str_replace('/', '--', $folder);

    // Construct feature name subpath.
    $feature_name_subpath = ($folder == '<root>') ? $basename : "$folder--$basename";
    $feature_name_subpath_old = $input['feature_name_subpath_old'];

    // Write the feature file.
    $fm = new BeEditFeatureManager($feature_name_subpath);
    $write_status = $fm->write($input['feature_content']);

    if ($write_status !== FALSE) {
      drupal_set_message(t('Feature file saved.'), 'status');

      // If it is an existing feature and the feature name or folder has
      // changed, then delete the old file.
      if (!empty($feature_name_subpath_old) && $feature_name_subpath_old != $feature_name_subpath) {
        $fm->setFeaturePath($feature_name_subpath_old);
        $fm->delete();
      }
    }
    else {
      // Something went wrong with saving the feature file.
      drupal_set_message(t("An error occurred. The feature file was not saved."), 'error');
    }

    // Redirect user depending on which action was taken.
    if ($trigger['#id'] == 'edit-feature-save') {
      $form_state->setRedirect('beedit.edit', ['feature_name_subpath' => $feature_name_subpath]);
    }
    elseif ($trigger['#id'] == 'edit-feature-save-run') {
      $form_state->setRedirect('beedit.run', ['feature_name_subpath' => $feature_name_subpath]);
    }
    else {
      // Trigger id must be edit-feature-save-exit.
      $form_state->setRedirect('beedit.list');
    }
  }

  /**
   * AJAX callback function for "New Folder" field.
   */
  public function ajaxNewFolderCallback(array $form, FormStateInterface $form_state) {
    // Set #value to be equal to #default_value. This is required in the ajax
    // callback since setting #default_value on the form declaration does not
    // work in an ajax change.
    $form['main']['feature_folder']['#value'] = $form['main']['feature_folder']['#default_value'];

    return $form['main']['feature_folder'];
  }

}
