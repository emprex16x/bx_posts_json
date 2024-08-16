<?php 

define('STOP_STATISTICS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

\Bitrix\Main\Loader::includeModule('iblock');

$postsLimit = isset($_GET['pageSize']) && $_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10;
$postsOffset = isset($_GET['page']) && $_GET['page'] > 1 ? ($_GET['page'] - 1) * $postsLimit : 0;


$dbPosts = \Bitrix\Iblock\ElementTable::getList([
	'filter' => [
        "=IBLOCK_ID" => 12, // Set required Iblock ID
        ">=ACTIVE_FROM" => new \Bitrix\Main\Type\DateTime('01.01.2015 00:00:00'), 
        "<=ACTIVE_FROM" => new \Bitrix\Main\Type\DateTime('31.12.2015 23:59:59')
    ], 
    'order' => ['SORT' => 'ASC'],
	'select' => [
        'ID', 'IBLOCK_ID', 'CODE', 'PREVIEW_PICTURE', 'NAME', 
        'IBLOCK_SECTION_ID', 'ACTIVE_FROM', 'TAGS', 
        'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL'
    ],
    "offset" => $postsOffset,
    "limit" => $postsLimit,
	'cache' => [
        'ttl' => 86400,
		'cache_joins' => true
	]
]);

$arPosts = [];
while ($arPost = $dbPosts->fetch()) {
    $post['id'] = $arPost['ID'];
    $post['url'] = CIBlock::ReplaceDetailUrl($arPost['DETAIL_PAGE_URL'], $arPost, false, 'E');;
    $post['image'] = CFile::GetPath($arPost["PREVIEW_PICTURE"]);
    $post['name'] = $arPost['NAME'];
    $post['date'] = FormatDate("j F Y H:i", MakeTimeStamp($arPost['ACTIVE_FROM']));

    // Get section name
    if ($arPost['IBLOCK_SECTION_ID'])
        $dbSection = \Bitrix\Iblock\SectionTable::getById($arPost['IBLOCK_SECTION_ID'])->fetch();

    $post['sectionName'] = $dbSection['NAME'] ?: '';
    
    // Get author name 
    $dbProperty = \CIBlockElement::getProperty($arPost['IBLOCK_ID'], $arPost['ID'], array("sort", "asc"), array('CODE' => 'AUTHOR'));
    while ($arProperty = $dbProperty->GetNext()) {
        if ($arProperty['VALUE']) {
            $dbAuthor = \Bitrix\Iblock\ElementTable::getById($arProperty['VALUE'], array(
                'select' => array('ID', 'NAME')
            ))->fetch();
        }

        $post['author'] = $dbAuthor['NAME'] ?: '';
    }

    $post['tags'] = array_filter(explode(', ', $arPost['TAGS']));
       
    $arPosts[] = $post;
}

echo json_encode($arPosts);