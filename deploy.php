<?php

namespace Deployer;

require 'recipe/common.php';

set('application', 'public-transport-app');
set('user', 'deployer');
set('repository', 'https://github.com/Mistrfilda/public-transport-app.git');
set('deploy_path', '/var/www/deployer/public-transport-app');
set('shared_dirs', ['log']);
set('shared_files', ['config/config.local.neon']);
set('writable_dirs', ['log', 'temp']);
set('copy_dirs', ['node_modules', 'vendor']);
set('keep_releases', 3);

localhost('localhost')
	->user('deployer');

task('deploy:build', function() {
	cd('{{release_path}}');

	run('composer install -o');
	run('yarn install', ['timeout' => 1000]);

	run('composer deploy-prod');
});

task('deploy', [
	'deploy:info',
	'deploy:prepare',
	'deploy:lock',
	'deploy:release',
	'deploy:update_code',
	'deploy:shared',
	'deploy:writable',
	'deploy:copy_dirs',
	'deploy:build',
	'deploy:clear_paths',
	'deploy:symlink',
	'deploy:unlock',
	'cleanup',
	'success'
]);

after('deploy:failed', 'deploy:unlock');