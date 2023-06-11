<?php

defined('_JEXEC') or die;

// Access to module parameters
$folder = $params->get('folder', '');
echo "Folder: " .$folder;

/*
$scan = scandir('images/'.$folder);
foreach($scan as $file) {
   if (is_dir("images/$folder/$file")) {
      echo $file.', ';
   }
}
*/

?>