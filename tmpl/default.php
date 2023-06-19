<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

// Prepare the output
$output = "";

// Retrieve the value of the "prepare_content" parameter
$prepareContent = $params->get('prepare_content', 0);

// Access to module parameters
$folder = $params->get('folder', '');
// $output .= "Folder: " .$folder;

$scan = scandir('images/'.$folder);
$numberofFiles = 0;

// PRINT FOLDERS
foreach($scan as $file) {
   if (is_dir("images/$folder/$file")) {
    // $output .= $file.', ';
    $output .= "<a href='#&folder=".$folder/$file."'><img src='folder.png' />".$file."</a>";
   } else {
    $numberofFiles = $numberofFiles +1;
   }
}

if ($numberofFiles > 0) {
    // echo "Det finns också: ". $numberofFiles. " filer.";
    // TODO: Put start- and end-TAG in configuration instead of hard coding
    $output .= "{gallery}".$folder."{/gallery}";
} else {
    // $output .= "Det finns inga filer i mappen.";
}

// Conditionally prepare the content if the switch is enabled
if ($prepareContent == 1) {
  // Get the application instance
  $app = Joomla\CMS\Factory::getApplication();

  // Prepare the content using Joomla's content preparation methods
  $output = HTMLHelper::_('content.prepare', $output, '', 'mod_hqgallerymodule');
}

// Finally, print the result
echo $output;
?>