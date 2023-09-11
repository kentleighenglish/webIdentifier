<?php

require_once(__DIR__. "/main.php");

$fakeId = "123e4567-e89b-12d3-a456-426655440000";

$secret = "delicious";

$main1 = new WebIdentifier\WebIdentifier($secret, [
	"mode" => "text"
]);

$main2 = new WebIdentifier\WebIdentifier($secret, [
	"mode" => "image",
	"imageSize" => 1000,
	"baseColour" => "8899FF"
]);

$out1 = $main1->generate($fakeId, "Here is some text");

$out2 = $main2->generate($fakeId);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Web Identifier</title>
		<link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura.css" type="text/css">
	</head>
	<body>
		<p><?php echo $out1; ?></p>

		<img src="<?php echo $out2; ?>" />
	</body>
</html>