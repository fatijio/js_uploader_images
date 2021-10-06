<?php
// LAST MODIFY 24.09.2021
//header('Content-Type: text/html; charset=utf-8');
//define( '_JEXEC', 1);
// папка в которую загружать фото uploadimages, формат файлов png, jpg, jpeg
defined( '_JEXEC')  or die();
define( 'DS', DIRECTORY_SEPARATOR );
define('JPATH_BASE', $_SERVER['DOCUMENT_ROOT'] . DS . '');
require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );
$mainframe = &JFactory::getApplication('site');
require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php');
require_once (JPATH_SITE.'/components/com_jshopping/lib/image.lib.php');
//require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');
jimport( 'joomla.filesystem.folder' );
jimport( 'joomla.filesystem.file' );
define ('PATH', JPATH_BASE."/uploadimages/");
//echo $path = str_replace('\\', '', $path);
file_put_contents("log_image.txt", date("d.m.y H:i")." DATE STARTED.\r\n",FILE_APPEND);
//$jshopConfig = JSFactory::getConfig();
$db =& JFactory::getDBO();
if (!$db->connected()) {
    //echo "Нет соединения с базой данных";
    jexit();
} else {
    //echo "Можем работать с базой данных";
}
$query = $db->getQuery(true);


// ================
// РАБОТА С ФАЙЛАМИ
// ================

function getFiles($folder){
    $arFiles = JFolder::files($folder, '\.jpg|\.jpeg|\.png$', true, true );
    for($i = 0; $i < count($arFiles); $i++){
        $arFiles[$i] = JFile::getName(iconv('windows-1251','UTF-8',$arFiles[$i])); // конвертация и получение имен файлов
        //file_put_contents("log_image.txt", date("d.m.y H:i")." FILE_NAME: {$arFiles[$i]}\r\n",FILE_APPEND);
        //echo $arFiles[$i].'<br/>';
    }
    return $arFiles;
}

//getFiles($path);

function resizeImages($files){

    if(!$files || empty($files)){
        echo 'Нет файлов для загрузки (ресайз)';
        file_put_contents("log_image.txt", date("d.m.y H:i")." NO PRODUCT OR FILES FOR RESIZE.\r\n",FILE_APPEND);
        return;
    }

    // image_product_full_width = Ширина изображения товара
    // image_product_width = Ширина превью товара
    // image_product_original_width = Ширина оригинального изображения

    $jshopConfig = JSFactory::getConfig();

    //echo $jshopConfig->image_product_path.'<br/>';
    //echo $jshopConfig->image_product_original_width.'<br/>';
    //echo $jshopConfig->image_product_original_height.'<br/>';
    //echo $jshopConfig->image_quality.'<br/>';
    //echo $jshopConfig->image_fill_color.'<br/>';
    
    foreach($files as $file) {
        //echo $file;
        //echo PATH.$file;
        $file = iconv('UTF-8','windows-1251',$file);
        $name_image = $file;
        $name_thumb = 'thumb_'.$name_image;
        $name_full = 'full_'.$name_image;
        $path_image = $jshopConfig->image_product_path."/".$name_image;
        $path_thumb = $jshopConfig->image_product_path."/".$name_thumb;
        //echo '<br>'.$path_thumb.'<br>';
        $path_full =  $jshopConfig->image_product_path."/".$name_full;

        //image thumb
        $product_width_image_thumb = $jshopConfig->image_product_width;
        $product_height_image_thumb = $jshopConfig->image_product_height;
        if (!ImageLib::resizeImageMagic(PATH.$file, $product_width_image_thumb, $product_height_image_thumb, $jshopConfig->image_cut, $jshopConfig->image_fill, $path_thumb, $jshopConfig->image_quality, $jshopConfig->image_fill_color)) {
            $error = 1;
            file_put_contents("log_image.txt", date("d.m.y H:i")." ERROR_FILE_NAME(thumb): {$name_image}\r\n",FILE_APPEND);
        }
        //image big thumb
        $product_full_width_image = $jshopConfig->image_product_full_width;
        $product_full_height_image = $jshopConfig->image_product_full_height;
        if (!ImageLib::resizeImageMagic(PATH.$file, $product_full_width_image, $product_full_height_image, $jshopConfig->image_cut, $jshopConfig->image_fill, $path_full, $jshopConfig->image_quality, $jshopConfig->image_fill_color)) {
            $error = 1;
            file_put_contents("log_image.txt", date("d.m.y H:i")." ERROR_CREATE_FILE_NAME(full): {$name_image}\r\n",FILE_APPEND);
        }
        //image original
        JFile::move(PATH.$file, $jshopConfig->image_product_path."/".$name_image);
        /*$product_original_image_width = $jshopConfig->image_product_original_width;
        $product_original_image_height = $jshopConfig->image_product_original_height;
        if (!ImageLib::resizeImageMagic(PATH.$file, $product_original_image_width, $product_original_image_height, $jshopConfig->image_cut, $jshopConfig->image_fill, $path_image, $jshopConfig->image_quality, $jshopConfig->image_fill_color)) {
            JError::raiseWarning("", _JSHOP_ERROR_CREATE_THUMBAIL . " " . $name_image);
            saveToLog("error.log", "Resize Product Image - Error create image " . $name_image);
            $error = 1;
            echo "original";
        }*/
    }
}

// =====================
// РАБОТА С БАЗОЙ ДАННЫХ
// =====================
function getProductByArticle($articles){
    global $db, $query;

    $arItems = array();
    $arItemsNoImage = array();
    $arResult = array();
    foreach ($articles as $item) {
        $arItems[] = JFile::stripExt($item);
        //echo $item;
    }
    //print_r($articles);
    //$articles = JFile::stripExt($articles);
    //$isImage = "";
    $query
        ->select(
            $db->quoteName(
            array('product_id', 'product_ean', 'image')
            )
        )
        ->from($db->quoteName('#__jshopping_products'))
        ->where('product_ean IN ('."'" . implode("', '",$arItems) ."'". ')');
    $db->setQuery($query);

    //print_r($list);
    $arItemsNoImage = $db->loadObjectList();
        
            foreach($arItemsNoImage as $item) {
                for($i = 0; $i < count($articles); $i++){
                    if($item->product_ean == JFile::stripExt($articles[$i])){
                        $arResult['resize'][] = $articles[$i];
                    }
                }
            }
        
            foreach($arItemsNoImage as $item) {
                //echo $item->image;
                //echo $item->product_id;
                //print_r($item);
                $arResult['base'][$item->product_id]['product_id'] = $item->product_id;
                $arResult['base'][$item->product_id]['product_ean'] = $item->product_ean;
                for($i = 0; $i < count($articles); $i++){
                    //echo $articles[$i];
                    //echo JFile::stripExt($articles[$i]);
                    if($arResult['base'][$item->product_id]['product_ean'] == JFile::stripExt($articles[$i])){
                        $arResult['base'][$item->product_id]['image'] = $articles[$i];
                        break;
                    }
                }
            }
        

    return $arResult;
}

function setImageToProductBD($products){
    global $db, $query;

    if(!$products || empty($products)){
        echo 'Нет файлов для загрузки (загрузка в базу данных)';
        file_put_contents("log_image.txt", date("d.m.y H:i")." NO PRODUCT OR FILES FOR INSERT TO BASE.\r\n",FILE_APPEND);
        return;
    }

    foreach($products as $product){
        //echo $product['product_ean'];
            $query = "INSERT INTO `#__jshopping_products_images` SET 
                `product_id` = '".$db->escape($product['product_id'])."', 
                `image_name` = '".$db->escape($product['image'])."',
                `name` = '".$db->escape($product['image'])."',
                `ordering` = 1";
            $db->setQuery($query);
            $db->query();
    }
    foreach($products as $product){
        $query = "UPDATE `#__jshopping_products` SET `image`='".$db->escape($product['image'])."' "
                . "WHERE `product_id`=".(int)$product['product_id'];
        $db->setQuery($query);
        $db->query();
    }
}

echo '<pre>';
$list = getFiles(PATH); // получили список файлов для работы с ними дальше
//print_r($list);
//print_r(getProductByArticle($list));
$res = getProductByArticle($list);
print_r($res['resize']);
print_r($res['base']);
//$resultListToResize = getProductByArticle($list);
//$resultListToBD = getProductByArticle($list);

setImageToProductBD($res['base']); // установка данных в базу
resizeImages ($res['resize']); // запуск проверки ресаза и перемещения картинок
echo '</pre>';