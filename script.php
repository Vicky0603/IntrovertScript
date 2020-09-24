<?php
    //Захаркодим наш аккаунт, это плохо, но это тестовое задание
    $subdomain='test';

    //Аутентификация
    function auth( $subdomain, $count ){

        //Если это уже пятая попытка, то не надо
        if ( $count == 5 ){
            return false;
        }

        //Кусок кода из документации, отвечающий за  необходимый запрос
        $user=[
            'USER_LOGIN'=>'test@testmail.com', #Ваш логин (электронная почта)
            'USER_HASH'=>'7ebefd1d4741106a4daa0e0a673bba2e4dc16054' #Хэш для доступа к API (смотрите в профиле пользователя)
        ];
        $link='https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';
        $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
        curl_setopt($curl,CURLOPT_URL,$link);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($user));
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
        $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
        $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
        curl_close($curl); #Завершаем сеанс cURL

        //Если код неравен 200 увеличиваем счёчик запроса и пробуем аутентифицироваться ещё раз
        if ( $code != '200' ){
            auth( $subdomain, $count + 1 );
        } else {
            return true;
        }
    }

    //Запрос списка сделок без открытых задач
    function get_leads( $subdomain, $offset, $count ){

        //Если это уже пятая попытка, то не надо
        if ( $count == 5 ){
            return false;
        }
        
        //В документации нет примера использования filter, но мне кажется что так.
        $add_offset = '';

        if ( $offset > 0 ){
            $add_offset = '&limit_offset=' . strval( $offset * 50 );
        }

        $link='https://'.$subdomain.'.amocrm.ru/api/v2/leads/filter?tasks=1&limit_rows=50' . $add_offset;

        //Кусок кода из документации, отвечающий за запрос
        $curl=curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
        curl_setopt($curl,CURLOPT_URL,$link);
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
        $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
        $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);

        //Работаем с кодом ответа
        //Если истекла сессия и надо по новой аутентифицироваться
        if ( $code == '401' ){
            if ( auth( $subdomain, 0 ) ){
                //аутентификация прошла успешно, повторный запрос
                get_leads( $subdomain, $offset, $count );
            } else {
                //проблема с аутентификацией, выходим
                return false;
            }
        }
        //Если код неравен 200 увеличиваем счёчик запроса и пробуем ещё раз
        if ( $code != '200' ){
            get_leads( $subdomain, $offset, $count + 1 );
        } else {
            return json_decode( $out, true )['_embedded']['items'];
        }
    }

    //Создание задач
    function add_task( $subdomain, $tasks ,$count ){
        
        //Если это уже пятая попытка, то не надо
        if ( $count == 5 ){
            return false;
        }

        $link='https://'.$subdomain.'.amocrm.ru/api/v2/tasks';

        //Кусок кода из документации, отвечающий за запрос
        $curl=curl_init(); 
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
        curl_setopt($curl,CURLOPT_URL,$link);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($tasks));
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt');
        curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt');
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
        $out=curl_exec($curl);
        $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);

        //Работаем с кодом ответа
        //Если истекла сессия и надо по новой аутентифицироваться
        if ( $code == '401' ){
            if ( auth( $subdomain, 0 ) ){
                //аутентификация прошла успешно, повторный запрос
                add_task( $subdomain, $tasks ,$count );
            } else {
                //проблема с аутентификацией, выходим
                return false;
            }
        }
        //Если код неравен 200 увеличиваем счёчик запроса и пробуем ещё раз
        if ( $code != '200' ){
            add_task( $subdomain, $tasks ,$count + 1 );
        } else {
            return true;
        }
    }

    
    //Массив задач для создания задач
    $tasks = [];
    $tasks['add'] = [];
    
    //Признак продолжения запроса списка сделок
    $continue_get_leads = true;
    
    //Смещение по списку
    $offset = 0;

    //будем запрашивать по 50 штук
    while ( $continue_get_leads ){
        $response = get_leads( $subdomain, $offset, 0 );

        //Если проблемы с получением ответа, то выходим
        if ( !$response ){
            return false;
        }

        //заполняем массив задач
        foreach ( $response as $value ) {
            //array_push( $leads, $value );
            $tmp = [];
            $tmp['element_id'] = $value['id'];
            $tmp['element_type'] = 2;
            $tmp['task_type']= 3;
            $tmp['text'] = 'Сделка без задачи';
            $tmp['responsible_user_id'] = $value['responsible_user_id'];
            $tmp['complete_till_at'] = 2146435200;

            array_push( $tasks['add'], $tmp );
        }

        //Если длина массива ответа меньше 50, то мы уже получили полный список и дальнейшие запросы не нужны
        if ( count( $response ) < 50 ){
            $continue_get_leads = false;
        }
    }

    //Пришло время собирать камни
    //Если массив задач пуст, то скрипт отработал, если нет, то создаём задачи
    if ( count( $tasks['add'] ) > 0 ){
        if ( add_task( $subdomain, $tasks ,0 ) ){
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
?>