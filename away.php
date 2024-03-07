<?
/* 
 * Здесь считаем количество кликов по ссылке "перейти к курсу"
 * НЕ передаем в GET, потому что у некотрых курсов строка адреса спотыкалась на кодированной в строке переадресации...
 */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
CModule::IncludeModule("iblock");

/* если авторизованы и если есть реферрер и если пришли с портала
 * разобрать path
 * если выделенная подстрока - число, 
 * последние цифры взять за id курса
 * считать адрес на кнопке и текущее значение счетчика
 * сделать хэдер-локэйшн
 *  */
if ($USER->IsAuthorized()
    // если это был переход с какого-то сайта (а вообще лишняя проверка=), т.к. ниже все равно проверятся))
    and isset($_SERVER['HTTP_REFERER'])
    //и если мы пришли с example.com
    and parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) == 'example.com'
) {
    // тут присвоить referrer    
    //$raw_course_id_from_url = '/xxxcourse/DO/76868/?rtyrt=ert&erter=234';
    $raw_course_id_from_url = $_SERVER['HTTP_REFERER'];
    //echo $raw_course_id_from_url.'<br>';

    $course_id_from_url = parse_url($raw_course_id_from_url, PHP_URL_PATH);

    //echo $course_id_from_url.'<br>';

    //убираем слэш
    if (substr($course_id_from_url, -1) == '/') {
        $course_id_from_url = substr($course_id_from_url, 0, -1);
        //echo $course_id_from_url.'<br>';
    }
    $ar_extracted_course_id = explode('/', $course_id_from_url);
    $extracted_course_id = $ar_extracted_course_id[count($ar_extracted_course_id) - 1];
    if (ctype_digit($extracted_course_id)) {
        // если с этим кодом есть живой активный курс, то 
        // делаем +1
        // отправляем на курс

        //получим курс (попытаемся)
        $flag = 0;
        $res = CIBlockElement::GetList(array(), array("ID" => $extracted_course_id, "IBLOCK_ID" => "18", "ACTIVE" => "Y"), false, false, array("ID", "IBLOCK_ID", "PROPERTY_URLKURS", "PROPERTY_GO2COURSECOUNT"));
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            // есть курс ? поднимем флаг
            $flag = 1;
        }
        //если флаг поднят и поле адреса курса заполнено, то
        if ($flag and isset($arFields['PROPERTY_URLKURS_VALUE'])) {
            // обновим счетчик (инкрементируем значение. На самом деле есть риск (неподтвержденный),
            // что возможно некорректное увеличение счетчика, если какой-то процесс php отработал раньше другого для этого скрипта)
            CIBlockElement::SetPropertyValues($extracted_course_id, 18, ++$arFields['PROPERTY_GO2COURSECOUNT_VALUE'], "GO2COURSECOUNT");

            //перейдем к курсу
            //костыль из \bitrix\templates\xxx\components\bitrix\news\courses_refilter\bitrix\news.detail\.default\template.php:
            if ($arFields["ID"] == 76858
                || $arFields["ID"] == 76859
                || $arFields["ID"] == 76861
                || $arFields["ID"] == 76863
                || $arFields["ID"] == 76864
                || $arFields["ID"] == 76865
                || $arFields["ID"] == 76866
                || $arFields["ID"] == 76867
                || $arFields["ID"] == 76868
                || $arFields["ID"] == 76869
                || $arFields["ID"] == 76826
                || $arFields["ID"] == 76870
                || $arFields["ID"] == 76986
                || $arFields["ID"] == 76987
            ) {
                header('Location: ' . BnhExtAuth::getToken($arFields['PROPERTY_URLKURS_VALUE']));
                //echo BnhExtAuth::getToken($arFields['PROPERTY_URLKURS_VALUE']);
            } else
                header('Location: ' . $arFields['PROPERTY_URLKURS_VALUE']);
            //костыль закончился
        } else
            //отправим на главную, если не было активных курсов с таким кодом (защита от старой дохлой ссылки или чего-нибудь)
            header('Location: /');
    } else
        //отправим на главную, если не смогли выделить код курса 
        header('Location: /');

} else {
    header('Location: /');
}
?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>