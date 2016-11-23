<?php

namespace Drupal\beedit;

/**
 * Class BehatFeatureManager.
 *
 * Methods and functions to handle Behat feature file operations.
 *
 * @package Drupal\beedit
 */
abstract class BehatFeatureManager implements BehatFeatureManagerInterface {
  protected $behatProjectRoot;
  protected $behatBin;
  protected $behatConfig;
  protected $featuresRootPath;
  protected $featureFullPath;
  protected $sortField;
  protected $sortDirection;
  protected $drupalTempPath;
  public $featureName;
  public $featureBasename;
  public $featureFolder;

  /**
   * BehatFeatureManager constructor.
   *
   * @param string $feature_name_subpath
   *   The relative subpath of a feature. Note slashes are replaced with --.
   */
  public function __construct($feature_name_subpath = '') {
    // Load BeEdit settings.
    $this->loadSettings();

    // Set behat features folder.
    $this->featuresRootPath = "$this->behatProjectRoot/features";

    if (!empty($feature_name_subpath)) {
      $this->setFeaturePath($feature_name_subpath);
    }
  }

  /**
   * Load BeEdit settings..
   *
   * This method loads configuration settings and updates the variables
   * $behatProjectRoot, $behatBin, $behatConfig, and $drupalTempPath.
   *
   * This method is overridden in the extended class BeEditFeatureManager.php
   * to allow for different Drupal versions.
   */
  public function loadSettings() {
    $this->behatProjectRoot = '';
    $this->behatBin = '';
    $this->behatConfig = '';
  }

  /**
   * Set the name of the feature file.
   */
  public function setFeaturePath($feature_name_subpath) {
    $this->featureName = str_replace('--', '/', $feature_name_subpath);
    $this->featureFullPath = "$this->featuresRootPath/$this->featureName.feature";
    $this->featureBasename = basename($this->featureName);

    if (dirname($this->featureName) == '.') {
      $this->featureFolder = '<root>';
    }
    else {
      $this->featureFolder = dirname($this->featureName);
    }
  }

  /**
   * Return an array of Behat feature names.
   */
  public function listing($filter_folder = '_all_', $filter_feature_name = '', $sort_field = 'subfolder', $sort_direction = 'asc') {
    // If beedit behat root path not set, then exit.
    if (empty($this->featuresRootPath)) {
      return [];
    }

    // Setup the path to search for .feature files.
    if ($filter_folder == '_all_' || $filter_folder == '<root>') {
      $search_path = $this->featuresRootPath;
    }
    else {
      $filter_folder = str_replace('--', '/', $filter_folder);
      $search_path = "$this->featuresRootPath/$filter_folder";
    }

    // Recursively get a list of files.
    $directory = new \RecursiveDirectoryIterator($search_path);
    $iterator = new \RecursiveIteratorIterator($directory);
    if ($filter_folder != '_all_') {
      $iterator->setMaxDepth(0);
    }

    // Search for files with $filter_feature_name in it and with the extension
    // .feature.
    $regex = '/^.*' . $filter_feature_name . '[^\/]*\.feature$/i';
    $results = new \RegexIterator($iterator, $regex, \RecursiveRegexIterator::GET_MATCH);

    // From the results, construct an array containing a list of feature name.
    $features = [];
    foreach ($results as $result) {
      $filename = $result[0];
      $directory = pathinfo($filename, PATHINFO_DIRNAME);
      $subfolder = $this->relativePath($directory);
      $basename = basename($result[0], '.feature');
      $feature_name_subpath = empty($subfolder) ? $basename : str_replace('/', '--', $subfolder) . '--' . $basename;

      $features[] = [
        'subfolder' => $subfolder,
        'feature_name' => $basename,
        'feature_name_subpath' => $feature_name_subpath,
        'file_size' => filesize($result[0]),
        'last_updated' => filemtime($result[0]),
      ];
    }

    // Sort the list accordingly.
    $this->sortField = $sort_field;
    $this->sortDirection = $sort_direction;
    usort($features, [$this, 'sortCompareCallback']);

    return $features;
  }

  /**
   * Read the content of a Behat feature file.
   */
  public function read() {
    if (file_exists($this->featureFullPath)) {
      $content = file_get_contents($this->featureFullPath);
    }
    else {
      $content = NULL;
    }
    return $content;
  }

  /**
   * Write content to feature file.
   */
  public function write($content) {
    if ($this->featureFolder != '<root>') {
      $folder = $this->featuresRootPath . '/' . $this->featureFolder;
      if (!file_exists($folder)) {
        mkdir($folder, 0755, TRUE);
      }
    }

    $status = file_put_contents($this->featureFullPath, $content);
    return $status;
  }

  /**
   * Delete feature file.
   */
  public function delete() {
    $status = unlink($this->featureFullPath);
    return $status;
  }

  /**
   * Run feature file.
   */
  public function run() {
    $timestamp = time();
    $filename = "$this->drupalTempPath/beedit_$timestamp.txt";

    // Run behat command as a separate process and store output in temp folder.
    $command = "cd $this->behatProjectRoot && $this->behatBin $this->behatConfig --colors 'features/$this->featureName.feature' > $filename & echo $!";
    $pid = exec($command);

    return [
      'file_id' => $timestamp,
      'pid' => $pid,
    ];
  }

  /**
   * Generate a list of behat step definitions.
   *
   * This is used for display in the feature edit help screen.
   *
   * @return string
   *   A list of step definitions with line breaks between each.
   */
  public function definitionList() {
    $command = "cd $this->behatProjectRoot && $this->behatBin -dl --no-colors 2>&1";
    exec($command, $output, $return_value);

    $result = '';
    foreach ($output as $line) {
      $line = trim(substr($line, strpos($line, '|') + 1));
      $result .= $this->ansiToHtml($line);
    }
    return $result;
  }

  /**
   * ANSI to HTML converter.
   *
   * Note: This is not a complete converter. It just converts ANSI codes used
   * by Behat.
   */
  public function ansiToHtml($ansi) {
    // Define ANSI escaped color codes to html span tags.
    $ansi_html_map = [
      '[1m' => '<span class="ansi-bold">',
      '[30m' => '<span class="ansi-black">',
      '[31m' => '<span class="ansi-red">',
      '[31;1m' => '<span class="ansi-red ansi-bold">',
      '[32m' => '<span class="ansi-green">',
      '[32;1m' => '<span class="ansi-green ansi-bold">',
      '[33m' => '<span class="ansi-yellow">',
      '[36m' => '<span class="ansi-cyan">',
      '[36;1m' => '<span class="ansi-cyan ansi-bold">',
      '[37;41m' => '<span class="ansi-white ansi-bg-red">',
      '[39;49m' => '</span>',
      '[22m' => '</span>',
      '[39m' => '</span>',
      '[39;22m' => '</span>',
    ];

    // Sanitize first, then convert ansi color codes to html.
    $html = htmlspecialchars($ansi);
    $html = str_replace(array_keys($ansi_html_map), $ansi_html_map, $html);

    // Wrap html line with div tag or assign <br> if empty.
    if (!empty(trim($html))) {
      $html = "<div class='clipboard'>$html</div>";
    }
    else {
      $html = '<br>';
    }

    return $html;
  }

  /**
   * Get a list of folders within the Behat features directory.
   */
  public function folders() {
    if (!file_exists($this->featuresRootPath)) {
      return [];
    }

    $directory = new \RecursiveDirectoryIterator($this->featuresRootPath,
      \RecursiveDirectoryIterator::SKIP_DOTS);

    $iterator = new \RecursiveIteratorIterator($directory,
      \RecursiveIteratorIterator::SELF_FIRST,
      \RecursiveIteratorIterator::CATCH_GET_CHILD);

    // Construct an array containing just folders.
    $paths = ['<root>' => '<root>'];
    foreach ($iterator as $path => $dir) {
      if ($dir->isDir()) {
        $relative_path = $this->relativePath($path);
        $index = str_replace('/', '--', $relative_path);
        $paths[$index] = $relative_path;
      }
    }

    // Remove bootstrap subfolder since that contain behat php context files.
    if (($key = array_search("bootstrap", $paths)) !== NULL) {
      unset($paths[$key]);
    }

    return $paths;
  }

  /**
   * Return the subpath relative to the Behat features root path.
   */
  public function relativePath($directory) {
    $diff = strlen($directory) - strlen($this->featuresRootPath);
    if ($diff) {
      $relative_path = substr($directory, 1 - $diff);
    }
    else {
      $relative_path = '';
    }
    return $relative_path;
  }

  /**
   * A usort callback function.
   */
  public function sortCompareCallback($a, $b) {
    if ($this->sortDirection == 'asc') {
      return strcasecmp($a[$this->sortField], $b[$this->sortField]);
    }
    else {
      return strcasecmp($b[$this->sortField], $a[$this->sortField]);
    }
  }

  /**
   * Check if a feature name subpath exists.
   */
  public function featureNameExist($feature_name_subpath) {
    $features = $this->listing();
    foreach ($features as $feature) {
      if ($feature['feature_name_subpath'] == $feature_name_subpath) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
