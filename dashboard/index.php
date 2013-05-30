<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="style.css">
	<title>Localisation Dashboard</title>
	<link href="images/favicon.ico" rel="icon" type="image/x-icon" />
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
		
		// POT file download link
		$potFileLink = $zanataToolkit->getZanataCurlRequest()->getZanataApiUrl()->fileService($projectSlug, $iterationSlug, 'pot', $key);
		
		if (!empty($stats))
		{
			$total = $stats[key($stats)]['total'];
			echo <<<ECHO
			<div class="topRow">
			<p>
			Total number of strings: <span class="totalText">$total</span><br>
			<a href="$potFileLink">Get POT file</a>
			</p>
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
				
				// PO file link
				$poFileLink = $zanataToolkit->getZanataCurlRequest()->getZanataApiUrl()->fileService($projectSlug, $iterationSlug, 'po', $key, $locale);
				
				echo <<<ECHO
<div class="row">
					<h3>$locale <img src="images/flags/$flagName.gif"/></h3>
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
					<div class="button"><a href="$poFileLink">Get PO file</a></div>
				</div>
ECHO;
			}
		}


		
	}
}
?>
</body>
</html>