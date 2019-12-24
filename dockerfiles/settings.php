<?php

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\Database\Database;

require_once __DIR__ . '/settings.k8s.php';

$settings['container_yamls'][] = __DIR__ . '/services.yml';

$settings['allow_authorize_operations'] = FALSE;

$databases['default']['default'] = array(
  'driver' => 'mysql',
  'database' => k8s_config('mysql.database') ?: 'local',
  'username' => k8s_config('mysql.username') ?: 'drupal',
  'password' => k8s_config('mysql.password') ?: 'drupal',
  'host' => k8s_config('mysql.hostname') ?: '127.0.0.1',
);

$config['cron_safe_threshold'] = '0';
$settings['file_public_path'] = 'sites/default/files';
$config['system.file']['path']['temporary'] = '/mnt/temporary';
$settings['file_private_path'] = '/mnt/private';

$settings['hash_salt'] = !empty($settings['hash_salt']) ? $settings['hash_salt'] : 'xxxxxxxxxxxxxxxxxxxx';

$settings['trusted_host_patterns'][] = '^127\.0\.0\.1$';