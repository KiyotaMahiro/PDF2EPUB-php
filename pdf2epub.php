<?php
//手動設定
$bookpath = ""; //このファイルから変換する書籍への相対パス(同じフォルダなら空文字、そうでなければ基本的に/で終わる)
$filename = "A01"; //処理する書籍名(拡張子なし)
$outputPath = ""; //結果を出力する場所への相対パス(同じフォルダなら空文字、そうでなければ基本的に/で終わる)結果ファイル名は入力ファイルと同じになるので、ファイル名は書かずそこまでのパスだけでよい

//前処理
try{
	$im = new Imagick();
	$im->readImage($bookpath.$filename.".pdf");
}catch(Exception $e){
	echo "ERROR : This pdf2epub conversion program requires ImageMagick. Please install it to this server.";
}
$totalPage = $im->getImageScene(); //ページ総数取得

//各種パス
$entireFolderPath = $outputPath.$filename; //全体が入るフォルダ
$metainfFolderPath = $outputPath.$filename."/META-INF"; //META-INFフォルダのパス
$containerPath = $outputPath.$filename."/META-INF/container.xml"; //container.xmlのパス
$opsPath = $outputPath.$filename."/OPS"; //OPSフォルダのパス
$imageFolderPath = $outputPath.$filename."/OPS/images"; // 画像を格納するimagesフォルダのパス
$ncxPath = $outputPath.$filename."/OPS/fb.ncx"; //ncxファイルのパス
$opfPath = $outputPath.$filename."/OPS/fb.opf"; //opfファイルのパス
$mimetypePath = $outputPath.$filename."/mimetype"; //mimetypeファイルのパス
$cssPath = $outputPath.$filename."/OPS/style.css"; //cssファイルのパス

//フォルダ作成
mkdir($entireFolderPath); // root
mkdir($metainfFolderPath); // root/META-INF
mkdir($opsPath); // root/OS
mkdir($imageFolderPath); // root/OPS/images

//ファイル作成
touch($containerPath); // root/META-INF/container.xml
touch($ncxPath); // root/OPS/fb.ncx
touch($opfPath); // root/OPS/fb.opf
touch($mimetypePath); // root/mimetype
touch($cssPath); // root/OPS/style.css

// ファイルの中身作成
$contentsOfContainer = '<?xml version="1.0" encoding="UTF-8"?><container xmlns="urn:oasis:names:tc:opendocument:xmlns:container" version="1.0"><rootfiles><rootfile full-path="OPS/fb.opf" media-type="application/oebps-package+xml"/></rootfiles></container>';
$contentsOfNCX = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE ncxPUBLIC "-//NISO//DTD ncx 2005-1//EN" "http://www.daisy.org/z3986/2005/ncx-2005-1.dtd"><ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1"><head><meta name="dtb:uid" content="123456"></meta><meta name="dtb:depth" content="1"></meta><meta name="dtb:totalPageCount" content="0"></meta><meta name="dtb:maxPageNumber" content="0"></meta></head><docTitle><text>'+$filename+'</text></docTitle><navMap>';
$contentsOfOPF = '<?xml version="1.0" encoding="UTF-8"?><package xmlns="http://www.idpf.org/2007/opf" unique-identifier="EPB-UUID" version="2.0"><metadata xmlns:opf="http://www.idpf.org/2007/opf" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title></dc:title><dc:creator></dc:creator><dc:subject></dc:subject><dc:description></dc:description><dc:contributor>PScript5.dll Version 5.2.2, Acrobat Distiller 10.1.13 (Windows)</dc:contributor><dc:date>2015-03-31</dc:date><dc:type></dc:type><dc:format></dc:format><dc:source></dc:source><dc:relation></dc:relation><dc:coverage></dc:coverage><dc:rights></dc:rights><dc:identifier id="EPB-UUID">urn:uuid:123456</dc:identifier><dc:language>en-gb</dc:language></metadata><manifest>';
$contentsOfMimetype = "application/epub+zip";
$contentsOfCss = '@font-face{font-family:"msyh";src:url("../Fonts/EmbedFont.ttf");}@font-face {font-family:"cnepub";src:url(res:///opt/sony/ebook/FONT/tt0011m_.ttf), url(res:///tt0011m_.ttf);}body {font-family:"cnepub", serif;font-family: "msyh";margin: 0px;}p {padding: 0em;margin: 0px;-webkit-margin-before: 0em;-webkit-margin-after: 0em;}div p span {vertical-align: top;}table {border-collapse: collapse;table-layout: fixed;}td {padding: 0px;}';


//ページ作成

for ($i = 0; $i <= $totalPage; $i++) {
	$im->setImageIndex($i);
	$im->thumbnailImage(640, 640, true);
	$im->sharpenImage(0, 1);
	$im->writeImage($imageFolderPath.'/out_' . $i . '.jpg');
	touch($opsPath."/page-".$i.".html");
	$contentsOfNCX = $contentsOfNCX.'<navPoint id="navpoint-'.$i.'" playOrder="'.$i.'"><navLabel><text>page-'.$i.'</text></navLabel><content src="page-'.$i.'.html"></content></navPoint>';
	$contentsOfOPF = $contentsOfOPF.'<item id="page-'.$i.'" href="page-'.$i.'.html" media-type="application/xhtml+xml"></item>';
	file_put_contents($opsPath."/page-".$i.".html",'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-cn"><head><link rel="stylesheet" type="text/css" href="style.css"></link><title>page-'.$i.'</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta></head><body style="background-color:#ffffff; "><div style="position:absolute; top:0.000000em; left:0.000000em; padding-top:6px; z-index:-1;"><img src="images/out_'.$i.'.jpg" style="width:768px;" alt="pdfconverter"></img></div></body></html>');
}
for($i = 0; $i <= $totalPage; $i++){
	$contentsOfOPF = $contentsOfOPF.'<item id="img-'.$i.'" href="images/out_'.$i.'.jpg" media-type="image/jpeg"></item>';
}
$contentsOfOPF = $contentsOfOPF.'<item id="style" href="style.css" media-type="text/css"></item><item id="ncx" href="fb.ncx" media-type="application/x-dtbncx+xml"></item></manifest><spine toc="ncx">';
for($i = 0; $i <= $totalPage; $i++){
	$contentsOfOPF = $contentsOfOPF.'<itemref idref="page-'.$i.'" linear="yes"></itemref>';
}

//書き込み
file_put_contents($ncxPath, $contentsOfNCX."</navMap></ncx>");
file_put_contents($opfPath, $contentsOfOPF."</spine></package>");
file_put_contents($containerPath, $contentsOfContainer);
file_put_contents($mimetypePath, $contentsOfMimetype);
file_put_contents($cssPath, $contentsOfCss);

// 終了処理
$im->destroy();
?>
