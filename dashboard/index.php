<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="style.css">
	<title>Localisation Dashboard</title>
</head>
<body>
<?php
require_once('ZanataPHPToolkit.php');

// Parse the ini file 
$configs = parse_ini_file('config.ini', true);

$zanataUrl = $configs['Zanata']['url'];
$user = $configs['Zanata']['user'];
$apiKey = $configs['Zanata']['api_key'];

foreach ($configs as $key => $config)
{
	if ($key !== 'Zanata')
	{
		$projectSlug = $config['project_slug'];
		$iterationSlug = $config['iteration_slug'];	
		
		$zanataToolkit = new ZanataPHPToolkit($user, $apiKey, $projectSlug, $iterationSlug, $zanataUrl);
		
		// Retrieve project name
		$projectName = json_decode($zanataToolkit->getZanataCurlRequest()->getProject($projectSlug))->name;
		
		echo "<h1>$projectName - $iterationSlug</h1>";
		
		$stats = $zanataToolkit->getTranslationStats();
		
		if (!empty($stats))
		{
			foreach ($stats as $locale => $stat)
			{
				$total = $stat['total'];
				$translated = $stat['translated'];
				$needReview = $stat['needReview'];
				$untranslated = $stat['untranslated'];
				
				// Compute progress bar stuff
				$totalSize = 100;
				$translatedSize = $translated * $totalSize / $total;
				$needReviewSize = $needReview * $totalSize / $total;
				$untranslatedSize = $untranslated * $totalSize / $total;
				
				echo <<<ECHO
				<div class="row">
					<h3>$locale</h3>
					<div class="translated" style="width:{$translatedSize}px"></div>
					<div class="needReview" style="width:{$needReviewSize}px"></div>
					<div class="untranslated" style="width:{$untranslatedSize}px"></div>
					<br><br>
					<ul>
						<li>Total: <span class="totalText">$total</span></li>
						<li>Translated: <span class="translatedText">$translated</span></li>
						<li>Need review: <span class="needReviewText">$needReview</span></li>
						<li>Untranslated: <span class="untranslatedText">$untranslated</span></li>
						<li>Last translated: $stat[lastTranslated]</li>
					</ul>
				</div>
ECHO;
			}
		}


		
	}
}
?>
</body>
</html>