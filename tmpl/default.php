<?php

defined('_JEXEC') or die;

// HTMLhelper behövs för att kunna köra content-prepare på output från modulen
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Helper\ModuleHelper;
$module = ModuleHelper::getModule('mod_hqgallerymodule');
if ($module)
{
    $moduleId = $module->id;
    // echo $moduleId; // TEST
}

// See if this page was initiated by someone requesting a certain folder
if (isset($_GET["moduleid"]) && $moduleId == $_GET["moduleid"]) {
    $target=$_GET["target"];
} else {
    $target = '';
}

// Initiate the output variable
$output = "";

// Set the style for folder listing
$output .= "<style>
.hq-wrapper {
  display: grid;
  grid-template-columns: repeat( auto-fit, minmax(250px, 1fr) );
  grid-auto-rows: 200px;
}
.hq-folder-name {
    margin-left: 20px;
  }
</style>";

// Retrieve the value of the "prepare_content" parameter
$prepareContent = $params->get('prepare_content', 0);

// Get target folder from parameters to the page or default to module parameters
if ($target<>'') $folder=$target; else $folder = $params->get('folder', '');

$scan = scandir('images/'.$folder);
$numberofFiles = 0;

$output .= "<div class='hq-wrapper'>";
// INSERT "UP" LINK IF NOT ALREADY IN ROOT
if ($folder != $params->get('folder', '')) {
    $target = dirname($folder);
    $output .= "<a href='?moduleid=".$moduleId."&target=".$target."'><div><img src='modules/mod_hqgallerymodule/tmpl/folder.png' style='width: 200px; opacity: 50%;' /><div class='hq-folder-name'>Tillbaka till: ".basename(dirname($folder))."</div></div></a>";            
}

// LOOP FOLDERS
foreach($scan as $file) {
    if (($file !='.') && ($file != '..')) {
        if (is_dir("images/$folder/$file")) {
            $target = $folder."/".$file;
            $output .= "<a href='?moduleid=".$moduleId."&target=".$target."'><div><img src='modules/mod_hqgallerymodule/tmpl/folder.png' style='width: 200px;' /><div class='hq-folder-name'>".$file."</div></div></a>";
        } else {
            $numberofFiles = $numberofFiles +1;
        }
    }
}
$output .= "</div>";

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