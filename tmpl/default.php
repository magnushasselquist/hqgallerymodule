<?php

// Make sure we are running this file from Joomla
defined('_JEXEC') or die;

// HTMLhelper behövs för att kunna köra content-prepare på output från modulen
use Joomla\CMS\HTML\HTMLHelper;

// ModuleHelper behövs för att läsa ut modul-id-t
use Joomla\CMS\Helper\ModuleHelper;
$moduleId = $module->id;
// echo $moduleId; // DEBUG

// Initiate the output variable
$output = "";

// Se om användaren har rätt att ladda upp bilder
use Joomla\CMS\Factory;
$user = Factory::getUser();
// echo $user->name; // DEBUG
$upload_permission = false; // DEFAULT
if ($user->authorise('core.edit', 'com_content')) {
    // användaren har rätt att ladda upp bilder
    $upload_permission = true;
}

if (!function_exists('getThumb')) {
    function getThumb($realFolder) {
        $thumb = scandir($realFolder)[3] ; // Find a thumbnail from directory to use TODO: GET FROM SUBFOLDERS ALSO..
        if ($thumb <> '') {
            $thumb = '/images/'.$target."/".$thumb;
        } else {
            $thumb = "/modules/mod_hqgallerymodule/tmpl/folder.png";
        }
        $thumb = "/modules/mod_hqgallerymodule/tmpl/folder.png"; // TODO: FIX: OVERRIDE FOR NOW.
        return $thumb;
    }
}

if (!function_exists('numberOfFiles'))   {
    function numberOfFiles($rootDir) {
        $fileCount = 0;
        $dirIterator = new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                // Check if the file name is "index.html" and skip counting it
                if ($file->getBasename() === 'index.html') {
                    continue;
                }            
                $fileCount++;
            } else {
                // Check if the directory name is "thumbs" and skip counting its files
                if (($file->getBasename() === 'thumbs') OR ($file->getBasename() === 'resizedimages')) {
                    $iterator->next();
                }
            }
        }

        return $fileCount;
    }
}

if (!function_exists('scan_dir'))   {  
    function scan_dir($dir, $order = 0) {
        $files = array();    
        foreach (scandir($dir) as $file) {
            $files[$file] = fileatime($dir . '/' . $file);
        }
        if ($order == 0) asort($files); // SORT ASC
        else if ($order == 1) arsort($files); // SORT DESC
        $files = array_keys($files);
        return $files;
    }
}  

// CHECK USER INTENT
if (isset($_POST["q"]) && $_POST["q"] == 'upload' && isset($_POST["m"]) && $moduleId == $_POST["m"] && $upload_permission == true) { 
    // USER wants to UPLOAD pictures to this module and folder and is allowed to
    $target=$_POST["g"];
    
    // Configure upload directory and allowed file types
    $upload_dir = "images/".$target."/";
    // echo "Target: ".$target; // DEBUG
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
                    echo "<div class='cmj_error'>Error: File size is larger than the allowed limit.</div>";

                // If file with name already exist then append time in
                // front of name of the file to avoid overwriting of file
                if(file_exists($filepath)) {
                    $filepath = $upload_dir.time().$file_name;
                    
                    if( move_uploaded_file($file_tmpname, $filepath)) {
                        echo "<div class='cmj_success'>{$file_name} successfully uploaded </div>";
                    }
                    else {                    
                        echo "<div class='cmj_error'>Error uploading {$file_name} ({$filepath})</div>";
                    }
                }
                else {
                
                    if( move_uploaded_file($file_tmpname, $filepath)) {
                        echo "<div class='cmj_success'>{$file_name} successfully uploaded</div>";
                    }
                    else {                    
                        echo "<div class='cmj_error'>Error uploading {$file_name} ({$filepath})</div>";
                    }
                }
            }
            else {
                
                // If file extension not valid
                echo "<div class='cmj_error'>Error uploading {$file_name} ({$file_ext} file type is not allowed)</div>>";
            }
        }
    }
    else {
        
        // If no files selected
        echo "<div class='cmj_error'>No files selected.</div>";
    }

} else if (isset($_POST["q"]) && $_POST["q"] == 'new_folder' && isset($_POST["m"]) && $moduleId == $_POST["m"] && $upload_permission == true) { 
    // USER WANTS TO CREATE A FOLDER
    $target=$_POST["g"];
    if ((strpos($target, '../') == false) && (strpos($_POST['new_folder'], '/') == false)) {}
    $new_folder = 'images/'.$target.'/'.$_POST['new_folder'];
    // echo "new_folder: ".$new_folder; //debug
    if ($_POST['new_folder'] == '') {
        echo "<div class='cmj_error'>Please specify a folder name.</div>";
    } else {
        if (is_dir($new_folder)) {
            echo "<div class='cmj_error'>Folder already exists.</div>";
        } else {
            mkdir($new_folder, 0755, false);
            file_put_contents($new_folder.'index.html', ''); // Also create an empty index.html file
            if (is_dir($new_folder)) echo "<div class='cmj_success'>Folder created.</div>";
            else echo "<div class='cmj_error'>Could not create folder.</div>";
        } 
    }
} else if (isset($_GET["m"]) && ($moduleId == $_GET["m"] OR $_GET["m"] =='')) { 
    // USER WANTS TO VIEW A FOLDER IN THIS MODULE (OR WAS DIRECTED TO THIS URL WITHOUT SPECIFIC MODULEID)
    $target = $_GET["g"];

} else {
    $target = '';
}
$target = urldecode($target);

// Set the style for folder listing
$output .= "<style>
.hq-wrapper {
    display: grid;
    grid-template-columns: repeat( auto-fit, minmax(200px, 0fr) );
    grid-auto-rows: 150px;
    word-wrap: break-word;
}
.hq-folder-name {
    position: relative;
    bottom: 65px;
    left: 25px;
    font-weight: bold;
    color: black;
}
div.cmj_success, div.cmj_error {
    border: 1px solid;
    margin: 15px;
    padding: 10px;
} 
div.cmj_error  {
    color: #D8000C;
    background-color: #FFBABA;  
}  
div.cmj_success {
    color: #4F8A10;
    background-color: #DFF2BF; 
}  
</style>";

// Retrieve the value of the "prepare_content" parameter
$prepareContent = $params->get('prepare_content', 1);

// Retrieve the value of the "show_header" parameter
$showHeader = $params->get('show_header', 1);

// Retrieve the value of the "show_images" parameter
$showImages = $params->get('show_images', 1);

// Retrieve the value of the "show_videos" parameter
$showVideos = $params->get('show_videos', 1);

// Retrieve the value of the "limit_folders" parameter
$limitFolders = $params->get('limit_folders', 0);

// Retrieve the value of the "gallery_url" parameter
$gallery_url = $params->get('gallery_url', '');
if ($gallery_url <>'') {
    if (substr($gallery_url, -1) != '/') $gallery_url = $gallery_url .'/'; // add trailing / to URL 
    $moduleId = ''; // Set moduleID to '' to enable recieving URL to display wanted target
}

// Retrieve the value of the "folder_sorting" parameter
$folderSorting = $params->get('folder_sorting', 0);
// 0 : Alphabetically ASC
// 1 : Alphabetically DESC
// 2 : Date added ASC
// 3 : Date added DESC

// Get target folder from parameters to the page or default to module parameters
// First some sanity check 
if (($target<>'') && (strpos($target, '../') == false) && (strpos($target, '/thumbs') == false) && (strpos($target, '/resizedimages') == false)) {
    $folder = $target; 
} else {
    $folder = $params->get('folder', '');
}

// print the folder name as header
if ($showHeader == 1) {
    $output .= "<h2>".basename($folder)."</h2>";
    $output .= "<p>".dirname($folder)."</p>";
}

if ($folderSorting == 0) $scan = scandir('images/'.$folder); 
else if ($folderSorting == 1) $scan = scandir('images/'.$folder, 1); 
else if ($folderSorting == 2) $scan = scan_dir('images/'.$folder, 0);
else if ($folderSorting == 3) $scan = scan_dir('images/'.$folder, 1);

$numberofImages = 0;

$output .= "<div class='hq-wrapper'>";
// INSERT "UP" LINK IF NOT ALREADY IN ROOT
if ($folder != $params->get('folder', '')) {
    $target = dirname($folder);
    $output .= "<a href='?m=".$moduleId."&g=".$target."'><div><img src='/modules/mod_hqgallerymodule/tmpl/folder.png' style='width: 200px; opacity: 50%;' /><div class='hq-folder-name'>Tillbaka till:<br />".basename(dirname($folder))."</div></div></a>";            
}

// FIND AND ECHO FOLDERS
$countDir = 0;
foreach($scan as $file) {
    if (($file !='.') && ($file != '..') && ($file != 'thumbs') && ($file != 'resizedimages') && ($file != 'index.html')) {
        if (is_dir("images/$folder/$file")) {
            $countDir = $countDir + 1;
            $target = $folder."/".$file;
            $path = $_SERVER['DOCUMENT_ROOT'] . "/images/" . $target;
            $count = numberOfFiles($path);
            $thumb = getThumb($path);
            if (($limitFolders >0) and ($countDir > $limitFolders)) break; // STOP PROCESSING FOLDERS IF LIMIT IS SET AND REACHED
            $output .= "<a href='".$gallery_url."?m=".$moduleId."&g=".$target."'><div><img src='".$thumb."' style='width: 200px;' /><div class='hq-folder-name'>".$file."<br />(".number_format($count, 0,',',' ').")</div></div></a>";
        } else {
            $numberofImages = $numberofImages +1;
        }
    }
}
$output .= "</div>";

if ($limitFolders >0) {
    // ECHO LINK TO VIEW MODE FOLDERS IF CAPPED IN THIS MODULE
    $output .= "<a href='".$gallery_url."?m=".$moduleId."&g=".$folder."'>Visa fler..</a>";
}

if (($upload_permission) and ($folder != $params->get('folder', ''))) {
    // användaren har rätt att ladda upp bilder och vi är INTE i rooten.
  
    // ECHO OPTION TO CREATE NEW FOLDER
    $output .= '<h3>Skapa ny mapp i "'.basename($folder).'"</h3><form action="" method="POST" enctype="multipart/form-data">
    <input type="text" id ="new_folder" name="new_folder">
    <input type="submit" name="Skapa" value="Skapa" >
    <input type="hidden" name="q" value="new_folder">
    <input type="hidden" name="g" value="'.$folder.'">
    <input type="hidden" name="m" value="'.$moduleId.'">
    </form>'; 
  
    // ECHO OPTION TO UPLOAD FILES
    $output .= '<h3>Ladda upp bilder till "'.basename($folder).'"</h3><form action="" method="POST" enctype="multipart/form-data">
    <input type="file" name="files[]" multiple>
    <input type="submit" name="Upload" value="Ladda upp" >
    <input type="hidden" name="q" value="upload">    
    <input type="hidden" name="g" value="'.$folder.'">
    <input type="hidden" name="m" value="'.$moduleId.'">
    </form>';
}

// FIND AND SHOW VIDEOS
if ($showVideos == 1) {
    foreach($scan as $file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == "mp4") {
            $output .= "{mp4}".$folder."/".pathinfo($file, PATHINFO_FILENAME)."{/mp4}";
        }
    }
}

// FIND AND SHOW IMAGES
if ($showImages == 1) {
    if ($numberofImages > 0) {
        // echo "Det finns också: ". $numberofImages. " filer.";
        // TODO: Put start- and end-TAG in configuration instead of hard coding
        $output .= "{gallery}".$folder."{/gallery}";
    } else {
        // $output .= "Det finns inga filer i mappen.";
    }
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
