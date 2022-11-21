<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="proxy.js"></script>
    <title>Document</title>
</head>
<body>
<header>
    Header master
</header>
<main>
    <article>Article master</article>
    <aside>Aside</aside>
    <?php
    CModule::IncludeModule('tasks');
    CModule::IncludeModule("crm");
    echo '<pre>';
    $cityTasks = $tasks = [];
    $rsTask = \CTasks::GetList(
        array(),
        array('PARENT_ID' => 59815),
        array("*"),
        array()
    );
    while($arTask = $rsTask->GetNext()){
        $cityTasks[] = $arTask;
    }
    if(!empty($cityTasks)){
        foreach ($cityTasks as $cityTask){
            $rsTask = \CTasks::GetList(
                array(),
                array('PARENT_ID' => $cityTask),
                array("*"),
                array()
            );
            while($arTask = $rsTask->GetNext()){
                $tasks[] = $arTask;
            }
        }
        print_r($tasks);
    }

    function sendNotify(int $fromUserId, int $toUserId, string $message)
    {
        if (! \Bitrix\Main\Loader::includeModule('im')) {
            throw new \Bitrix\Main\LoaderException('Unable to load IM module');
        }

        $fields = [
            "TO_USER_ID" => $fromUserId, // ID пользователя
            "FROM_USER_ID" => $toUserId,  // От кого (0 - системное)
            "MESSAGE_TYPE" => "S",
            "NOTIFY_MODULE" => "im",
            "NOTIFY_MESSAGE" => $message, // Текст сообщения
        ];

        $msg = new \CIMMessenger();
        if (! $msg->Add($fields)) {
            $e = $GLOBALS['APPLICATION']->GetException();
            throw new \Bitrix\Main\SystemException($e->GetString()); // $e->GetString() - тут находится сообщение об ошибке
        }
    }

    function sendMessage(string $email, int $taskId)
    {
        $message = getMessageText($taskId);
        $arEventField = array(
            "EMAIL_TO" => $email,
            "TEXT" => $message,
        );
        CEvent::Send("NOTIFY_EMAIL", 's1', $arEventField,'Y',200);
    }

    function getMessageText(int $taskId): string
    {
        $dateNotify = new DateTime('+ 1 days');
        $message = "В задаче $taskId Вы упомянуты ответственным.
    Необходимо в течение дня ($dateNotify->format('d.m.Y')) предоставить фотографии приборов учета.
    Приборы учета:
        1. Счетчики электроэнергии
        2. Счетчики воды
        3. Счетчик тепла (Тепловычислитель)
    Требование к фотографиям:
        · На 1 фотографии должно быть не более 1го счетчика.
        · На фотографии должны быть четко видны все цифры показания счетчика.
        · Должен быть четко виден номер счетчика.
        · Если на одной и той же фотографии не удается сделать и показания и номер, можно сделать 2 фотографии 1го
            счетчика.
    Если есть вопросы, необходимо написать об этом в задаче.";
        return $message;
    }




    function getNotifyTasks(): array
    {
        $tasks = [];
        if (CModule::IncludeModule('tasks')){
            $cityTasks = [];
            $rsTask = \CTasks::GetList(
                array(),
                array('PARENT_ID' => 59815),
                array("ID")
            );
            while($arTask = $rsTask->GetNext()){
                $cityTasks[] = $arTask['ID'];
            }
            if(!empty($cityTasks)){
                foreach ($cityTasks as $cityTask){
                    $rsTask = \CTasks::GetList(
                        array(),
                        array('PARENT_ID' => $cityTask, '<STATUS' => 5),
                        array('ID', 'TITLE', 'STATUS', 'RESPONSIBLE_ID', 'CREATED_BY')
                    );
                    while($arTask = $rsTask->GetNext()){
                        $tasks['user_ids'][$arTask['RESPONSIBLE_ID']] = $arTask['RESPONSIBLE_ID'];
                        $tasks[] = $arTask;
                    }
                }
            }
        }
        return $tasks;
    }

    function getUserData(array $user_ids): array
    {
        $usersEmail = [];
        $user_ids = implode(' | ', $user_ids);
        $filter = Array
        (
            "ID" => $user_ids,
        );
        $rsUsers = CUser::GetList(($by="personal_country"), ($order="desc"), $filter);
        while($arUser = $rsUsers->GetNext()){
            $usersEmail[$arUser['ID']] = $arUser['EMAIL'];
        }
        return $usersEmail;
    }

    function sendEmailAndNotify()
    {
        $tasks = getNotifyTasks();
        if (!empty($tasks)){
            $usersEmail = getUserData($tasks['user_ids']);
            foreach ($tasks as $task){
                $message = getMessageText($task['ID']);
                sendNotify($task['CREATED_BY'], $task['RESPONSIBLE_ID'], $message);
                if (!empty($usersEmail[$tasks['RESPONSIBLE_ID']])) {
                    sendMessage($usersEmail[$tasks['RESPONSIBLE_ID']], $task['ID']);
                }
            }
        }
    }


    ?>

    <div>
        В задаче <?=111?> Вы упомянуты ответственным.<br>Необходимо в течение дня (<?=date('d.m.Y')?>)
        предоставить фотографии приборов учета.<br><br>Приборы учета:<br>
        <ol>
            <li>Счетчики электроэнергии<br></li>
            <li>Счетчики воды<br></li>
            <li>Счетчик тепла (Тепловычислитель)<br></li>
        </ol>
        Требование к фотографиям:<br>
        <ul>
            <li>На 1 фотографии должно быть не более 1го счетчика.<br></li>
            <li>На фотографии должны быть четко видны все цифры показания счетчика.<br></li>
            <li>Должен быть четко виден номер счетчика.<br></li>
            <li>Если на одной и той же фотографии не удается сделать и показания и номер, можно сделать 2 фотографии 1го
                счетчика.<br></li>
        </ul>
        Если есть вопросы, необходимо написать об этом в задаче.<br>
    </div>


    --------------------------------

    В задаче 111 Вы упомянуты ответственным.
    Необходимо в течение дня (date('d.m.Y')) предоставить фотографии приборов учета.
    Приборы учета:
        1. Счетчики электроэнергии
        2. Счетчики воды
        3. Счетчик тепла (Тепловычислитель)
    Требование к фотографиям:
        · На 1 фотографии должно быть не более 1го счетчика.
        · На фотографии должны быть четко видны все цифры показания счетчика.
        · Должен быть четко виден номер счетчика.
        · Если на одной и той же фотографии не удается сделать и показания и номер, можно сделать 2 фотографии 1го
            счетчика.
    Если есть вопросы, необходимо написать об этом в задаче.
    ---------------------------
</main>
</body>
</html>
