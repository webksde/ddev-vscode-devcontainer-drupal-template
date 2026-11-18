#!/usr/bin/env php
<?php

/**
 * @file
 * Prepares a .cspell.json file customized for the gitlab templates environment.
 *
 * Arguments:
 *
 *   -s --suffix    Optional suffix to append to the input filename before
 *                  writing out. Useful when running locally during development
 *                  to avoid overwriting the input .cspell.json file.
 *
 *   -v --verbose   Show verbose debug output. This can also be enabled by
 *                  setting environment variable SCRIPT_VERBOSE to 1
 */

// Get the arguments.
$options = getopt('s:v', ['suffix:', 'verbose']);
$verbose = array_key_exists('v', $options) || array_key_exists('verbose', $options) || getenv('SCRIPT_VERBOSE') == '1';
$suffix = $options['s'] ?? $options['suffix'] ?? '';
$verbose && print '$suffix=' . $suffix . PHP_EOL;

// Get the contents of .cspell.json into an array. This file will be either the
// projects own .cspell.json or the default copied from /assets.
$cspell_filename = '.cspell.json';
$cspell_json = json_decode(file_get_contents($cspell_filename), TRUE);
if (empty($cspell_json)) {
  throw new RuntimeException("Unable to read $cspell_filename");
}
$verbose && print 'At start cspell_json=' . print_r($cspell_json, TRUE) . PHP_EOL;

// Some directories in the project root are not part of the project.
$webRoot = getenv('_WEB_ROOT') ?: 'web';
$non_project_directories = ["$webRoot", 'vendor', 'node_modules', '.git', 'recipes'];

if (($composer_bin_dir = getenv('COMPOSER_BIN_DIR')) && ($ci_project_dir = getenv('CI_PROJECT_DIR'))) {
  $relative_bin_dir = str_replace("$ci_project_dir/", "", $composer_bin_dir);
  $non_project_directories[] = $relative_bin_dir;
  $verbose && print '$ci_project_dir=' . $ci_project_dir . PHP_EOL;
  $verbose && print '$composer_bin_dir=' . $composer_bin_dir . PHP_EOL;
  $verbose && print '$relative_bin_dir=' . $relative_bin_dir . PHP_EOL;
}

// Specify the files that are always ignored.
$ignore_files = [
  // *.patch will match files in the top level and all sub-directories.
  '*.patch',
  '*.svg',
  '*_ignore_*',
  'build.env',
  'get-file-via-curl.sh',
  // Include the artifact files that are created in the cspell job.
  '_cspell_unrecognized_words.txt',
  '_cspell_updated_project_words.txt',
];
$verbose && print 'Initial $ignore_files=' . print_r($ignore_files, TRUE) . PHP_EOL;

// Specify the standard files to ignore if _CSPELL_IGNORE_STANDARD_FILES=1.
$ignore_standard_files = [
  '**/.*.json',
  'package.json',
  'yarn.lock',
  'phpstan*',
  '.*ignore',
];

// This list of files is also ignored when _CSPELL_IGNORE_STANDARD_FILES=1 but
// we need to find the actual case-sensitive names for these.
$filenames_to_find = [
  'license',
  'copyright',
  'maintainers',
  'changelog',
  'composer',
];

// -----
// Words
// -----
//
// Get the words from $_CSPELL_WORDS.
if ($cspell_words = getenv('_CSPELL_WORDS')) {
  $verbose && print 'Initial $cspell_words=' . $cspell_words . PHP_EOL;
  // Remove all double quotes and spaces.
  $words = str_replace(['"', ' '], ['', ''], $cspell_words);
  // Remove single quotes from the very start or very end of the string.
  $words = preg_replace("/^(')|(')$/n", '', $words);
  // Replace ,' and ', with just a , to remove quotes from around words but not
  // from the middle of a word.
  $words = str_replace([",'", "',"], [',', ','], $words);
  $verbose && print 'Modified $words=' . $words . PHP_EOL;
}

// The module's machine name might not be a real word, so add this. The value of
// $CI_PROJECT_NAME can end -nnnnnnn so remove this part.
$module_name_parts = explode('_', stristr(getenv('CI_PROJECT_NAME') . '-', '-', TRUE));
// Also find of all the sub-modules by looking for .info.yml files, and add each
// name part to the allowed list.
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
  // Ignore everything in these folders.
  foreach ($non_project_directories as $dir) {
    if (stripos($file, "./$dir/") !== FALSE) {
      continue 2;
    }
  }
  if (stripos($file->getFilename(), '.info.yml') !== FALSE) {
    // Get each underscore-separated part of the module name.
    $module_name_parts = array_merge(
      $module_name_parts,
      explode('_', str_replace('.info.yml', '', $file->getFilename())),
    );
  }
  // Identify files with a name in this list, regardless of case or extension.
  // This is not part of the 'Words' section, but it is convenient to use this
  // foreach loop, instead of traversing the directory a second time in the
  // 'Ignore paths' section.
  $filename_without_ext = strtolower(substr($file->getFilename(), 0, strpos($file->getFilename(), '.')));
  if (in_array($filename_without_ext, $filenames_to_find)) {
    $ignore_standard_files[] = $file->getPathname();
  }
}
$verbose && print '$module_name_parts=' . print_r($module_name_parts, TRUE) . PHP_EOL;

// Merge into the existing json 'words' value, but cater for that being empty.
// array_values() is needed after array_unique() to restore the keys to numeric.
$cspell_json['words'] = array_values(array_filter(array_unique(array_merge(
  $cspell_json['words'] ?? [],
  array_filter(explode(',', $words ?? '')),
  $module_name_parts,
  // Add lando and ddev to cater for references to local docker integrations.
  ['lando', 'ddev'],
  // Add some common words that were dropped from core dictionaries.
  // See https://www.drupal.org/project/gitlab_templates/issues/3494834
  ['endapply', 'nightwatchjs', 'testgroup', 'testgroups', 'backlinks', 'mdhash', 'uploaders', 'vocabs'],
  // Add Curl options and constants dropped from core dictionaries.
  ['curlopt', 'returntransfer', 'curle', 'customrequest', 'failonerror', 'postfields', 'followlocation'],
  // Add six phpcs sniff names/error codes that cannot be corrected.
  // See https://www.drupal.org/i/3418190
  [
    'colourdefinitionsniff', 'seletorsingleline', 'teamplatespacingaftercomment', 'tforvalue', 'trhowscommentindentation',
    'unecessaryfiledeclaration',
  ],
  // Add the alternative suggestion for a flagged word that is not in the
  // pre-configured dictionaries.
  ['blocklisted'],
  // Add words used in environment variable names.
  [
    'flagwords', 'mkdocs', 'autorun', 'runtests', 'dcms', 'browsertest', 'chromedriver', 'dockerhub', 'drupalorg',
    'drush', 'simpletest', 'minwordlength', 'iid', 'xdg',
  ],
))));
$verbose && print '$cspell_json[\'words\']=' . print_r($cspell_json['words'], TRUE) . PHP_EOL;

// ----------
// Flag Words
// ----------
//
// Give an advisory message if the .cspell.json does not contain all the default
// flagged words. This will only happen if the project has a custom file.
// cspell:disable
$default_flagwords = ['blacklist', 'blacklists', 'blacklisted', 'e-mail',
  'grey', 'queuing', 'whitelist', 'whitelists', 'whitelisted',
];
// cspell:enable
$project_flagwords = $cspell_json['flagWords'] ?? [];
// Flagged words can contain suggestions denoted by '->' or ':' syntax which we
// do not want. Actual words can contain '-' such as 'e-mail' which must remain
// unchanged. Therefore change all '->' into ':' before splitting on ':'.
array_walk($project_flagwords, function (&$value) {
  $value = strtok(str_replace('->', ':', $value), ':');
});
$verbose && print '$project_flagwords=' . print_r($project_flagwords, TRUE);
if ($missing_flagwords = array_diff($default_flagwords, $project_flagwords)) {
  $divider = str_repeat('*', 120);
  print "$divider\n[WARNING] This project's CSpell configuration does not contain all of the default flagged words. The missing words are:\n \n  "
    . implode(', ', $missing_flagwords) . "\n \nIt is recommended to add these words to your custom .cspell.json file, or remove that file to use the default.\n"
    . "See change record https://www.drupal.org/node/3524446 for more details.\n$divider\n \n";
}

// Get any flagged words from $_CSPELL_FLAGWORDS.
if ($cspell_flagwords = getenv('_CSPELL_FLAGWORDS')) {
  $verbose && print 'Input $cspell_flagwords=' . $cspell_flagwords . PHP_EOL;
  // Remove all double quotes and spaces.
  $cspell_flagwords = str_replace(['"', ' '], ['', ''], $cspell_flagwords);
  // Remove single quotes from the very start or very end of the string.
  $cspell_flagwords = preg_replace("/^(')|(')$/n", '', $cspell_flagwords);
  // Replace ,' and ', with just a , to remove quotes from around words but not
  // from the middle of a word.
  $cspell_flagwords_array = explode(',', str_replace([",'", "',"], [',', ','], $cspell_flagwords));
  $verbose && print '$cspell_flagwords_array=' . print_r($cspell_flagwords_array, TRUE) . PHP_EOL;

  // Words starting with '-' are to be removed from the final flagwords list.
  $flagwords_to_remove = preg_grep('/^-/', $cspell_flagwords_array);
  $flagwords_to_add = array_diff($cspell_flagwords_array, $flagwords_to_remove);
  // Delete the leading '-' to give just the actual word.
  array_walk($flagwords_to_remove, function (&$value) {
    $value = substr($value, 1);
  });
  $verbose && print '$flagwords_to_add=' . print_r($flagwords_to_add, TRUE) . PHP_EOL
    . '$flagwords_to_remove=' . print_r($flagwords_to_remove, TRUE) . PHP_EOL;

  $cspell_json['flagWords'] = array_values(array_unique(array_merge(
    // Remove the unwanted words.
    array_diff($cspell_json['flagWords'] ?? [], $flagwords_to_remove),
    // Add the new words.
    $flagwords_to_add,
  )));
  $verbose && print '$cspell_json[\'flagWords\']=' . print_r($cspell_json['flagWords'], TRUE) . PHP_EOL;
}

// -------------------
// Minimum word length
// -------------------
//
// The default minimum length of words to check is 4.
if ($minwordlength = getenv('_CSPELL_MINWORDLENGTH')) {
  $verbose && print '$minwordlength=' . $minwordlength . PHP_EOL;
  $cspell_json['minWordLength'] = $minwordlength;
}

// ------------
// Ignore Paths
// ------------
//
// Ignore the paths in the project root folder that are not part of the project.
$paths = $non_project_directories;

// Add the paths that are always ignored.
$paths = array_merge($paths, $ignore_files);

// Add these commonly ignored paths and files if required.
$cspell_ignore_standard_files = getenv('_CSPELL_IGNORE_STANDARD_FILES');
if ("$cspell_ignore_standard_files" != "0") {
  $paths = array_merge($paths, $ignore_standard_files);
}
$verbose && print '$paths=' . print_r($paths, TRUE) . PHP_EOL;

// Add the values from $_CSPELL_IGNORE_PATHS, removing quotes and spaces.
if ($cspell_ignore_paths = getenv('_CSPELL_IGNORE_PATHS')) {
  $cspell_ignore_paths = str_replace(["'", '"', ' '], ['', '', ''], $cspell_ignore_paths);
}
$cspell_json['ignorePaths'] = array_values(array_unique(array_merge(
  $cspell_json['ignorePaths'] ?? [],
  $paths,
  $cspell_ignore_paths ? explode(',', $cspell_ignore_paths) : [],
)));

// ------------
// Dictionaries
// ------------
$dictionary_definitions = [
  [
    'name' => 'drupal',
    'path' => $webRoot . '/core/misc/cspell/drupal-dictionary.txt',
  ],
  [
    'name' => 'dictionary',
    'path' => $webRoot . '/core/misc/cspell/dictionary.txt',
  ],
];
if ($project_dictionary = getenv('_CSPELL_DICTIONARY')) {
  $verbose && print '$project_dictionary=' . $project_dictionary . PHP_EOL;
  $dictionary_definitions[] = [
    'name' => 'project-words',
    'path' => './' . $project_dictionary,
    'description' => "The project's own custom dictionary (optional)",
  ];
}
$verbose && print 'Initial $dictionary_definitions=' . print_r($dictionary_definitions, TRUE) . PHP_EOL;

$dictionary_names = [];
foreach ($dictionary_definitions as $key => $data) {
  // Add the 'name' if the file exists. Remove the array entry if it does not.
  if (file_exists($data['path'])) {
    $dictionary_names[] = $data['name'];
  }
  else {
    unset($dictionary_definitions[$key]);
  }
}
$verbose && print 'After checking files, $dictionary_definitions=' . print_r($dictionary_definitions, TRUE) . PHP_EOL;

// These dictionaries are provided by CSpell.
$built_in_dictionaries = [
  'companies',
  'fonts',
  'html',
  'php',
  'softwareTerms',
  'misc',
  'typescript',
  'node',
  'css',
  'bash',
  'filetypes',
  'npm',
  'lorem-ipsum',
  'sql',
  'fullstack',
];
$cspell_json['dictionaries'] = array_values(array_unique(array_merge(
  $cspell_json['dictionaries'] ?? [],
  $built_in_dictionaries,
  $dictionary_names,
)));

// Remove any matching entries so that the updated versions take precedence.
foreach ($cspell_json['dictionaryDefinitions'] ?? [] as $key => $dictionary) {
  if (in_array($dictionary['name'], $dictionary_names)) {
    unset($cspell_json['dictionaryDefinitions'][$key]);
  }
}
$cspell_json['dictionaryDefinitions'] = merge_deep($dictionary_definitions, $cspell_json['dictionaryDefinitions'] ?? []);
$verbose && print '$cspell_json[\'dictionaryDefinitions\']=' . print_r($cspell_json['dictionaryDefinitions'], TRUE) . PHP_EOL;

// ---------------------------
// Write out the modified file
// ---------------------------
// Allow for easy testing by avoiding overwriting the input file.
$cspell_filename .= $suffix;
$verbose && print 'At end $cspell_json=' . print_r($cspell_json, TRUE) . PHP_EOL;
print "Writing json array to {$cspell_filename}" . PHP_EOL;
file_put_contents($cspell_filename, json_encode($cspell_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

/**
 * Deeply merges arrays. Borrowed from Drupal core.
 */
function merge_deep(): array {
  return merge_deep_array(func_get_args());
}

/**
 * Deeply merges arrays. Borrowed from drupal.org/project/core.
 *
 * @param array $arrays
 *   An array of array that will be merged.
 * @param bool $preserve_integer_keys
 *   Whether to preserve integer keys.
 */
function merge_deep_array(array $arrays, bool $preserve_integer_keys = FALSE): array {
  $result = [];
  foreach ($arrays as $array) {
    foreach ($array as $key => $value) {
      if (is_int($key) && !$preserve_integer_keys) {
        $result[] = $value;
      }
      elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
        $result[$key] = merge_deep_array([$result[$key], $value], $preserve_integer_keys);
      }
      else {
        $result[$key] = $value;
      }
    }
  }
  return $result;
}
