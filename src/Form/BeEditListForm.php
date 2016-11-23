<?php

namespace Drupal\beedit\Form;

use Drupal\beedit\BeEditFeatureManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BeEditListForm.
 *
 * @package Drupal\beedit\Form
 */
class BeEditListForm extends FormBase {

  /**
   * Return a Form ID.
   */
  public function getFormId() {
    return 'beedit_list_form';
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get query parameters.
    $request = \Drupal::request();
    $sort_direction = $request->query->get('sort');
    $sort_field = $request->query->get('order');
    $sort_field = strtolower(str_replace(' ', '_', $sort_field));
    $filter_folder = empty($request->query->get('folder')) ? '_all_' : $request->query->get('folder');
    $filter_feature_name = $request->query->get('feature_name');

    $fm = new BeEditFeatureManager();

    $form['#attached']['library'][] = 'beedit/beedit.feature.list';

    // Setup filter fields.
    $form['filter_fields'] = [
      '#type' => 'container',
    ];

    $form['filter_fields']['filter_folder'] = [
      '#type' => 'select',
      '#title' => $this->t('Folder'),
      '#options' => $fm->folders(),
      '#required' => FALSE,
      '#empty_option' => $this->t('- all -'),
      '#empty_value' => '_all_',
      '#default_value' => $filter_folder,
    ];

    $form['filter_fields']['filter_feature_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Feature Name'),
      '#default_value' => $filter_feature_name,
    ];

    $form['filter_buttons'] = [
      '#type' => 'container',
    ];

    $form['filter_buttons']['filter_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    if ($filter_folder != '_all_' || !empty($filter_feature_name)) {
      $form['filter_buttons']['filter_reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
      ];
    }

    // Setup the table which will contain the list of feature files.
    $form['feature_list'] = [
      '#type' => 'table',
      '#header' => [
        [
          'data' => $this->t('Subfolder'),
          'field' => 'subfolder',
          'sort' => 'asc',
        ],
        ['data' => $this->t('Feature Name'), 'field' => 'feature_name'],
        ['data' => $this->t('File Size')],
        ['data' => $this->t('Last Updated'), 'field' => 'last_updated'],
        ['data' => $this->t('Operations')],
      ],
      '#empty' => $this->t('No features found.'),
    ];

    // Retrieve an array of feature files.
    $features = $fm->listing($filter_folder, $filter_feature_name, $sort_field, $sort_direction);

    // Build table rows of feature files.
    foreach ($features as $feature) {
      // Create default operation links.
      $operation_links = [
        'edit' => [
          'title' => t('Edit'),
          'url' => Url::fromRoute('beedit.edit', ['feature_name_subpath' => $feature['feature_name_subpath']]),
        ],
        'Run' => [
          'title' => t('Run'),
          'url' => Url::fromRoute('beedit.run', ['feature_name_subpath' => $feature['feature_name_subpath']]),
        ],
        'delete' => [
          'title' => t('Delete'),
          'url' => Url::fromRoute('beedit.delete', ['feature_name_subpath' => $feature['feature_name_subpath']]),
        ],
      ];

      // Remove certain operations if user does not have permission.
      if (!\Drupal::currentUser()->hasPermission('maintain behat features')) {
        unset($operation_links['edit']);
        unset($operation_links['delete']);
      }

      // Build a single row.
      $form['feature_list'][] = [
        // Display subfolder.
        'subfolder' => [
          '#plain_text' => $feature['subfolder'],
        ],
        // Display feature name as a hyperlink to view page.
        'feature_name' => [
          '#title' => $feature['feature_name'],
          '#type' => 'link',
          '#url' => Url::fromRoute('beedit.view', ['feature_name_subpath' => $feature['feature_name_subpath']]),
        ],
        // Display feature file size.
        'file_size' => [
          '#plain_text' => format_size($feature['file_size']),
        ],
        // Display feature last update.
        'last_update' => [
          '#plain_text' => \Drupal::service('date.formatter')->format($feature['last_updated'], 'short'),
        ],
        // Display Operation links.
        'operations' => [
          '#type' => 'operations',
          '#links' => $operation_links,
        ],
      ];
    }

    return $form;
  }

  /**
   * Form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('op') == 'Reset') {
      $query_parameters = [];
    }
    else {
      $query_parameters = [
        'feature_name' => $form_state->getValue('filter_feature_name'),
        'folder' => $form_state->getValue('filter_folder'),
      ];
    }
    $form_state->setRedirect('beedit.list', $query_parameters);
  }

}
