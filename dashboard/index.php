<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="style.css">
	<title>Localisation Dashboard</title>
</head>
<body>
<?php
require_once('../ZanataPHPToolkit.php');

// Parse the ini file 
$configs = parse_ini_file('../config.ini', true);

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
		$rawStats = json_decode($zanataToolkit->getZanataCurlRequest()->getProject($projectSlug));
		
		if (empty($rawStats))
			exit(1);
		
		$projectName = $rawStats->name;
		
		echo "<h1>$projectName - $iterationSlug</h1>";
		
		$stats = $zanataToolkit->getTranslationStats();
		
		if (!empty($stats))
		{
			$total = $stats[key($stats)]['total'];
			echo <<<ECHO
			<div class="topRow">
			Total number of strings: <span class="totalText">$total</span>
			</div>
ECHO;
			
			foreach ($stats as $locale => $stat)
			{
				$translated = $stat['translated'];
				$needReview = $stat['needReview'];
				$untranslated = $stat['untranslated'];
				
				// Compute progress bar stuff
				$totalSize = 70;
				$translatedSize = $translated * $totalSize / $total;
				$needReviewSize = $needReview * $totalSize / $total;
				$untranslatedSize = $untranslated * $totalSize / $total;
				
				// Flag
				$flagName = strtolower(substr($locale, 0, 2));
				
				echo <<<ECHO
<div class="row">
					<h3>$locale <img src="flags/$flagName.gif"/></h3>
					<div class="translated" style="width:{$translatedSize}%"></div>
					<div class="needReview" style="width:{$needReviewSize}%"></div>
					<div class="untranslated" style="width:{$untranslatedSize}%"></div>
					<br><br>
					<ul>
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