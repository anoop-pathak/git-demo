<?php

use App\Services\FileSystem\FlySystem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Solarium\QueryType\Server\CoreAdmin\Query\Action\Create;
use App\Models\Job;
use App\Models\Message;
use App\Models\Company;
use Config;
use Log;

function ine($haystack, $needle)
{
    return (isset($haystack[$needle]) && !empty($haystack[$needle]));
}

function issetRetrun($haystack, $needle)
{
    return isset($haystack[$needle]) ? $haystack[$needle] : false;
}


function isSetNotEmpty($haystack, $needle)
{
    return ine($haystack, $needle) ? $haystack[$needle] : false;
}

    // do combine array_unique and array_filter
function arry_fu(array $array)
{
    return array_unique(array_filter($array));
}

    //check if true
function isTrue($value = false)
{
    return (($value === true) || ($value === 'true') || ($value === 1) || ($value === '1'));
}

    //check if false
function isFalse($value = false)
{
    return (($value === false) || ($value === 'false') || ($value === 0) || ($value === '0'));
}

    //check if string is json
function is_JSON()
{
    call_user_func_array('json_decode', func_get_args());
    return (json_last_error()===JSON_ERROR_NONE);
}

function utcConvert($dateTime, $tz = null)
{
    if (!$tz && !(Auth::check() && Auth::user()->isOpenAPIUser())) {
        $tz = \Settings::get('TIME_ZONE');
    }

    $dateTime = new \Carbon\Carbon($dateTime, $tz);
    $dateTime = $dateTime->setTimeZone('UTC');
    return $dateTime;
}

function dateTimeParse($dateTime, $format = null, $tzConvert = null, $tzCurrent = null)
{
    if ($tzCurrent) {
        $dateTime = new \Carbon\Carbon($dateTime, $tzCurrent);
    } else {
        $dateTime = new \Carbon\Carbon($dateTime);
    }

    if ($tzConvert) {
        $dateTime = $dateTime->setTimeZone($tzConvert);
    }
        
    if ($format) {
        return $dateTime->format($format);
    }
        
    return $dateTime;
}

    /**
     * Decode Base 64 and uploade
     * @param $data String | Base64 encoded data
     * @param $fullpath String | Full path where image to upload image
     * @param $name String | File name
     * @param $drive rotationAngle | Rotation angle..
     * @param $createThumb Boolean | Create a thumb for image
     * @param $drive String | Storage location e.g. s3, local
     * @return Array of file details or false if fail.
     */
function uploadBase64Image(
    $data,
    $fullPath,
    $name = null,
    $rotationAngle = null,
    $createThumb = false,
    $drive = null,
    $thumbFullPath = null
) {

    try {
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $image = base64_decode($data, true);
        if (!$name) {
            $name = \Carbon\Carbon::now()->timestamp.'_'.'image.jpg';
        }
        $img = \Image::make($image);
        // rotate image ..
        if ($rotationAngle) {
            $img->rotate($rotationAngle);
        }

        // save file..
        $uploaded = \FlySystem::connection($drive)
            ->put($fullPath.'/'.$name, $img->encode()->getEncoded());

        $thumbExist = false;
                
        // create thumb..
        if ($createThumb) {
            $thumb = $img;
            if ($thumb->height() > $thumb->width()) {
                $thumb->heighten(200, function ($constraint) {
                    $constraint->upsize();
                });
            } else {
                $thumb->widen(200, function ($constraint) {
                    $constraint->upsize();
                });
            }
                
            // add thumb suffix in filename for thumb name
            if(!$thumbFullPath) {
                $thumbName = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $name);
                $thumbFullPath = $fullPath.'/'.$thumbName;
            }

            \FlySystem::connection($drive)
                ->put($thumbFullPath, $thumb->encode()->getEncoded());

            $thumbExist = true;
        }

        if (!$uploaded) {
            return false;
        }
    } catch (\Exception $e) {
        return false;
    }
        
    return $ret = [
        'name'      => $name,
        'size'      => \FlySystem::connection($drive)->getSize($fullPath.'/'.$name),
        'mime_type' => 'image/jpeg',
        'thumb_exists' => $thumbExist,
    ];
}

    /**
     * Convert an image into Base64 encode
     * @param $filePath String | File path
     * @return Base 64 encoded string.
     */
function getBase64EncodedData($filePath, $height = null, $width = null)
{

    $height = is_null($height) ? \config('jp.json_image_height') : $height;
    $width = is_null($width) ? \config('jp.json_image_width') : $width;
    $type = \FlySystem::getMimetype($filePath);
    // if(!in_array($type, \config('resources.image_types'))){
    // 	throw new JobProgress\Resources\Exceptions\InvalidFileException("Base64 encoding possible for images only");
    // }
    $content = \FlySystem::read($filePath);
    $image = \Image::make($content);
    if ($image->height() > $image->width()) {
        $img = \Image::cache(function ($image) use ($content, $height) {
            $image->make($content)->heighten($height, function ($constraint) {
                $constraint->upsize();
            });
        });
    } else {
        $img = \Image::cache(function ($image) use ($content, $width) {
            $image->make($content)->widen($width, function ($constraint) {
                $constraint->upsize();
            });
        });
    }
    $base64 = 'data:' . $type . ';base64,' . base64_encode($img);
    return $base64;
}
    
    /**
     * Google Geocoder
     * @param  String $address | Address String
     * @return co-ordinates array or false
     */
function geocode($address)
{
    try {
        // track usage..
        //TempLog::trackGecodingRequest();

        $response = \Geocoder::geocode('json', ['address' => $address]);
        $response = json_decode($response, true);
        if (isset($response['error_message']) && !isset($response['results'][0]['geometry']['location'])) {
            // log the error
            \Log::warning('Geocoder Error: '.$response['error_message']);

            return false;
        }
            
        return $response['results'][0]['geometry']['location'];
    } catch (\Exception $e) {
        // log the error
        \Log::warning('Geocoder Error: '.$e->getMessage());
            
        return false;
    }
}

    /**
     * Get offset accroding to current timezone scope
     * @return string | offset
     */
function getCurrentTimeZoneOffset()
{
    if (Auth::user()->isOpenAPIUser()) {
        return \Carbon\Carbon::now('UTC')->format('P');
    } 
    
    return \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format('P');
}

    /**
     * Build query to convert timezone accroding to current timezone scope
     * @param  String $columnName | DateTime Column Name
     * @return string | query
     */
function buildTimeZoneConvertQuery($columnName)
{
    $offset = getCurrentTimeZoneOffset();
    $query = "CONVERT_TZ($columnName, '+00:00', '$offset')";
    return $query;
}

    /**
     * ******
     * @param  [date time] $dateTime [dateTime]
     * @param  [timezone] $timezone [user time zone]
     * @return [date]           [return date]
     */
function convertTimezone($dateTime, $timezone)
{
    $dateTime = new \Carbon\Carbon($dateTime, 'UTC');
    $dateTime = $dateTime->setTimeZone($timezone);
    return $dateTime;
}
    
function getMimeType($filePath)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $type  = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    return $type;
}

function safeTransaction(Closure $callback)
{
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        try {
            return \DB::transaction($callback);
        } catch (Illuminate\Database\QueryException $e) {
            if ($e->getCode() != 40001 || $attempt >= 5) {
                throw $e;
            }
        }
    }
}
        
    /**
     * Get Image
     * @param  [url] $filePath [description]
     * @return [json_encoded]  [image]
     */
    // function getImage($filePath)
    // {
    // 	$type = getMimeType($filePath);
    // 	$image = Image::make($filePath);
    // 	$img = \Image::cache(function($image) use($filePath){
    // 		$image->make($filePath);
    // 	});
        
    // 	$base64 = 'data:' . $type . ';base64,' . base64_encode($img);
    //  return $base64;
    // }

    /**
     * get format file size
     * @param  [int] $bytes [description]
     * @return [type]        [description]
     */
function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}
    
function publicUrl($basePath)
{
    return config('app.url').config('jp.BASE_PATH').$basePath;
}

function numberFormat($value, $decimals = 2)
{
    return round($value, $decimals);
}


function unsetByValue($array, $values)
{
    $arr = array_diff($array, (array)$values);
    sort($arr);
    return $arr;
}

function isIndexExists($tableName, $key)
{
    $keyExists = \DB::select(
        \DB::raw(
            "SHOW KEYS
		        FROM $tableName
		        WHERE Key_name='$key'"
        )
    );
    if ($keyExists) {
        return true;
    }
    return false;
}

    /**
     * get thumb name for file
     *
     * @param  mimr_type of file
     * @return thumb name
     */
function getFileIcon($mimeType, $filePath = null)
{
        
    $ext = ($filePath) ? strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) : null;

    if(in_array($ext, ['ac5', 'ac6', 'ai', 'csv', 'doc', 'dxf', 'eml', 'eps', 'jpg', 'pdf', 'pnf', 'pnf', 'png', 'ppt', 'psd', 'rar', 'skp', 'txt', 've', 'xls', 'zip', 'sdr', 'esx', 'sfz', 'dwg'])) {
        return config('app.url').config('jp.MIME_TYPE_ICON_PATH'). $ext . '.png';
    }
        
    if (!$mimeType) {
        return;
    }

    $icons = [
    'application/pdf'   => 'pdf.png',
    'image/jpeg'        => 'jpg.png',
    'image/jpg'         => 'jpg.png',
    'image/png'         => 'png.png',
    'application/zip'   => 'zip.png',
    'application/x-zip' => 'zip.png',
    'application/x-zip-compressed' => 'zip.png',
    'application/rar'   => 'rar.png',
    'application/x-rar' => 'rar.png',
    'application/x-rar-compressed' => 'rar.png',
    'application/vnd.ms-excel' => 'xls.png',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xls.png',
    'application/msword' => 'doc.png',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'doc.png',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'ppt.png',
    'application/vnd.ms-powerpoint' => 'ppt.png',
    'text/plain'        => 'txt.png',
    ];

    return config('app.url').config('jp.MIME_TYPE_ICON_PATH'). $icons[$mimeType];
}

function clean($string)
{
    $string = str_replace(' ', '', $string); // Replaces all spaces.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

    /**
     * Seconds To Day:Hour:Min
     * @param  [type] $seconds [description]
     * @return [type]          [description]
     */
function secondsToDhm($seconds, $workingHoursOfDay = 8)
{
    $secondsInAMinute = 60;
    $secondsInAnHour  = 60 * $secondsInAMinute;
    $secondsInADay    = $workingHoursOfDay * $secondsInAnHour;

    // extract days
    $days = floor($seconds / $secondsInADay);

    // extract hours
    $hourSeconds = $seconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);

    // extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);

    // extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);

    // return the final array
    $obj = [
    'd' => (int) $days,
    'h' => (int) $hours,
    'm' => (int) $minutes,
    // 's' => (int) $seconds,
    ];
// return $obj;
// formated ..
    return implode(':', $obj);
}

    /**
     * Day:Hour:Min to Seconds
     * @param  [type] $dhm [description]
     * @return [type]      [description]
     */
function dhmToSeconds($dhm)
{
    $secondsInAMinute = 60;
    $secondsInAnHour  = 60 * $secondsInAMinute;
    $secondsInADay    = 8 * $secondsInAnHour;

    $dhm  =  explode(':', $dhm);
    list($d,$h,$m) = $dhm;

    $seconds = 0;
    $seconds += $d * $secondsInADay;
    $seconds += $h * $secondsInAnHour;
    $seconds += $m * $secondsInAMinute;
    return $seconds;
}

    /**
     * [uniqueMultidimArray Get unique array from multidimensional array]
     * @param  [type] $array [array]
     * @param  string $key   [field name]
     * @return [type]        [unique multidimensional array]
     */
function uniqueMultidimArray($array, $key = 'id')
{
    $temp_array = [];
    $i = 0;
    $key_array = [];
        
    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}

    /**
     * replace \n to br tag
     * @param  string $string [description]
     * @return [type]         [description]
     */
function lineBreak($string = "")
{
    return str_replace('\n', "<br>", $string);
}

function zipCodeFormat($zip, $country = null)
{
    if (empty($zip)) {
        return $zip;
    }

    // 5 digit for USA
    if (!empty($country) && ($country == 1)) {
        return sprintf("%05s", $zip);
    }

    return $zip;
}

function orderByCaseQuery($column, $value)
{
    $value = htmlspecialchars($value, ENT_QUOTES);
    $query = "CASE WHEN ".$column." like '".$value." %' 
			   THEN 0 
			   WHEN ".$column." like '".$value."%' THEN 1
               WHEN ".$column." like '% ".$value."%' THEN 2
               ELSE 3
          	   END, " . $column;

    return $query;
}
    /**
     * Get Company Scope Id
     * @return [type] [description]
     */
function getScopeId()
{
    try {
        $scopeId = config('company_scope_id');

        if ($scopeId) {
            return $scopeId;
        }

        $context = \App::make('App\Services\Contexts\Context');

        if ($context->scope->has()) {
            return $context->scope->id();
        }

        return null;
    } catch (\Exception $e) {
        return null;
    }
}

function setScopeId($companyId)
{
    \Config::set('company_scope_id', $companyId);
    $company = Company::find($companyId);

    $context = \App::make('App\Services\Contexts\Context');
    $context->set($company);

    $country = $company->country;

    \Config::set('company_country_currency_symbol', $country->currency_symbol);
}

    /**
     * Generate Unique Token
     * @return string
     */
function generateUniqueToken()
{
    return \Carbon\Carbon::now()->timestamp.uniqid();
}

function logQueries()
{
    // disable query log on production..
    if (App::environment('production') || !config('system.query_log')) {
        return;
    }

    \DB::listen(function($query) {
        \Log::info(
            $query->sql,
            $query->bindings,
            $query->time
        );
    });
}

function timestamp()
{
    return \Carbon\Carbon::now()->timestamp;
}

function uniqueTimestamp()
{
    return timestamp() .'_'. rand();
}

function moneyFormat($value)
{
    return number_format((float)$value, 2, '.', ',');
}

function isValidDate($value)
{
    return (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $value));
}

function getFirstLetters($string)
{
    $words = explode(' ', $string);
    $letter = '';
    foreach ($words as $word) {
        $letter .= substr($word, 0, 1);
    }

    return strtoupper($letter);
}

function getErrorDetail(Exception $e)
{
    return $e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine();
}

function phoneNumberFormat($number, $countryCode)
{
    switch ($countryCode) {
        case 'US':
        case 'BHS':
        case 'CA':
        case 'AU':
            return preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/', '($1) $2-$3', $number);
            break;
        default:
            return $number;
    }
}

function sortBy($field, &$array, $direction = 'asc')
{
    usort($array, create_function(
        '$a, $b',
        'if(!isset($a["' . $field . '"]) || !isset($b["' . $field . '"])) return 0;

			$a = $a["' . $field . '"];
			$b = $b["' . $field . '"];

			if ($a == $b)
			{
				return 0;
			}

			return ($a ' . ($direction == 'desc' ? '>' : '<') .' $b) ? -1 : 1;
		'
    ));

    return true;
}

function calculateTax($amount, $tax)
{
    if (!$tax) {
        return 0;
    }

    return round($amount*($tax / 100 ), 2);
}

function calculateTaxRate($amount, $taxAmount)
{
    if(!$taxAmount) return 0;

    return round(($taxAmount * 100)/$amount, 7);
}

function totalAmount($amount, $tax = null)
{
    if (!$tax) {
        return $amount;
    }

    return round($amount*($tax / 100 ), 2) + $amount;
}

    /**
     * Generate sql query with binding from Query Builder
     * @param  Query Builder $queryBuilder | Query Builder
     * @return sql string
     */
function generateQueryWithBindings($queryBuilder)
{
    $sql = $queryBuilder->toSql();
    $bindings = $queryBuilder->getBindings();

    foreach ($bindings as $binding) {
        $value = is_numeric($binding) ? $binding : "'".$binding."'";
        $sql = preg_replace('/\?/', $value, $sql, 1);
    }

    return $sql;
}

function getGoogleSheetUrl($sheetId)
{
    if (!$sheetId) {
        null;
    }
    return "https://docs.google.com/spreadsheets/d/".$sheetId."/edit?usp=sharing";
}

function getGoogleSheetThumbUrl($sheetId, $size = 200)
{
    if (!$sheetId) {
        null;
    }
    return "https://drive.google.com/thumbnail?authuser=0&sz=w".$size."&id=".$sheetId;
}

function googleDocsExportLink($mime, $fileId)
{
    switch ($mime) {
        case "application/vnd.google-apps.document":
            return "https://docs.google.com/document/d/{$fileId}/export?format=docx";
            break;
        case "application/vnd.google-apps.drawing":
            return "https://docs.google.com/drawings/d/{$fileId}/export?format=jpg";
            break;
        case "application/vnd.google-apps.presentation":
            return "https://docs.google.com/presentation/d/{$fileId}/export?format=pptx";
            break;
        case "application/vnd.google-apps.spreadsheet":
            return "https://docs.google.com/spreadsheets/d/{$fileId}/export?format=xlsx";
            break;
        default:
            return null;
            break;
    }
}

function showAmount($amount)
{
    if ($amount >= 0) {
        return '$'.currencyFormat($amount);
    }

    return '-'.currencyFormat(abs($amount));
}


function makeCurlRequest($method, $url, $headers, $body, $timeout = 30)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

    // $verbose = fopen('curl_request_logs.txt', 'w+');
    // curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $data = curl_exec($ch);

    curl_close($ch);
    return $data;
}


function getMimeTypeFromExt($ext)
{
    $mime_types = [

    'txt' => 'text/plain',
    'htm' => 'text/html',
    'html' => 'text/html',
    'php' => 'text/html',
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'xml' => 'application/xml',
    'swf' => 'application/x-shockwave-flash',
    'flv' => 'video/x-flv',

    // images
    'png' => 'image/png',
    'jpe' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'ico' => 'image/vnd.microsoft.icon',
    'tiff' => 'image/tiff',
    'tif' => 'image/tiff',
    'svg' => 'image/svg+xml',
    'svgz' => 'image/svg+xml',

    // archives
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'exe' => 'application/x-msdownload',
    'msi' => 'application/x-msdownload',
    'cab' => 'application/vnd.ms-cab-compressed',

    // audio/video
    'mp3' => 'audio/mpeg',
    'qt' => 'video/quicktime',
    'mov' => 'video/quicktime',

    // adobe
    'pdf' => 'application/pdf',
    'psd' => 'image/vnd.adobe.photoshop',
    'ai' => 'application/postscript',
    'eps' => 'application/postscript',
    'ps' => 'application/postscript',

    // ms office
    'doc' => 'application/msword',
    'rtf' => 'application/rtf',
    'xls' => 'application/vnd.ms-excel',
    'ppt' => 'application/vnd.ms-powerpoint',

    // open office
    'odt' => 'application/vnd.oasis.opendocument.text',
    'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    'csv' => 'text/plain',
    ];

    if (isset($mime_types[$ext])) {
        return $mime_types[$ext];
    }

    return null;
}

function addExtIfMissing($name, $mime)
{
    $fileExt = \File::extension($name);
    if (empty($fileExt)) {
        $ext = mimeToExt($mime);
        return "{$name}.{$ext}";
    }

    return $name;
}

function extToMime($ext)
{
    $mime_types = mimeExtMap();

    if (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext][0];
    }

    return null;
}

function paymentMethod($method)
{
    switch ($method) {
        case 'cc':
            $method = 'Credit Card';
            break;
        case 'echeque':
            $method = 'Check';
            break;
    }

    return ucfirst($method);
}

/**
 * Removed quickbook special chars(:,\t,\n)
 * @param  string $string String
 * @return String
 */
function removeQBSpecialChars($string)
{
    $specialChar = array(':', '\t', '\n', '’', '–');
    $replaced    = array('-', ' ', ' ', "'", '–');

    $string = str_replace($specialChar, $replaced, $string);

    return htmlspecialchars($string);
}

function mimeToExt($mime)
{
    $all_mimes = mimeExtMap();
    foreach ($all_mimes as $key => $value) {
        if (array_search($mime, $value) !== false) {
            return $key;
        }
    }

    return false;
}

function mimeExtMap()
{
    // public/data/all_mime_types.json
    $all_mimes = file_get_contents('data/all_mime_types.json');

    return json_decode($all_mimes, true);
}

function multiTierStructure($collection = null, $addSum = false, $sellingPrice = false, &$goRecusive = [], $level = 0, $useLivePricing = null)
{
    $tree = $goRecusive;

    if ($collection) {
        foreach ($collection as $key => $value) {
            if($useLivePricing && $value) {
                $value->live_pricing = $value->getLivePricing();
                unset($value->livePricingThroughBranchCode);
                unset($value->livePricingThroughProductId);
            }
            if (ine($value, 'tier1') && ine($value, 'tier2') && ine($value, 'tier3')) {
                $tree[$value['tier1']][$value['tier2']][$value['tier3']][] = (object)$value;
            } elseif (ine($value, 'tier1') && ine($value, 'tier2')) {
                $tree[$value['tier1']][$value['tier2']][] = (object)$value;
            } elseif (ine($value, 'tier1')) {
                $tree[$value['tier1']][] = (object)$value;
            } else {
                $tree[] = (object)$value;
            }
        }
    }

    $data = [];
    $level += 1;
    foreach ($tree as $key => $value) {
        if (is_array($value)) {
            $collection = (object)[
                'type' => 'collection',
                'tier' => $level,
                'tier_name' => (string)$key,
                'data' => multiTierStructure(null, $addSum, $sellingPrice, $value, $level, $useLivePricing),
            ];

            if ($addSum) {
                $sum = addMultiTierSum($collection, $sellingPrice);
                $collection->sum = $sum;
            }

            $data[] = $collection;
        }

        if (is_object($value)) {
            $data[] = (object)[
                'type' => 'item',
                'data' => $value,
            ];
        }
    }

    return $data;
}

function addMultiTierSum(&$collection, $sellingPrice, $sum = 0)
{
    foreach ($collection->data as $key => $value) {
        if ($value->type == 'collection') {
            $sum = addMultiTierSum($value, $sellingPrice, $sum);
        } else {
            $item = $value->data;
            if ($sellingPrice) {
                $sum += $item->selling_price * $item->quantity;
            } else {
                $sum += $item->unit_cost * $item->quantity;
            }
        }
    }

    return $sum;
}

function switchDBConnection($connection)
{
    if (!config('database.connections.'.$connection)) {
        return;
    }
    \DB::purge('mysql');
    \Config::set('database.default', $connection);
}

function switchDBToReadOnly() 
{
    \DB::disconnect('mysql');
    \DB::setDefaultConnection('mysql2');
}

function switchDBToReadWrite() 
{
    \DB::disconnect('mysql2');
    \DB::setDefaultConnection('mysql');
}

    // remove special characters from price, tax, etc.
function parseIntFloatValues($string)
{
    return preg_replace('/[^0-9\.]/', '', $string);
}

function diffInHours($startDateTime, $endDateTime)
{
    $starttimestamp = strtotime($startDateTime);
    $endtimestamp = strtotime($endDateTime);
    $difference = round(abs($endtimestamp - $starttimestamp)/3600, 4);
}

function durationFromSeconds($value, $duration = 'H:i:s')
{
    $hours   = sprintf("%02s", floor($value / 3600));
    $minutes = sprintf("%02s", floor(($value / 60) % 60));
    // $seconds = sprintf("%02s", $value % 60);

    return "$hours:$minutes";
}

//array search column by values
function arrayCSByValue($data, $value, $column)
{
    return array_search($value, array_column($data, $column));
}

function currencyFormat($amount)
{
    if(empty(config('company_country_currency_symbol'))) {
        Config::set('company_country_currency_symbol', '$');
    }

    return config('company_country_currency_symbol').moneyFormat($amount);
}

function getWorksheetMarginMarkup($isMargin, $amount, $marginRate)
{
    if ($isMargin) {
        $sp =  (100 * $amount) / (100 - $marginRate);
        $profit = numberFormat($sp - $amount);
    } else {
        $profit = calculateTax($amount, $marginRate);
    }

    return $profit;
}

function removeAllWhiteSpace($string)
{
    return preg_replace('/\s+/', '', $string);
}

    // set user in auth and company scope (for queue handlers)
function setAuthAndScope($userId)
{
    $user = \App\Models\User::find($userId);

    if (!$user) {
        return false;
    }
    if(!$user->company_id){
        return false;
    }

    \Auth::guard('web')->login($user);

    setScopeId($user->company_id);

    return true;
}

/**
 * get abbreviation of a timezone
 * @param  string $timezone     name of timezone
 * @param  string $dateTime     date time string
 * @return $tzAbbreviation
 */
function getTimezoneAbbreviation($timezone, $dateTime)
{
    $tzAbbreviation = Carbon\Carbon::parse($dateTime)
        ->timezone($timezone)
        ->format('T');
    return $tzAbbreviation;
}
function getPathWithVersion($path) {
    return config('app.url').$path.'?v='.uniqueTimestamp();
}

function addPhotoWatermark($image, $jobId = null)
{
    $user = Auth::user();
    $timezone = \Settings::forUser($user->id, $user->company_id)->get('TIME_ZONE');
    $info = $user->full_name. PHP_EOL. \Carbon\Carbon::now($timezone)->format('m/d/Y h:i a') . ' ('. \Carbon\Carbon::now($timezone)->format('T').')';
    if($jobId) {
        $job = Job::find($jobId);
        $info .= PHP_EOL. $job->customer->full_name . ' / '. $job->number;
        if(($address = $job->address) && ($address = $address->present()->fullAddressOneLine)) {
            $info .= PHP_EOL . $address;
        }
    }
    $image->text($info, 25, ((($image->getHeight() * 3) / 100 )) , function($font) use($image) {
        $font->file(public_path('fonts/Roboto-Regular.ttf'));
        $fontSize = (($image->getHeight() * 3) / 100 );

        $font->size($fontSize);
        $font->color('rgba(255,255,255,0.70)');
        $font->align('left');
    });

    return $image;
}

/**
 * Get Division Scope Id
* @return [Array] [division ids]
*/
function getDivisions()
{
    return \Auth::user()->divisions->pluck('id')->toArray();
}

function searcharray($value, $key, $array) {
    foreach ($array as $k => $val) {
        if (isset($val[$key]) && $val[$key] == $value) {
            return $array[$k];
        }
    }
    return null;
}

function getJobToken($request)
{
    $token = $request->header('Authorization');
    $bearer = strripos($token, ' ');
    $token =  substr($token, $bearer);
    $jobToken = trim($token);

    return $jobToken;
}

function byteToMB($value = null)
{
    return $value/1000000;
}

if ( ! function_exists('d'))
	{
		/**
		 * Dump the passed variables.
		 *
		 * @param  mixed
		 * @return void
		 */
		function d()
		{
			array_map(function($x) {
				echo "\n";
				print_r($x);
				echo "\n";
			}, func_get_args());
		}
	}

	if ( ! function_exists('prx'))
	{
		/**
		 * Dump the passed variables.
		 *
		 * @param  mixed
		 * @return void
		 */
		function prx()
		{
			array_map(function($x) {
				echo "\n<pre>";
				print_r($x);
				echo "</pre>\n";
				die;
			}, func_get_args());
		}
	}

	if ( ! function_exists('pr'))
	{
		/**
		 * Dump the passed variables.
		 *
		 * @param  mixed
		 * @return void
		 */
		function pr()
		{
			array_map(function($x) {
				echo "\n<pre>";
				print_r($x);
				echo "</pre>\n";
			}, func_get_args());
		}
	}

	if (!function_exists('logx')) {

		function logx($x, $throwException = false)
		{
			if (!\App::environment('local')) {
				return false;
			}

			\Log::debug(print_r($x, true));

			if ($throwException) {
				throw new \Exception('Debug');
			}
		}
	}

	function debug($data)
	{
		echo "<pre>";
		print_r($data);
		exit;
	}

	function getNumber($number)
	{
		return preg_replace("/[^0-9]+/", "", $number);
	}

	/**
	 * sort multi dimensional array by column
	 * @param  Array 	$array
	 * @param  String 	$column
	 * @param  CONST 	$order
	 * @return $array
	 */
	function sortMultiDimArrayByCol($array, $column, $order = SORT_ASC)
	{
		array_multisort(array_column($array, $column), $order, $array);

		return $array;
	}

	/**	
	 * convert value to string if it's starting with special symbol for excel export
	 * @param  String | $value | Value for a cell
	 * @return $value
	 */
	function excelExportConvertValueToString($value)
	{
		if(preg_match('/[^a-zA-Z\d]/', substr($value, 0, 1))) {
			$value = "'$value'";
		}

		return $value;
	}

	function arrayToXML($data, $startElement = 'fx_request', $xml_version = null, $xml_encoding = null){
	    if(!is_array($data)){
	        $err = 'Invalid variable type supplied, expected array not found on line '.__LINE__." in Class: ".__CLASS__." Method: ".__METHOD__;
	        trigger_error($err);
	        if($this->_debug) echo $err;
	        return false; //return false error occurred
	    }
	    $xml = new XmlWriter();
	    $xml->openMemory();
	    if($xml_version && $xml_encoding) {
	    	$xml->startDocument($xml_version, $xml_encoding);
	    }
	    $xml->startElement($startElement);

	    /**
	     * Write XML as per Associative Array
	     * @param object $xml XMLWriter Object
	     * @param array $data Associative Data Array
	     */
	    function writeXML(XMLWriter $xml, $data){
	        foreach($data as $key => $value){
	            if (is_array($value) && isset($value[0])){
	                foreach($value as $itemValue){
	                    //$xml->writeElement($key, $itemValue);

	                    if(is_array($itemValue)){
	                        $xml->startElement($key);
	                        writeXML($xml, $itemValue);
	                        $xml->endElement();
	                        continue;
	                    }

	                    if (!is_array($itemValue)){
	                        $xml->writeElement($key, $itemValue."");
	                    }
	                }
	            }else if(is_array($value)){
	                $xml->startElement($key);
	                writeXML($xml, $value);
	                $xml->endElement();
	                continue;
	            }

	            if (!is_array($value)){
	            	if(!$value) continue;
	                $xml->writeElement($key, $value."");
	            }
	        }
	    }
	    writeXML($xml, $data);

	    $xml->endElement();//write end element
	    //returns the XML results
	    return $xml->outputMemory(true);
	}
