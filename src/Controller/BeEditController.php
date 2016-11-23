<?php

namespace Drupal\beedit\Controller;

use Drupal\beedit\BeEditFeatureManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class BeEditController.
 *
 * @package Drupal\beedit\Controller
 */
class BeEditController extends ControllerBase {

  /**
   * Create render array displaying the content of a feature file.
   *
   * @param string $feature_name_subpath
   *   The relative subpath of a feature. Note slashes are replaced with --.
   *
   * @return array
   *   A render array displaying the content of a feature file.
   */
  public function view($feature_name_subpath) {
    $fm = new BeEditFeatureManager($feature_name_subpath);
    if (($content = $fm->read()) === NULL) {
      drupal_set_message(t('The feature %feature_name does not exist', ['%feature_name' => $fm->featureName]), 'error');
      $render = [];
    }
    else {
      $render = [
        '#markup' => $content,
        '#prefix' => '<div class="beedit-feature-view"><pre>',
        '#suffix' => '</pre></div>',
        '#title' => $this->t('View Feature: %feature_file', ['%feature_file' => $fm->featureName]),
      ];
    }
    return $render;
  }

  /**
   * Create render array displaying the results of a Behat test.
   *
   * @param string $feature_name_subpath
   *   The relative subpath of a feature. Note slashes are replaced with --.
   *
   * @return array
   *   A render array displaying the output of Behat run.
   */
  public function run($feature_name_subpath) {
    $fm = new BeEditFeatureManager($feature_name_subpath);
    $run = $fm->run();

    $render = [
      '#theme' => 'feature_run_output',
      '#pid' => $run['pid'],
      '#file_id' => $run['file_id'],
      '#attached' => [
        'library' => ['beedit/beedit.feature.run'],
      ],
      '#title' => $this->t('Run Feature: %feature_file', ['%feature_file' => $fm->featureName]),
    ];
    return $render;
  }

  /**
   * Check the status of a Behat run.
   *
   * @param int $pid
   *   Behat run process identifier so job can be monitored.
   * @param int $file_id
   *   Output file identifier so multiple Behat tests can be run at same time.
   * @param int $line_number
   *   Indicates starting line number in the output file to keep.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON object containing the output results.
   */
  public function runStatus($pid = 0, $file_id = 0, $line_number = 0) {
    $fm = new BeEditFeatureManager();

    // Retrieve Behat output file and send only the portion which have not
    // been sent to the client browser yet.
    $output_path = file_directory_temp() . "/beedit_$file_id.txt";
    $content = file($output_path);
    $slice = array_slice($content, $line_number);

    // Convert ANSI output to HTML.
    foreach ($slice as $index => $line) {
      $slice[$index] = $fm->ansiToHtml($line);
    }

    // Delete Behat output file if the process has finished.
    $eof = !posix_getpgid($pid);
    if ($eof) {
      unlink($output_path);
    }

    // Return results as JSON.
    $output = [
      'eof' => $eof,
      'content' => $slice,
    ];

    return new JsonResponse($output);
  }

}
