<?php

defined('_JEXEC') or die;

// Access to module parameters
$folder = $params->get('folder', '');
echo "Folder: " .$folder;

$scan = scandir('images/'.$folder);
$numberofFiles = 0;

// PRINT FOLDERS
foreach($scan as $file) {
   if (is_dir("images/$folder/$file")) {
      echo $file.', ';
   } else {
    $numberofFiles = $numberofFiles +1;
   }
}

if ($numberofFiles > 0) {
    echo "Det finns också: ". $numberofFiles. " filer.";
} else {
    echo "Det finns inga filer i mappen.";
}
?>