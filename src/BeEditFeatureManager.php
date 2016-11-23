<?php

namespace Drupal\beedit;

/**
 * Class BeEditFeatureManager.
 *
 * Methods and functions to handle Behat feature file operations.
 *
 * @package Drupal\beedit
 */
class BeEditFeatureManager extends BehatFeatureManager {

  /**
   * Load BeEdit settings.
   */
  public function loadSettings() {
    $config = \Drupal::config('beedit.settings');

    // Get Behat bin file location.
    $this->behatBin = $config->get('beedit_behat_bin');
    if (empty($this->behatBin)) {
      drupal_set_message(t('Behat bin file location needs to be set.'), 'error');
    }

    // Get Behat project root folder.
    $this->behatProjectRoot = $config->get('beedit_behat_project_root');
    if (empty($this->behatProjectRoot)) {
      drupal_set_message(t('Behat Project Root needs to be set.'), 'error');
    }

    // Get Drupal temp file path.
    $this->drupalTempPath = file_directory_temp();
  }

}
