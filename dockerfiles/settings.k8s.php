<?php

/**
 * @file
 * Contains Kubernetes helper functions.
 */

/**
 * Gets config.
 *
 * @param string $key
 *   The config key.
 *
 * @return bool|string
 *   The config value, or FALSE if not found.
 */
function k8s_config($key) {
  static $confs;

  if (empty($confs)) {
    $filename = '/etc/drupal/config.json';

    if (!file_exists($filename)) {
      return FALSE;
    }

    clearstatcache(TRUE, $filename);
    $confs = json_decode(file_get_contents($filename), TRUE);
  }

  return !empty($confs[$key]) ? $confs[$key] : FALSE;
}