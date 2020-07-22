<?php
$item1 = "<li>";
$item2 = "</li>" . PHP_EOL;

// echo($_REQUEST['time'] . '_____<br>');
// echo (is_numeric($_REQUEST['time']) . '.....<br>');
// return;
$browser = true;
if (!empty($argv[1])) {
    parse_str($argv[1], $_REQUEST);
    $browser = false;
}

if(isset($_REQUEST['time']) && is_numeric($_REQUEST['time']))
    set_time_limit($_REQUEST['time']);
else
    set_time_limit(6);

error_reporting(E_ERROR | E_WARNING);
$timescale;
$fPath = "./";
// $mp4Handle = fopen($fPath . "jpuvcur7xh751.mp4", "r");
$mp4Handle = fopen($fPath . "Avatar.The.Last.Airbender.s01e18.1080p.mp4", "r");
// $mp4Handle = fopen("./Naruto Shippuuden (Dub) Episode 298 - Watch Naruto Shippuuden (Dub) Episode.mp4", "r");
$fileChunk = 1000000;
$mp4file = fread($mp4Handle, $fileChunk);
$currentEnd = $fileChunk;
$currentStart = 0;
$currentHandler = "";
$subtitleTimes = array();
$subtitles = array();
if(! isset($_REQUEST['verbose']))
    $_REQUEST['verbose'] = '0';     //STRING VALUE________

//$mp4file = file_get_contents("./Avatar.The.Last.Airbender.s01e18.1080p.mp4");
if($_REQUEST['verbose'] != 'srt')
    sendHTMLbegin();
printAllHeaders();
if($_REQUEST['verbose'] != 'srt')
    sendHTMLclose();

if($_REQUEST['verbose'] == 'srt'){
    printSubtitles();
}

///////////////////////////////////     stco
function processHeader($loc){
    global $mp4file, $item1, $item2, $currentHandler, $timescale;

    $parent_array = ["moov", "mvhd", "trak", "tkhd", "mdia", "minf", "edts", "dinf", "stbl", "udta"];
    // $atom_size1 = unpack("N", substr($mp4file, $loc, 4))[1];
    $atom_size1 = getInt($loc);
    // $type = substr($mp4file, $loc + 4, 4);
    $type = getString($loc + 4, 4);
    //if($type == 'moov') $mp4file = substr($mp4file, 0, $loc+$atom_size1+100);
    if($_REQUEST['verbose'] != 'srt'){
        $parent1 = '<li><span class="caret">';
        $parent2 = '</span>
        <ul class="nested">';

        if (in_array($type, $parent_array))
            echo $parent1 . "start: " . $loc . "(" . dechex($loc) . ")". " size = " . $atom_size1 . " type= " . $type . $parent2;
        else
            echo $item1 . "start: " . $loc . "(" . dechex($loc) . ")". " size = " . $atom_size1 . " type= " . $type . $item2;
    }

    // error_log($loc . ": " . $type);
    switch ($type){
        case 'mvhd':
            $timescale = getInt($loc+20);
            if($_REQUEST['verbose'] != 'srt')
                printMvhd($loc + 8, 'mvhd');
            break;
        case 'tkhd':
            if($_REQUEST['verbose'] != 'srt')
                printTkhd($loc + 8);
            break;
        case 'vmhd':
            if($_REQUEST['verbose'] != 'srt')
                printVmhd($loc + 8);
            break;
        case 'smhd':
            if($_REQUEST['verbose'] != 'srt')
                printSmhd($loc + 8);
            break;
        case 'mdhd':
            if($_REQUEST['verbose'] != 'srt')
                printMvhd($loc + 8, 'mdhd');
            break;
        case 'hdlr':
            $currentHandler = getString($loc+16, 4);
            if($_REQUEST['verbose'] != 'srt')
                printHdlr($loc + 8, $atom_size1);
            break;
        case 'meta':
            if($_REQUEST['verbose'] != 'srt')
                printMeta($loc + 8, $atom_size1);
            break;
        case 'elst':
        case 'dref':
        case 'stco':
        case 'stsd':
        case 'stts':
        case 'stsc':
        case 'stsz':
        case 'stss':
        case 'ctts':
            if($_REQUEST['verbose'] != 'srt' || ($type == 'stco' || $type == 'stts' || $type == 'stbl'))
                printElst($loc + 8, $type);
            break;
        default:
            if(in_array($type, $parent_array))
                printBranch($loc + 8, $atom_size1);
            break;
    }
    if($_REQUEST['verbose'] != 'srt')
        if (in_array($type, $parent_array)) echo "</ul></li>";

    return $atom_size1;
}

function printMeta($metaLoc, $metaSize){
    global $item1, $item2;
    echo $item1 . " Meta data: " . getString($metaLoc, $metaSize-8) . $item2;
}

function getInt($start){
    global $currentStart;
    return unpack("N", substr(getFileContents($start, 4), $start-$currentStart, 4))[1];
}

function getString($start, $length){
    global $currentStart;
    return substr(getFileContents($start, $length), $start-$currentStart, $length);
}

function getShort($start){
    global $currentStart;
    // error_log($start);
    return unpack("n", substr(getFileContents($start, 2), $start-$currentStart, 2))[1];
}

function getUnsigned($start){
    global $currentStart;
    return unpack("n", substr(getFileContents($start, 4), $start-$currentStart, 4))[1];
}

function getIntFrom3($start){
    global $currentStart;
    return unpack("N", "\x0" . substr(getFileContents($start, 3), $start-$currentStart, 3))[1];
}

function getHex($start){
    global $currentStart;
    return unpack("H", substr(getFileContents($start, 1), $start-$currentStart, 1))[1];
}

function getHexString($start, $length){
    global $currentStart;
    return bin2hex(substr(getFileContents($start, $length), $start-$currentStart, $length));
}

function getFileContents($begin, $requiredSize){
    global $mp4file, $currentEnd, $fileChunk, $currentStart, $mp4Handle;
    if (($begin + $requiredSize) > $currentEnd){
        fseek($mp4Handle, $begin, SEEK_SET);
        $mp4file = fread($mp4Handle, $fileChunk);       //read 1MB from begin 
        $currentEnd = $begin + $fileChunk;
        $currentStart = $begin;
        return $mp4file;
    }
    else return $mp4file;
}

function printHdlr($hdlrLoc, $hdlrSize){
    global $mp4file, $item1, $item2, $currentHandler;
    echo $item1 . "Version: " . getHex($hdlrLoc) . $item2;
    echo $item1 . "Flags: " . getIntFrom3($hdlrLoc+1) . $item2;
    echo $item1 . "Component type: " . getString($hdlrLoc+4, 4) . $item2;
    echo $item1 . "Component subtype: " . getString($hdlrLoc+8, 4) . $item2;
    echo $item1 . "Component manufacturer: " . getInt($hdlrLoc+12) . $item2;
    echo $item1 . "Component flags: " . getInt($hdlrLoc+16) . $item2;
    echo $item1 . "Component flask mask: " . getInt($hdlrLoc+20) . $item2;
    echo $item1 . "Component name: " . getString($hdlrLoc+24, $hdlrSize-28) . $item2;
}

function printElst($elstLoc, $headerType){
    global $mp4file, $item1, $item2, $timescale, $currentEnd, $currentStart, $currentHandler;
    global $subtitleTimes, $subtitles;

    $entrySize = getInt($elstLoc+4);
    if($_REQUEST['verbose'] != 'srt'){
        echo $item1 . "Version: " . getHex($elstLoc, 1) . $item2;
        echo $item1 . "Flags: " . getIntFrom3($elstLoc+1) . $item2;
        echo $item1 . "Number of entries: " . $entrySize . $item2;
    
        if($headerType == "elst"){
            for($y = 0; $y < $entrySize; $y++){
                $duration = getInt($elstLoc+8+($y*12));
                $mediatime = getInt($elstLoc+12+($y*12));
                $mediarate = getHexString($elstLoc+16+($y*12), 4);
                echo $item1 . "Track duration: " . gmdate("H:i:s", $duration/$timescale) . 
                    "; Media time: " . gmdate("H:i:s", $mediatime/$timescale) . 
                    "; Media rate: " . $mediarate . $item2;
            }
        }
        else if($headerType == "dref"){
            for($y = 0, $offset = 8; $y < $entrySize; $y++){
                $size = getInt($elstLoc+$offset, 4);
                echo $item1 . "Size: " . $size . 
                    "; Type: " . substr($mp4file, $elstLoc+4+$offset, 4) . 
                    "; Version: " . getHex($elstLoc+8+$offset) . 
                    "; Flags: " . getIntFrom3($elstLoc+9+$offset) . $item2;
                if($size > 0)
                    echo $item1 . getString($elstLoc+12+$offset, $size-12) . $item2;
                $offset += $size;
            }
        }
    }
    
    if($headerType == "stco"){
        // error_log($_REQUEST['verbose']);
        // error_log("Currenthandler: " . $currentHandler);
        if($_REQUEST['verbose'] == '8'){
            for($y = 0, $offset = 8; $y < $entrySize; $y++){
                echo $item1 . "Offset: " . getInt($elstLoc+$offset) . " (" . dechex(getInt($elstLoc+$offset)) . ")" . $item2;
                $offset+=4;
            }
        }
        else if($currentHandler == 'sbtl'){
            if($_REQUEST['verbose'] == 'srt'){
                // error_log($entrySize);
                for($y = 0, $offset = 8; $y < $entrySize; $y++){
                    $srtLoc = getInt($elstLoc+$offset);
                    
                    $tmpStart = $currentStart;
                    $tmpEnd = $currentEnd;
                    $tmpmp4file = $mp4file;

                    // error_log($y);
                    $srtSize = getShort($srtLoc);
                    $srtLoc += 2;
                    if ($srtSize == 0){
                        // error_log($y);
                        $srtSize = getShort($srtLoc);
                        $srtLoc += 2;
                    }
                    // echo "Size: " . $srtSize;
                    if ($srtSize > 0) {
                        array_push($subtitles, getString($srtLoc, $srtSize));
                        // echo $item1 . getString($srtLoc, $srtSize) . $item2;
                    }

                    $currentStart = $tmpStart;
                    $currentEnd = $tmpEnd;
                    $mp4file = $tmpmp4file;

                    $offset+=4;
                }
            }
        }
    }
    else if($headerType == "stts" && $currentHandler == 'sbtl'){
        if($_REQUEST['verbose'] == 'srt'){
            // $count = 0;
            for($y = 0, $offset = 8; $y < $entrySize; $y++){
                $duration = getInt($elstLoc+4+$offset);
                // echo $item1 . "; Sample count: " . getInt($elstLoc+$offset);
                // if($duration/$timescale < 1){
                //     echo "; Sample duration: " . $duration/$timescale . " milliseconds" . $item2;
                // }
                // else{
                //     list($seconds, $millis) = explode('.', $duration/$timescale);
                //     $millis = str_pad(intval($duration)%intval($timescale), 3, STR_PAD_LEFT);
                //     // echo "; Sample duration: " . gmdate("H:i:s,", $duration/$timescale/$timescale) . $millis . $item2;
                // }
                // $count++;
                array_push($subtitleTimes, $duration/$timescale);
                $offset += 8;
            }
            // error_log($count);
        }
    }
    else if($headerType == "stsc"){
        if($_REQUEST['verbose'] == '9'){
            for($y = 0, $offset = 8; $y < $entrySize; $y++){
                echo $item1 . "; First chunk: " . getInt($elstLoc+$offset) . 
                "; Samples per chunk: " . getInt($elstLoc+4+$offset) . 
                "; Sample description ID: " . getInt($elstLoc+8+$offset) . $item2;
                $offset += 12;
            }
        }
    }
    else if($headerType == "stsd"){
        if($_REQUEST['verbose'] == '9'){
            for($y = 0, $offset = 8; $y < $entrySize; $y++){
                $size = getInt($elstLoc+$offset);
                $dataformat = getString($elstLoc+4+$offset, 4);
                echo $item1 . "Size: " . $size . 
                    "; Data format: " . $dataformat . 
                    "; Reserved: " . getHexString($elstLoc+8+$offset, 6) . 
                    "; Data reference index: " . getUnsigned($elstLoc+14+$offset) . $item2;
                if($size > 14)
                    echo $item1 . getString($elstLoc+18+$offset, $size-14) . $item2;
                $offset += $size;
            }
        }
    }
    else if($headerType == "stsz"){
        if($_REQUEST['verbose'] == '9'){
            for($y = 0, $offset = 8; $y < $entrySize; $y++){
                echo $item1 . "Sample size: " . getInt($elstLoc+4+$offset) . $item2;
                $offset+=4;
            }
        }
    }
    else if($headerType == "ctts"){
        if($_REQUEST['verbose'] == '9'){
            for($y = 0, $offset = 8; $y < $entrySize; $y++){
                echo $item1 . "; Sample Count: " . getInt($elstLoc+$offset) . 
                "; Composition Offset: " . getInt($elstLoc+4+$offset) . $item2;
                $offset += 8;
            }
        }
    }
    else if($headerType == "stss"){
        if($_REQUEST['verbose'] == '9'){
            for($y = 0, $offset = 8; $y < $entrySize; $y++){
                echo $item1 . "; Sync sample number: " . getInt($elstLoc+$offset) . $item2;
                $offset += 4;
            }
        }
    }
}

function printVmhd($vmhdLoc){
    global $mp4file, $item1, $item2;
    echo $item1 . "Version: " . getHex($vmhdLoc) . $item2;
    echo $item1 . "Flags: " . getIntFrom3($vmhdLoc+1) . $item2;
    echo $item1 . "Graphics mode: " . getShort($vmhdLoc+4) . $item2;
    echo $item1 . "Opcolor: " . getShort($vmhdLoc+6) . $item2;
}

function printSmhd($smhdLoc){
    global $mp4file, $item1, $item2;
    echo $item1 . "Version: " . getHex($smhdLoc) . $item2;
    echo $item1 . "Flags: " . getIntFrom3($smhdLoc+1) . $item2;
    echo $item1 . "Balance: " . getShort($smhdLoc+4) . $item2;
    echo $item1 . "Reserved: " . getShort($smhdLoc+6) . $item2;
}

function printTkhd($tkhdLoc){
    global $mp4file, $item1, $item2, $timescale;

    echo $item1 . "Version: " . getHex($tkhdLoc) . $item2;

    $flags = getIntFrom3($tkhdLoc+1);
    echo $item1 . "Flags: " . $flags . " - ";
    if($flags & 0x0001) echo "enabled; ";
    if($flags & 0x0002) echo "movie; ";
    if($flags & 0x0004) echo "preview; ";
    if($flags & 0x0008) echo "poster; ";
    echo $item2;

    echo $item1 . "Creation time: " . date("Y-m-d H:i:s", getUnsigned($tkhdLoc+4)) . $item2;
    echo $item1 . "Modification time: " . date("Y-m-d H:i:s", getUnsigned($tkhdLoc+8)) . $item2;
    echo $item1 . "Track ID: " . getInt($tkhdLoc+12) . $item2;
    echo $item1 . "Reserved: " . getInt($tkhdLoc+16) . $item2;
    $duration = getInt($tkhdLoc+20);
    echo $item1 . "Duration: " . gmdate("H:i:s", $duration/$timescale) . $item2;
    // echo $item1 . "Reserved: " . unpack("J", substr($mp4file, $tkhdLoc+24, 8))[1] . $item2;
    echo $item1 . "Reserved: " .  getHexString($tkhdLoc+24, 8) . $item2;
    echo $item1 . "Layer: " . getShort($tkhdLoc+32) . $item2;
    $alternateGroup = getShort($tkhdLoc+34);
    echo $item1 . "Alternate Group: " . $alternateGroup . " - ";
    // if($alternateGroup == 0) echo "video ";
    if($alternateGroup == 1) echo "sound ";
    else if($alternateGroup == 2) echo "subtitle ";
    echo $item2;
    echo $item1 . "Volume: " . getHexString($tkhdLoc+36, 2) . $item2;
    echo $item1 . "Reserved: " . getShort($tkhdLoc+38, 2) . $item2;
    echo $item1 . "Matrix structure: [";
    for($x = 0; $x < 3; $x++){
        for($y = 0; $y < 3; $y++){
            echo getUnsigned($tkhdLoc+40 + (3*+$x)+$y) . " ";
        }
    }
    echo "]" . $item2;
    echo $item1 . "Track width: " . getUnsigned($tkhdLoc+76) . $item2;
    echo $item1 . "Track height: " . getUnsigned($tkhdLoc+80) . $item2;

}

function printMvhd($mvhdLoc, $header){
    global $mp4file, $item1, $item2, $timescale;
    echo $item1 . "Version: " . getHex($mvhdLoc) . $item2;

    $flags = getIntFrom3($mvhdLoc+1);
    echo $item1 . "Flags: " . $flags . " - ";
    if($flags & 0x0001) echo "enabled; ";
    if($flags & 0x0002) echo "movie; ";
    if($flags & 0x0004) echo "preview; ";
    if($flags & 0x0008) echo "poster; ";
    echo $item2;

    echo $item1 . "Creation time: " . date("Y-m-d H:i:s", getUnsigned($mvhdLoc+4)) . $item2;
    echo $item1 . "Modification time: " . date("Y-m-d H:i:s", getUnsigned($mvhdLoc+8)) . $item2;
    $duration = getInt($mvhdLoc+16);
    echo $item1 . "Time scale: " . $timescale . $item2;
    echo $item1 . "Duration: " . gmdate("H:i:s", $duration/$timescale) . $item2;
    if($header == 'mvhd'){
        echo $item1 . "Preferred rate: " . getHexString($mvhdLoc+20, 4) . $item2;
        echo $item1 . "Preferred volume: " . getHexString($mvhdLoc+24, 2) . $item2;
    }
    else {
        echo $item1 . "Language: " . getHexString($mvhdLoc+20, 2) . $item2;
        echo $item1 . "Quality: " . getShort($mvhdLoc+22) . $item2;
    }
}

function printBranch($trakLoc, $branchsize){
    global $mp4file;
    $reqsize = $trakLoc + $branchsize - 8;
    for($k = 0; $trakLoc < $reqsize; $k++){
        $atom_size2 = processHeader($trakLoc);
        $trakLoc += $atom_size2;
    }
}

function printAllHeaders(){
    global $mp4file;
    $start = 0;
    // echo "len: " . strlen($mp4file);         //*****  FIX THE below condition____ set EOF in getFileContents_____ */
    for($j = 0; $start < strlen($mp4file); $j++){
        // error_log($start . ", " . strlen($mp4file));
        $atom_size = processHeader($start);
        $start += $atom_size;
    }
}

function printSubtitles(){
    global $subtitleTimes, $subtitles, $timescale;

    $count = 1;
    $popIndex = array();
    $subtitleCount = count($subtitleTimes);
    for ($i = 1; $i < $subtitleCount; $i++){
        $subtitleTimes[$i] = $subtitleTimes[$i] + $subtitleTimes[$i-1];
    }

    $srtTxt = '';
    $millis = 0;
    $seconds = 0;
    for ($i = 0; $i < count($subtitles); $i++) {
        $srtTxt .= $i+1 . "\n";
        // list($seconds, $millis) = explode('.', $subtitleTimes[2*$i]);
        $millis = str_pad($subtitleTimes[2*$i]%($timescale+0), 3, STR_PAD_LEFT);
        $srtTxt .= gmdate("H:i:s,", $subtitleTimes[2*$i]/$timescale) . $millis . " --> ";
        // list($seconds, $millis) = explode('.', $subtitleTimes[2*$i+1]);
        $millis = str_pad($subtitleTimes[2*$i+1]%($timescale+0), 3, STR_PAD_LEFT);
        $srtTxt .= gmdate("H:i:s,", $subtitleTimes[2*$i+1]/$timescale) . $millis . "\n";
        $srtTxt .= $subtitles[$i] . "\n\n";
    }
    if ($browser)
        echo str_replace("\n", "<br>", $srtTxt);
    else
        echo $srtTxt;
    file_put_contents("Avatar.The.Last.Airbender.s01e18.1080p.srt", $srtTxt);
}

function sendHTMLclose(){
    echo '</ul><script>
    var toggler = document.getElementsByClassName("caret");
    var i;
    
    for (i = 0; i < toggler.length; i++) {
      toggler[i].addEventListener("click", function() {
        this.parentElement.querySelector(".nested").classList.toggle("active");
        this.classList.toggle("caret-down");
      });
    }
    </script></body></html>';
}
function sendHTMLbegin(){
    echo '<!DOCTYPE html>
    <html>
    <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    ul, #myUL {
      list-style-type: none;
    }
    
    #myUL {
      margin: 0;
      padding: 0;
    }
    
    .caret {
      cursor: pointer;
      -webkit-user-select: none; /* Safari 3.1+ */
      -moz-user-select: none; /* Firefox 2+ */
      -ms-user-select: none; /* IE 10+ */
      user-select: none;
    }
    
    .caret::before {
      content: "\25B6";
      color: black;
      display: inline-block;
      margin-right: 6px;
    }
    
    .caret-down::before {
      -ms-transform: rotate(90deg); /* IE 9 */
      -webkit-transform: rotate(90deg); /* Safari */
      transform: rotate(90deg);  
    }
    
    .nested {
      display: none;
    }
    
    .active {
      display: block;
    }
    </style>
    </head>
    <body>
    
    
    <ul id="myUL">';
}
?>
