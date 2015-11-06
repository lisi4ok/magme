<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Magme.php';
try {
	$magme = new Magme;
	$magme->setAdditionalAttributes(array(
		'featured',
		'child_size',
		'kids_sizes',
		'size',
		'two_sizes',
	));
	$magme->export();
} catch (\Exception $e) {
	echo $e->getMessage() . PHP_EOL;
}
echo 'Success.' . PHP_EOL;