<?php
// see http://www.w3schools.com/php/php_file_upload.asp

// config
$target_dir = "queue/";
$converted_dir = "converted/";

$max_file_size = 500000;
$allowed_filetypes = array('jpeg', 'jpg', 'gif', 'png');
$allowed_bits = array(8, 12, 16);

// preconditions
if(!file_exists($target_dir) || is_file($target_dir))
{
  @unlink($target_dir);
  mkdir($target_dir);
}

if(!file_exists($converted_dir) || is_file($converted_dir))
{
  @unlink($converted_dir);
  mkdir($converted_dir);
}

// functions
function generateId($filename)
{
  return md5($filename.time().rand());
}

// trigger conversion
echo system('(./doconversion.sh >> /tmp/conversion.log) &');

// converted file request
if(isset($_GET['f']) && !empty($_GET['f']))
{
  $user_hash = basename(strip_tags(html_entity_decode($_GET['f'])));
  $data_path = $converted_dir.$user_hash.'/';
  $lcd_filename = $user_hash.'.lcd';
  $header_filename = $lcd_filename.'.h';

  $lcd_path = $data_path.$lcd_filename;
  $header_path = $data_path.$header_filename;

  if(file_exists($lcd_path))
  {
    // header also generated, pack it up
    if(file_exists($header_path))
    {
      chdir($data_path);
      $zipname = $user_hash.'.zip';
      $zip = new ZipArchive;
      $zip->open($zipname, ZipArchive::CREATE);
      $zip->addFile($lcd_filename);
      $zip->addFile($header_filename);
      $zip->close();
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename='.$zipname);
      header('Content-Length: ' . filesize($zipname));
      readfile($zipname);
      @unlink($zipname); 
    }
    else
    {
      header('Content-Disposition: attachment; filename="'.$user_hash.'.lcd"');
      header('Pragma: public');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Content-Length: ' . filesize($lcd_path));
      readfile($lcd_path);
    }
  }
  else
  {
    echo'file not converted yet or invalid hash ('.$user_hash.').<br/>';
  }
}

// file upload
if(isset($_FILES['fileToUpload']) && is_array($_FILES['fileToUpload']))
{
  $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
  $uploadOk = 1;

  // check if filename was provided
  if(!isset($_FILES['fileToUpload']['tmp_name']) || empty($_FILES['fileToUpload']['tmp_name']))
  {
    $uploadOk = 0;
  }

  // Check if image file is a actual image or fake image
  if($uploadOk && isset($_POST["submit"])) 
  {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) 
    {
      $uploadOk = 1;
    } 
    else 
    {
      echo 'File is not an image.<br/>';
      $uploadOk = 0;
    }
  }

  // Check file size
  if ($uploadOk && $_FILES["fileToUpload"]["size"] > $max_file_size) 
  {
    echo 'Sorry, your file is too large (limit: '.$max_file_size.'b)<br/>';
    $uploadOk = 0;
  }

  // Allow certain file formats
  $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
  if($uploadOk && !in_array($imageFileType, $allowed_filetypes)) 
  {
    echo 'Sorry, only the following file types are allowed: ';
    foreach($allowed_filetypes as $type)
    {
      echo $type.', ';
    }
    echo '<br/>';
    $uploadOk = 0;
  }

  // Check if $uploadOk is set to 0 by an error
  if ($uploadOk == 1) 
  {
    $bits = '-';
    if(isset($_POST['bits']))
    {
      $form_bits = (int)$_POST['bits'];
      if(in_array($form_bits, $allowed_bits))
      {
        $bits = $form_bits;
      }
    }

    $code = 0;
    if(isset($_POST['code']))
    {
      $code = 1;
    }

    // generate hash for this file
    $hash = generateId($target_file);
    $target_hash = $target_dir . $hash;

    // append parameters to filename
    $target_hash .= '_'.$bits.'_'.$code;

    // Check if file already exists to avoid collisions
    while (file_exists($target_hash)) 
    {
      $hash = generateId($target_file);
      $target_hash = $target_dir . $hash;
      $target_hash .= '_'.$bits.'_'.$code;
    }

    // actually move the file
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_hash)) 
    {
      echo 'Get file after conversion from <a href="'.$_SERVER['PHP_SELF'].'?f='.$hash.'" target="_blank">here</a>';
    } 
    else 
    {
      echo 'Sorry, there was an error uploading your file.<br/>';
    }
  }
}
?>

<!DOCTYPE html>
<html>
<body>

<hr>

<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload"><br/>
    Bits: <select name="bits">
      <option>8</option>
      <option selected>12</option>
      <option>16</option>
    </select><br/>
    Code: <input type="checkbox" name="code"><br/>
    <input type="submit" value="Convert Image" name="submit">
</form>

</body>
</html>
