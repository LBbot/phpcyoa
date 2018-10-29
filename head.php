<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $page_title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="styles.css" />
</head>
<body>
<div class = "container">
    <h1>PHP CYOA</h1>

    <!-- If any errors: show them -->
    <?php
    if (!empty($input_error_array)) {
        foreach ($input_error_array as $single_error) {
            echo "<h3>$single_error</h3>";
        }
    }
    ?>
