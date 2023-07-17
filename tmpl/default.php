<?php

// Make sure we are running this file from Joomla
defined('_JEXEC') or die;

// HTMLhelper behövs för att kunna köra content-prepare på output från modulen
use Joomla\CMS\HTML\HTMLHelper;

// ModuleHelper behövs för att läsa ut modul-id-t
use Joomla\CMS\Helper\ModuleHelper;
$moduleId = $module->id;
// echo $moduleId; // DEBUG

// Se om användaren har rätt att ladda upp bilder
use Joomla\CMS\Factory;
$user = Factory::getUser();
echo $user->name; // DEBUG
$upload_permission = false; // DEFAULT
if ($user->authorise('core.edit', 'com_content')) { 
    echo " har rätt att ladda upp bilder";
    $upload_permission = true;
}

function getFileCount($path) {
    $path = "/var/www/scout/images/".$path; // TODO: Not hard coded..
    return $path;
}
//    $size = 0;
//    $ignore = array('.','..','cgi-bin','.DS_Store');
//    $files = scandir($path);
//    foreach($files as $t) {
//        if(in_array($t, $ignore)) continue;
//        if (is_dir(rtrim($path, '/') . '/' . $t)) {
//            $size += getFileCount(rtrim($path, '/') . '/' . $t);
//        } else {
//            $size++;
//        }   
//    }
//    return $size;
//}

// Depending on POST or GET or no request:
if (isset($_POST["m"]) && $moduleId == $_POST["m"] && $upload_permission == true) { 
    // USER wants to UPLOAD pictures to this module and folder and is allowed to
    $target=$_POST["g"];
    
    // Configure upload directory and allowed file types
    $upload_dir = "images/".$target."/";
    echo "Target: ".$target; // DEBUG
    $allowed_types = array('jpg', 'png', 'jpeg', 'gif'); // TODO: INTEGRATE WITH JOOMLA?!
    
    // Define maxsize for files i.e 200 MB
    $maxsize = 200 * 1024 * 1024; // TODO: GET THIS FROM PHP OR JOOMLA

    // Checks if user sent an empty form
    if(!empty(array_filter($_FILES['files']['name']))) {

        // Loop through each file in files[] array
        foreach ($_FILES['files']['tmp_name'] as $key => $value) {
            
            $file_tmpname = $_FILES['files']['tmp_name'][$key];
            $file_name = $_FILES['files']['name'][$key];
            $file_size = $_FILES['files']['size'][$key];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);

            // Set upload file path
            $filepath = $upload_dir.$file_name; 

            // Check file type is allowed or not
            if(in_array(strtolower($file_ext), $allowed_types)) {

                // Verify file size - 2MB max
                if ($file_size > $maxsize)        
                    echo "Error: File size is larger than the allowed limit.";

                // If file with name already exist then append time in
                // front of name of the file to avoid overwriting of file
                if(file_exists($filepath)) {
                    $filepath = $upload_dir.time().$file_name;
                    
                    if( move_uploaded_file($file_tmpname, $filepath)) {
                        echo "{$file_name} successfully uploaded <br />";
                    }
                    else {                    
                        echo "Error uploading {$file_name} ({$filepath})<br />";
                    }
                }
                else {
                
                    if( move_uploaded_file($file_tmpname, $filepath)) {
                        echo "{$file_name} successfully uploaded <br />";
                    }
                    else {                    
                        echo "Error uploading {$file_name} ({$filepath})<br />";
                    }
                }
            }
            else {
                
                // If file extension not valid
                echo "Error uploading {$file_name} ";
                echo "({$file_ext} file type is not allowed)<br / >";
            }
        }
    }
    else {
        
        // If no files selected
        echo "No files selected.";
    }

} else if (isset($_GET["m"]) && $moduleId == $_GET["m"]) { 
    // someone requesting to VIEW a certain folder
    $target=$_GET["g"];
} else {
    $target = '';
}
$target = urldecode($target);

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
if (($target<>'') && (strpos($target, '../') == false)) {
    $folder=$target; 
} else {
    $folder = $params->get('folder', '');
}

$scan = scandir('images/'.$folder);
$numberofFiles = 0;

$output .= "<div class='hq-wrapper'>";
// INSERT "UP" LINK IF NOT ALREADY IN ROOT
if ($folder != $params->get('folder', '')) {
    $target = dirname($folder);
    $output .= "<a href='?m=".$moduleId."&g=".$target."'><div><img src='modules/mod_hqgallerymodule/tmpl/folder.png' style='width: 200px; opacity: 50%;' /><div class='hq-folder-name'>Tillbaka till: ".basename(dirname($folder))."</div></div></a>";            
}

// LOOP FOLDERS
foreach($scan as $file) {
    if (($file !='.') && ($file != '..')) {
        if (is_dir("images/$folder/$file")) {
            $target = $folder."/".$file;
            $output .= "<a href='?m=".$moduleId."&g=".$target."'><div><img src='modules/mod_hqgallerymodule/tmpl/folder.png' style='width: 200px;' /><div class='hq-folder-name'>".$file.getFileCount($target)."</div></div></a>";
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

if ($upload_permission == true) {
    $output .= '<h3>Ladda upp bilder</h3><form action="" method="POST" enctype="multipart/form-data">
    <input type="file" name="files[]" multiple>
    <input type="submit" name="Upload" value="Upload" >
    <input type="hidden" name="g" value="'.$folder.'">
    <input type="hidden" name="m" value="'.$moduleId.'">
    </form>';
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
