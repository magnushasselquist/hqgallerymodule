<?php

defined('_JEXEC') or die;

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
    $output .= $file.', ';
   } else {
    $numberofFiles = $numberofFiles +1;
   }
}

if ($numberofFiles > 0) {
    // echo "Det finns också: ". $numberofFiles. " filer.";
    $output .= "{gallery}".$folder."{/gallery}";
} else {
    $output .= echo "Det finns inga filer i mappen.";
}

// Conditionally prepare the content if the switch is enabled
if ($prepareContent == 1) {
  // Get the application instance
  $app = Joomla\CMS\Factory::getApplication();

  // Prepare the content using Joomla's content preparation methods
  use Joomla\CMS\HTML\HTMLHelper;
  $output = HTMLHelper::_('content.prepare', $output, '', 'mod_hqgallerymodule');
}

// Finally, print the result
echo $output;
?>