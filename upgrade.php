<?php

exec('git tag', $tags);


$opts = [
  'http' => [
    'method' => 'GET',
	'header' => [
       'User-Agent: PHP, cebe/assetfree-yii2 updater',
	]
  ]
];
$context = stream_context_create($opts);
$yiiTagsInfo = json_decode(file_get_contents('https://api.github.com/repos/yiisoft/yii2/git/matching-refs/tags/?per_page=100', false, $context), true);
$yiiTags = [];
foreach($yiiTagsInfo as $tagInfo) {
		if (strpos($tagInfo['ref'], 'refs/tags/2.0') === 0
			&& strpos($tagInfo['ref'], 'alpha') === false
			&& strpos($tagInfo['ref'], 'beta') === false
			&& strpos($tagInfo['ref'], 'rc') === false
		) {
			$yiiTags[] = substr($tagInfo['ref'], 10);

		}
}

$missingTags = array_diff($yiiTags, $tags);
foreach($missingTags as $tag) {
		echo "update composer for $tag...";
		updateComposer("=$tag");
		echo "done.\n";
		echo "creating tag for $tag...\n";
		createTag($tag);
		echo "done.\n";
}

echo "reset master...\n";
updateComposer("dev-master");
resetMaster();
echo "done.\n";

function updateComposer($tag)
{
		$file = __DIR__ . '/composer.json';
		$content = file_get_contents($file);
		$content = preg_replace('~"yiisoft/yii2": "[^"]+"~i', '"yiisoft/yii2": "'.$tag.'"', $content);
		if (empty($content)) {
				echo 'failed to update composer.json';
				exit(1);
	   	}
		file_put_contents($file, $content);
}

function createTag($tag)
{
	$cmd = "git add composer.json";
	echo "$cmd\n";
	exec($cmd);
	$cmd = "git commit -m 'Version $tag'";
	echo "$cmd\n";
	exec($cmd);
	$cmd = "git tag -s $tag -m 'Version $tag'";
	echo "$cmd\n";
	exec($cmd);
}

function resetMaster()
{
	$cmd = "git add composer.json";
	echo "$cmd\n";
	exec($cmd);
	$cmd = "git commit -m 'Require dev-master on master'";
	echo "$cmd\n";
	exec($cmd);
}
